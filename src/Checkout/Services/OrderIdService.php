<?php

namespace PlisioPaymentGateway\Checkout\Services;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * Class OrderIdService
 */
class OrderIdService {
    /**
     * @var EntityRepository
     */
    private $orderTransactionRepository;

    /**
     * OrderIdService constructor.
     * @param EntityRepository $orderTransactionRepository
     */
    public function __construct(
        EntityRepository $orderTransactionRepository
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    /**
     * @param string $orderTransactionId
     * @param string $paymentProviderOrderId
     * @param string $customFieldName
     * @param Context $context
     */
    public function setOrderId(
        string $orderTransactionId,
        string $paymentProviderOrderId,
        string $customFieldName,
        Context $context
    ): void {
        $data = [
            'id' => $orderTransactionId,
            'customFields' => [
                $customFieldName => $paymentProviderOrderId,
            ],
        ];
        $this->orderTransactionRepository->update([$data], $context);
    }
}
