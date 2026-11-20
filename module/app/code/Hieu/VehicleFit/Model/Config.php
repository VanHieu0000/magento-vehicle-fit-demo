<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_ENABLED = 'vehiclefit/general/enabled';
    private const XML_CACHE_LIFETIME = 'vehiclefit/general/cache_lifetime';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getCacheLifetime(): int
    {
        return max(0, (int)$this->scopeConfig->getValue(self::XML_CACHE_LIFETIME));
    }
}
