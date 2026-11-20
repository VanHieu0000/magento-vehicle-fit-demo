<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Api;

/**
 * Answers "which products fit this vehicle?".
 */
interface FitmentResolverInterface
{
    /**
     * @param int $vehicleId
     * @return int[] product entity IDs
     */
    public function getProductIds(int $vehicleId): array;

    public function fits(int $vehicleId, int $productId): bool;
}
