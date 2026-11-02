import concurrent.futures as cf
import urllib.request

import duckdb
OUT = "data/harrow-listings.csv"

con = duckdb.connect("harrow.duckdb", read_only=True)
con.execute("SET lambda_syntax='ENABLE_SINGLE_ARROW';")

CORE_TOP = "('shopping','food_and_drink','lifestyle_services','health_care','lodging')"
SVC = "('home_service','legal_service','real_estate_service','financial_service','professional_service','event_or_party_service','family_service','laundry_service','printing_service','design_service','building_or_construction_service','housing_or_property_service','rental_service')"
LOCAL_FACING = f"""(hier[1] IN {CORE_TOP}
   OR (hier[1]='services_and_business' AND hier[2] IN {SVC})
   OR (hier[1]='travel_and_transportation' AND hier[2] IN ('vehicle_service','travel_service'))
   OR (hier[1]='sports_and_recreation' AND hier[2]='sport_or_fitness_facility')
   OR (hier[1]='arts_and_entertainment' AND hier[2] IN ('gaming_venue','performing_arts_venue')))"""
OFFICEY = "(hier[1]='services_and_business' AND hier[2] IN ('financial_service','professional_service','real_estate_service','legal_service','design_service','building_or_construction_service','home_service','housing_or_property_service'))"

con.execute(f"""
  CREATE TEMP TABLE q AS
  SELECT *, list_filter(list_distinct(src), s -> s <> 'Overture') AS ds,
         regexp_replace(lower(coalesce(name,'')), '[^a-z0-9]', '', 'g') AS nname,
         replace(upper(coalesce(pc,'')),' ','') AS npc,
         list_any_value(ph) AS phone1, list_any_value(web) AS web1,
         regexp_extract(lower(coalesce(list_any_value(web),'')), '^(?:https?://)?(?:www\\.)?([^/]+)', 1) AS domain
  FROM p
""")

# Chain detection from the data itself: same name or website at 3+ Harrow locations.
con.execute("CREATE TEMP TABLE chain_n AS SELECT nname FROM q WHERE nname<>'' GROUP BY 1 HAVING count(*)>=3")
con.execute("CREATE TEMP TABLE chain_d AS SELECT domain FROM q WHERE domain<>'' GROUP BY 1 HAVING count(DISTINCT nname)>=3")

VALID_PC = r"regexp_matches(npc, '^[A-Z]{1,2}[0-9][0-9A-Z]?[0-9][A-Z]{2}$')"
PERSON = r"regexp_matches(name, '(^[A-Z]+ (Mr|Mrs|Ms|Dr|Miss)\b)|(\b(Mr|Mrs|Ms|Miss)\s+[A-Z])')"
BASE = f"coalesce(st,'') <> 'permanently_closed' AND {VALID_PC} AND ph IS NOT NULL AND {LOCAL_FACING}"
NOCHAIN = "brand IS NULL AND nname NOT IN (SELECT nname FROM chain_n) AND domain NOT IN (SELECT domain FROM chain_d)"
NOFSQ = "ds <> ['Foursquare'] AND ds <> ['AllThePlaces']"

steps = [
  ("1 open, valid postcode, phone, local categories", BASE),
  ("2 +not Foursquare/ATP-only (fixed .77 score)", f"{BASE} AND {NOFSQ}"),
  ("3 +conf>=.8", f"{BASE} AND {NOFSQ} AND conf>=0.8"),
  ("4 +office types conf>=.95", f"{BASE} AND {NOFSQ} AND conf>=0.8 AND (NOT {OFFICEY} OR conf>=0.95)"),
  ("5 +no person-name records", f"{BASE} AND {NOFSQ} AND conf>=0.8 AND (NOT {OFFICEY} OR conf>=0.95) AND NOT {PERSON}"),
  ("6 +no chains", f"{BASE} AND {NOFSQ} AND conf>=0.8 AND (NOT {OFFICEY} OR conf>=0.95) AND NOT {PERSON} AND {NOCHAIN}"),
  ("7 +website required", f"{BASE} AND {NOFSQ} AND conf>=0.8 AND (NOT {OFFICEY} OR conf>=0.95) AND NOT {PERSON} AND {NOCHAIN} AND web IS NOT NULL"),
]
for name, w in steps:
    print(name, con.execute(f"SELECT count(*) FROM q WHERE {w}").fetchone()[0])


HA = "npc LIKE 'HA%'"
DRNAME = r"regexp_matches(name, '^[A-Z][a-z]+ (Dr|Mr|Mrs|Ms)( [A-Z])+$')"
w = f"{steps[5][1]} AND {HA} AND NOT {DRNAME} AND len(list_filter(ph, v -> v IS NOT NULL AND v<>''))>0"
df = con.execute(f"""
  SELECT name, hier[1] AS category_group, hier[2] AS subcategory, cat AS category,
         addr AS address, pc AS postcode,
         array_to_string(ph, ' | ') AS phone, array_to_string(web, ' | ') AS website,
         array_to_string(em, ' | ') AS email, array_to_string(so, ' | ') AS socials,
         round(conf, 3) AS confidence, array_to_string(list_sort(ds), ' | ') AS sources,
         round(y, 6) AS latitude, round(x, 6) AS longitude, id AS overture_id
  FROM (SELECT *, row_number() OVER (PARTITION BY nname, npc ORDER BY conf DESC) rn FROM q WHERE {w})
  WHERE rn=1 ORDER BY category_group, subcategory, name
""").df()
df.to_csv(OUT, index=False, encoding="utf-8-sig")
print("exported", len(df))
