<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Api;

use Klevu\PlatformPipelines\Exception\CouldNotGenerateConfigurationOverridesException;

interface ConfigurationOverridesHandlerInterface
{
    public const XML_PATH_CONFIGURATION_OVERRIDES_GENERATION_ENABLED = 'klevu/platform_pipelines/configuration_overrides_generation_enabled'; // phpcs:ignore Generic.Files.LineLength.TooLong

    /**
     * @return void
     * @throws CouldNotGenerateConfigurationOverridesException
     */
    public function execute(): void;
}
