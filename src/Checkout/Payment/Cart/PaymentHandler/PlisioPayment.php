<?php

namespace PlisioPaymentGateway\Checkout\Payment\Cart\PaymentHandler;

use PlisioPaymentGateway\Checkout\Services\PlisioApiService;
use PlisioPaymentGateway\Checkout\Services\OrderIdService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class PlisioPayment
 */
class PlisioPayment implements AsynchronousPaymentHandlerInterface {
    public const CUSTOM_FIELD_MAPPING_NAME = "plisio_order_id";
    public const WEBHOOK_PATH = "storefront.plisio.webhook";

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    /**
     * @var OrderIdService
     */
    private $orderIdService;

    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var PlisioApiService
     */
    private PlisioApiService $plisioApiService;

    /**
     * PlisioPayment constructor.
     * @param OrderTransactionStateHandler $orderTransactionStateHandler
     * @param OrderIdService $orderIdService
     * @param PlisioApiService $plisioApiService
     * @param RouterInterface $router
     */
    public function __construct(
        OrderTransactionStateHandler $orderTransactionStateHandler,
        OrderIdService $orderIdService,
        PlisioApiService $plisioApiService,
        RouterInterface $router
    ) {
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderIdService = $orderIdService;
        $this->plisioApiService = $plisioApiService;
        $this->router = $router;
    }
    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param RequestDataBag $dataBag
     * @param SalesChannelContext $salesChannelContext
     * @return RedirectResponse
     */
    public function pay(
        AsyncPaymentTransactionStruct $transaction,
        RequestDataBag $dataBag,
        SalesChannelContext $salesChannelContext
    ): RedirectResponse {
        try {
            $client = $this->plisioApiService->getApiClient($salesChannelContext);

            /** @var CustomerEntity $customer */
            $customer = $transaction->getOrder()->getOrderCustomer();

            $params = [
                'order_number' => $transaction->getOrder()->getOrderNumber(),
                'order_name' => 'Order #' . $transaction->getOrder()->getOrderNumber(),
                'source_amount' => $transaction->getOrderTransaction()->getAmount()->getTotalPrice(),
                'source_currency' => $transaction->getOrder()->getCurrency()->getIsoCode(),
                'callback_url' => $this->router->generate(self::WEBHOOK_PATH, [], UrlGeneratorInterface::ABSOLUTE_URL),
                'cancel_url' => $transaction->getReturnUrl() . "&cancel=1",
                'success_url' => $transaction->getReturnUrl(),
                'email' => $customer->getEmail(),
                'plugin' => 'Shopware',
                'version' => '1.0.0'
            ];

            $order = $client->createTransaction($params);

            $this->orderIdService->setOrderId(
                $transaction->getOrderTransaction()->getId(),
                $transaction->getOrder()->getOrderNumber(),
                self::CUSTOM_FIELD_MAPPING_NAME,
                $salesChannelContext->getContext()
            );
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                implode(',', json_decode($order['data']['message'], true))
            );
        }

        return new RedirectResponse($order['data']['invoice_url']);
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request $request,
        SalesChannelContext $salesChannelContext
    ): void {

        /** @var string $transactionId */
        $transactionId = $transaction->getOrderTransaction()->getId();

        if ($request->get("cancel", false)) {
            throw PaymentException::asyncFinalizeInterrupted($transactionId, 'Customer canceled the payment');
        }
    }
}
