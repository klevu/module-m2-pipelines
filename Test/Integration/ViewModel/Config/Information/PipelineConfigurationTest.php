<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\ViewModel\Config\Information;

use Klevu\Configuration\ViewModel\Config\FieldsetInterface;
use Klevu\PlatformPipelines\Api\PipelineConfigurationOverridesFilepathsProviderInterface;
use Klevu\PlatformPipelines\Service\Provider\PipelineConfigurationProvider;
use Klevu\PlatformPipelines\ViewModel\Config\Information\PipelineConfiguration;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\PlatformPipelines\ViewModel\Config\Information\PipelineConfiguration::class
 * @method PipelineConfiguration instantiateTestObject(?array $arguments = null)
 */
class PipelineConfigurationTest extends TestCase
{
    use TestImplementsInterfaceTrait;
    use ObjectInstantiationTrait;

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

        $this->implementationFqcn = PipelineConfiguration::class;
        $this->interfaceFqcn = FieldsetInterface::class;

        $this->constructorArgumentDefaults = [
            'pipelineIdentifier' => '',
        ];
    }

    public function testGetChildBlocks(): void
    {
        $viewModel = $this->instantiateTestObject([
            'pipelineIdentifier' => '',
        ]);

        $this->assertSame(
            expected: [],
            actual: $viewModel->getChildBlocks(),
        );
    }

    public function testGetMessages(): void
    {
        $viewModel = $this->instantiateTestObject([
            'pipelineIdentifier' => '',
        ]);

        $this->assertSame(
            expected: [],
            actual: $viewModel->getMessages(),
        );
    }

    public function testGetStyles(): void
    {
        $viewModel = $this->instantiateTestObject([
            'pipelineIdentifier' => '',
        ]);

        $this->assertSame(
            expected: '',
            actual: $viewModel->getStyles(),
        );
    }

    // phpcs:disable SlevomatCodingStandard.Whitespaces.DuplicateSpaces.DuplicateSpaces
    /**
     * @testWith ["foo"]
     *           ["   "]
     *
     * @param string $pipelineIdentifier
     *
     * @return void
     */
    // phpcs:enable SlevomatCodingStandard.Whitespaces.DuplicateSpaces.DuplicateSpaces
    public function testGetPipelineIdentifier(
        string $pipelineIdentifier,
    ): void {
        $viewModel = $this->instantiateTestObject([
            'pipelineIdentifier' => $pipelineIdentifier,
        ]);

        $this->assertSame(
            expected: $pipelineIdentifier,
            actual: $viewModel->getPipelineIdentifier(),
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testGetPipelineConfigurationOverridesFilepaths(): array
    {
        return [
            [
                'foo',
                [
                    '/foo/bar/baz.yml',
                ],
            ],
            [
                'bar',
                [],
            ],
            [
                'baz',
                [
                    'a.yml',
                    'b.yml',
                    'c.yml',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_testGetPipelineConfigurationOverridesFilepaths
     *
     * @param string $pipelineIdentifier
     * @param string[] $expectedResult
     *
     * @return void
     */
    public function testGetPipelineConfigurationOverridesFilepaths(
        string $pipelineIdentifier,
        array $expectedResult,
    ): void
    {
        $pipelineConfigurationProvider = $this->objectManager->create(
            type: PipelineConfigurationProvider::class,
            arguments: [
                'pipelineConfigurationOverridesFilepathsProviders' => [
                    'foo' => $this->getMockPipelineConfigurationOverridesFilepathsProvider([
                        '/foo/bar/baz.yml',
                    ]),
                    'baz' => $this->getMockPipelineConfigurationOverridesFilepathsProvider([
                        'a.yml',
                        'b.yml',
                        'c.yml',
                    ]),
                ],
            ],
        );

        $viewModel = $this->instantiateTestObject([
            'pipelineConfigurationProvider' => $pipelineConfigurationProvider,
            'pipelineIdentifier' => $pipelineIdentifier,
        ]);

        $this->assertSame(
            expected: $expectedResult,
            actual: $viewModel->getPipelineConfigurationOverridesFilepaths(),
        );
    }

    /**
     * @param string[] $filepaths
     *
     * @return MockObject&PipelineConfigurationOverridesFilepathsProviderInterface
     */
    private function getMockPipelineConfigurationOverridesFilepathsProvider(
        array $filepaths,
    ): MockObject {
        $mockPipelineConfigurationOverridesFilepathsProvider = $this->getMockBuilder(
                PipelineConfigurationOverridesFilepathsProviderInterface::class,
            )->disableOriginalConstructor()
            ->getMock();

        $mockPipelineConfigurationOverridesFilepathsProvider->method('get')
            ->willReturn($filepaths);

        return $mockPipelineConfigurationOverridesFilepathsProvider;
    }
}
