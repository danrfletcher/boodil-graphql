<?php

namespace Boodil\Payment\Cron\Payment;

use Boodil\Payment\Api\BoodilApiInterface;

class Status
{
    /**
     * @var BoodilApiInterface
     */
    private $boodileApiInterface;

    /**
     * Status constructor.
     * @param BoodilApiInterface $boodileApiInterface
     */
    public function __construct(
        BoodilApiInterface $boodileApiInterface
    ) {
        $this->boodileApiInterface = $boodileApiInterface;
    }

    /**
     * @return false
     */
    public function execute()
    {
        if (!$this->boodileApiInterface->isActive()) {
            return false;
        }

        $this->boodileApiInterface->getPaymentStatusAPI();
    }

}
