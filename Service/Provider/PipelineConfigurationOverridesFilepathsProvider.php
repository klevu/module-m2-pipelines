<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Service\Provider;

use Klevu\PlatformPipelines\Api\PipelineConfigurationOverridesFilepathsProviderInterface;
use Klevu\PlatformPipelines\Service\Action\ParseFilepathActionInterface;
use Magento\Framework\Exception\NotFoundException;

class PipelineConfigurationOverridesFilepathsProvider implements PipelineConfigurationOverridesFilepathsProviderInterface // phpcs:ignore Generic.Files.LineLength.TooLong
{
    /**
     * @var ParseFilepathActionInterface
     */
    private readonly ParseFilepathActionInterface $parseFilepathAction;
    /**
     * @var GeneratedConfigurationOverridesFilepathProviderInterface
     */
    private readonly GeneratedConfigurationOverridesFilepathProviderInterface $generatedConfigurationOverridesFilepathProvider; // phpcs:ignore Generic.Files.LineLength.TooLong
    /**
     * @var string[]
     */
    private array $pipelineConfigurationOverrideFilepaths = [];

    /**
     * @param ParseFilepathActionInterface $parseFilepathAction
     * @param GeneratedConfigurationOverridesFilepathProviderInterface $generatedConfigurationOverridesFilepathProvider
     * @param string[] $pipelineConfigurationOverrideFilepaths
     */
    public function __construct(
        ParseFilepathActionInterface $parseFilepathAction,
        GeneratedConfigurationOverridesFilepathProviderInterface $generatedConfigurationOverridesFilepathProvider,
        array $pipelineConfigurationOverrideFilepaths = [],
    ) {
        $this->parseFilepathAction = $parseFilepathAction;
        $this->generatedConfigurationOverridesFilepathProvider = $generatedConfigurationOverridesFilepathProvider;
        array_walk(
            array: $pipelineConfigurationOverrideFilepaths,
            callback: [$this, 'addPipelineConfigurationOverrideFilepath'],
        );
    }

    /**
     * @return string[]
     */
    public function get(): array
    {
        $filepaths = array_merge(
            $this->pipelineConfigurationOverrideFilepaths,
            [
                $this->generatedConfigurationOverridesFilepathProvider->get(),
            ],
        );

        return array_values(
            array: array_filter($filepaths),
        );
    }

    /**
     * @param string $filepath
     *
     * @return void
     * @throws NotFoundException
     */
    private function addPipelineConfigurationOverrideFilepath(string $filepath): void
    {
        $parsedFilepath = $this->parseFilepathAction->execute(
            filepath: $filepath,
        );
        if (!in_array($parsedFilepath, $this->pipelineConfigurationOverrideFilepaths, true)) {
            $this->pipelineConfigurationOverrideFilepaths[] = $parsedFilepath;
        }
    }
}
