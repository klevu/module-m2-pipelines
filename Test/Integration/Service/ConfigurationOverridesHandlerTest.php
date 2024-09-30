<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Test\Integration\Service;

use Klevu\PlatformPipelines\Api\ConfigurationOverridesHandlerInterface;
use Klevu\PlatformPipelines\Api\GenerateConfigurationOverridesContentActionInterface;
use Klevu\PlatformPipelines\Exception\CouldNotGenerateConfigurationOverridesException;
use Klevu\PlatformPipelines\Service\ConfigurationOverridesHandler;
use Klevu\PlatformPipelines\Service\Provider\GeneratedConfigurationOverridesFilepathProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validator\ValidatorInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\PlatformPipelines\Service\ConfigurationOverridesHandler::class
 * @method ConfigurationOverridesHandlerInterface instantiateTestObject(?array $arguments = null)
 * @method ConfigurationOverridesHandlerInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class ConfigurationOverridesHandlerTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var DirectoryList|null
     */
    private ?DirectoryList $directoryList = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();

        $this->implementationFqcn = ConfigurationOverridesHandler::class;
        $this->interfaceFqcn = ConfigurationOverridesHandlerInterface::class;

        $this->directoryList = $this->objectManager->get(DirectoryList::class);
    }

    public function testExecute_PerformsNoAction_WhenGenerationDisabled(): void
    {
        ConfigFixture::setGlobal(
            path: 'klevu/platform_pipelines/configuration_overrides_generation_enabled',
            value: 0,
        );

        $filepathInVar = 'klevu-phpunit/testExecute_PerformsNoAction_WhenGenerationDisabled' . microtime() . '.yml';
        $handler = $this->instantiateTestObject([
            'logger' => $this->getMockLogger(
                expectedLogLevels: [],
            ),
            'generatedConfigurationOverridesFilepathProvider' => $this->getMockGeneratedConfigurationOverridesFilepathProvider( // phpcs:ignore Generic.Files.LineLength.TooLong
                filepathInVar: $filepathInVar,
                touchFile: false,
            ),
            'fileSystemWrite' => $this->getMockFileIoWithNoAction(),
        ]);

        $handler->execute();
    }

    public function testExecute_PerformsNoAction_WhenOverrideFileNotSpecified(): void
    {
        ConfigFixture::setGlobal(
            path: 'klevu/platform_pipelines/configuration_overrides_generation_enabled',
            value: 1,
        );

        $handler = $this->instantiateTestObject([
            'logger' => $this->getMockLogger(
                expectedLogLevels: [],
            ),
            'generatedConfigurationOverridesFilepathProvider' => $this->getMockGeneratedConfigurationOverridesFilepathProvider( // phpcs:ignore Generic.Files.LineLength.TooLong
                filepathInVar: null,
                touchFile: false,
            ),
            'fileSystemWrite' => $this->getMockFileIoWithNoAction(),
        ]);

        $handler->execute();
    }

    public function testExecute_CreatesFile_WhenOverridesFileDoesNotExist(): void
    {
        ConfigFixture::setGlobal(
            path: 'klevu/platform_pipelines/configuration_overrides_generation_enabled',
            value: 1,
        );

        $filepathInVar = 'klevu-phpunit/testExecute_PerformsNoAction_WhenGenerationDisabled' . microtime() . '.yml';
        $absoluteFilepath = $this->directoryList->getPath(AppDirectoryList::VAR_DIR)
            . DIRECTORY_SEPARATOR
            . $filepathInVar;

        $mockLogger = $this->getMockLogger(
            expectedLogLevels: ['debug'],
        );
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with(
                'Configuration overrides file content regenerated for {targetFilePath}',
                $this->callback(function (array $context) use ($absoluteFilepath): bool {
                    $this->assertArrayHasKey(
                        key: 'targetFilePath',
                        array: $context,
                    );
                    $this->assertSame(
                        expected: $absoluteFilepath,
                        actual: $context['targetFilePath'],
                    );

                    return true;
                }),
            );
        $mockFileIo = $this->getMockFileIo();
        $mockFileIo->expects($this->once())
            ->method('checkAndCreateFolder')
            ->with(
                $this->directoryList->getPath(AppDirectoryList::VAR_DIR)
                    . DIRECTORY_SEPARATOR
                    . 'klevu-phpunit',
                0755,
            );
        $mockFileIo->expects($this->once())
            ->method('write')
            ->with(
                $absoluteFilepath,
                '# Test Content',
                0644,
            );

        $handler = $this->instantiateTestObject([
            'logger' => $mockLogger,
            'generatedConfigurationOverridesFilepathProvider' => $this->getMockGeneratedConfigurationOverridesFilepathProvider( // phpcs:ignore Generic.Files.LineLength.TooLong
                filepathInVar: $filepathInVar,
                touchFile: false,
            ),
            'fileSystemWrite' => $mockFileIo,
            'generateConfigurationOverridesContentAction' => $this->getMockGenerateConfigurationOverridesContentActionWithContent( // phpcs:ignore Generic.Files.LineLength.TooLong
                content: '# Test Content',
            ),
            'forceFileRegeneration' => false,
        ]);

        $handler->execute();
    }

    public function testExecute_PerformsNoAction_WhenOverridesFileExists_AndForceFileRegenerationDisabled(): void
    {
        ConfigFixture::setGlobal(
            path: 'klevu/platform_pipelines/configuration_overrides_generation_enabled',
            value: 1,
        );

        $filepathInVar = 'klevu-phpunit/testExecute_PerformsNoAction_WhenGenerationDisabled' . microtime() . '.yml';
        $handler = $this->instantiateTestObject([
            'logger' => $this->getMockLogger(
                expectedLogLevels: [],
            ),
            'generatedConfigurationOverridesFilepathProvider' => $this->getMockGeneratedConfigurationOverridesFilepathProvider( // phpcs:ignore Generic.Files.LineLength.TooLong
                filepathInVar: $filepathInVar,
                touchFile: true,
            ),
            'fileSystemWrite' => $this->getMockFileIoWithNoAction(),
            'forceFileRegeneration' => false,
        ]);

        $handler->execute();
    }

    public function testExecute_CreatesFile_WhenOverridesFileExists_AndForceFileRegenerationEnabled(): void
    {
        ConfigFixture::setGlobal(
            path: 'klevu/platform_pipelines/configuration_overrides_generation_enabled',
            value: 1,
        );

        $filepathInVar = 'klevu-phpunit/testExecute_PerformsNoAction_WhenGenerationDisabled' . microtime() . '.yml';
        $absoluteFilepath = $this->directoryList->getPath(AppDirectoryList::VAR_DIR)
            . DIRECTORY_SEPARATOR
            . $filepathInVar;

        $mockLogger = $this->getMockLogger(
            expectedLogLevels: ['debug'],
        );
        $mockLogger->expects($this->once())
            ->method('debug')
            ->with(
                'Configuration overrides file content regenerated for {targetFilePath}',
                $this->callback(function (array $context) use ($absoluteFilepath): bool {
                    $this->assertArrayHasKey(
                        key: 'targetFilePath',
                        array: $context,
                    );
                    $this->assertSame(
                        expected: $absoluteFilepath,
                        actual: $context['targetFilePath'],
                    );

                    return true;
                }),
            );
        $mockFileIo = $this->getMockFileIo();
        $mockFileIo->expects($this->once())
            ->method('checkAndCreateFolder')
            ->with(
                $this->directoryList->getPath(AppDirectoryList::VAR_DIR)
                . DIRECTORY_SEPARATOR
                . 'klevu-phpunit',
                0755,
            );
        $mockFileIo->expects($this->once())
            ->method('write')
            ->with(
                $absoluteFilepath,
                '# Test Content',
                0644,
            );

        $handler = $this->instantiateTestObject([
            'logger' => $mockLogger,
            'generatedConfigurationOverridesFilepathProvider' => $this->getMockGeneratedConfigurationOverridesFilepathProvider( // phpcs:ignore Generic.Files.LineLength.TooLong
                filepathInVar: $filepathInVar,
                touchFile: true,
            ),
            'fileSystemWrite' => $mockFileIo,
            'generateConfigurationOverridesContentAction' => $this->getMockGenerateConfigurationOverridesContentActionWithContent( // phpcs:ignore Generic.Files.LineLength.TooLong
                content: '# Test Content',
            ),
            'forceFileRegeneration' => true,
        ]);

        $handler->execute();
    }

    public function testExecute_ThrowsException_OnFailedFileContentGeneration(): void
    {
        ConfigFixture::setGlobal(
            path: 'klevu/platform_pipelines/configuration_overrides_generation_enabled',
            value: 1,
        );

        $filepathInVar = 'klevu-phpunit/testExecute_PerformsNoAction_WhenGenerationDisabled' . microtime() . '.yml';
        $handler = $this->instantiateTestObject([
            'logger' => $this->getMockLogger(
                expectedLogLevels: [],
            ),
            'generatedConfigurationOverridesFilepathProvider' => $this->getMockGeneratedConfigurationOverridesFilepathProvider( // phpcs:ignore Generic.Files.LineLength.TooLong
                filepathInVar: $filepathInVar,
                touchFile: false,
            ),
            'fileSystemWrite' => $this->getMockFileIoWithNoAction(),
            'generateConfigurationOverridesContentAction' => $this->getMockGenerateConfigurationOverridesContentActionThrowsException( // phpcs:ignore Generic.Files.LineLength.TooLong
                exceptionMessage: 'Test Error',
            ),
            'forceFileRegeneration' => false,
        ]);

        $this->expectException(CouldNotGenerateConfigurationOverridesException::class);
        $this->expectExceptionMessage('Error generating configuration overrides content: Test Error');

        $handler->execute();
    }

    public function testExecute_ThrowsException_OnInvalidFileContentGeneration(): void
    {
        ConfigFixture::setGlobal(
            path: 'klevu/platform_pipelines/configuration_overrides_generation_enabled',
            value: 1,
        );

        $filepathInVar = 'klevu-phpunit/testExecute_PerformsNoAction_WhenGenerationDisabled' . microtime() . '.yml';
        $handler = $this->instantiateTestObject([
            'logger' => $this->getMockLogger(
                expectedLogLevels: [],
            ),
            'generatedConfigurationOverridesFilepathProvider' => $this->getMockGeneratedConfigurationOverridesFilepathProvider( // phpcs:ignore Generic.Files.LineLength.TooLong
                filepathInVar: $filepathInVar,
                touchFile: false,
            ),
            'fileSystemWrite' => $this->getMockFileIoWithNoAction(),
            'generateConfigurationOverridesContentAction' => $this->getMockGenerateConfigurationOverridesContentActionWithContent( // phpcs:ignore Generic.Files.LineLength.TooLong
                content: '# Test Content',
            ),
            'configurationOverridesContentValidator' => $this->getMockValidator(
                isValid: false,
                messages: [
                    'Test Error',
                    'Another test error',
                ],
            ),
            'forceFileRegeneration' => false,
        ]);

        $this->expectException(CouldNotGenerateConfigurationOverridesException::class);
        $this->expectExceptionMessage('Generated overrides content is invalid: Test Error, Another test error');

        $handler->execute();
    }

    /**
     * @param string[] $expectedLogLevels
     *
     * @return MockObject&LoggerInterface
     */
    private function getMockLogger(array $expectedLogLevels = []): MockObject
    {
        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $notExpectedLogLevels = array_diff(
            $expectedLogLevels,
            [
                'emergency',
                'alert',
                'critical',
                'error',
                'warning',
                'notice',
                'info',
                'debug',
            ],
        );
        foreach ($notExpectedLogLevels as $notExpectedLogLevel) {
            $mockLogger->expects($this->never())
                ->method($notExpectedLogLevel);
        }

        return $mockLogger;
    }

    /**
     * @return MockObject&FileIo
     */
    private function getMockFileIo(): MockObject
    {
        return $this->getMockBuilder(FileIo::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'checkAndCreateFolder',
                'write',
            ])->getMock();
    }

    /**
     * @return MockObject&FileIo
     */
    private function getMockFileIoWithNoAction(): MockObject
    {
        $mockFileIo = $this->getMockFileIo();

        $mockFileIo->expects($this->never())
            ->method('checkAndCreateFolder');
        $mockFileIo->expects($this->never())
            ->method('write');

        return $mockFileIo;
    }

    /**
     * @param string|null $filepathInVar
     * @param bool $touchFile
     *
     * @return MockObject&GeneratedConfigurationOverridesFilepathProviderInterface
     * @throws FileSystemException
     */
    private function getMockGeneratedConfigurationOverridesFilepathProvider(
        ?string $filepathInVar,
        bool $touchFile,
    ): MockObject {
        $mockGeneratedConfigurationOverridesFilepathProvider = $this->getMockBuilder(
                GeneratedConfigurationOverridesFilepathProviderInterface::class,
            )->disableOriginalConstructor()
            ->getMock();

        if ($filepathInVar) {
            $absoluteFilepath = $this->directoryList->getPath(AppDirectoryList::VAR_DIR)
                . DIRECTORY_SEPARATOR
                . $filepathInVar;

            if ($touchFile) {
                touch($absoluteFilepath);
            }
        } else {
            $absoluteFilepath = null;
        }

        $mockGeneratedConfigurationOverridesFilepathProvider->method('get')
            ->willReturn(
                value: $absoluteFilepath,
            );

        return $mockGeneratedConfigurationOverridesFilepathProvider;
    }

    /**
     * @return MockObject&GenerateConfigurationOverridesContentActionInterface
     */
    private function getMockGenerateConfigurationOverridesContentAction(): MockObject
    {
        return $this->getMockBuilder(GenerateConfigurationOverridesContentActionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $exceptionMessage
     *
     * @return MockObject&GenerateConfigurationOverridesContentActionInterface
     */
    private function getMockGenerateConfigurationOverridesContentActionThrowsException(
        string $exceptionMessage,
    ): MockObject {
        $mockGenerateConfigurationOverridesContentAction = $this->getMockGenerateConfigurationOverridesContentAction();

        $mockGenerateConfigurationOverridesContentAction->method('execute')
            ->willThrowException(
                new LocalizedException(__($exceptionMessage)),
            );

        return $mockGenerateConfigurationOverridesContentAction;
    }

    /**
     * @param string $content
     *
     * @return MockObject&GenerateConfigurationOverridesContentActionInterface
     */
    private function getMockGenerateConfigurationOverridesContentActionWithContent(
        string $content,
    ): MockObject {
        $mockGenerateConfigurationOverridesContentAction = $this->getMockGenerateConfigurationOverridesContentAction();

        $mockGenerateConfigurationOverridesContentAction->method('execute')
            ->willReturn($content);

        return $mockGenerateConfigurationOverridesContentAction;
    }

    /**
     * @param bool $isValid
     * @param string[] $messages
     *
     * @return ValidatorInterface
     */
    private function getMockValidator(
        bool $isValid,
        array $messages,
    ): ValidatorInterface {
        $mockValidator = $this->getMockBuilder(ValidatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockValidator->method('isValid')
            ->willReturn($isValid);
        $mockValidator->method('getMessages')
            ->willReturn($messages);

        return $mockValidator;
    }
}
