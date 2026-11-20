<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model;

/**
 * Plain PHP (no Magento deps) so it is easy to unit test.
 * CSV columns: make, model, year_from, year_to, engine (optional), sku
 */
class CsvRowValidator
{
    public const REQUIRED = ['make', 'model', 'year_from', 'year_to', 'sku'];
    private const MIN_YEAR = 1950;
    private const MAX_LENGTH = 64;

    /**
     * @param array<string, mixed> $row
     * @return string[] empty when the row is valid
     */
    public function validate(array $row, ?int $currentYear = null): array
    {
        $errors = [];
        foreach (self::REQUIRED as $column) {
            if (trim((string)($row[$column] ?? '')) === '') {
                $errors[] = sprintf('missing %s', $column);
            }
        }
        if ($errors !== []) {
            return $errors;
        }

        foreach (['make', 'model', 'engine'] as $column) {
            if (mb_strlen(trim((string)($row[$column] ?? ''))) > self::MAX_LENGTH) {
                $errors[] = sprintf('%s longer than %d chars', $column, self::MAX_LENGTH);
            }
        }

        $maxYear = ($currentYear ?? (int)date('Y')) + 2;
        $from = trim((string)$row['year_from']);
        $to = trim((string)$row['year_to']);
        if (!ctype_digit($from) || !ctype_digit($to)) {
            $errors[] = 'years must be numbers';
            return $errors;
        }
        if ((int)$from < self::MIN_YEAR || (int)$to > $maxYear) {
            $errors[] = sprintf('years must be between %d and %d', self::MIN_YEAR, $maxYear);
        }
        if ((int)$from > (int)$to) {
            $errors[] = 'year_from is after year_to';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{make:string, model:string, year_from:int, year_to:int, engine:string}
     */
    public function toVehicle(array $row): array
    {
        return [
            'make' => trim((string)$row['make']),
            'model' => trim((string)$row['model']),
            'year_from' => (int)$row['year_from'],
            'year_to' => (int)$row['year_to'],
            'engine' => trim((string)($row['engine'] ?? '')),
        ];
    }
}
