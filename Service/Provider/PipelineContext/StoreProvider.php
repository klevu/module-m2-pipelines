<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Service\Provider\PipelineContext;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\PlatformPipelines\Api\PipelineContextProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

class StoreProvider implements PipelineContextProviderInterface
{
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;
    /**
     * @var mixed[]
     */
    private array $contextForStoreId = [];

    /**
     * @param ScopeProviderInterface $scopeProvider
     */
    public function __construct(
        ScopeProviderInterface $scopeProvider,
    ) {
        $this->scopeProvider = $scopeProvider;
    }

    /**
     * @return $this
     */
    public function get(): self
    {
        return $this;
    }

    /**
     * @return mixed[]|null
     */
    public function getForCurrentStore(): array|null
    {
        $currentScope = $this->scopeProvider->getCurrentScope();
        if (
            ScopeInterface::SCOPE_STORES !== $currentScope->getScopeType()
            || !$currentScope->getScopeId()
        ) {
            return null;
        }

        $storeId = $currentScope->getScopeId();
        /** @var Store $store */
        $store = $currentScope->getScopeObject();
        if (!($store instanceof StoreInterface)) {
            return null;
        }
        if (!array_key_exists($storeId, $this->contextForStoreId)) {
            $this->contextForStoreId[$storeId] = [
                'base_currency_code' => $store->getBaseCurrencyCode(),
                'base_url' => $store->getBaseUrl(
                    type: UrlInterface::URL_TYPE_LINK,
                    secure: $store->isFrontUrlSecure(),
                ),
                'media_url' => $store->getBaseUrl(
                    type: UrlInterface::URL_TYPE_MEDIA,
                    secure: $store->isFrontUrlSecure(),
                ),
                'store_id' => (int)$store->getId(),
            ];
        }

        return $this->contextForStoreId[$storeId];
    }
}
