<?php

declare(strict_types=1);

namespace Odaf\Engine\Workflow;

use Odaf\Engine\Workflow\Contracts\WorkflowStateInterface;

/**
 * Implementasi snapshot state workflow.
 */
final class WorkflowState implements WorkflowStateInterface
{
    public function __construct(
        private readonly string $activityId,
        private readonly string $stateCode,
        private readonly string $stateName,
        private readonly string $instanceStatus,
        private readonly bool $final,
    ) {}

    public function activityId(): string
    {
        return $this->activityId;
    }

    public function stateCode(): string
    {
        return $this->stateCode;
    }

    public function stateName(): string
    {
        return $this->stateName;
    }

    public function instanceStatus(): string
    {
        return $this->instanceStatus;
    }

    public function isFinal(): bool
    {
        return $this->final;
    }
}
