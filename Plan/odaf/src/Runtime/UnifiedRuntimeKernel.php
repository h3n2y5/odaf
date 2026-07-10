<?php

declare(strict_types=1);

namespace Odaf\Runtime;

use Odaf\Compiler\Contracts\RuntimePackageInterface;
use Odaf\Runtime\Contracts\ExecutionContextInterface;
use Odaf\Runtime\Contracts\RuntimeKernelInterface;
use RuntimeException;

/**
 * BB-03 Unified Runtime Kernel.
 *
 * Mengeksekusi Runtime Package terkompilasi (CORE-002: tidak menyentuh metadata
 * design-time). Menyediakan akses terstruktur ke objek runtime (menu, page,
 * dataset, rule) yang dipakai engine & renderer, serta resolusi service.
 *
 * Pipeline execute(): Locate Package -> Resolve Object -> Dispatch.
 */
final class UnifiedRuntimeKernel implements RuntimeKernelInterface
{
    /** @var array<string, RuntimePackageInterface> package aktif per applicationId */
    private array $packages = [];

    public function __construct(private readonly ServiceRegistry $registry) {}

    public function loadPackage(RuntimePackageInterface $package): void
    {
        $this->packages[strtoupper($package->applicationId())] = $package;
    }

    public function isLoaded(string $applicationId): bool
    {
        return isset($this->packages[strtoupper($applicationId)]);
    }

    public function resolve(string $serviceContract): object
    {
        return $this->registry->resolve($serviceContract);
    }

    /**
     * Eksekusi objek runtime. Untuk F1 mendukung pembukaan halaman:
     * mengembalikan model halaman terkompilasi (fields, dataset) untuk renderer.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(ExecutionContextInterface $context, string $runtimeObjectId, array $input = []): array
    {
        $page = $this->page($context->applicationId(), $runtimeObjectId);
        if ($page === null) {
            throw new RuntimeException("Objek runtime tidak ditemukan pada package: {$runtimeObjectId}");
        }

        return [
            'page' => $page,
            'dataset' => $page['datasetId'] !== null
                ? $this->dataset($context->applicationId(), $page['datasetId'])
                : null,
            'input' => $input,
        ];
    }

    // ---- Akses payload terkompilasi ----------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function payload(string $applicationId): array
    {
        return $this->package($applicationId)->toArray();
    }

    /**
     * Pohon menu terkompilasi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function menus(string $applicationId): array
    {
        return $this->payload($applicationId)['menus'] ?? [];
    }

    /**
     * Model halaman by OBJECT_ID.
     *
     * @return array<string, mixed>|null
     */
    public function page(string $applicationId, string $pageId): ?array
    {
        return $this->payload($applicationId)['pages'][$pageId] ?? null;
    }

    /**
     * Model halaman by OBJECT_CODE.
     *
     * @return array<string, mixed>|null
     */
    public function pageByCode(string $applicationId, string $pageCode): ?array
    {
        $payload = $this->payload($applicationId);
        $pageId = $payload['index']['pageByCode'][$pageCode] ?? null;

        return $pageId !== null ? ($payload['pages'][$pageId] ?? null) : null;
    }

    /**
     * Model dataset by OBJECT_ID.
     *
     * @return array<string, mixed>|null
     */
    public function dataset(string $applicationId, string $datasetId): ?array
    {
        return $this->payload($applicationId)['datasets'][$datasetId] ?? null;
    }

    /**
     * Aturan validasi terkompilasi untuk sebuah dataset.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rulesForDataset(string $applicationId, string $datasetId): array
    {
        return $this->payload($applicationId)['rules'][$datasetId] ?? [];
    }

    /**
     * Definisi workflow terkompilasi by OBJECT_ID.
     *
     * @return array<string, mixed>|null
     */
    public function workflow(string $applicationId, string $workflowId): ?array
    {
        return $this->payload($applicationId)['workflows'][$workflowId] ?? null;
    }

    /**
     * Workflow yang mengatur sebuah dataset (bila ada).
     *
     * @return array<string, mixed>|null
     */
    public function workflowForDataset(string $applicationId, string $datasetId): ?array
    {
        $payload = $this->payload($applicationId);
        $workflowId = $payload['index']['workflowByDataset'][$datasetId] ?? null;

        return $workflowId !== null ? ($payload['workflows'][$workflowId] ?? null) : null;
    }

    /**
     * Definisi notifikasi terkompilasi by OBJECT_ID.
     *
     * @return array<string, mixed>|null
     */
    public function notification(string $applicationId, string $notificationId): ?array
    {
        return $this->payload($applicationId)['notifications'][$notificationId] ?? null;
    }

    /**
     * Definisi LOV (List of Values) terkompilasi by OBJECT_ID.
     *
     * @return array<string, mixed>|null
     */
    public function lov(string $applicationId, string $lovId): ?array
    {
        return $this->payload($applicationId)['lovs'][$lovId] ?? null;
    }

    /**
     * Subscription yang berlangganan sebuah event code.
     *
     * @return array<int, array<string, mixed>>
     */
    public function subscriptionsForEvent(string $applicationId, string $eventCode): array
    {
        $payload = $this->payload($applicationId);
        $indices = $payload['index']['subscriptionsByEvent'][$eventCode] ?? [];
        $subscriptions = $payload['subscriptions'] ?? [];

        $out = [];
        foreach ($indices as $i) {
            if (isset($subscriptions[$i])) {
                $out[] = $subscriptions[$i];
            }
        }

        return $out;
    }

    private function package(string $applicationId): RuntimePackageInterface
    {
        $key = strtoupper($applicationId);
        if (! isset($this->packages[$key])) {
            throw new RuntimeException("Package runtime belum dimuat untuk aplikasi: {$applicationId}");
        }

        return $this->packages[$key];
    }
}
