<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Api;

use Magento\Framework\Exception\LocalizedException;

interface GenerateConfigurationOverridesContentActionInterface
{
    /**
     * @return string
     * @throws LocalizedException
     */
    public function execute(): string;
}
