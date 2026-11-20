<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Plugin\Catalog;

use Hieu\VehicleFit\Api\FitmentResolverInterface;
use Hieu\VehicleFit\Model\Config;
use Magento\Catalog\Model\Layer;
use Magento\Framework\App\RequestInterface;

/**
 * Category / search list: keep only products that fit ?vehicle=ID.
 *
 * Why URL param and not session: the vehicle is part of the URL, so full page cache
 * (built-in, Varnish, Fastly or any CDN) caches each filtered page normally and guests
 * never get a session cookie just for picking a car.
 *
 * Known limit: layered navigation counts come from the search engine aggregation, so
 * they still show unfiltered numbers. Fix for production = push fitment into the
 * OpenSearch index as an attribute (see docs/README.md, "Next steps").
 */
class LayerFilterPlugin
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly FitmentResolverInterface $fitmentResolver,
        private readonly Config $config
    ) {
    }

    /**
     * @param Layer $subject
     * @param Layer $result
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     */
    public function afterPrepareProductCollection(Layer $subject, $result, $collection)
    {
        $vehicleId = (int)$this->request->getParam('vehicle');
        if ($vehicleId <= 0 || !$this->config->isEnabled()) {
            return $result;
        }

        $productIds = $this->fitmentResolver->getProductIds($vehicleId);
        // [0] = "nothing fits" -> empty list instead of a broken IN () query
        $collection->addIdFilter($productIds ?: [0]);

        return $result;
    }
}
