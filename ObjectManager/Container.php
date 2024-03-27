<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\ObjectManager;

use Klevu\Pipelines\Exception\ObjectManager\ObjectInstantiationException;
use Klevu\Pipelines\ObjectManager\ObjectManagerInterface as KlevuObjectManagerInterface;
use Magento\Framework\ObjectManagerInterface as MagentoObjectManagerInterface;

class Container implements KlevuObjectManagerInterface
{
    /**
     * @var MagentoObjectManagerInterface
     */
    private readonly MagentoObjectManagerInterface $objectManager;
    /**
     * @var object[]
     */
    private array $sharedInstances = [];
    /**
     * @var string[]
     */
    private array $preferences = [];

    /**
     * @param MagentoObjectManagerInterface $objectManager
     * @param object[] $sharedInstances
     * @param string[] $preferences
     */
    public function __construct(
        MagentoObjectManagerInterface $objectManager,
        array $sharedInstances = [],
        array $preferences = [],
    ) {
        $this->objectManager = $objectManager;
        $this->sharedInstances = $sharedInstances;
        $this->preferences = array_map('strval', $preferences);
    }

    /**
     * @param string $id
     * @param mixed[] $constructorArgs
     * @return object
     * @throws ObjectInstantiationException
     */
    public function create(string $id, array $constructorArgs): object
    {
        $id = ltrim($id, ' \\');
        $type = $this->preferences[$id] ?? $id;

        try {
            $return = $this->objectManager->create(
                type: $type,
                arguments: $constructorArgs,
            );
        } catch (\LogicException | \BadMethodCallException $exception) {
            throw new ObjectInstantiationException(
                identifier: sprintf('%s [%s]', $id, $type),
                message: $exception->getMessage(),
                previous: $exception,
            );
        }

        return $return;
    }

    /**
     * @param string $id
     * @return object
     * @throws ObjectInstantiationException
     */
    public function get(string $id): object
    {
        if (array_key_exists($id, $this->sharedInstances)) {
            return $this->sharedInstances[$id];
        }

        $type = $this->preferences[$id] ?? $id;

        try {
            $return = $this->objectManager->get(
                type: $type,
            );
        } catch (\LogicException | \BadMethodCallException $exception) {
            throw new ObjectInstantiationException(
                identifier: sprintf('%s [%s]', $id, $type),
                message: $exception->getMessage(),
                previous: $exception,
            );
        }

        return $return;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(
        string $id, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): bool {
        // Magento's ObjectManager does not implement has()
        return true;
    }

    /**
     * @param string $identifier
     * @param object|null $instance
     * @return void
     */
    public function addSharedInstance(string $identifier, ?object $instance): void
    {
        if (null === $instance) {
            unset($this->sharedInstances[$identifier]);
            return;
        }

        $this->sharedInstances[$identifier] = $instance;
    }

    /**
     * @param string $namespace
     * @param int $sortOrder
     * @return void
     */
    public function registerNamespace(
        string $namespace, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        int $sortOrder = self::DEFAULT_NAMESPACE_SORT_ORDER, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter, Generic.Files.LineLength.TooLong
    ): void {
        // Not implemented
    }
}
