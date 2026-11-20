<?php
declare(strict_types=1);

namespace Hieu\VehicleFit\Block;

use Hieu\VehicleFit\Model\Config;
use Hieu\VehicleFit\Model\FitmentResolver;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * "Choose your vehicle" form on category pages.
 * IdentityInterface adds the hieu_vehiclefit tag to the page, so an import purges these pages from FPC.
 */
class Selector extends Template implements IdentityInterface
{
    public function __construct(
        Context $context,
        private readonly Config $config,
        private readonly Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function getIdentities(): array
    {
        return [FitmentResolver::CACHE_TAG];
    }

    public function getJsConfig(): string
    {
        return $this->json->serialize([
            'Hieu_VehicleFit/js/selector' => [
                'optionsUrl' => $this->getUrl('vehiclefit/ajax/options'),
                'selectedVehicleId' => (int)$this->getRequest()->getParam('vehicle'),
            ],
        ]);
    }
}
