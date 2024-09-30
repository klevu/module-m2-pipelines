<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Service;

use Klevu\PlatformPipelines\Api\ConfigurationOverridesBuilderInterface;
use Klevu\PlatformPipelines\Service\ConfigurationOverridesBuilder;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\PlatformPipelines\Service\ConfigurationOverridesBuilder::class
 * @method ConfigurationOverridesBuilderInterface instantiateTestObject(?array $arguments = null)
 * @method ConfigurationOverridesBuilderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class ConfigurationOverridesBuilderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;

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

        $this->implementationFqcn = ConfigurationOverridesBuilder::class;
        $this->interfaceFqcn = ConfigurationOverridesBuilderInterface::class;
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testBuild(): array
    {
        return [
            [
                [
                    'foo' => ['a' => 'b'],
                    'foo.bar' => ['baz'],
                    'wom' => ['bat'],
                    'foo.3' => ['14' => '42'],
                ],
                [
                    'foo' => [
                        'a' => 'b',
                        'bar' => [
                            'baz',
                        ],
                        '3' => [
                            '14' => '42',
                        ],
                    ],
                    'wom' => [
                        'bat',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_testBuild
     *
     * @param array<string, mixed[]> $configurationByPath
     * @param mixed[] $expectedResult
     *
     * @return void
     */
    public function testBuild(
        array $configurationByPath,
        array $expectedResult,
    ): void {
        $configurationOverridesBuilder = $this->instantiateTestObject();

        foreach ($configurationByPath as $path => $configuration) {
            $configurationOverridesBuilder->addConfigurationByPath(
                path: $path,
                configuration: $configuration,
            );
        }

        $result1 = $configurationOverridesBuilder->build();
        $this->assertSame(
            expected: $expectedResult,
            actual: $result1,
        );

        $configurationOverridesBuilder->clear();

        foreach ($configurationByPath as $path => $configuration) {
            $configurationOverridesBuilder->addConfigurationByPath(
                path: $path,
                configuration: $configuration,
            );
        }

        $result2 = $configurationOverridesBuilder->build();
        $this->assertSame(
            expected: $expectedResult,
            actual: $result2,
        );
    }
}
