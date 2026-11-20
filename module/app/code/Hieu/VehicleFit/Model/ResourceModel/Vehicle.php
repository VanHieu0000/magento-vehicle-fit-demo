<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Vehicle extends AbstractDb
{
    public const LINK_TABLE = 'hieu_vehiclefit_product';

    protected function _construct(): void
    {
        $this->_init('hieu_vehiclefit_vehicle', 'vehicle_id');
    }

    /**
     * @return int[]
     */
    public function getProductIds(int $vehicleId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::LINK_TABLE), ['product_id'])
            ->where('vehicle_id = ?', $vehicleId);

        return array_map('intval', $connection->fetchCol($select));
    }

    public function hasLink(int $vehicleId, int $productId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::LINK_TABLE), ['vehicle_id'])
            ->where('vehicle_id = ?', $vehicleId)
            ->where('product_id = ?', $productId)
            ->limit(1);

        return (bool)$connection->fetchOne($select);
    }

    /**
     * @return string[]
     */
    public function getMakes(): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->distinct()
            ->from($this->getMainTable(), ['make'])
            ->order('make ASC');

        return $connection->fetchCol($select);
    }

    /**
     * @return string[]
     */
    public function getModels(string $make): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->distinct()
            ->from($this->getMainTable(), ['model'])
            ->where('make = ?', $make)
            ->order('model ASC');

        return $connection->fetchCol($select);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getVehicles(string $make, string $model): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('make = ?', $make)
            ->where('model = ?', $model)
            ->order(['year_from DESC', 'engine ASC']);

        return $connection->fetchAll($select);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findVehicles(string $make, string $model, int $year): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('make = ?', $make)
            ->where('model = ?', $model)
            ->where('year_from <= ?', $year)
            ->where('year_to >= ?', $year);

        return $connection->fetchAll($select);
    }

    /**
     * Returns the ID of an existing vehicle, or creates it.
     *
     * @param array{make:string, model:string, year_from:int, year_to:int, engine:string} $vehicle
     */
    public function getOrCreateVehicleId(array $vehicle): int
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();

        $connection->insertOnDuplicate($table, $vehicle, ['make']);

        $select = $connection->select()
            ->from($table, ['vehicle_id'])
            ->where('make = ?', $vehicle['make'])
            ->where('model = ?', $vehicle['model'])
            ->where('year_from = ?', $vehicle['year_from'])
            ->where('year_to = ?', $vehicle['year_to'])
            ->where('engine = ?', $vehicle['engine']);

        return (int)$connection->fetchOne($select);
    }

    /**
     * @param array<int, array{0:int, 1:int}> $pairs list of [vehicle_id, product_id]
     * @return int number of rows sent (duplicates are ignored by MySQL)
     */
    public function addLinks(array $pairs): int
    {
        if ($pairs === []) {
            return 0;
        }
        $connection = $this->getConnection();
        $sent = 0;
        foreach (array_chunk($pairs, 1000) as $chunk) {
            $sent += $connection->insertArray(
                $this->getTable(self::LINK_TABLE),
                ['vehicle_id', 'product_id'],
                $chunk,
                AdapterInterface::INSERT_IGNORE
            );
        }

        return $sent;
    }
}
