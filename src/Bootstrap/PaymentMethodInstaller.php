<?php

namespace PlisioPaymentGateway\Bootstrap;

use PlisioPaymentGateway\Checkout\Payment\Cart\PaymentHandler\PlisioPayment;
use PlisioPaymentGateway\PlisioPaymentGateway;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

class PaymentMethodInstaller {
    /**
     * @var EntityRepository
     */
    private $paymentRepository;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var PluginIdProvider
     */
    private $pluginIdProvider;

    /**
     * PaymentMethodInstaller constructor.
     * @param Context $context
     * @param EntityRepository $paymentRepository
     * @param PluginIdProvider $pluginIdProvider
     */
    public function __construct(
        Context $context,
        EntityRepository $paymentRepository,
        PluginIdProvider $pluginIdProvider
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->context = $context;
        $this->pluginIdProvider = $pluginIdProvider;
    }

    public function createPlisioPaymentMethod() {

        /** @var PluginIdProvider $pluginIdProvider */
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(PlisioPaymentGateway::class, $this->context);

        $paymentData = [
            'handlerIdentifier' => PlisioPayment::class,
            'name' => 'Plisio Payment',
            'description' => 'Plisio cryptocurrency payment gateway for Shopware',
            'translations' => [
                'en-GB' => [
                    'name' => 'Pay with Plisio',
                    'description' => 'Start accepting cryptocurrencies with Plisio.',
                ],
            ],
            'pluginId' => $pluginId,
        ];

        $this->paymentRepository->create([$paymentData], $this->context);
    }

    /**
     * @param bool $active
     */
    public function setPlisioMethodActiveState(bool $active) {
        /** @var string|null $paymentMethodId */
        $paymentMethodId = $this->getPlisioPaymentMethodId();

        if (!$paymentMethodId) {
            return;
        }

        $paymentData = [
            'id' => $paymentMethodId,
            'active' => $active,
        ];

        $this->paymentRepository->update([$paymentData], $this->context);
    }

    /**
     * @return bool
     */
    public function hasPlisioPaymentMethod() {
        return !is_null($this->getPlisioPaymentMethodId());
    }

    /**
     * @return string|null
     */
    public function getPlisioPaymentMethodId() {
        $paymentCriteria = (new Criteria())->addFilter(new EqualsFilter('handlerIdentifier', PlisioPayment::class));
        return $this->paymentRepository->searchIds($paymentCriteria, $this->context)->firstId();
    }
}
