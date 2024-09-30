<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Service\Provider;

interface GeneratedConfigurationOverridesFilepathProviderInterface
{
    /**
     * @return string|null
     */
    public function get(): ?string;
}
