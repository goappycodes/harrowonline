import duckdb

REL = "2026-10-21.0"
BASE = f"s3://overturemaps-us-west-2/release/{REL}"
W, E, S, N = -0.41, -0.26, 51.555, 51.645

con = duckdb.connect("harrow.duckdb")
con.execute("INSTALL spatial; LOAD spatial; INSTALL httpfs; LOAD httpfs; SET s3_region='us-west-2'; SET lambda_syntax='ENABLE_SINGLE_ARROW';")

BBOX = f"bbox.xmin < {E} AND bbox.xmax > {W} AND bbox.ymin < {N} AND bbox.ymax > {S}"

con.execute(f"""
  CREATE OR REPLACE TABLE harrow AS
  SELECT geometry FROM read_parquet('{BASE}/theme=divisions/type=division_area/*', hive_partitioning=1)
  WHERE {BBOX} AND names.primary ILIKE '%Harrow%' AND subtype = 'county'
  ORDER BY ST_Area(geometry) DESC LIMIT 1
""")

con.execute(f"""
  CREATE OR REPLACE TABLE p AS
  SELECT pl.id, pl.names.primary AS name, pl.taxonomy.primary AS cat, pl.taxonomy.hierarchy AS hier,
         pl.basic_category AS basic, pl.confidence AS conf, pl.brand.names.primary AS brand,
         pl.websites AS web, pl.phones AS ph, pl.emails AS em, pl.socials AS so,
         pl.addresses[1].freeform AS addr, pl.addresses[1].postcode AS pc, pl.operating_status AS st,
         list_transform(pl.sources, s -> s.dataset) AS src,
         ST_X(pl.geometry) AS x, ST_Y(pl.geometry) AS y
  FROM read_parquet('{BASE}/theme=places/type=place/*', hive_partitioning=1) pl, harrow h
  WHERE pl.bbox.xmin < {E} AND pl.bbox.xmax > {W} AND pl.bbox.ymin < {N} AND pl.bbox.ymax > {S}
    AND ST_Within(pl.geometry, h.geometry)
""")

print("rows", con.execute("SELECT count(*) FROM p").fetchone())

print("top-level taxonomy, open, conf>=0.7:")
print(con.execute("""
  SELECT hier[1] top, count(*) n, count(*) FILTER (len(list_filter(src, s -> s <> 'Overture')) >= 2) multi
  FROM p WHERE conf >= 0.7 AND coalesce(st,'') <> 'permanently_closed'
  GROUP BY 1 ORDER BY n DESC
""").df().to_string())

print("second-level taxonomy, open, conf>=0.7:")
print(con.execute("""
  SELECT hier[1] top, hier[2] second, count(*) n
  FROM p WHERE conf >= 0.7 AND coalesce(st,'') <> 'permanently_closed'
  GROUP BY 1,2 ORDER BY 1, n DESC
""").df().to_string())

print("number of non-Overture sources:")
print(con.execute("""
  SELECT len(list_filter(src, s -> s <> 'Overture')) k, count(*) n,
         count(*) FILTER (conf>=0.7) conf70, count(*) FILTER (conf>=0.9) conf90
  FROM p GROUP BY 1 ORDER BY 1
""").df().to_string())

print("BrightQuery only:", con.execute("""
  SELECT count(*) FROM p WHERE list_filter(src, s -> s <> 'Overture') = ['BrightQuery']
""").fetchone())

print("branded:", con.execute("SELECT count(*) FROM p WHERE brand IS NOT NULL").fetchone())
