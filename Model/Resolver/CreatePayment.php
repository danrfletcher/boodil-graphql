<?php
namespace Boodil\Payment\Model\Resolver;

use Boodil\Payment\Api\BoodilApiInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Boodil\Payment\Logger\Logger;
use Boodil\Payment\Model\Service\Headless;
use Exception;

class CreatePayment implements ResolverInterface
{
    protected $logger;
    private $boodileApiInterface;
    private $headlessService;

    public function __construct(
        Logger $logger,
        BoodilApiInterface $boodileApiInterface,
        Headless $headlessService
    ) {
        $this->logger = $logger;
        $this->boodileApiInterface = $boodileApiInterface;
        $this->headlessService = $headlessService;
    }

    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
       $uuid = $args['input']['uuid'] ?? null;
       $consentToken = $args['input']['consentToken'] ?? null;
       $cartId = $args['input']['cartId'] ?? null;

       if(!isset($uuid) || !isset($consentToken) || !isset($cartId))
       {
        throw new GraphQlInputException(__('Required field is missing on input.'));
       }

        $params = [
            "merchantUuid" => $this->boodileApiInterface->getMerchantUuid(),
            "uuid" => $uuid,
            "consentToken" => $consentToken
        ];
        $headers = $this->boodileApiInterface->getAuthHeaders();
        $boodilApiUrl = $this->boodileApiInterface->getApiUrl("payments");

        try {
            $results = $this->boodileApiInterface->callCurl(
                $boodilApiUrl,
                $params,
                'POST',
                $headers,
                true
            );

            $completeStatusCode = [
                'ACSC',
                'ACCC',
                'ACCP',
                'ACSP',
                'ACTC',
                'ACWC',
                'ACWP',
                'ACFC'
            ];

            $pendingStatusCode = [
                'PDNG',
                'RCVD',
                'PART',
                'PATC'
            ];

            if (
                isset($results['result']['statusCode']) &&
                in_array($results['result']['statusCode'], array_merge($completeStatusCode, $pendingStatusCode))
            ) {
                $statusCode = $results['result']['statusCode'];
                $uuid = $results['uuid'];
                $paymentReference = $results['reference'];

                $orderId = $this->headlessService->createOrderFromCart($cartId, $uuid, $paymentReference, $statusCode);

                return [
                    'processingTime' => $results['processingTime'],
                    'reference' => $results['reference'],
                    'amount' => $results['amount'],
                    'currency' => $results['currency'],
                    'orderId' => $orderId,
                    'statusCode' => $statusCode
                ];
            } else 
            {
                throw new Exception('Request to create payment failed, did not result in pending or completed status code.');
            }


        } catch (\Exception $e) {
            $this->logger->debug('API call to Boodil failed');
            throw new GraphQlNoSuchEntityException(__('Payment & order creation failed'));
        }

        return $results;
    }
}