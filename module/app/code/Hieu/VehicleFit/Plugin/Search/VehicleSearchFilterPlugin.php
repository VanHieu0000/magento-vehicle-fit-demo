<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Plugin\Search;

use Hieu\VehicleFit\Api\FitmentResolverInterface;
use Hieu\VehicleFit\Model\Config;
use Magento\Elasticsearch\ElasticAdapter\SearchAdapter\Mapper;
use Magento\Framework\App\RequestInterface as HttpRequest;
use Magento\Framework\Search\RequestInterface as SearchRequest;

/**
 * Category / search list: keep only products that fit ?vehicle=ID.
 *
 * The filter goes into the OpenSearch / Elasticsearch query itself (ids filter), so the
 * search engine does paging, "Items 1-12 of N" and layered navigation counts correctly.
 *
 * Why not a plugin on Catalog\Model\Layer::prepareProductCollection: Layer calls that method
 * on $this, so plugins never run there, and an SQL filter after the search would only
 * filter inside the page the engine already returned.
 *
 * Why URL param and not session: the vehicle is part of the URL, so full page cache
 * (built-in, Varnish, Fastly or any CDN) caches each filtered page normally and guests
 * never get a session cookie just for picking a car.
 */
class VehicleSearchFilterPlugin
{
    private const REQUESTS = ['catalog_view_container', 'quick_search_container'];

    public function __construct(
        private readonly HttpRequest $httpRequest,
        private readonly FitmentResolverInterface $fitmentResolver,
        private readonly Config $config
    ) {
    }

    /**
     * @param Mapper $subject
     * @param array $query
     * @param SearchRequest $request
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterBuildQuery(Mapper $subject, array $query, SearchRequest $request): array
    {
        $vehicleId = (int)$this->httpRequest->getParam('vehicle');
        if ($vehicleId <= 0 || !in_array($request->getName(), self::REQUESTS, true) || !$this->config->isEnabled()) {
            return $query;
        }

        // product id = document _id in the catalog index; ["0"] = nothing fits -> empty list
        $ids = array_map('strval', $this->fitmentResolver->getProductIds($vehicleId)) ?: ['0'];
        $query['body']['query']['bool']['filter'][] = ['ids' => ['values' => $ids]];

        return $query;
    }
}
