<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model;

use Hieu\VehicleFit\Api\FitmentResolverInterface;
use Hieu\VehicleFit\Model\ResourceModel\Vehicle as VehicleResource;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Reads vehicle -> product links from an indexed table and keeps them in the app cache.
 * Cache is tagged, so one import cleans everything with a single tag.
 */
class FitmentResolver implements FitmentResolverInterface
{
    public const CACHE_TAG = 'hieu_vehiclefit';
    private const CACHE_KEY_PREFIX = 'hieu_vehiclefit_products_';

    /** @var array<int, int[]> per-request memo, so a category page hits cache once */
    private array $memo = [];

    public function __construct(
        private readonly VehicleResource $vehicleResource,
        private readonly CacheInterface $cache,
        private readonly SerializerInterface $serializer,
        private readonly Config $config
    ) {
    }

    public function getProductIds(int $vehicleId): array
    {
        if ($vehicleId <= 0) {
            return [];
        }
        if (isset($this->memo[$vehicleId])) {
            return $this->memo[$vehicleId];
        }

        $key = self::CACHE_KEY_PREFIX . $vehicleId;
        $cached = $this->cache->load($key);
        if (is_string($cached) && $cached !== '') {
            return $this->memo[$vehicleId] = array_map('intval', (array)$this->serializer->unserialize($cached));
        }

        $ids = $this->vehicleResource->getProductIds($vehicleId);
        $this->cache->save(
            $this->serializer->serialize($ids),
            $key,
            [self::CACHE_TAG],
            $this->config->getCacheLifetime() ?: null
        );

        return $this->memo[$vehicleId] = $ids;
    }

    public function fits(int $vehicleId, int $productId): bool
    {
        if ($vehicleId <= 0 || $productId <= 0) {
            return false;
        }

        return in_array($productId, $this->getProductIds($vehicleId), true);
    }

    /**
     * Called after an import so long-running processes (cron, consumers) don't keep old data.
     */
    public function resetMemo(): void
    {
        $this->memo = [];
    }
}
