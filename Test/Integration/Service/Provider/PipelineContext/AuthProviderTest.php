<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Service\Provider\PipelineContext;

use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\PlatformPipelines\Service\Provider\PipelineContext\AuthProvider;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\PlatformPipelines\Service\Provider\PipelineContext\AuthProvider::class
 */
class AuthProviderTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     * @throws NoSuchEntityException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        /** @var StoreManager $storeManager */
        $storeManager = $this->objectManager->get(StoreManager::class);

        $storeScopeProvider = $this->getMockBuilder(StoreScopeProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeScopeProvider->method('getCurrentStore')
            ->willReturn($storeManager->getStore('default'));

        $this->objectManager->addSharedInstance(
            instance: $storeScopeProvider,
            className: StoreScopeProviderInterface::class,
            forPreference: true,
        );
    }

    public function testGet(): void
    {
        /** @var AuthProvider $authProvider */
        $authProvider = $this->objectManager->get(AuthProvider::class);

        $this->assertSame(
            $authProvider,
            $authProvider->get(),
        );
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     */
    public function testGetForCurrentStore_NotSet(): void
    {
        ConfigFixture::setGlobal(
            path: 'klevu_configuration/auth_keys/js_api_key',
            value: '',
        );
        ConfigFixture::setForStore(
            path: 'klevu_configuration/auth_keys/js_api_key',
            value: '',
            storeCode: 'default',
        );
        ConfigFixture::setGlobal(
            path: 'klevu_configuration/auth_keys/rest_auth_key',
            value: '',
        );
        ConfigFixture::setForStore(
            path: 'klevu_configuration/auth_keys/rest_auth_key',
            value: '',
            storeCode: 'default',
        );

        /** @var AuthProvider $authProvider */
        $authProvider = $this->objectManager->get(AuthProvider::class);

        $expectedResult = [
            'js_api_key' => '',
            'rest_auth_key' => '',
        ];

        $this->assertSame(
            $expectedResult,
            $authProvider->getForCurrentStore(),
        );
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     */
    public function testGetForCurrentStore_Set(): void
    {
        ConfigFixture::setGlobal(
            path: 'klevu_configuration/auth_keys/js_api_key',
            value: '',
        );
        ConfigFixture::setForStore(
            path: 'klevu_configuration/auth_keys/js_api_key',
            value: 'klevu-1234567890',
            storeCode: 'default',
        );
        ConfigFixture::setGlobal(
            path: 'klevu_configuration/auth_keys/rest_auth_key',
            value: '',
        );
        ConfigFixture::setForStore(
            path: 'klevu_configuration/auth_keys/rest_auth_key',
            value: 'ABCDE1234567890',
            storeCode: 'default',
        );

        /** @var AuthProvider $authProvider */
        $authProvider = $this->objectManager->get(AuthProvider::class);

        $expectedResult = [
            'js_api_key' => 'klevu-1234567890',
            'rest_auth_key' => 'ABCDE1234567890',
        ];

        $this->assertSame(
            $expectedResult,
            $authProvider->getForCurrentStore(),
        );
    }
}
