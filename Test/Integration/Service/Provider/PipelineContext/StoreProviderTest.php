<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Service\Provider\PipelineContext;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\PlatformPipelines\Api\PipelineContextProviderInterface;
use Klevu\PlatformPipelines\Service\Provider\PipelineContext\StoreProvider;
use Klevu\TestFixtures\Customer\CustomerGroupTrait;
use Klevu\TestFixtures\Customer\Group\CustomerGroupFixturePool;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Customer\Model\Group;
use Magento\Directory\Model\Currency;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers StoreProvider
 * @method StoreProvider instantiateTestObject(?array $arguments = null)
 * @method StoreProvider instantiateTestObjectFromInterface(?array $arguments = null)
 */
class StoreProviderTest extends TestCase
{
    use CustomerGroupTrait;
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use WebsiteTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
        $this->implementationFqcn = StoreProvider::class;
        $this->interfaceFqcn = PipelineContextProviderInterface::class;

        $this->websiteFixturesPool = $this->objectManager->get(WebsiteFixturesPool::class);
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
        $this->customerGroupFixturePool = $this->objectManager->get(CustomerGroupFixturePool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->customerGroupFixturePool->rollback();
        $this->storeFixturesPool->rollback();
        $this->websiteFixturesPool->rollback();
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testGetForCurrentStore(): void
    {
        $this->createStore([
            'key' => 'test_store_1',
            'code' => 'klevu_test_store_1',
        ]);
        $storeFixture1 = $this->storeFixturesPool->get('test_store_1');

        $this->createWebsite();
        $websiteFixture = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'key' => 'test_store_2',
            'code' => 'klevu_test_store_2',
            'website_id' => $websiteFixture->getId(),
        ]);
        $storeFixture2 = $this->storeFixturesPool->get('test_store_2');

        $this->createCustomerGroup([
            'excluded_website_ids' => [
                $websiteFixture->getId(),
            ],
        ]);
        $customerGroupFixture = $this->customerGroupFixturePool->get('test_customer_group');

        ConfigFixture::setGlobal(
            path: Store::XML_PATH_PRICE_SCOPE,
            value: Store::PRICE_SCOPE_WEBSITE,
        );
        ConfigFixture::setGlobal(
            path: Currency::XML_PATH_CURRENCY_ALLOW,
            value: 'EUR,GBP,USD',
        );
        ConfigFixture::setForStore(
            path: Currency::XML_PATH_CURRENCY_BASE,
            value: 'GBP',
            storeCode: $storeFixture1->getCode(),
        );
        ConfigFixture::setForStore(
            path: Currency::XML_PATH_CURRENCY_BASE,
            value: 'EUR',
            storeCode: $storeFixture2->getCode(),
        );

        ConfigFixture::setGlobal(
            path: 'web/unsecure/base_url',
            value: 'http://magento.test/',
        );
        ConfigFixture::setForStore(
            path: 'web/unsecure/base_url',
            value: 'http://store1.test/',
            storeCode: $storeFixture1->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'web/unsecure/base_url',
            value: 'http://store2.test/',
            storeCode: $storeFixture2->getCode(),
        );
        ConfigFixture::setGlobal(
            path: 'web/secure/base_url',
            value: 'https://magento.test/',
        );
        ConfigFixture::setForStore(
            path: 'web/secure/base_url',
            value: 'https://store1.test/',
            storeCode: $storeFixture1->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'web/secure/base_url',
            value: 'https://store2.test/',
            storeCode: $storeFixture2->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'web/secure/base_link_url',
            value: 'https://store1.test/',
            storeCode: $storeFixture1->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'web/secure/base_link_url',
            value: 'https://store2.test/',
            storeCode: $storeFixture2->getCode(),
        );
        ConfigFixture::setGlobal(
            path: 'web/secure/use_in_frontend',
            value: 1,
        );
        ConfigFixture::setGlobal(
            path: 'web/seo/use_rewrites',
            value: 0,
        );
        ConfigFixture::setForStore(
            path: 'web/seo/use_rewrites',
            value: 0,
            storeCode: $storeFixture1->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'web/seo/use_rewrites',
            value: 1,
            storeCode: $storeFixture2->getCode(),
        );

        // STORE 2
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScopeByCode(
            scopeCode: $storeFixture2->getCode(),
            scopeType: ScopeInterface::SCOPE_STORES,
        );
        $provider = $this->instantiateTestObject();
        $result2 = $provider->getForCurrentStore();

        $this->assertIsArray(actual: $result2);

        $this->assertArrayHasKey(key: 'store_id', array: $result2);
        $this->assertSame(expected: $storeFixture2->getId(), actual: $result2['store_id']);

        $this->assertArrayHasKey(key: 'base_currency_code', array: $result2);
        $this->assertSame(expected: 'EUR', actual: $result2['base_currency_code']);

        $this->assertArrayHasKey(key: 'base_url', array: $result2);
        $this->assertSame(expected: 'https://store2.test/', actual: $result2['base_url']);

        $this->assertArrayHasKey(key: 'media_url', array: $result2);
        $this->assertSame(expected: 'https://store2.test/media/', actual: $result2['media_url']);

        $this->assertArrayHasKey(key: 'customer_groups', array: $result2);
        $this->assertIsArray(actual: $result2['customer_groups']);
        $this->assertNotContains(needle: $customerGroupFixture->getId(), haystack: $result2['customer_groups']);
        $this->assertNotContains(needle: Group::NOT_LOGGED_IN_ID, haystack: $result2['customer_groups']);

        // STORE 1
        $scopeProvider->setCurrentScopeByCode(
            scopeCode: $storeFixture1->getCode(),
            scopeType: ScopeInterface::SCOPE_STORES,
        );
        $provider = $this->instantiateTestObject();
        $result1 = $provider->getForCurrentStore();

        $this->assertIsArray(actual: $result1);

        $this->assertArrayHasKey(key: 'store_id', array: $result1);
        $this->assertSame(expected: $storeFixture1->getId(), actual: $result1['store_id']);

        $this->assertArrayHasKey(key: 'base_currency_code', array: $result1);
        $this->assertSame(expected: 'GBP', actual: $result1['base_currency_code']);

        $this->assertArrayHasKey(key: 'base_url', array: $result1);
        $this->assertSame(expected: 'https://store1.test/index.php/', actual: $result1['base_url']);

        $this->assertArrayHasKey(key: 'media_url', array: $result1);
        $this->assertSame(expected: 'https://store1.test/media/', actual: $result1['media_url']);

        $this->assertArrayHasKey(key: 'customer_groups', array: $result1);
        $this->assertIsArray(actual: $result1['customer_groups']);
        $this->assertContains(needle: $customerGroupFixture->getId(), haystack: $result1['customer_groups']);
        $this->assertNotContains(needle: Group::NOT_LOGGED_IN_ID, haystack: $result1['customer_groups']);
    }
}
