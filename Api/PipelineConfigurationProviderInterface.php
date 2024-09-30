<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Api;

use Magento\Framework\Exception\NotFoundException;

interface PipelineConfigurationProviderInterface
{
    /**
     * @return string[]
     */
    public function getConfiguredIdentifiers(): array;

    /**
     * @param string $identifier
     *
     * @return string
     * @throws NotFoundException
     */
    public function getPipelineConfigurationFilepathByIdentifier(string $identifier): string;

    /**
     * @param string $identifier
     *
     * @return string[]
     */
    public function getPipelineConfigurationOverridesFilepathsByIdentifier(string $identifier): array;
}
