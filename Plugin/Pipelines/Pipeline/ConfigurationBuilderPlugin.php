<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Plugin\Pipelines\Pipeline;

use Klevu\IndexingApi\Service\Action\ParseFilepathActionInterface;
use Klevu\Pipelines\Pipeline\ConfigurationBuilder;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\View\Asset\Repository;

class ConfigurationBuilderPlugin
{
    /**
     * @var ParseFilepathActionInterface
     */
    private readonly ParseFilepathActionInterface $parseFilepathAction;

    /**
     * @param ParseFilepathActionInterface $parseFilepathAction
     */
    public function __construct(ParseFilepathActionInterface $parseFilepathAction)
    {
        $this->parseFilepathAction = $parseFilepathAction;
    }

    /**
     * @param ConfigurationBuilder $subject
     * @param string $importFilepath
     * @param string $baseDirectory
     *
     * @return string[]
     * @throws NotFoundException
     */
    public function beforeGetImportFilePath(
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        ConfigurationBuilder $subject,
        string $importFilepath,
        string $baseDirectory,
    ): array {
        if (str_contains(haystack: $importFilepath, needle: Repository::FILE_ID_SEPARATOR)) {
            $importFilepath = $this->parseFilepathAction->execute(filepath: $importFilepath);
        }

        return [$importFilepath, $baseDirectory];
    }
}
