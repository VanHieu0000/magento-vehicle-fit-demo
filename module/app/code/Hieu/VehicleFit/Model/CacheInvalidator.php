<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;

/**
 * Cleans app cache AND full page cache (built-in, Varnish or Fastly) for fitment data.
 *
 * The selector block and the GraphQL query both carry the hieu_vehiclefit tag,
 * so dispatching clean_cache_by_tags purges only those pages, not the whole store.
 */
class CacheInvalidator implements IdentityInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly EventManager $eventManager,
        private readonly FitmentResolver $fitmentResolver
    ) {
    }

    public function getIdentities(): array
    {
        return [FitmentResolver::CACHE_TAG];
    }

    public function invalidate(): void
    {
        $this->cache->clean($this->getIdentities());
        $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $this]);
        $this->fitmentResolver->resetMemo();
    }
}
