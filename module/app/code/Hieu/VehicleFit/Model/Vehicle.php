<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model;

use Hieu\VehicleFit\Api\Data\VehicleInterface;
use Magento\Framework\Model\AbstractModel;

class Vehicle extends AbstractModel implements VehicleInterface
{
    protected $_eventPrefix = 'hieu_vehiclefit_vehicle';

    protected function _construct(): void
    {
        $this->_init(ResourceModel\Vehicle::class);
    }

    public function getMake(): string
    {
        return (string)$this->getData(self::MAKE);
    }

    public function getModel(): string
    {
        return (string)$this->getData(self::MODEL);
    }

    public function getYearFrom(): int
    {
        return (int)$this->getData(self::YEAR_FROM);
    }

    public function getYearTo(): int
    {
        return (int)$this->getData(self::YEAR_TO);
    }

    public function getEngine(): string
    {
        return (string)$this->getData(self::ENGINE);
    }

    public function getLabel(): string
    {
        return VehicleLabel::format($this->getYearFrom(), $this->getYearTo(), $this->getEngine());
    }
}
