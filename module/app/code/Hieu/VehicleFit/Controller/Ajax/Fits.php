<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Controller\Ajax;

use Hieu\VehicleFit\Api\FitmentResolverInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * GET vehiclefit/ajax/fits?vehicle=ID&product=ID -> {"fits": true|false}
 *
 * The product page HTML stays the same for everyone (cacheable); the badge asks this
 * endpoint with the vehicle saved in the browser.
 */
class Fits implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly FitmentResolverInterface $fitmentResolver
    ) {
    }

    public function execute(): Json
    {
        $fits = $this->fitmentResolver->fits(
            (int)$this->request->getParam('vehicle'),
            (int)$this->request->getParam('product')
        );

        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'public, max-age=3600', true);

        return $result->setData(['fits' => $fits]);
    }
}
