<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model;

use Hieu\VehicleFit\Model\ResourceModel\Vehicle as VehicleResource;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;

/**
 * Imports vehicle -> SKU links. Safe to re-run: vehicles are upserted, links are INSERT IGNORE.
 */
class CsvImporter
{
    public function __construct(
        private readonly Csv $csv,
        private readonly CsvRowValidator $validator,
        private readonly VehicleResource $vehicleResource,
        private readonly ProductResource $productResource,
        private readonly CacheInvalidator $cacheInvalidator
    ) {
    }

    /**
     * @return array{rows:int, vehicles:int, links:int, errors:array<int, string>}
     * @throws LocalizedException
     */
    public function import(string $filePath): array
    {
        $data = $this->csv->getData($filePath);
        if (count($data) < 2) {
            throw new LocalizedException(__('The file is empty or has only a header row.'));
        }

        $header = array_map(static fn($h) => strtolower(trim((string)$h)), array_shift($data));
        $missing = array_diff(CsvRowValidator::REQUIRED, $header);
        if ($missing !== []) {
            throw new LocalizedException(__('Missing columns: %1', implode(', ', $missing)));
        }

        $errors = [];
        $valid = [];
        foreach ($data as $index => $values) {
            $line = $index + 2; // +1 for header, +1 for 1-based lines
            if (count($values) !== count($header)) {
                $errors[$line] = 'wrong number of columns';
                continue;
            }
            $row = array_combine($header, $values);
            $rowErrors = $this->validator->validate($row);
            if ($rowErrors !== []) {
                $errors[$line] = implode('; ', $rowErrors);
                continue;
            }
            $valid[$line] = $row;
        }

        // One query for all SKUs instead of one per row.
        $skus = array_values(array_unique(array_map(static fn($r) => trim((string)$r['sku']), $valid)));
        $skuToId = array_change_key_case($this->productResource->getProductsIdsBySkus($skus), CASE_LOWER);

        $vehicleIds = [];
        $pairs = [];
        $connection = $this->vehicleResource->getConnection();
        $connection->beginTransaction();
        try {
            foreach ($valid as $line => $row) {
                $sku = strtolower(trim((string)$row['sku']));
                if (!isset($skuToId[$sku])) {
                    $errors[$line] = sprintf('SKU "%s" not found', $row['sku']);
                    continue;
                }
                $vehicle = $this->validator->toVehicle($row);
                $key = implode('|', $vehicle);
                $vehicleIds[$key] ??= $this->vehicleResource->getOrCreateVehicleId($vehicle);
                $pairs[] = [$vehicleIds[$key], (int)$skuToId[$sku]];
            }
            $links = $this->vehicleResource->addLinks($pairs);
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->cacheInvalidator->invalidate();
        ksort($errors);

        return [
            'rows' => count($data),
            'vehicles' => count($vehicleIds),
            'links' => $links,
            'errors' => $errors,
        ];
    }
}
