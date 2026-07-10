<?php

declare(strict_types=1);

namespace Odaf\Engine\Workflow\Contracts;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-06 Workflow Engine (Vol.3 Bab 12).
 *
 * Mengeksekusi graph workflow terkompilasi (immutable) terhadap baris bisnis.
 * Logika bisnis tidak berada di dalam engine (WFE-002): engine hanya melakukan
 * orkestrasi state, penegakan transisi, dan pencatatan riwayat.
 *
 * F2.1: state machine approval (sequential). Parallel/kompensasi/timer menyusul.
 */
interface WorkflowEngineInterface
{
    /**
     * Apakah dataset diatur oleh sebuah workflow.
     */
    public function hasWorkflow(ExecutionContextInterface $context, string $datasetId): bool;

    /**
     * Mulai instance workflow untuk sebuah baris bisnis (idempoten).
     * Mengembalikan state awal. No-op bila instance sudah ada.
     */
    public function start(ExecutionContextInterface $context, string $datasetId, string $entityKey): ?WorkflowStateInterface;

    /**
     * State workflow saat ini untuk sebuah baris bisnis (null bila tak ada instance).
     */
    public function currentState(ExecutionContextInterface $context, string $datasetId, string $entityKey): ?WorkflowStateInterface;

    /**
     * Transisi yang tersedia bagi pengguna aktif dari state saat ini
     * (sudah difilter role & guard).
     *
     * @return array<int, array<string, mixed>>
     */
    public function availableTransitions(ExecutionContextInterface $context, string $datasetId, string $entityKey): array;

    /**
     * Jalankan sebuah transisi berdasarkan ACTION_CODE.
     *
     * @throws WorkflowException bila transisi tidak valid/diizinkan.
     */
    public function perform(
        ExecutionContextInterface $context,
        string $datasetId,
        string $entityKey,
        string $actionCode,
        ?string $comment = null,
    ): WorkflowStateInterface;

    /**
     * Riwayat transisi sebuah instance (terbaru dahulu).
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(ExecutionContextInterface $context, string $datasetId, string $entityKey): array;
}
