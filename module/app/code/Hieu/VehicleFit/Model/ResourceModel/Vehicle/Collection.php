<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model\ResourceModel\Vehicle;

use Hieu\VehicleFit\Model\ResourceModel\Vehicle as VehicleResource;
use Hieu\VehicleFit\Model\Vehicle;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'vehicle_id';

    protected function _construct(): void
    {
        $this->_init(Vehicle::class, VehicleResource::class);
    }
}
