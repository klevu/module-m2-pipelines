<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Service\Provider;

use Klevu\PlatformPipelines\Exception\Validator\InvalidFilepathException;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

class GeneratedConfigurationOverridesFilepathProvider implements GeneratedConfigurationOverridesFilepathProviderInterface // phpcs:ignore Generic.Files.LineLength.TooLong
{
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var AppState
     */
    private readonly AppState $appState;
    /**
     * @var DirectoryList
     */
    private readonly DirectoryList $directoryList;
    /**
     * @var DriverInterface
     */
    private readonly DriverInterface $fileDriver;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $filepathValidator;
    /**
     * @var string|null
     */
    private readonly ?string $filepathInVar;

    /**
     * @param LoggerInterface $logger
     * @param AppState $appState
     * @param DirectoryList $directoryList
     * @param DriverInterface $fileDriver
     * @param ValidatorInterface $filepathValidator
     * @param string|null $filepathInVar
     */
    public function __construct(
        LoggerInterface $logger,
        AppState $appState,
        DirectoryList $directoryList,
        DriverInterface $fileDriver,
        ValidatorInterface $filepathValidator,
        ?string $filepathInVar = null,
    ) {
        $this->logger = $logger;
        $this->appState = $appState;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->filepathValidator = $filepathValidator;
        $this->filepathInVar = $filepathInVar;
    }

    /**
     * @return string|null
     * @throws FileSystemException
     * @throws InvalidFilepathException
     */
    public function get(): ?string
    {
        if (!trim((string)$this->filepathInVar)) {
            return null;
        }

        $filepath = $this->directoryList->getPath(AppDirectoryList::VAR_DIR)
            . DIRECTORY_SEPARATOR
            . $this->filepathInVar;

        $return = $this->fileDriver->getRealPathSafety($filepath);
        try {
            $filepathIsValid = $this->filepathValidator->isValid($return);
            $validationErrors = $this->filepathValidator->getMessages();
        } catch (\Exception $exception) {
            $filepathIsValid = false;
            $validationErrors = [
                $exception->getMessage(),
            ];
        }
        
        if (!$filepathIsValid) {
            if (AppState::MODE_PRODUCTION !== $this->appState->getMode()) {
                throw new InvalidFilepathException(
                    phrase: __(
                        'Invalid configuration overrides filepath defined (%1): %2',
                        $this->filepathInVar,
                        implode(', ', $validationErrors),
                    ),
                );
            }

            $this->logger->error(
                message: 'Invalid configuration overrides filepath defined: omitting from pipeline',
                context: [
                    'method' => __METHOD__,
                    'filepathInVar' => $this->filepathInVar,
                    'validationErrors' => $validationErrors,
                ],
            );

            $return = null;
        }

        return $return;
    }
}
