<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Pipeline\Stage;

use Klevu\Pipelines\Exception\Pipeline\InvalidPipelinePayloadException;
use Klevu\Pipelines\Pipeline\PipelineInterface;
use Klevu\PlatformPipelines\Pipeline\Stage\SetCustomerGroupId;
use Klevu\TestFixtures\Catalog\ProductTrait;
use Klevu\TestFixtures\Customer\CustomerGroupTrait;
use Klevu\TestFixtures\Customer\Group\CustomerGroupFixturePool;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Customer\Model\Group;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Catalog\ProductFixturePool;

/**
 * @covers \Klevu\PlatformPipelines\Pipeline\Stage\SetCustomerGroupId::class
 * @method PipelineInterface instantiateTestObject(?array $arguments = null)
 * @method PipelineInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class SetCustomerGroupIdTransformerTest extends TestCase
{
    use CustomerGroupTrait;
    use ObjectInstantiationTrait;
    use ProductTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = SetCustomerGroupId::class;
        $this->interfaceFqcn = PipelineInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();

        $this->productFixturePool = $this->objectManager->get(ProductFixturePool::class);
        $this->customerGroupFixturePool = $this->objectManager->get(CustomerGroupFixturePool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->productFixturePool->rollback();
        $this->customerGroupFixturePool->rollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @dataProvider testTransform_ThrowsException_WhenCustomerGroupIdIsInvalid_dataProvider
     */
    public function testExecute_ThrowsException_WhenCustomerGroupIdIsInvalid(mixed $invalidCustomerGroupId): void
    {
        $this->expectException(InvalidPipelinePayloadException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Payload must be numeric (integer only); Received %s',
                is_scalar($invalidCustomerGroupId)
                    ? $invalidCustomerGroupId
                    : get_debug_type($invalidCustomerGroupId),
            ),
        );

        $stage = $this->instantiateTestObject();
        $stage->execute(
            payload: $invalidCustomerGroupId,
        );
    }

    /**
     * @return mixed[]
     */
    public function testTransform_ThrowsException_WhenCustomerGroupIdIsInvalid_dataProvider(): array
    {
        return [
            [true],
            [false],
            [null],
            ['string'],
            ['1.3'],
            [1.23],
            [[1]],
            [new DataObject()],
        ];
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testExecute_ThrowsException_WhenCustomerGroupIdDoesNotExist(): void
    {
        $invalidCustomerGroupId = 9999999999999;

        $this->expectException(InvalidPipelinePayloadException::class);
        $this->expectExceptionMessage(
            sprintf(
                'No customer group exists for payload "%s"',
                $invalidCustomerGroupId,
            ),
        );

        $stage = $this->instantiateTestObject();
        $stage->execute(
            payload: $invalidCustomerGroupId,
        );
    }

    /**
     * @magentoDbIsolation disabled
     */
    public function testExecute_SetsCustomerGroupIdInSession(): void
    {
        $customerSession = $this->objectManager->get(CustomerSession::class);
        $this->assertSame(
            expected: Group::NOT_LOGGED_IN_ID,
            actual: $customerSession->getCustomerGroupId(),
        );
        $this->createCustomerGroup();
        $customerGroupFixture = $this->customerGroupFixturePool->get('test_customer_group');

        $stage = $this->instantiateTestObject();
        $stage->execute(
            payload: $customerGroupFixture->getId(),
        );

        $this->assertSame(
            expected: $customerGroupFixture->getId(),
            actual: $customerSession->getCustomerGroupId(),
        );
    }
}
