<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\ObjectManager;

use Klevu\AnalyticsOrderSync\Pipeline\OrderAnalytics\Stage\MarkOrderAsProcessed;
use Klevu\AnalyticsOrderSync\Pipeline\OrderAnalytics\Stage\MarkOrderAsProcessing;
use Klevu\Pipelines\ObjectManager\PipelineFqcnProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\PlatformPipelines\ObjectManager\PipelineFqcnProvider::class
 * @method PipelineFqcnProviderInterface instantiateTestObject(?array $arguments = null)
 */
class PipelineFqcnProviderTest extends TestCase
{
    use ObjectInstantiationTrait {
        getExpectedFqcns as trait_getExpectedFqcns;
    }
    use TestImplementsInterfaceTrait;

    private const PROVIDER_VIRTUAL_TYPE = 'Klevu\PlatformPipelines\ObjectManager\PipelineFqcnProvider';

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; //@phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = ObjectManager::getInstance();

        // VirtualType
        $this->implementationFqcn = self::PROVIDER_VIRTUAL_TYPE;
        $this->interfaceFqcn = PipelineFqcnProviderInterface::class;
    }

    /**
     * @testWith ["OrderAnalytics\\Stage\\Foo"]
     *           ["MarkOrderAsProcessed"]
     */
    public function testGetFqcn_ClassNotExists(
        string $alias,
    ): void {
        $pipelineFqcnProvider = $this->instantiateTestObject();

        $result = $pipelineFqcnProvider->getFqcn($alias);
        $this->assertNull($result);
    }

    public function testGetFqcn(): void
    {
        $pipelineFqcnProvider = $this->instantiateTestObject();

        $this->assertSame(
            expected: ltrim(
                MarkOrderAsProcessed::class, // @phpstan-ignore-line virtualType
                '\\',
            ),
            actual: ltrim(
                $pipelineFqcnProvider->getFqcn('OrderAnalytics\Stage\MarkOrderAsProcessed'),
                '\\',
            ),
        );
        $this->assertSame(
            expected: ltrim(
                MarkOrderAsProcessing::class, // @phpstan-ignore-line virtualType
                '\\',
            ),
            actual: ltrim(
                $pipelineFqcnProvider->getFqcn('OrderAnalytics\Stage\MarkOrderAsProcessing'),
                '\\',
            ),
        );
    }
}
