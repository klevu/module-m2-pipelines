<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Api;

interface ConfigurationOverridesBuilderInterface
{
    public const PATH_PARTS_SEPARATOR = '.';

    /**
     * @param string $path
     * @param mixed[] $configuration
     *
     * @return void
     */
    public function addConfigurationByPath(
        string $path,
        array $configuration,
    ): void;

    /**
     * @return void
     */
    public function clear(): void;

    /**
     * @return mixed[]
     */
    public function build(): array;
}
