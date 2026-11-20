# Hieu_VehicleFit - Magento 2.4 vehicle fitment module (demo)

Customer picks **make -> model -> year/engine** and sees only the parts that fit.
Built cache-first: no session, no cookies, so full page cache / Varnish / CDN keep working.

## Features

| Area | What it does | Files |
|---|---|---|
| Data | 2 tables, indexed, FK to products with cascade delete | `etc/db_schema.xml` |
| Storefront filter | `?vehicle=ID` on category + search pages | `Plugin/Catalog/LayerFilterPlugin.php`, `etc/frontend/di.xml` |
| Selector | 3 dropdowns, ajax, remembers choice in localStorage | `Block/Selector.php`, `view/frontend/templates/selector.phtml`, `view/frontend/web/js/selector.js` |
| Product badge | "Fits your 2019 Toyota Corolla" | `view/frontend/templates/product/fit-badge.phtml`, `view/frontend/web/js/fit-badge.js`, `Controller/Ajax/Fits.php` |
| Admin grid | Catalog > Vehicle Fitment | `view/adminhtml/ui_component/hieu_vehiclefit_vehicle_listing.xml` |
| CSV import | Admin upload + CLI, safe to re-run, errors per line | `Model/CsvImporter.php`, `Model/CsvRowValidator.php`, `Console/Command/ImportCommand.php` |
| Cache | app cache by tag + FPC purge by tag after import | `Model/FitmentResolver.php`, `Model/CacheInvalidator.php` |
| GraphQL | `vehicleFitment(make, model, year)` for headless / PWA | `etc/schema.graphqls`, `Model/Resolver/*` |
| Config | Stores > Configuration > Catalog > Vehicle Fitment | `etc/adminhtml/system.xml` |
| Tests | validator + resolver (cache hit/miss) | `Test/Unit/Model/*` |

## Install

```bash
cp -r app/code/Hieu/VehicleFit <magento>/app/code/Hieu/
bin/magento module:enable Hieu_VehicleFit
bin/magento setup:upgrade
bin/magento setup:di:compile        # production mode only
bin/magento cache:flush
bin/magento vehiclefit:import app/code/Hieu/VehicleFit/docs/sample-fitment.csv
```

Sample SKUs in `sample-fitment.csv` must exist in the catalog, otherwise the line is skipped and reported.

## Run tests

```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Hieu/VehicleFit/Test/Unit
```

## GraphQL

```graphql
query {
  vehicleFitment(make: "Toyota", model: "Corolla", year: 2019) {
    vehicle_id engine product_ids
  }
}
```

## Cache design (why it is built this way)

* Vehicle lives in the **URL** (`?vehicle=12`), not the session. Each filtered page is a normal cacheable URL.
* No `cacheable="false"` anywhere.
* Selector block implements `IdentityInterface` -> page gets the `hieu_vehiclefit` tag in `X-Magento-Tags`.
* Import dispatches `clean_cache_by_tags` -> Magento_PageCache purges only those pages (built-in FPC, Varnish or Fastly).
* Product badge is filled by ajax from localStorage, so product page HTML is the same for everyone.

## Known limits / next steps

1. Layered navigation counts come from OpenSearch aggregations, so they show unfiltered numbers.
   Production fix: add fitment as an indexed attribute (or a custom search request filter) so OpenSearch filters and counts.
2. Very big catalogs (millions of links): move `getProductIds` to an indexer table per store and add a message-queue import.
3. Add an integration test for `CsvImporter` against a real DB (dev/tests/integration).
