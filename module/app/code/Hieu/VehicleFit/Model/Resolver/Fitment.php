<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model\Resolver;

use Hieu\VehicleFit\Api\FitmentResolverInterface;
use Hieu\VehicleFit\Model\ResourceModel\Vehicle as VehicleResource;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * query { vehicleFitment(make: "Toyota", model: "Corolla", year: 2019) { vehicle_id engine product_ids } }
 */
class Fitment implements ResolverInterface
{
    public function __construct(
        private readonly VehicleResource $vehicleResource,
        private readonly FitmentResolverInterface $fitmentResolver
    ) {
    }

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        $make = trim((string)($args['make'] ?? ''));
        $model = trim((string)($args['model'] ?? ''));
        $year = (int)($args['year'] ?? 0);

        if ($make === '' || $model === '') {
            throw new GraphQlInputException(__('make and model are required.'));
        }
        if ($year < 1950 || $year > (int)date('Y') + 2) {
            throw new GraphQlInputException(__('year is out of range.'));
        }

        return array_map(
            fn(array $row) => [
                'vehicle_id' => (int)$row['vehicle_id'],
                'make' => $row['make'],
                'model' => $row['model'],
                'year_from' => (int)$row['year_from'],
                'year_to' => (int)$row['year_to'],
                'engine' => $row['engine'],
                'product_ids' => $this->fitmentResolver->getProductIds((int)$row['vehicle_id']),
            ],
            $this->vehicleResource->findVehicles($make, $model, $year)
        );
    }
}
