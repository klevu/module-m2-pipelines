<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Service\Action;

use Klevu\PlatformPipelines\Api\GenerateConfigurationOverridesContentActionInterface;
use Klevu\PlatformPipelines\Service\Action\GenerateConfigurationOverridesContent;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

// phpcs:disable Generic.Files.LineLength.TooLong
/**
 * @covers \Klevu\PlatformPipelines\Service\Action\GenerateConfigurationOverridesContent::class
 * @method GenerateConfigurationOverridesContentActionInterface instantiateTestObject(?array $arguments = null)
 * @method GenerateConfigurationOverridesContentActionInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
// phpcs:enable Generic.Files.LineLength.TooLong
class GenerateConfigurationOverridesContentTest extends TestCase
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

        $this->implementationFqcn = GenerateConfigurationOverridesContent::class;
        $this->interfaceFqcn = GenerateConfigurationOverridesContentActionInterface::class;
    }

    public function testExecute(): void
    {
        $generationConfigurationOverridesContentAction = $this->instantiateTestObject();

        $content = $generationConfigurationOverridesContentAction->execute();
        $this->assertNotEmpty($content);

        $contentLines = explode(
            separator: PHP_EOL,
            string: $content,
        );
        $commentAndEmptyLines = array_filter(
            $contentLines,
            static fn (string $contentLine): bool => (
                '' === trim($contentLine)
                || str_starts_with($contentLine, '#')
            ),
        );

        $this->assertSame(
            expected: $contentLines,
            actual: $commentAndEmptyLines,
        );
    }
}
