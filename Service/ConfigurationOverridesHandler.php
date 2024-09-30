<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Service;

use Klevu\PlatformPipelines\Api\ConfigurationOverridesHandlerInterface;
use Klevu\PlatformPipelines\Api\GenerateConfigurationOverridesContentActionInterface;
use Klevu\PlatformPipelines\Exception\CouldNotGenerateConfigurationOverridesException;
use Klevu\PlatformPipelines\Service\Provider\GeneratedConfigurationOverridesFilepathProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File as FileIo;
use Magento\Framework\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

class ConfigurationOverridesHandler implements ConfigurationOverridesHandlerInterface
{
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var GeneratedConfigurationOverridesFilepathProviderInterface
     */
    private readonly GeneratedConfigurationOverridesFilepathProviderInterface $generatedConfigurationOverridesFilepathProvider; // phpcs:ignore Generic.Files.LineLength.TooLong
    /**
     * @var FileIo
     */
    private readonly FileIo $fileSystemWrite;
    /**
     * @var GenerateConfigurationOverridesContentActionInterface
     */
    private readonly GenerateConfigurationOverridesContentActionInterface $generateConfigurationOverridesContentAction;
    /**
     * @var ValidatorInterface|null
     */
    private readonly ?ValidatorInterface $configurationOverridesContentValidator;
    /**
     * @var bool
     */
    private readonly bool $forceFileRegeneration;

    /**
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param GeneratedConfigurationOverridesFilepathProviderInterface $generatedConfigurationOverridesFilepathProvider
     * @param FileIo $fileSystemWrite
     * @param GenerateConfigurationOverridesContentActionInterface $generateConfigurationOverridesContentAction
     * @param ValidatorInterface|null $configurationOverridesContentValidator
     * @param bool $forceFileRegeneration
     */
    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        GeneratedConfigurationOverridesFilepathProviderInterface $generatedConfigurationOverridesFilepathProvider,
        FileIo $fileSystemWrite,
        GenerateConfigurationOverridesContentActionInterface $generateConfigurationOverridesContentAction,
        ?ValidatorInterface $configurationOverridesContentValidator,
        bool $forceFileRegeneration = false,
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->generatedConfigurationOverridesFilepathProvider = $generatedConfigurationOverridesFilepathProvider;
        $this->fileSystemWrite = $fileSystemWrite;
        $this->generateConfigurationOverridesContentAction = $generateConfigurationOverridesContentAction;
        $this->configurationOverridesContentValidator = $configurationOverridesContentValidator;
        $this->forceFileRegeneration = $forceFileRegeneration;
    }

    /**
     * @return void
     * @throws CouldNotGenerateConfigurationOverridesException
     */
    public function execute(): void
    {
        $isEnabled = $this->scopeConfig->isSetFlag(
            static::XML_PATH_CONFIGURATION_OVERRIDES_GENERATION_ENABLED,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0,
        );
        if (!$isEnabled) {
            return;
        }

        $this->createOverridesFilesIfRequired();
    }

    /**
     * @return void
     * @throws CouldNotGenerateConfigurationOverridesException
     */
    private function createOverridesFilesIfRequired(): void
    {
        $targetFilePath = $this->generatedConfigurationOverridesFilepathProvider->get();
        if (
            !$targetFilePath
            || (!$this->forceFileRegeneration && $this->fileSystemWrite->fileExists($targetFilePath))
        ) {
            return;
        }

        try {
            $fileContent = $this->generateConfigurationOverridesContentAction->execute();
        } catch (LocalizedException $exception) {
            throw new CouldNotGenerateConfigurationOverridesException(
                message: sprintf(
                    'Error generating configuration overrides content: %s',
                    $exception->getMessage(),
                ),
                previous: $exception,
            );
        }

        if ($this->configurationOverridesContentValidator) {
            $errors = [];
            $previous = null;
            try {
                if (!$this->configurationOverridesContentValidator->isValid($fileContent)) {
                    $errors = $this->configurationOverridesContentValidator->getMessages() ?: [''];
                }
            } catch (\Exception $exception) {
                $errors = [
                    $exception->getMessage(),
                ];
                $previous = $exception;
            }

            if ($errors) {
                throw new CouldNotGenerateConfigurationOverridesException(
                    message: sprintf(
                        'Generated overrides content is invalid: %s',
                        implode(', ', $errors),
                    ),
                    previous: $previous,
                );
            }
        }

        $this->writeFile(
            filepath: $targetFilePath,
            content: $fileContent,
        );

        $this->logger->debug(
            message: 'Configuration overrides file content regenerated for {targetFilePath}',
            context: [
                'targetFilePath' => $targetFilePath,
            ],
        );
    }

    /**
     * @param string $filepath
     * @param string $content
     *
     * @return void
     * @throws CouldNotGenerateConfigurationOverridesException
     */
    private function writeFile(
        string $filepath,
        string $content,
    ): void {
        $pathInfo = $this->fileSystemWrite->getPathInfo($filepath);
        $directoryName = trim((string)($pathInfo['dirname'] ?? null));
        if (!$directoryName) {
            throw new CouldNotGenerateConfigurationOverridesException(
                message: sprintf(
                    'Could not determine parent directory for filepath "%s"',
                    $filepath,
                ),
            );
        }

        try {
            $this->fileSystemWrite->checkAndCreateFolder(
                folder: $directoryName,
                mode: 0755,
            );

            $this->fileSystemWrite->write(
                filename: $filepath,
                src: $content,
                mode: 0644,
            );
        } catch (LocalizedException $exception) {
            throw new CouldNotGenerateConfigurationOverridesException(
                message: sprintf(
                    'Could not create file "%s": %s',
                    $filepath,
                    $exception->getMessage(),
                ),
                previous: $exception,
            );
        }
    }
}
