<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model;

/**
 * One place for the dropdown label so storefront, admin and GraphQL show the same text.
 */
class VehicleLabel
{
    public static function format(int $yearFrom, int $yearTo, string $engine): string
    {
        $years = $yearFrom === $yearTo ? (string)$yearFrom : $yearFrom . '-' . $yearTo;
        $engine = trim($engine);

        return $engine === '' ? $years : $years . ' ' . $engine;
    }
}
