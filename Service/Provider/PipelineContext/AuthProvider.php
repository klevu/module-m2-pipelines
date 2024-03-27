<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Service\Provider\PipelineContext;

use Klevu\Configuration\Service\Provider\ApiKeyProviderInterface;
use Klevu\Configuration\Service\Provider\AuthKeyProviderInterface;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\PlatformPipelines\Api\PipelineContextProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;

class AuthProvider implements PipelineContextProviderInterface
{
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;
    /**
     * @var ApiKeyProviderInterface
     */
    private readonly ApiKeyProviderInterface $apiKeyProvider;
    /**
     * @var AuthKeyProviderInterface
     */
    private readonly AuthKeyProviderInterface $authKeyProvider;
    /**
     * @var mixed[][]
     */
    private array $contextForStoreId = [];

    /**
     * @param ScopeProviderInterface $scopeProvider
     * @param ApiKeyProviderInterface $apiKeyProvider
     * @param AuthKeyProviderInterface $authKeyProvider
     */
    public function __construct(
        ScopeProviderInterface $scopeProvider,
        ApiKeyProviderInterface $apiKeyProvider,
        AuthKeyProviderInterface $authKeyProvider,
    ) {
        $this->scopeProvider = $scopeProvider;
        $this->apiKeyProvider = $apiKeyProvider;
        $this->authKeyProvider = $authKeyProvider;
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
     * @throws NoSuchEntityException
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
        if (!array_key_exists($storeId, $this->contextForStoreId)) {
            $this->contextForStoreId[$storeId] = [
                'js_api_key' => $this->apiKeyProvider->get($currentScope),
                'rest_auth_key' => $this->authKeyProvider->get($currentScope),
            ];
        }

        return $this->contextForStoreId[$storeId];
    }
}
