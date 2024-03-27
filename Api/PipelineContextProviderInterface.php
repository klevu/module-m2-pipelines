<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Api;

interface PipelineContextProviderInterface
{
    /**
     * @return mixed[]|object
     */
    public function get(): array|object;
}
