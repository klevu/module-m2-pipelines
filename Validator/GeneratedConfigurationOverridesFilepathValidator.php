<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Validator;

use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Validator\AbstractValidator;

class GeneratedConfigurationOverridesFilepathValidator extends AbstractValidator
{
    /**
     * @var DirectoryList
     */
    private readonly DirectoryList $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(
        DirectoryList $directoryList,
    ) {
        $this->directoryList = $directoryList;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     * @throws FileSystemException
     */
    public function isValid(mixed $value): bool
    {
        $this->_clearMessages();

        if (null === $value) {
            return true;
        }
        if (!is_string($value)) {
            $this->_addMessages([
                __(
                    'Filepath must be of type string, received %1',
                    get_debug_type($value),
                )->render(),
            ]);

            return false;
        }

        $varFilepath = $this->directoryList->getPath(AppDirectoryList::VAR_DIR);

        if (!str_starts_with($value, $varFilepath)) {
            $this->_addMessages([
                __(
                    'Generated configuration filepath must live in var directory; received %1',
                    $value,
                )->render(),
            ]);

            return false;
        }

        return true;
    }
}
