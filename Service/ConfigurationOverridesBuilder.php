<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Service;

use Klevu\PlatformPipelines\Api\ConfigurationOverridesBuilderInterface;

class ConfigurationOverridesBuilder implements ConfigurationOverridesBuilderInterface
{
    /**
     * @var array<string, mixed[]>
     */
    private array $stagesConfigurationByPath = [];

    /**
     * @param string $path
     * @param mixed[] $configuration
     *
     * @return void
     */
    public function addConfigurationByPath(string $path, array $configuration): void
    {
        $this->stagesConfigurationByPath[$path] = $configuration;
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->stagesConfigurationByPath = [];
    }

    /**
     * @return mixed[]
     */
    public function build(): array
    {
        $return = [];
        foreach ($this->stagesConfigurationByPath as $stagesPath => $configuration) {
            $this->buildConfigurationByPath(
                path: $stagesPath,
                builtConfiguration: $return,
                configuration: $configuration,
            );
        }

        $this->clear();

        return $return;
    }

    /**
     * @param string $path
     * @param mixed[][] $builtConfiguration
     * @param mixed[] $configuration
     *
     * @return void
     */
    private function buildConfigurationByPath(
        string $path,
        array &$builtConfiguration, // phpcs:ignore SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference, Generic.Files,LineLength.TooLong
        array $configuration,
    ): void {
        $pathParts = explode(static::PATH_PARTS_SEPARATOR, $path);
        $currentPart = array_shift($pathParts);

        if (!$pathParts) {
            // We have run out of parts; set the config and leave
            $builtConfiguration[$currentPart] = $configuration;
            return;
        }

        // We still have more parts to process
        // Set the current part if not already present...
        $builtConfiguration[$currentPart] ??= [];
        // ...and recursively apply the rest of the parts until we run out
        $this->buildConfigurationByPath(
            path: implode(
                separator: static::PATH_PARTS_SEPARATOR,
                array: $pathParts,
            ),
            builtConfiguration: $builtConfiguration[$currentPart],
            configuration: $configuration,
        );
    }
}
