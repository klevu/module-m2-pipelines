<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Api;

interface PipelineConfigurationOverridesFilepathsProviderInterface
{
    /**
     * @return string[]
     */
    public function get(): array;
}
