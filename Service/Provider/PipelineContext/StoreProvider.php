<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Service\Provider\PipelineContext;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Customer\Service\Provider\GroupProviderInterface as CustomerGroupProviderInterface;
use Klevu\PlatformPipelines\Api\PipelineContextProviderInterface;
use Magento\Customer\Model\Group;
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
     * @var CustomerGroupProviderInterface
     */
    private readonly CustomerGroupProviderInterface $customerGroupProvider;
    /**
     * @var mixed[]
     */
    private array $contextForStoreId = [];

    /**
     * @param ScopeProviderInterface $scopeProvider
     * @param CustomerGroupProviderInterface $customerGroupProvider
     */
    public function __construct(
        ScopeProviderInterface $scopeProvider,
        CustomerGroupProviderInterface $customerGroupProvider,
    ) {
        $this->scopeProvider = $scopeProvider;
        $this->customerGroupProvider = $customerGroupProvider;
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
                'customer_groups' => $this->getCustomerGroupIds($store),
            ];
        }

        return $this->contextForStoreId[$storeId];
    }

    /**
     * @param StoreInterface $store
     *
     * @return int[]
     */
    private function getCustomerGroupIds(StoreInterface $store): array
    {
        return array_filter(
            array: array_keys(array: $this->customerGroupProvider->get(store: $store)),
            callback: static fn (int $groupId): bool => $groupId !== Group::NOT_LOGGED_IN_ID,
        );
    }
}
