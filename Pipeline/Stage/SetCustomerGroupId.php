<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PlatformPipelines\Pipeline\Stage;

use Klevu\Pipelines\Exception\Pipeline\InvalidPipelinePayloadException;
use Klevu\Pipelines\Pipeline\PipelineInterface;
use Klevu\Pipelines\Pipeline\StagesNotSupportedTrait;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class SetCustomerGroupId implements PipelineInterface
{
    use StagesNotSupportedTrait;

    /**
     * @var GroupRepositoryInterface
     */
    private readonly GroupRepositoryInterface $groupRepository;
    /**
     * @var CustomerSession
     */
    private readonly CustomerSession $customerSession;
    /**
     * @var string
     */
    private readonly string $identifier;

    /**
     * @param GroupRepositoryInterface $groupRepository
     * @param CustomerSession $customerSession
     * @param mixed[] $stages
     * @param string $identifier
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        CustomerSession $customerSession,
        array $stages = [],
        string $identifier = '',
    ) {
        $this->groupRepository = $groupRepository;
        $this->customerSession = $customerSession;
        array_walk($stages, [$this, 'addStage']);
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param mixed[] $args
     *
     * @return void
     */
    public function setArgs(
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        array $args,
    ): void {
        // intentionally left empty, stage does not accept arguments
    }

    /**
     * @param mixed $payload
     * @param \ArrayAccess<int|string, mixed>|null $context
     *
     * @return int|string
     */
    public function execute(
        mixed $payload,
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        ?\ArrayAccess $context = null,
    ): int | string {
        $this->validatePayload($payload);
        $this->customerSession->setCustomerGroupId($payload);

        return $payload;
    }

    /**
     * @param mixed $payload
     *
     * @return void
     * @throws InvalidPipelinePayloadException
     */
    private function validatePayload(mixed $payload): void
    {
        if ((!is_numeric($payload) || !ctype_digit((string)$payload))) {
            throw new InvalidPipelinePayloadException(
                pipelineName: $this::class,
                message: (string)__(
                    'Payload must be numeric (integer only); Received %1',
                    is_scalar($payload)
                        ? $payload
                        : get_debug_type($payload),
                ),
            );
        }

        $customerGroupId = (int)$payload;
        try {
            $this->groupRepository->getById(id: $customerGroupId);
        } catch (NoSuchEntityException | LocalizedException) {
            throw new InvalidPipelinePayloadException(
                pipelineName: $this::class,
                message: (string)__('No customer group exists for payload "%1"', $payload),
            );
        }
    }
}
