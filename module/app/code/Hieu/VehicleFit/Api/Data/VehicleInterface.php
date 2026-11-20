<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Api\Data;

interface VehicleInterface
{
    public const VEHICLE_ID = 'vehicle_id';
    public const MAKE = 'make';
    public const MODEL = 'model';
    public const YEAR_FROM = 'year_from';
    public const YEAR_TO = 'year_to';
    public const ENGINE = 'engine';

    public function getMake(): string;

    public function getModel(): string;

    public function getYearFrom(): int;

    public function getYearTo(): int;

    public function getEngine(): string;

    /**
     * Short label for dropdowns, eg. "2018-2022 1.8 Hybrid".
     */
    public function getLabel(): string;
}
