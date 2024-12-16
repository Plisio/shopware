<?php

namespace PlisioPaymentGateway\Checkout\Services;

use PlisioPaymentGateway\Lib\PlisioClient;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PlisioApiService {
    /**
     * @var SystemConfigService
     */
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService) {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return PlisioClient
     * @throws \Exception
     */
    public function getApiClient(SalesChannelContext $salesChannelContext): PlisioClient {
        $apiKey = $this->systemConfigService->get("PlisioPaymentGateway.config." . "ApiKey", $salesChannelContext->getSalesChannelId());

        if (empty($apiKey)) {
            throw new \Exception("Plisio API key is missing, make sure you set it correctly");
        }

        return new PlisioClient($apiKey);
    }
}