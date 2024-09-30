<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\ViewModel\Config\Information;

use Klevu\Configuration\ViewModel\Config\FieldsetInterface;
use Klevu\PlatformPipelines\Api\PipelineConfigurationProviderInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Phrase;

class PipelineConfiguration implements FieldsetInterface
{
    /**
     * @var PipelineConfigurationProviderInterface
     */
    private readonly PipelineConfigurationProviderInterface $pipelineConfigurationProvider;
    /**
     * @var string
     */
    private readonly string $pipelineIdentifier;

    /**
     * @param PipelineConfigurationProviderInterface $pipelineConfigurationProvider
     * @param string $pipelineIdentifier
     */
    public function __construct(
        PipelineConfigurationProviderInterface $pipelineConfigurationProvider,
        string $pipelineIdentifier,
    ) {
        $this->pipelineConfigurationProvider = $pipelineConfigurationProvider;
        $this->pipelineIdentifier = $pipelineIdentifier;
    }

    /**
     * @return string[]
     */
    public function getChildBlocks(): array
    {
        return [];
    }

    /**
     * @return Phrase[][]
     */
    public function getMessages(): array
    {
        return [];
    }

    /**
     * @return string
     */
    public function getStyles(): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getPipelineIdentifier(): string
    {
        return $this->pipelineIdentifier;
    }

    /**
     * @return string|null
     */
    public function getPipelineConfigurationFilepath(): ?string
    {
        try {
            $pipelineConfigurationFilepath = $this->pipelineConfigurationProvider
                ->getPipelineConfigurationFilepathByIdentifier(
                    identifier: $this->pipelineIdentifier,
                );
        } catch (NotFoundException) {
            $pipelineConfigurationFilepath = null;
        }

        return $pipelineConfigurationFilepath;
    }

    /**
     * @return string[]
     */
    public function getPipelineConfigurationOverridesFilepaths(): array
    {
        return $this->pipelineConfigurationProvider
            ->getPipelineConfigurationOverridesFilepathsByIdentifier(
                identifier: $this->pipelineIdentifier,
            );
    }
}
