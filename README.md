# Harrow Online — Business Directory preview

A static preview of about 2,600 Harrow businesses, filtered from [Overture Maps](https://overturemaps.org) open data. It has category, area and contact filters, a clustered map and a detail page per listing. It is a data preview for the client, not the production directory, and is set to `noindex`.

## Run locally

```bash
python -m http.server 8000
```

Then open http://localhost:8000.

## Rebuild the data

Needs Python with `duckdb`, `pandas` and `numpy`.

1. `python scripts/1_fetch_overture.py` downloads Harrow places from the Overture release into `harrow.duckdb` and prints a profile.
2. `python scripts/2_filter_export.py` applies the filters and writes `data/harrow-listings.csv`.
3. `python scripts/3_build_site_data.py` converts the CSV into `data/listings.json` for the site.

## Filters applied

- Inside the London Borough of Harrow boundary, with a valid HA postcode and a phone number
- Not marked permanently closed
- Customer-facing categories only (no B2B, corporate offices, parking, parks, schools or government)
- Overture confidence of 0.8 or more, or 0.95 for office-type businesses
- Not sourced only from Foursquare or AllThePlaces
- No records named after a person, no chains (same name at 3+ locations or same website across 3+ names)
- Duplicates with the same name and postcode removed

## Attribution

Business data © Overture Maps Foundation and its sources (BrightQuery, Meta, Microsoft, Uberall, DAC, PinMeTo; CDLA Permissive 2.0).
