<?php
namespace Boodil\Payment\Controller\Index;

use Boodil\Payment\Controller\BoodilpayAbstract;

class Index extends BoodilpayAbstract
{
    /**
     * @return false|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('request') != 'transaction') {
            return false;
        }

        try {
            $resultJson = $this->resultJsonFactory->create();
            $merchantUuid = $this->createTransactionsApi();
            if (isset($merchantUuid['uuid'])) {
                return $resultJson->setData(['uuid' => $merchantUuid['uuid']]);
            } else {
                return $resultJson->setData(['error' => $merchantUuid['message']]);
            }
        } catch (\Exception $e) {
            return $resultJson->setData(['error' => $e->getMessage()]);
        }
    }
}
