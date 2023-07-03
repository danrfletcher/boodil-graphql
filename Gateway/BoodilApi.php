<?php

namespace Boodil\Payment\Gateway;

use Exception;
use Boodil\Payment\Model\Transactions;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Boodil\Payment\Api\BoodilApiInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Boodil\Payment\Model\TransactionsFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\HTTP\Client\CurlFactory as ClientFactory;
use Magento\Sales\Api\InvoiceOrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class BoodilApi implements BoodilApiInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Encryptor
     */
    protected $encryptor;

    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var \Boodil\Payment\Model\TransactionsFactory
     */
    protected $transactionsFactory;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Transactions
     */
    private $transactions;

    /**
     * @var InvoiceOrderInterface
     */
    protected $invoiceOrderInterface;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * BoodilApi constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $messageManager
     * @param Json $json
     * @param ClientFactory $httpClientFactory
     * @param LoggerInterface $logger
     * @param ResourceConnection $resource
     * @param Transactions $transactions
     * @param TransactionsFactory $transactionsFactory
     * @param InvoiceOrderInterface $invoiceOrderInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param Encryptor $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $messageManager,
        Json $json,
        ClientFactory $httpClientFactory,
        LoggerInterface $logger,
        ResourceConnection $resource,
        Transactions $transactions,
        TransactionsFactory $transactionsFactory,
        InvoiceOrderInterface $invoiceOrderInterface,
        OrderRepositoryInterface $orderRepository,
        Encryptor $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->messageManager = $messageManager;
        $this->httpClientFactory = $httpClientFactory;
        $this->resource = $resource;
        $this->transactions = $transactions;
        $this->transactionsFactory = $transactionsFactory;
        $this->invoiceOrderInterface = $invoiceOrderInterface;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get API URL
     *
     * @return string
     */
    public function getApiUrl($name)
    {
        $environment = $this->getEnvironment();
        if ($environment == "production") {
            $url = self::PRODUCTION_URL;
        } else {
            $url = self::SANDBOX_URL;
        }

        return $url . $name;
    }

    /**
     * @return string
     */
    public function getMerchantUuid()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::XML_PATH_BOODIL_UUID, $storeScope));
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_BOODIL_ENVIRONMENT, $storeScope);
    }

    /**
     * @return string
     */
    public function getLogo()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_BOODIL_LOGO, $storeScope);
    }

    /**
     * @return string
     */
    public function getSuccessUrl()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_BOODIL_SUCCESS_URL, $storeScope);
    }

    /**
     * @return array
     */
    public function getAuthHeaders()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $userName = $this->encryptor->decrypt(
            $this->scopeConfig->getValue(self::XML_PATH_BOODIL_USERNAME, $storeScope));
        $accessKey = $this->encryptor->decrypt(
            $this->scopeConfig->getValue(self::XML_PATH_BOODIL_ACCESS_KEY, $storeScope)
        );

        $client = $this->httpClientFactory->create();
        $client->setCredentials($userName, $accessKey);

        return $client;
    }

    /**
     * @param $url
     * @param array $params
     * @param string $method
     * @param array $headers
     * @param false $assoc
     * @param false $statusCode
     * @return array|mixed|string
     */
    public function callCurl($url, $params = [], $method = 'GET', $headers = [], $assoc = false, $statusCode = false)
    {
        $client = $this->getAuthHeaders();
        $paramString = null;
        if ($params) {
            if ($method == 'GET') {
                foreach ($params as $name => $value) {
                    $param[] = urlencode($name) . '=' . urlencode($value);
                }
                $paramString = implode('&', $param);
            } else {
                $client->post($url, $params);
            }
        }

        if ($paramString) {
            $url .= "?" . $paramString;
            $client->get($url);
        }

        try {
            $responseBody = $client->getBody();
            if ($statusCode) {
                return [
                    "responseBody" => json_decode($responseBody, $assoc),
                    "responseCode" => $client->getStatus()
                ];
            }
            $jsonResult = json_decode($responseBody, $assoc);
            return $jsonResult;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * @return mixed
     */
    public function isActive()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_IS_ACTIVE_SCOPE, $storeScope);
    }

    /**
     * @return mixed|void
     * @throws Exception
     */
    public function getPaymentStatusAPI()
    {
        $transactions = $this->getOrderCollection();
        foreach ($transactions as $transaction) {
            $params = [
                "merchantUuid" => $this->getMerchantUuid(),
                "uuid" => $transaction->getUuid(),
            ];

            $headers = $this->getAuthHeaders();
            $boodilApiUrl = $this->getApiUrl("payments/status");

            try {
                $results = $this->callCurl(
                    $boodilApiUrl,
                    $params,
                    'GET',
                    $headers,
                    true
                );

                $boodilStatusCode = $results['result']['statusCode'] ?? "";
                if ($boodilStatusCode != $transaction->getStatusCode()) {
                    if ($boodilStatusCode == "ACSC" || $boodilStatusCode == "ACCC") {
                        $order = $this->orderRepository->get($transaction->getOrderId());
                        if ($order->canInvoice()) {
                            $this->createInvoice($transaction->getOrderId());
                        }
                    }
                    $boodilTrans = $this->getTransaction($transaction->getEntityId());
                    $boodilTrans->setStatusCode($boodilStatusCode);
                    $boodilTrans->setTimes($transaction->getTimes() + 1);
                    if (($transaction->getTimes() + 1) > 4 && ($boodilStatusCode != "ACSC" || $boodilStatusCode != "ACCC")) {
                        $boodilTrans->setStatusCode("FAILED");
                    }
                    $boodilTrans->save();
                }
            } catch (\Exception $e) {
                $this->logger->debug('Boodil\\Payment\\Gateway\\getTransactionApi: '. $e->getMessage());
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * @param $orderId
     */
    public function createInvoice($orderId)
    {
        try {
            $this->invoiceOrderInterface->execute(
                $orderId,
                true
            );
        } catch (\Exception $exception) {
            $results = [
                "message" => __("Something went wrong while creating the invoice"),
                "error" => $exception->getMessage()
            ];
            $this->logger->debug('Boodil\\Payment\\Gateway\\createInvoice: '. $this->json->serialize($results));
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getTransaction($id)
    {
        $transaction = $this->transactionsFactory->create()->load($id);

        return $transaction;
    }

    /**
     * @return \Magento\Framework\Data\Collection\AbstractDb|\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection|mixed|null
     */
    public function getOrderCollection()
    {
        $to = date("Y-m-d h:i:s");
        $from = strtotime('-2 day', strtotime($to));
        $from = date('Y-m-d h:i:s', $from);

        $collection = $this->transactions->getCollection()
            ->addFieldToFilter("status_code", ["neq" => "ACSC"])
            ->addFieldToFilter("status_code", ["neq" => "FAILED"])
            ->addFieldToFilter('created_at', array('from'=> $from, 'to'=> $to))
            ->setOrder('entity_id', 'DESC');

        return $collection;
    }
}
