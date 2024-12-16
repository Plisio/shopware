<?php declare(strict_types=1);

namespace PlisioPaymentGateway\Checkout\Controller;

use PlisioPaymentGateway\Checkout\Payment\Cart\PaymentHandler\PlisioPayment;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class PlisioWebhookController extends StorefrontController {
    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    /**
     * @var EntityRepository
     */
    private $orderTransactionRepository;

    /**
     * @var SystemConfigService
     */
    private SystemConfigService $systemConfigService;

    /**
     * PlisioWebhookController constructor.
     * @param OrderTransactionStateHandler $orderTransactionStateHandler
     * @param EntityRepository $orderTransactionRepository
     */
    public function __construct(
        OrderTransactionStateHandler $orderTransactionStateHandler,
        EntityRepository $orderTransactionRepository,
        SystemConfigService $systemConfigService
    ) {
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param RequestDataBag $dataBag
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return Response
     */
    #[Route(path:'/plisio/webhook', name: 'storefront.plisio.webhook', methods: ['GET','POST'])]
    public function index(RequestDataBag $dataBag, Request $request, SalesChannelContext $salesChannelContext): Response {
        $apiKey = $this->systemConfigService->get("PlisioPaymentGateway.config." . "ApiKey", $salesChannelContext->getSalesChannelId());
        try {
            if (!$this->verifyCallbackData($_POST, $apiKey)) {
                return new JsonResponse(["success" => false, "message" => 'Plisio response looks compromised. Skip status update'], 500);
            }
            $plisioOrderId = $request->get("order_number", null);
            $paymentStatus = $request->get("status");

            if (is_null($plisioOrderId)) {
                throw new \Exception("Plisio Order ID missing");
            }

            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter("customFields." . PlisioPayment::CUSTOM_FIELD_MAPPING_NAME, $plisioOrderId));

            $orderTransactionCollection = $this->orderTransactionRepository->search($criteria, $salesChannelContext->getContext());
            if ($orderTransactionCollection->count() === 0) {
                throw new \Exception("No Order transaction found for Plisio order " . $plisioOrderId);
            }

            /** @var OrderTransactionEntity $orderTransaction */
            $orderTransaction = $orderTransactionCollection->first();

            switch ($paymentStatus) {
                case "completed":
                case "mismatch":
                    $this->orderTransactionStateHandler->paid($orderTransaction->getId(), $salesChannelContext->getContext());
                    break;

                case "cancelled":
                case "error":
                case "expired":
                    $this->orderTransactionStateHandler->cancel($orderTransaction->getId(), $salesChannelContext->getContext());
                    break;
            }
        } catch (\Exception $ex) {
            return new JsonResponse(["success" => false], 500);
        }

        return new JsonResponse(["success" => true], 200);
    }

    /**
     * @param $post
     * @param string $apiKey
     * @return bool
     */
    private function verifyCallbackData($post, string $apiKey): bool
    {
        if (!isset($post['verify_hash'])) {
            return false;
        }

        $verifyHash = $post['verify_hash'];
        unset($post['verify_hash']);
        ksort($post);
        if (isset($post['expire_utc'])){
            $post['expire_utc'] = (string)$post['expire_utc'];
        }
        if (isset($post['tx_urls'])){
            $post['tx_urls'] = html_entity_decode($post['tx_urls']);
        }
        $postString = serialize($post);
        $checkKey = hash_hmac('sha1', $postString, $apiKey);
        if ($checkKey != $verifyHash) {
            return false;
        }

        return true;
    }

}
