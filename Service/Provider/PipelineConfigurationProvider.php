<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Service\Provider;

use Klevu\PlatformPipelines\Api\PipelineConfigurationOverridesFilepathsProviderInterface;
use Klevu\PlatformPipelines\Api\PipelineConfigurationProviderInterface;
use Klevu\PlatformPipelines\Service\Action\ParseFilepathActionInterface;
use Magento\Framework\Exception\NotFoundException;
use Psr\Log\LoggerInterface;

class PipelineConfigurationProvider implements PipelineConfigurationProviderInterface
{
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var ParseFilepathActionInterface
     */
    private readonly ParseFilepathActionInterface $parseFilepathAction;
    /**
     * @var array<string, string>
     */
    private array $pipelineConfigurationFilepaths = [];
    /**
     * @var array<string, PipelineConfigurationOverridesFilepathsProviderInterface>
     */
    private array $pipelineConfigurationOverridesFilepathProviders = [];

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @param LoggerInterface $logger
     * @param ParseFilepathActionInterface $parseFilepathAction
     * @param array<string, string> $pipelineConfigurationFilepaths
     * @param array<string, PipelineConfigurationOverridesFilepathsProviderInterface> $pipelineConfigurationOverridesFilepathsProviders
     */
    // phpcs:enable Generic.Files.LineLength.TooLong
    public function __construct(
        LoggerInterface $logger,
        ParseFilepathActionInterface $parseFilepathAction,
        array $pipelineConfigurationFilepaths,
        array $pipelineConfigurationOverridesFilepathsProviders,
    ) {
        $this->logger = $logger;
        $this->parseFilepathAction = $parseFilepathAction;
        array_walk(
            array: $pipelineConfigurationFilepaths,
            callback: [$this, 'addPipelineConfigurationFilepath'],
        );
        array_walk(
            array: $pipelineConfigurationOverridesFilepathsProviders,
            callback: [$this, 'addPipelineConfigurationOverridesFilepathsProvider'],
        );
    }

    /**
     * @return string[]
     */
    public function getConfiguredIdentifiers(): array
    {
        return array_keys($this->pipelineConfigurationFilepaths);
    }

    /**
     * @param string $identifier
     *
     * @return string
     * @throws NotFoundException
     */
    public function getPipelineConfigurationFilepathByIdentifier(string $identifier): string
    {
        $pipelineConfigurationFilepath = $this->pipelineConfigurationFilepaths[$identifier] ?? null;

        if (null === $pipelineConfigurationFilepath) {
            throw new NotFoundException(
                __(
                    'No configuration filepath configured for identifier %1',
                    $identifier,
                ),
            );
        }

        return $this->parseFilepathAction->execute(
            filepath: $pipelineConfigurationFilepath,
        );
    }

    /**
     * @param string $identifier
     *
     * @return string[]
     */
    public function getPipelineConfigurationOverridesFilepathsByIdentifier(string $identifier): array
    {
        $pipelineConfigurationOverridesFilepathsProvider = $this->pipelineConfigurationOverridesFilepathProviders[$identifier] ?? null; // phpcs:ignore Generic.Files.LineLength.TooLong

        if (null === $pipelineConfigurationOverridesFilepathsProvider) {
            $this->logger->info(
                message: 'No configuration overrides filepaths provider configured for identifier {pipelineIdentifier}',
                context: [
                    'method' => __METHOD__,
                    'pipelineIdentifier' => $identifier,
                ],
            );
        }

        return $pipelineConfigurationOverridesFilepathsProvider?->get() ?? [];
    }

    /**
     * @param string $pipelineConfigurationFilepath
     * @param string $identifier
     *
     * @return void
     */
    private function addPipelineConfigurationFilepath(
        string $pipelineConfigurationFilepath,
        string $identifier,
    ): void {
        $this->pipelineConfigurationFilepaths[$identifier] = $pipelineConfigurationFilepath;
    }

    /**
     * @param PipelineConfigurationOverridesFilepathsProviderInterface $pipelineConfigurationOverridesFilepathsProvider
     * @param string $identifier
     *
     * @return void
     */
    private function addPipelineConfigurationOverridesFilepathsProvider(
        PipelineConfigurationOverridesFilepathsProviderInterface $pipelineConfigurationOverridesFilepathsProvider,
        string $identifier,
    ): void {
        $this->pipelineConfigurationOverridesFilepathProviders[$identifier] = $pipelineConfigurationOverridesFilepathsProvider; // phpcs:ignore Generic.Files.LineLength.TooLong
    }
}
