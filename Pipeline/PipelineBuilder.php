<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Pipeline;

use Klevu\PhpSDKPipelines\Pipeline\PipelineBuilder as BasePipelineBuilder;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\ObjectManager\ObjectManagerInterface;
use Klevu\Pipelines\Pipeline\ConfigurationBuilder;
use Klevu\Pipelines\Pipeline\PipelineFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PipelineBuilder extends BasePipelineBuilder
{
    /**
     * @param ObjectManagerInterface $container
     * @param ConfigurationBuilder|null $configurationBuilder
     * @param ObjectManagerInterface|null $transformerManager
     * @param ObjectManagerInterface|null $validatorManager
     * @param PipelineFactoryInterface|null $pipelineFactory
     * @param string|null $defaultPipeline
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ObjectManagerInterface $container,
        ?ConfigurationBuilder $configurationBuilder = null,
        ?ObjectManagerInterface $transformerManager = null,
        ?ObjectManagerInterface $validatorManager = null,
        ?PipelineFactoryInterface $pipelineFactory = null,
        ?string $defaultPipeline = null,
    ) {
        Container::setInstance($container);

        parent::__construct(
            $configurationBuilder,
            $transformerManager,
            $validatorManager,
            $pipelineFactory,
            $defaultPipeline,
        );
    }

    /**
     * @param mixed[] $constructorArgs
     * @return mixed[]
     */
    protected function getProcessedConstructorArgs(
        array $constructorArgs,
    ): array {
        // Filter anything which is null to allow di.xml specified args (eg logger)
        //  to be used instead of passing <null> explicitly
        return array_filter(
            array: $constructorArgs,
            callback: static fn (mixed $value): bool => null !== $value,
        );
    }
}
