<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Validator;

use Magento\Framework\Validator\AbstractValidator;

class ConfigurationOverridesContentValidator extends AbstractValidator
{
    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid(
        mixed $value, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): bool {
        // @todo
        return true;
    }
}
