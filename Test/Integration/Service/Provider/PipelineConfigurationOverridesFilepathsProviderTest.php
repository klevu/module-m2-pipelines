<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Service\Provider;

use Klevu\PlatformPipelines\Api\PipelineConfigurationOverridesFilepathsProviderInterface;
use Klevu\PlatformPipelines\Service\Action\ParseFilepathActionInterface;
use Klevu\PlatformPipelines\Service\Provider\GeneratedConfigurationOverridesFilepathProviderInterface;
use Klevu\PlatformPipelines\Service\Provider\PipelineConfigurationOverridesFilepathsProvider;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

// phpcs:disable Generic.Files.LineLength.TooLong
/**
 * @covers \Klevu\PlatformPipelines\Service\Provider\PipelineConfigurationOverridesFilepathsProvider::class
 * @method PipelineConfigurationOverridesFilepathsProviderInterface instantiateTestObject(?array $arguments = null)
 * @method PipelineConfigurationOverridesFilepathsProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
// phpcs:enable Generic.Files.LineLength.TooLong
class PipelineConfigurationOverridesFilepathsProviderTest extends TestCase
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

        $this->implementationFqcn = PipelineConfigurationOverridesFilepathsProvider::class;
        $this->interfaceFqcn = PipelineConfigurationOverridesFilepathsProviderInterface::class;
    }

    public function testGet_ReturnsEmpty_WhenNoFilepathsDefined(): void
    {
        $provider = $this->instantiateTestObject([
            'generatedConfigurationOverridesFilepathProvider' => $this->getMockGeneratedConfigurationOverridesFilepathProvider( // phpcs:ignore Generic.Files.LineLength.TooLong
                return: null,
            ),
        ]);

        $this->assertSame(
            expected: [],
            actual: $provider->get(),
        );
    }

    public function testGet_ReturnsParsedFilepaths(): void
    {
        $provider = $this->instantiateTestObject([
            'parseFilepathAction' => $this->getMockParseFilepathAction([
                ['foo/bar.yml', '/var/www/html/var/foo/bar.yml'],
                ['', ''],
                ['/foo/bar.yml', '/foo/bar.yml'],
                ['Klevu_Foo::bar.yml', '/var/www/html/vendor/klevu/foo/bar.yml'],
            ]),
            'generatedConfigurationOverridesFilepathProvider' => $this->getMockGeneratedConfigurationOverridesFilepathProvider( // phpcs:ignore Generic.Files.LineLength.TooLong
                return: '/var/www/html/var/generated/foo/bar.yml',
            ),
            'pipelineConfigurationOverrideFilepaths' => [
                'foo/bar.yml',
                '',
                '/foo/bar.yml',
                'Klevu_Foo::bar.yml',
                'foo/bar.yml',
            ],
        ]);

        $this->assertSame(
            expected: [
                '/var/www/html/var/foo/bar.yml',
                '/foo/bar.yml',
                '/var/www/html/vendor/klevu/foo/bar.yml',
                '/var/www/html/var/generated/foo/bar.yml',
            ],
            actual: $provider->get(),
        );
    }

    /**
     * @param array<string[]> $valueMap
     *
     * @return MockObject&ParseFilepathActionInterface
     */
    private function getMockParseFilepathAction(
        array $valueMap,
    ): MockObject {
        $mockParseFilepathAction = $this->getMockBuilder(ParseFilepathActionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockParseFilepathAction->method('execute')
            ->willReturnMap($valueMap);

        return $mockParseFilepathAction;
    }

    /**
     * @param string|null $return
     *
     * @return MockObject&GeneratedConfigurationOverridesFilepathProviderInterface
     */
    private function getMockGeneratedConfigurationOverridesFilepathProvider(
        ?string $return,
    ): MockObject {
        $mockGeneratedConfigurationOverridesFilepathsProvider = $this->getMockBuilder(
                GeneratedConfigurationOverridesFilepathProviderInterface::class,
            )->disableOriginalConstructor()
            ->getMock();

        $mockGeneratedConfigurationOverridesFilepathsProvider->method('get')
            ->willReturn($return);

        return $mockGeneratedConfigurationOverridesFilepathsProvider;
    }
}