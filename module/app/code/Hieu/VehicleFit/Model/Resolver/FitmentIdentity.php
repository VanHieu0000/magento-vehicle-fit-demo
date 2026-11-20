<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Model\Resolver;

use Hieu\VehicleFit\Model\FitmentResolver;
use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;

/**
 * Tags the GraphQL response so Varnish/Fastly purge it together with category pages after an import.
 */
class FitmentIdentity implements IdentityInterface
{
    public function getIdentities(array $resolvedData): array
    {
        return [FitmentResolver::CACHE_TAG];
    }
}
