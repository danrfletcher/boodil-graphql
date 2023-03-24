<?php

namespace Boodil\Payment\Model;

use Magento\Framework\Model\AbstractModel;

class Transactions extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Boodil\Payment\Model\ResourceModel\Transactions');
    }
}
