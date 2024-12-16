<?php declare(strict_types=1);

namespace PlisioPaymentGateway;

use PlisioPaymentGateway\Bootstrap\Installer;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

class PlisioPaymentGateway extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        $paymentMethodInstaller = $this->getInstaller($installContext)->getPaymentMethodInstaller();
        if (!$paymentMethodInstaller->hasPlisioPaymentMethod()) {
            $paymentMethodInstaller->createPlisioPaymentMethod();
        }
    }

    public function activate(ActivateContext $activateContext): void
    {
        $paymentMethodInstaller = $this->getInstaller($activateContext)->getPaymentMethodInstaller();
        $paymentMethodInstaller->setPlisioMethodActiveState(true);
    }

    public function deactivate(DeactivateContext $deactivateContext): void
    {
        $paymentMethodInstaller = $this->getInstaller($deactivateContext)->getPaymentMethodInstaller();
        $paymentMethodInstaller->setPlisioMethodActiveState(false);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        $paymentMethodInstaller = $this->getInstaller($uninstallContext)->getPaymentMethodInstaller();
        $paymentMethodInstaller->setPlisioMethodActiveState(false);
    }

    private function getInstaller(InstallContext $installContext): Installer
    {
        return new Installer(
            $installContext->getContext(),
            $this->container->get('payment_method.repository'),
            $this->container->get(PluginIdProvider::class)
        );
    }
}
