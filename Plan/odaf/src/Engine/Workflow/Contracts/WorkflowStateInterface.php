<?php

declare(strict_types=1);

namespace Odaf\Engine\Workflow\Contracts;

/**
 * Snapshot state workflow sebuah baris bisnis.
 */
interface WorkflowStateInterface
{
    /** OBJECT_ID activity saat ini. */
    public function activityId(): string;

    /** Kode state saat ini (mis. DRAFT, PENDING_APPROVAL). */
    public function stateCode(): string;

    /** Nama tampilan state. */
    public function stateName(): string;

    /** Status instance: RUNNING | COMPLETED | CANCELLED. */
    public function instanceStatus(): string;

    /** Apakah state akhir (final). */
    public function isFinal(): bool;
}
