<?php

namespace Boodil\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Boodil\Payment\Api\BoodilApiInterface;

class ConfigProvider implements ConfigProviderInterface
{

    /**
     * @var BoodilApiInterface
     */
    private $boodileApiInterface;

    /**
     * ConfigProvider constructor.
     * @param BoodilApiInterface $boodileApiInterface
     */
    public function __construct(
        BoodilApiInterface $boodileApiInterface
    ) {
        $this->boodileApiInterface = $boodileApiInterface;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'environment' => $this->boodileApiInterface->getEnvironment(),
            'logo' => $this->boodileApiInterface->getLogo(),
        ];
    }
}
