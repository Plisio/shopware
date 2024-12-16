<?php

namespace PlisioPaymentGateway\Bootstrap;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

/**
 * Class Installer
 */
class Installer {
    /**
     * @var PaymentMethodInstaller
     */
    private $paymentMethodInstaller;

    public function __construct(
        Context $context,
        EntityRepository $paymentRepository,
        PluginIdProvider $pluginIdProvider
    ) {
        $this->paymentMethodInstaller = new PaymentMethodInstaller($context, $paymentRepository, $pluginIdProvider);
    }

    /**
     * @return PaymentMethodInstaller
     */
    public function getPaymentMethodInstaller(): PaymentMethodInstaller {
        return $this->paymentMethodInstaller;
    }
}
