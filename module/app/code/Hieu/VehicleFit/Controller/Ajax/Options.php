<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Controller\Ajax;

use Hieu\VehicleFit\Model\ResourceModel\Vehicle as VehicleResource;
use Hieu\VehicleFit\Model\VehicleLabel;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * GET vehiclefit/ajax/options                     -> makes
 * GET vehiclefit/ajax/options?make=X              -> models
 * GET vehiclefit/ajax/options?make=X&model=Y      -> vehicles (year range + engine)
 *
 * Same answer for every visitor, no session used, so it is safe to cache at the edge.
 */
class Options implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly VehicleResource $vehicleResource
    ) {
    }

    public function execute(): Json
    {
        $make = trim((string)$this->request->getParam('make'));
        $model = trim((string)$this->request->getParam('model'));

        if ($make === '') {
            $items = array_map(static fn($m) => ['value' => $m, 'label' => $m], $this->vehicleResource->getMakes());
        } elseif ($model === '') {
            $items = array_map(static fn($m) => ['value' => $m, 'label' => $m], $this->vehicleResource->getModels($make));
        } else {
            $items = array_map(
                static fn(array $v) => [
                    'value' => (int)$v['vehicle_id'],
                    'label' => VehicleLabel::format((int)$v['year_from'], (int)$v['year_to'], (string)$v['engine']),
                ],
                $this->vehicleResource->getVehicles($make, $model)
            );
        }

        $result = $this->jsonFactory->create();
        $result->setHeader('Cache-Control', 'public, max-age=3600', true);

        return $result->setData(['items' => $items]);
    }
}
