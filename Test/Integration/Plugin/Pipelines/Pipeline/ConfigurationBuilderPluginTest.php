<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Plugin\Pipelines\Pipeline;

use Klevu\Pipelines\Exception\Pipeline\InvalidPipelineConfigurationException;
use Klevu\Pipelines\Pipeline\ConfigurationBuilder;
use Klevu\PlatformPipelines\Plugin\Pipelines\Pipeline\ConfigurationBuilderPlugin;
use Klevu\PlatformPipelines\Service\Action\ParseFilepathActionInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Interception\PluginList\PluginList;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\PlatformPipelines\Plugin\Pipelines\Pipeline\ConfigurationBuilderPlugin::class
 */
class ConfigurationBuilderPluginTest extends TestCase
{
    use ObjectInstantiationTrait;

    /**
     * @var string|null
     */
    private ?string $pluginName = 'Klevu_PlatformPipelines::PipelineConfigurationBuilderPlugin';
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->implementationFqcn = ConfigurationBuilderPlugin::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoAppArea global
     */
    public function testPlugin_InterceptsCallsToTheField_InGlobalScope(): void
    {
        $pluginInfo = $this->getSystemConfigPluginInfo();
        $this->assertArrayHasKey($this->pluginName, $pluginInfo);
        $this->assertSame(ConfigurationBuilderPlugin::class, $pluginInfo[$this->pluginName]['instance']);
    }

    public function testBeforeGetImportFilePath_ThrowsInvalidPipelineConfigurationException_WhenFileNotReadable(): void
    {
        $this->markTestIncomplete('set permission on file to not allow read');
        $this->expectException(InvalidPipelineConfigurationException::class);

        /** @var ParseFilepathActionInterface $parseFilePath */
        $parseFilePath = $this->objectManager->get(ParseFilepathActionInterface::class);
        $filePath = 'valid_not_readable.yml';
        $importFilePath = $parseFilePath->execute('Klevu_TestFixtures::_files/pipeline/' . $filePath);
        $baseDirectory = dirname(path: $importFilePath);

        /** @var File $fileSystem */
        $fileSystem = $this->objectManager->get(File::class);
        $fileSystem->chmod(filename: $importFilePath, mode: 0111);

        $this->assertFalse(condition: is_readable($baseDirectory . DIRECTORY_SEPARATOR . $filePath));

        $configBuilder = $this->objectManager->get(ConfigurationBuilder::class);
        $configBuilder->getImportFilePath(importFilepath: $filePath, baseDirectory: $baseDirectory);
    }

    public function testBeforeGetImportFilePath_DoesNotChangeData_WhenPathMissingColonSeparator(): void
    {
        $parseFilePath = $this->objectManager->get(ParseFilepathActionInterface::class);
        $filePath = 'valid_no_steps.yml';
        $importFilePath = $parseFilePath->execute('Klevu_TestFixtures::_files/pipeline/' . $filePath);
        $baseDirectory = dirname(path: $importFilePath);

        $this->assertTrue(condition: is_readable($baseDirectory . DIRECTORY_SEPARATOR . $filePath));

        $configBuilder = $this->objectManager->get(ConfigurationBuilder::class);
        $result = $configBuilder->getImportFilePath(importFilepath: $filePath, baseDirectory: $baseDirectory);

        $this->assertSame(expected: $importFilePath, actual: $result);
    }

    public function testBeforeGetImportFilePath_ReplacesPath_WhenPathColonSeparatorPresent(): void
    {
        $parseFilePath = $this->objectManager->get(ParseFilepathActionInterface::class);
        $filePath = 'valid_no_steps.yml';
        $importFilePath = $parseFilePath->execute('Klevu_TestFixtures::_files/pipeline/' . $filePath);
        $baseDirectory = dirname(path: $importFilePath);

        $this->assertTrue(condition: is_readable($baseDirectory . DIRECTORY_SEPARATOR . $filePath));

        $configBuilder = $this->objectManager->get(ConfigurationBuilder::class);
        $result = $configBuilder->getImportFilePath(
            importFilepath: 'Klevu_TestFixtures::_files/pipeline/' . $filePath,
            baseDirectory: $baseDirectory,
        );

        $this->assertSame(expected: $importFilePath, actual: $result);
    }

    /**
     * @return mixed[]|null
     */
    private function getSystemConfigPluginInfo(): ?array
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(ConfigurationBuilder::class, []);
    }
}
