# Run the module on a local Magento (for screenshots)

Needs: Windows 10/11 with WSL2, Docker Desktop, ~8 GB free RAM, free Magento access keys
(commercemarketplace.adobe.com > My profile > Access Keys). Keep the private key to yourself.

1. Inside WSL2 (Ubuntu), follow the one-line setup in the docker-magento README:
   https://github.com/markshust/docker-magento  - pick Magento Open Source 2.4.7.
2. Copy the module:
   `cp -r module/app/code/Hieu <your-magento>/src/app/code/`
3. Enable it:
   ```
   bin/magento module:enable Hieu_VehicleFit
   bin/magento setup:upgrade
   bin/magento cache:flush
   ```
4. Create 9 simple products with the SKUs from `module/app/code/Hieu/VehicleFit/docs/sample-fitment.csv`, then:
   `bin/magento vehiclefit:import app/code/Hieu/VehicleFit/docs/sample-fitment.csv`
5. Screenshots to take:
   - Admin > Catalog > Vehicle Fitment (grid)
   - Admin > Catalog > Import Fitment CSV (success message)
   - Category page with selector, before and after picking a vehicle
   - Product page badge "Fits your ..."
   - `curl -sI "https://magento.test/<category>.html?vehicle=1" | grep -i -E "x-magento-cache|age|set-cookie"` twice (MISS then HIT)
6. Run unit tests: `bin/clinotty vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Hieu/VehicleFit/Test/Unit`
