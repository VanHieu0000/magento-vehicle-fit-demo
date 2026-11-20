# VehicleFit - Magento 2.4 vehicle fitment module (demo)

Customers pick **make -> model -> year / engine** and see only the auto parts that fit.
Built cache-first: no session, no `cacheable="false"`, purge by cache tag, so full page cache, Varnish and CDN keep working.

**Live showcase:** https://vanhieu0000.github.io/magento-vehicle-fit-demo/

## What is in this repo

| Path | What |
|---|---|
| `module/app/code/Hieu/VehicleFit/` | The Magento 2 module (PHP 8.2/8.3, Magento 2.4.7) |
| `module/app/code/Hieu/VehicleFit/docs/README.md` | Module docs: install, config, GraphQL, cache design, known limits |
| `module/app/code/Hieu/VehicleFit/docs/sample-fitment.csv` | Sample data |
| `docs/AI-WORKFLOW.md` | How AI was used and what was changed after review |
| `docs/LOCAL-SETUP.md` | Run it on a local Docker Magento for real screenshots |
| `index.html`, `assets/` | Showcase site (GitHub Pages) |

## Module features

- Two indexed tables with foreign keys (declarative schema)
- Storefront selector (ajax) + `?vehicle=ID` filter on category and search pages (plugin on catalog layer)
- Product page badge "Fits your ..." loaded by ajax, page HTML stays cacheable
- Admin grid (UI component) + CSV import in admin and CLI (`bin/magento vehiclefit:import`)
- Tagged app cache + FPC purge by tag after import
- GraphQL query `vehicleFitment(make, model, year)`
- ACL, system config, unit tests

## Quick install

```bash
cp -r module/app/code/Hieu <magento>/app/code/
bin/magento module:enable Hieu_VehicleFit
bin/magento setup:upgrade
bin/magento cache:flush
```

## Status

Demo / portfolio module with sample data only. No client code or data.

## Media credits

Photos from Wikimedia Commons (Shixart1985 CC BY 2.0, Envy fstop CC BY-SA 4.0, Bernard Spragg. NZ CC0, Cschirp CC BY 3.0) and a Pexels video (Pexels License). 3D brake assembly built in code with three.js.

## License

MIT for the code. Media keep their own licenses.
