<?php
namespace Boodil\Payment\Model\Resolver;

use Boodil\Payment\Api\BoodilApiInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Boodil\Payment\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Exception;

class InitiatePayment implements ResolverInterface
{
    protected $logger;
    protected $cartRepository;
    private $boodileApiInterface;
    private $scopeConfig;

    public function __construct(
        Logger $logger,
        GuestCartRepositoryInterface $guestCartRepository,
        BoodilApiInterface $boodileApiInterface,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->logger = $logger;
        $this->cartRepository = $guestCartRepository;
        $this->boodileApiInterface = $boodileApiInterface;
        $this->scopeConfig = $scopeConfig;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $cartId = $args['input']['cartId'] ?? null;
        $order = $this->cartRepository->get($cartId);
        $redirectUrl = $this->scopeConfig->getValue(
            'payment/boodil/headless_callback_url',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $items = $order->getAllVisibleItems();
        $itemData = [];
        foreach ($items as $item) {
            $itemData[] = [
                "sku" => $item->getSku(),
                "name" => $item->getName(),
                "qty" => $item->getQty(),
                "price" => $item->getPriceInclTax()
            ];
        }
        $params = [
            "merchantUuid" => $this->boodileApiInterface->getMerchantUuid(),
            /*"reference" => $order->getReservedOrderId(),*/
            "email" => $order->getCustomerEmail() ?? $order->getBillingAddress()->getEmail(),
            "amount" => $order->getGrandTotal(),
            "currency" => $order->getQuoteCurrencyCode(),
            "redirectUrl" => $redirectUrl,
            "customerId" => $order->getCustomerId(),
            "firstName" => $order->getCustomerFirstname() ?? $order->getBillingAddress()->getName(),
            "middleName" => $order->getCustomerMiddlename() ?? $order->getBillingAddress()->getMiddlename(),
            "surname" => $order->getCustomerLastname() ?? $order->getBillingAddress()->getLastname(),
            "address1" => $order->getBillingAddress()->getStreetLine(1),
            "address2" => $order->getBillingAddress()->getStreetLine(2),
            "phone" => $order->getBillingAddress()->getTelephone(),
            "postcode" => $order->getBillingAddress()->getPostcode(),
            "city" => $order->getBillingAddress()->getCity(),
            "country" => $order->getBillingAddress()->getCountry(),
            "c1" => $cartId,
            "cart" => $itemData
        ];

        $headers = $this->boodileApiInterface->getAuthHeaders();
        $boodilApiUrl = $this->boodileApiInterface->getApiUrl("transactions");

        // Logging request parameters
        $this->logger->info('Request parameters to Boodil API: ' . json_encode($params));

        try {
            $paymentUuid = $this->boodileApiInterface->callCurl(
                $boodilApiUrl,
                $params,
                'POST',
                $headers,
                true
            );

            return $paymentUuid;
        } catch (\Exception $e) {
            // Logging exceptions
            $this->logger->debug('Boodil\\Payment\\Controller\\BoodilpayAbstract\\createTransactionsApi: '. $e->getMessage());
            return $e->getMessage();
        }
    }
}

