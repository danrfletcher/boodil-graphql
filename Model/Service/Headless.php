<?php

namespace Boodil\Payment\Model\Service;

use Boodil\Payment\Logger\Logger;

class Headless
{
    protected $logger;
    protected $cartManagement;
    protected $cartRepository;
    protected $quoteManagement;

    public function __construct(
        Logger $logger,
        \Magento\Quote\Api\GuestCartRepositoryInterface $cartRepository,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Quote\Model\QuoteManagement $quoteManagement
    ) {
        $this->logger = $logger;
        $this->cartRepository = $cartRepository;
        $this->cartManagement = $cartManagement;
        $this->quoteManagement = $quoteManagement;
    }

    public function createOrderFromCart($cartId, $uuid, $paymentReference, $paymentStatusCode)
    {
        $this->logger->info("Attempting to create order from cart: {$cartId}");

        try {
            $quote = $this->cartRepository->get($cartId);
            $this->logger->info("Loaded quote with ID {$quote->getId()} for cart {$cartId}");

            $paymentMethod = [
                'method' => 'boodil',
                'additional_data' => [
                    'uuid' => $uuid,
                    'paymentReference' => $paymentReference,
                    'paymentStatusCode' => $paymentStatusCode
                ]
            ];

            $quote->getPayment()->importData($paymentMethod);
            $this->logger->info("Payment method set with UUID {$uuid}");

            $quote->collectTotals()->save();
            $this->logger->info("Quote totals collected and saved for quote ID {$quote->getId()}");

            $orderId = $this->quoteManagement->placeOrder($quote->getId());

            if ($orderId) {
                $this->logger->info("Order placed successfully with ID {$orderId}");
                return $orderId;
            } else {
                $this->logger->error("Failed to place the order for cart {$cartId}");
                return "Failed to place the order.";
            }
        } catch (\Exception $e) {
            $this->logger->error("Error occurred when creating order: " . $e->getMessage());
            throw new \Exception(__('Error occurred when creating order: ' . $e->getMessage()));
        }
    }
}
