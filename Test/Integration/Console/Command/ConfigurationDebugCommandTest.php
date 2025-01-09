<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Console\Command;

use Klevu\Pipelines\Pipeline\ConfigurationBuilder;
use Klevu\PlatformPipelines\Api\ConfigurationOverridesHandlerInterface;
use Klevu\PlatformPipelines\Api\PipelineConfigurationOverridesFilepathsProviderInterface;
use Klevu\PlatformPipelines\Api\PipelineConfigurationProviderInterface;
use Klevu\PlatformPipelines\Console\Command\ConfigurationDebugCommand;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\PlatformPipelines\Console\Command\ConfigurationDebugCommand::class
 * @method ConfigurationDebugCommand instantiateTestObject(?array $arguments = null)
 */
class ConfigurationDebugCommandTest extends TestCase
{
    use ObjectInstantiationTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var DirectoryList|null
     */
    private ?DirectoryList $directoryList = null;
    /**
     * @var FileIo|null
     */
    private ?FileIo $fileIo = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        $this->implementationFqcn = ConfigurationDebugCommand::class;
        // newrelic-describe-commands globs onto Console commands
        $this->expectPlugins = true;

        $this->directoryList = $this->objectManager->get(DirectoryList::class);
        $this->fileIo = $this->objectManager->get(FileIo::class);
    }

    /**
     * @return mixed[][]
     */
    public static function dataProvider_testExecute_Config(): array
    {
        return [
            [
                [
                    ConfigurationOverridesHandlerInterface::XML_PATH_CONFIGURATION_OVERRIDES_GENERATION_ENABLED => '1',
                ],
            ],
            [
                [
                    ConfigurationOverridesHandlerInterface::XML_PATH_CONFIGURATION_OVERRIDES_GENERATION_ENABLED => '0',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProvider_testExecute_Config
     *
     * @param array<string, mixed> $config
     *
     * @return void
     */
    public function testExecute_Config(
        array $config,
    ): void {
        foreach ($config as $configPath => $value) {
            ConfigFixture::setGlobal(
                path: $configPath,
                value: $value,
            );
        }

        $configurationDebugCommand = $this->instantiateTestObject();

        $tester = new CommandTester(
            command: $configurationDebugCommand,
        );
        $responseCode = $tester->execute(
            input: [
                'action' => 'config',
            ],
        );

        $this->assertSame(0, $responseCode);

        $output = $tester->getDisplay();

        $enabledConfigPath = 'klevu/platform_pipelines/configuration_overrides_generation_enabled';
        $this->assertMatchesRegularExpression(
            pattern: sprintf(
                '#\s+%s : %s\s+#',
                preg_quote($enabledConfigPath),
                preg_quote($config[$enabledConfigPath] ?? ''),
            ),
            string: $output,
        );
    }

    public function testExecute_List(): void
    {
        $fixtureFilePrefix = $this->directoryList->getPath(AppDirectoryList::VAR_DIR)
            . DIRECTORY_SEPARATOR;
        $this->fileIo->write(
            filename: $fixtureFilePrefix . 'identifier1_config.yml',
            src: '',
            mode: 0644,
        );
        $this->fileIo->write(
            filename: $fixtureFilePrefix . 'identifier1_override1.yml',
            src: '',
            mode: 0644,
        );

        $configurationDebugCommand = $this->instantiateTestObject([
            'pipelineConfigurationProvider' => $this->getPipelineConfigurationProvider(
                pipelineConfigurationFilepathsByIdentifier: [
                    'IDENTIFIER::1' => $fixtureFilePrefix . 'identifier1_config.yml',
                    'IDENTIFIER::2' => $fixtureFilePrefix . 'identifier2_config.yml',
                ],
                pipelineConfigurationOverridesFilepathsByIdentifier: [
                    'IDENTIFIER::1' => [
                        $fixtureFilePrefix . 'identifier1_override1.yml',
                        $fixtureFilePrefix . 'identifier1_override2.yml',
                    ],
                    'IDENTIFIER::2' => [],
                ],
            ),
        ]);

        $tester = new CommandTester(
            command: $configurationDebugCommand,
        );
        $responseCode = $tester->execute(
            input: [
                'action' => 'list-files',
            ],
        );

        $this->assertSame(0, $responseCode);

        $output = $tester->getDisplay();

        $this->assertStringContainsString(
            needle: 'IDENTIFIER::1' . PHP_EOL
            . sprintf(
                '* Filepath           : %sidentifier1_config.yml',
                $fixtureFilePrefix,
            ) . PHP_EOL
            . sprintf(
                '* Override Filepaths : %sidentifier1_override1.yml; %sidentifier1_override2.yml',
                $fixtureFilePrefix,
                $fixtureFilePrefix,
            ),
            haystack: $output,
        );
        $this->assertStringContainsString(
            needle: 'IDENTIFIER::2' . PHP_EOL
            . sprintf(
                '* Filepath           : File %sidentifier2_config.yml does not exist',
                $fixtureFilePrefix,
            ) . PHP_EOL
            . '* Override Filepaths : ',
            haystack: $output,
        );
    }

    public function testExecute_Compile_ReturnsError_WhenNoPipelineIdentifier(): void
    {
        $configurationDebugCommand = $this->instantiateTestObject();

        $tester = new CommandTester(
            command: $configurationDebugCommand,
        );

        $responseCode = $tester->execute(
            input: [
                'action' => 'compile',
            ],
        );

        $this->assertSame(1, $responseCode);

        $output = $tester->getDisplay();

        $this->assertStringContainsString(
            needle: 'Please provide a pipeline identifier to compile',
            haystack: $output,
        );
    }

    public function testExecute_Compile_ReturnsError_WhenConfigurationFileNotFound(): void
    {
        $mockPipelineConfigurationProvider = $this->getMockPipelineConfigurationProvider();
        $mockPipelineConfigurationProvider->method('getPipelineConfigurationFilepathByIdentifier')
            ->willThrowException(
                exception: new NotFoundException(
                    phrase: __('Not found'),
                ),
            );

        $configurationDebugCommand = $this->instantiateTestObject([
            'pipelineConfigurationProvider' => $mockPipelineConfigurationProvider,
        ]);

        $tester = new CommandTester(
            command: $configurationDebugCommand,
        );

        $responseCode = $tester->execute([
            'action' => 'compile',
            'pipeline_identifier' => 'foo',
        ]);

        $this->assertSame(1, $responseCode);

        $output = $tester->getDisplay();

        $this->assertStringContainsString(
            needle: 'Pipeline for identifier foo is not registered with configuration provider',
            haystack: $output,
        );
    }

    public function testExecute_Compile(): void
    {
        $mockPipelineConfigurationProvider = $this->getMockPipelineConfigurationProvider();
        $mockPipelineConfigurationProvider->method('getPipelineConfigurationFilepathByIdentifier')
            ->with('foo')
            ->willReturn('/foo/bar/baz.yml');
        $mockPipelineConfigurationProvider->method('getPipelineConfigurationOverridesFilepathsByIdentifier')
            ->with('foo')
            ->willReturn([
                '/var/foo.yml',
                '/var/bar.yml',
                '/var/baz.yml',
            ]);

        $mockConfigurationBuilder = $this->getMockConfigurationBuilder();
        $mockConfigurationBuilder->method('buildFromFiles')
            ->with(
                '/foo/bar/baz.yml',
                [
                    '/var/foo.yml',
                    '/var/bar.yml',
                    '/var/baz.yml',
                ],
            )->willReturn([
                'foo' => [
                    'bar' => [
                        'baz',
                    ],
                ],
            ]);

        $configurationDebugCommand = $this->instantiateTestObject([
            'pipelineConfigurationProvider' => $mockPipelineConfigurationProvider,
            'configurationBuilder' => $mockConfigurationBuilder,
        ]);

        $tester = new CommandTester(
            command: $configurationDebugCommand,
        );
        $responseCode = $tester->execute([
            'action' => 'compile',
            'pipeline_identifier' => 'foo',
        ]);

        $this->assertSame(0, $responseCode);

        $output = $tester->getDisplay();

        $this->assertMatchesRegularExpression(
            pattern: '#.*foo:\s+bar:\s+- baz\s+.*#',
            string: $output,
        );
    }

    public function testExecute_ReturnsError_WhenNoAction(): void
    {
        $configurationDebugCommand = $this->instantiateTestObject();

        $tester = new CommandTester(
            command: $configurationDebugCommand,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "action"');
        $tester->execute(
            input: [],
        );
    }

    public function testExecute_WhenUnknownAction(): void
    {
        $configurationDebugCommand = $this->instantiateTestObject();

        $tester = new CommandTester(
            command: $configurationDebugCommand,
        );

        $responseCode = $tester->execute(
            input: [
                'action' => 'foo',
            ],
        );
        $this->assertSame(0, $responseCode);

        $output = $tester->getDisplay();

        $this->assertEmpty(
            actual: trim($output),
        );
    }

    /**
     * @param array<string, string> $pipelineConfigurationFilepathsByIdentifier
     * @param array<string, string[]> $pipelineConfigurationOverridesFilepathsByIdentifier
     *
     * @return PipelineConfigurationProviderInterface
     */
    private function getPipelineConfigurationProvider(
        array $pipelineConfigurationFilepathsByIdentifier,
        array $pipelineConfigurationOverridesFilepathsByIdentifier,
    ): PipelineConfigurationProviderInterface {
        $mockPipelineConfigurationOverridesFilepathsProviders = [];
        foreach ($pipelineConfigurationOverridesFilepathsByIdentifier as $pipelineIdentifier => $pipelineConfigurationOverridesFilepaths) { // phpcs:ignore Generic.Files.LineLength.TooLong
            $mockPipelineConfigurationOverridesFilepathsProviders[$pipelineIdentifier] = $this->getMockBuilder(
                    PipelineConfigurationOverridesFilepathsProviderInterface::class,
                )->disableOriginalConstructor()
                ->getMock();

            $mockPipelineConfigurationOverridesFilepathsProviders[$pipelineIdentifier]->method('get')
                ->willReturn($pipelineConfigurationOverridesFilepaths);
        }

        return $this->objectManager->create(
            type: PipelineConfigurationProviderInterface::class,
            arguments: [
                'pipelineConfigurationFilepaths' => $pipelineConfigurationFilepathsByIdentifier,
                'pipelineConfigurationOverridesFilepathsProviders' => $mockPipelineConfigurationOverridesFilepathsProviders, // phpcs:ignore Generic.Files.LineLength.TooLong
            ],
        );
    }

    /**
     * @return MockObject&PipelineConfigurationProviderInterface
     */
    private function getMockPipelineConfigurationProvider(): MockObject
    {
        return $this->getMockBuilder(PipelineConfigurationProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return MockObject&ConfigurationBuilder
     */
    private function getMockConfigurationBuilder(): MockObject
    {
        return $this->getMockBuilder(ConfigurationBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
