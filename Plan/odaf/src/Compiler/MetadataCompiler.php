<?php

declare(strict_types=1);

namespace Odaf\Compiler;

use Odaf\Compiler\Contracts\CompilationException;
use Odaf\Compiler\Contracts\CompilerDiagnosticInterface;
use Odaf\Compiler\Contracts\MetadataCompilerInterface;
use Odaf\Compiler\Contracts\RuntimePackageInterface;
use Odaf\Compiler\Diagnostic\CompilerDiagnostic;
use Odaf\Metadata\ApplicationGraph;
use Odaf\Metadata\Contracts\MetadataObjectInterface;
use Odaf\Support\Identity\IdentityGeneratorInterface;

/**
 * BB-02 Metadata Compiler.
 *
 * Pipeline: parse (graph sudah dimuat repository) -> validate (semantic) ->
 * build MIR -> emit Runtime Package deterministik.
 *
 * Determinisme (CORE-005): payload dinormalisasi (kunci terurut, tanpa timestamp)
 * sehingga metadata identik menghasilkan checksum identik.
 */
final class MetadataCompiler implements MetadataCompilerInterface
{
    public const COMPILER_VERSION = '1.0.0';

    public function __construct(
        private readonly IdentityGeneratorInterface $identity,
    ) {}

    public function compile(MetadataObjectInterface $applicationGraph): RuntimePackageInterface
    {
        $graph = $this->asGraph($applicationGraph);
        $diagnostics = $this->analyze($graph);

        $errors = array_filter(
            $diagnostics,
            static fn (CompilerDiagnosticInterface $d): bool => $d->severity() === CompilerDiagnosticInterface::SEVERITY_ERROR,
        );

        if ($errors !== []) {
            throw new CompilationException(
                sprintf('Kompilasi gagal: %d error metadata.', count($errors)),
                array_values($diagnostics),
            );
        }

        $payload = $this->buildPayload($graph);
        $checksum = hash('sha256', RuntimePackage::canonicalJson($payload));
        $version = sprintf('%d.%s', $graph->versionNo(), substr($checksum, 0, 12));

        return new RuntimePackage(
            packageId: $this->identity->generate(),
            packageVersion: $version,
            applicationId: $graph->objectId(),
            checksum: $checksum,
            payload: $payload,
        );
    }

    /**
     * @return array<int, CompilerDiagnosticInterface>
     */
    public function validate(MetadataObjectInterface $applicationGraph): array
    {
        return $this->analyze($this->asGraph($applicationGraph));
    }

    private function asGraph(MetadataObjectInterface $object): ApplicationGraph
    {
        if (! $object instanceof ApplicationGraph) {
            throw new CompilationException(
                'Compiler membutuhkan ApplicationGraph (hasil loadApplicationGraph).',
            );
        }

        return $object;
    }

    // ---- Semantic analysis --------------------------------------------------

    /**
     * @return array<int, CompilerDiagnosticInterface>
     */
    private function analyze(ApplicationGraph $graph): array
    {
        $diagnostics = [];

        $datasetIds = $this->idSet($graph->datasets());
        $pageIds = $this->idSet($graph->pages());
        $lovIds = $this->idSet($graph->lovs());

        if ($graph->objectCode() === '') {
            $diagnostics[] = CompilerDiagnostic::error(
                'ODAF-CMP-1000',
                'Aplikasi tidak memiliki OBJECT_CODE.',
                $graph->objectId(),
            );
        }

        // Halaman: dataset referensinya harus ada; FORM/GRID wajib punya dataset.
        foreach ($graph->pages() as $page) {
            $pageId = (string) ($page['OBJECT_ID'] ?? '');
            $datasetId = (string) ($page['DATASET_ID'] ?? '');
            $pageType = (string) ($page['PAGE_TYPE'] ?? '');

            if (in_array($pageType, ['FORM', 'GRID'], true) && $datasetId === '') {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1101',
                    sprintf('Halaman %s bertipe %s tetapi tidak memiliki dataset.', $page['OBJECT_CODE'] ?? $pageId, $pageType),
                    $pageId,
                );
            } elseif ($datasetId !== '' && ! isset($datasetIds[$datasetId])) {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1102',
                    sprintf('Halaman %s mereferensi dataset tak dikenal (%s).', $page['OBJECT_CODE'] ?? $pageId, $datasetId),
                    $pageId,
                );
            }

            if (count($graph->fieldsForPage($pageId)) === 0 && $pageType !== 'DASHBOARD') {
                $diagnostics[] = CompilerDiagnostic::warning(
                    'ODAF-CMP-2101',
                    sprintf('Halaman %s tidak memiliki field.', $page['OBJECT_CODE'] ?? $pageId),
                    $pageId,
                );
            }
        }

        // Field: harus menunjuk halaman valid & LOV valid (bila ada); wajib COLUMN_NAME.
        foreach ($graph->fields() as $field) {
            $fieldId = (string) ($field['OBJECT_ID'] ?? '');
            $pageId = (string) ($field['PAGE_ID'] ?? '');
            $lovId = (string) ($field['LOV_ID'] ?? '');

            if (! isset($pageIds[$pageId])) {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1201',
                    sprintf('Field %s menunjuk halaman tak dikenal.', $field['OBJECT_CODE'] ?? $fieldId),
                    $fieldId,
                );
            }

            if ((string) ($field['COLUMN_NAME'] ?? '') === '') {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1202',
                    sprintf('Field %s tidak memiliki COLUMN_NAME.', $field['OBJECT_CODE'] ?? $fieldId),
                    $fieldId,
                );
            }

            if ($lovId !== '' && ! isset($lovIds[$lovId])) {
                $diagnostics[] = CompilerDiagnostic::warning(
                    'ODAF-CMP-2201',
                    sprintf('Field %s mereferensi LOV tak dimuat (%s).', $field['OBJECT_CODE'] ?? $fieldId, $lovId),
                    $fieldId,
                );
            }
        }

        // Menu: PAGE_ID (bila ada) harus valid; deteksi siklus parent.
        foreach ($graph->menus() as $menu) {
            $menuId = (string) ($menu['OBJECT_ID'] ?? '');
            $pageId = (string) ($menu['PAGE_ID'] ?? '');
            if ($pageId !== '' && ! isset($pageIds[$pageId])) {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1301',
                    sprintf('Menu %s membuka halaman tak dikenal (%s).', $menu['OBJECT_CODE'] ?? $menuId, $pageId),
                    $menuId,
                );
            }
        }

        if ($this->hasMenuCycle($graph->menus())) {
            $diagnostics[] = CompilerDiagnostic::error(
                'ODAF-CMP-1302',
                'Terdapat siklus pada hierarki menu (PARENT_MENU_ID).',
                $graph->objectId(),
            );
        }

        // Dataset: sumber TABLE/VIEW wajib SOURCE_OBJECT; SQL wajib SOURCE_QUERY; wajib PK.
        foreach ($graph->datasets() as $ds) {
            $dsId = (string) ($ds['OBJECT_ID'] ?? '');
            $sourceType = (string) ($ds['SOURCE_TYPE'] ?? '');
            if (in_array($sourceType, ['TABLE', 'VIEW'], true) && (string) ($ds['SOURCE_OBJECT'] ?? '') === '') {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1401',
                    sprintf('Dataset %s (%s) tanpa SOURCE_OBJECT.', $ds['OBJECT_CODE'] ?? $dsId, $sourceType),
                    $dsId,
                );
            }
            if ($sourceType === 'SQL' && (string) ($ds['SOURCE_QUERY'] ?? '') === '') {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1402',
                    sprintf('Dataset %s bertipe SQL tanpa SOURCE_QUERY.', $ds['OBJECT_CODE'] ?? $dsId),
                    $dsId,
                );
            }
            if ((string) ($ds['PRIMARY_KEY_COLUMN'] ?? '') === '') {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1403',
                    sprintf('Dataset %s tanpa PRIMARY_KEY_COLUMN.', $ds['OBJECT_CODE'] ?? $dsId),
                    $dsId,
                );
            }
        }

        // Rule: dataset & field referensi harus valid.
        $fieldIds = $this->idSet($graph->fields());
        foreach ($graph->rules() as $rule) {
            $ruleId = (string) ($rule['OBJECT_ID'] ?? '');
            $dsId = (string) ($rule['DATASET_ID'] ?? '');
            $fieldId = (string) ($rule['FIELD_ID'] ?? '');
            if ($dsId !== '' && ! isset($datasetIds[$dsId])) {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1501',
                    sprintf('Rule %s mereferensi dataset tak dikenal.', $rule['OBJECT_CODE'] ?? $ruleId),
                    $ruleId,
                );
            }
            if ($fieldId !== '' && ! isset($fieldIds[$fieldId])) {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1502',
                    sprintf('Rule %s mereferensi field tak dikenal.', $rule['OBJECT_CODE'] ?? $ruleId),
                    $ruleId,
                );
            }
        }

        // Workflow: integritas graph (Vol.2 Bab 15 §19 WF-002..005).
        foreach ($graph->workflows() as $wf) {
            $this->analyzeWorkflow($graph, $wf, $datasetIds, $diagnostics);
        }

        // Notification: subscription harus mereferensi notifikasi valid & lengkap.
        $notificationIds = $this->idSet($graph->notifications());
        foreach ($graph->subscriptions() as $sub) {
            $subId = (string) ($sub['OBJECT_ID'] ?? '');
            $subCode = (string) ($sub['OBJECT_CODE'] ?? $subId);
            $notifId = (string) ($sub['NOTIFICATION_ID'] ?? '');
            if (! isset($notificationIds[$notifId])) {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1701',
                    sprintf('Subscription %s mereferensi notifikasi tak dikenal.', $subCode),
                    $subId,
                );
            }
            if ((string) ($sub['EVENT_CODE'] ?? '') === '') {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1702',
                    sprintf('Subscription %s tanpa EVENT_CODE.', $subCode),
                    $subId,
                );
            }
            $recipientType = (string) ($sub['RECIPIENT_TYPE'] ?? '');
            if (in_array($recipientType, ['ROLE', 'USER'], true) && (string) ($sub['RECIPIENT_REF'] ?? '') === '') {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1703',
                    sprintf('Subscription %s bertipe %s tanpa RECIPIENT_REF.', $subCode, $recipientType),
                    $subId,
                );
            }
        }

        return array_values($diagnostics);
    }

    /**
     * Validasi graph sebuah workflow.
     *
     * @param  array<string, mixed>  $wf
     * @param  array<string, bool>  $datasetIds
     * @param  array<int, CompilerDiagnosticInterface>  $diagnostics  (by-ref)
     */
    private function analyzeWorkflow(ApplicationGraph $graph, array $wf, array $datasetIds, array &$diagnostics): void
    {
        $wfId = (string) $wf['OBJECT_ID'];
        $wfCode = (string) ($wf['OBJECT_CODE'] ?? $wfId);
        $datasetId = (string) ($wf['DATASET_ID'] ?? '');

        if ($datasetId === '' || ! isset($datasetIds[$datasetId])) {
            $diagnostics[] = CompilerDiagnostic::error(
                'ODAF-CMP-1601',
                sprintf('Workflow %s mengatur dataset tak dikenal.', $wfCode),
                $wfId,
            );
        }

        $activities = $graph->activitiesForWorkflow($wfId);
        $transitions = $graph->transitionsForWorkflow($wfId);

        $activityIds = [];
        $initialIds = [];
        $finalIds = [];
        foreach ($activities as $act) {
            $id = (string) $act['OBJECT_ID'];
            $activityIds[$id] = true;
            if ((int) ($act['INITIAL_FLAG'] ?? 0) === 1) {
                $initialIds[] = $id;
            }
            if ((int) ($act['FINAL_FLAG'] ?? 0) === 1) {
                $finalIds[] = $id;
            }
        }

        if ($activities === []) {
            $diagnostics[] = CompilerDiagnostic::error(
                'ODAF-CMP-1602',
                sprintf('Workflow %s tidak memiliki activity.', $wfCode),
                $wfId,
            );

            return;
        }

        // WF-003: minimal satu start.
        if ($initialIds === []) {
            $diagnostics[] = CompilerDiagnostic::error(
                'ODAF-CMP-1603',
                sprintf('Workflow %s tidak memiliki activity awal (INITIAL_FLAG).', $wfCode),
                $wfId,
            );
        } elseif (count($initialIds) > 1) {
            $diagnostics[] = CompilerDiagnostic::warning(
                'ODAF-CMP-2603',
                sprintf('Workflow %s memiliki lebih dari satu activity awal.', $wfCode),
                $wfId,
            );
        }

        // WF-004: minimal satu end.
        if ($finalIds === []) {
            $diagnostics[] = CompilerDiagnostic::error(
                'ODAF-CMP-1604',
                sprintf('Workflow %s tidak memiliki activity akhir (FINAL_FLAG).', $wfCode),
                $wfId,
            );
        }

        // Transisi: referensi activity harus valid & dalam workflow yang sama.
        $adjacency = [];
        foreach ($transitions as $tr) {
            $trCode = (string) ($tr['OBJECT_CODE'] ?? $tr['OBJECT_ID']);
            $from = (string) ($tr['FROM_ACTIVITY_ID'] ?? '');
            $to = (string) ($tr['TO_ACTIVITY_ID'] ?? '');
            if (! isset($activityIds[$from]) || ! isset($activityIds[$to])) {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1605',
                    sprintf('Transisi %s mereferensi activity di luar workflow %s.', $trCode, $wfCode),
                    (string) $tr['OBJECT_ID'],
                );

                continue;
            }
            if ((string) ($tr['ACTION_CODE'] ?? '') === '') {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1606',
                    sprintf('Transisi %s tanpa ACTION_CODE.', $trCode),
                    (string) $tr['OBJECT_ID'],
                );
            }
            $adjacency[$from][] = $to;
        }

        // WF-002 / WF-004: seluruh activity & satu end terjangkau dari start.
        if ($initialIds !== []) {
            $reachable = $this->reachableFrom($initialIds[0], $adjacency);
            foreach ($activityIds as $id => $_) {
                if (! isset($reachable[$id])) {
                    $act = $this->activityById($activities, $id);
                    $diagnostics[] = CompilerDiagnostic::warning(
                        'ODAF-CMP-2602',
                        sprintf('Activity %s pada workflow %s tidak terjangkau dari awal.',
                            (string) ($act['OBJECT_CODE'] ?? $id), $wfCode),
                        $id,
                    );
                }
            }

            $endReachable = false;
            foreach ($finalIds as $fid) {
                if (isset($reachable[$fid])) {
                    $endReachable = true;
                    break;
                }
            }
            if ($finalIds !== [] && ! $endReachable) {
                $diagnostics[] = CompilerDiagnostic::error(
                    'ODAF-CMP-1607',
                    sprintf('Workflow %s: activity akhir tidak terjangkau dari awal.', $wfCode),
                    $wfId,
                );
            }
        }
    }

    /**
     * BFS: himpunan activity yang terjangkau dari sebuah simpul.
     *
     * @param  array<string, array<int, string>>  $adjacency
     * @return array<string, bool>
     */
    private function reachableFrom(string $start, array $adjacency): array
    {
        $seen = [$start => true];
        $queue = [$start];
        while ($queue !== []) {
            $node = array_shift($queue);
            foreach ($adjacency[$node] ?? [] as $next) {
                if (! isset($seen[$next])) {
                    $seen[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        return $seen;
    }

    /**
     * @param  array<int, array<string, mixed>>  $activities
     * @return array<string, mixed>
     */
    private function activityById(array $activities, string $id): array
    {
        foreach ($activities as $act) {
            if ((string) $act['OBJECT_ID'] === $id) {
                return $act;
            }
        }

        return [];
    }

    /**
     * @param  array<int, array<string, mixed>>  $menus
     */
    private function hasMenuCycle(array $menus): bool
    {
        $parent = [];
        foreach ($menus as $m) {
            $id = (string) ($m['OBJECT_ID'] ?? '');
            $parent[$id] = (string) ($m['PARENT_MENU_ID'] ?? '');
        }

        foreach ($parent as $start => $_) {
            $seen = [];
            $cur = $start;
            while ($cur !== '' && isset($parent[$cur])) {
                if (isset($seen[$cur])) {
                    return true;
                }
                $seen[$cur] = true;
                $cur = $parent[$cur];
            }
        }

        return false;
    }

    // ---- MIR / payload emission --------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(ApplicationGraph $graph): array
    {
        return [
            'compilerVersion' => self::COMPILER_VERSION,
            'application' => [
                'id' => $graph->objectId(),
                'code' => $graph->objectCode(),
                'name' => $graph->objectName(),
                'versionNo' => $graph->versionNo(),
            ],
            'menus' => $this->buildMenuTree($graph),
            'pages' => $this->buildPages($graph),
            'datasets' => $this->buildDatasets($graph),
            'rules' => $this->buildRules($graph),
            'lovs' => $this->buildLovs($graph),
            'workflows' => $this->buildWorkflows($graph),
            'notifications' => $this->buildNotifications($graph),
            'subscriptions' => $this->buildSubscriptions($graph),
            'index' => $this->buildIndex($graph),
        ];
    }

    /**
     * Kompilasi definisi notifikasi (template + channel).
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildNotifications(ApplicationGraph $graph): array
    {
        $notifications = [];
        foreach ($graph->notifications() as $n) {
            $id = (string) $n['OBJECT_ID'];
            $notifications[$id] = [
                'id' => $id,
                'code' => (string) $n['OBJECT_CODE'],
                'name' => (string) $n['OBJECT_NAME'],
                'channel' => (string) ($n['DEFAULT_CHANNEL'] ?? 'IN_APP'),
                'subjectTemplate' => (string) ($n['SUBJECT_TEMPLATE'] ?? ''),
                'bodyTemplate' => (string) ($n['BODY_TEMPLATE'] ?? ''),
            ];
        }

        return $notifications;
    }

    /**
     * Kompilasi subscription (event -> notifikasi + aturan penerima).
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildSubscriptions(ApplicationGraph $graph): array
    {
        $subscriptions = [];
        foreach ($graph->subscriptions() as $s) {
            $subscriptions[] = [
                'id' => (string) $s['OBJECT_ID'],
                'code' => (string) $s['OBJECT_CODE'],
                'eventCode' => (string) $s['EVENT_CODE'],
                'notificationId' => (string) $s['NOTIFICATION_ID'],
                'recipientType' => (string) $s['RECIPIENT_TYPE'],
                'recipientRef' => ($s['RECIPIENT_REF'] ?? '') !== '' ? (string) $s['RECIPIENT_REF'] : null,
                'channel' => ($s['CHANNEL'] ?? '') !== '' ? (string) $s['CHANNEL'] : null,
                'order' => (int) ($s['DISPLAY_ORDER'] ?? 0),
            ];
        }
        usort($subscriptions, static fn (array $a, array $b): int => [$a['order'], $a['code']] <=> [$b['order'], $b['code']]);

        return $subscriptions;
    }

    /**
     * Kompilasi definisi workflow menjadi graph eksekusi immutable (BB-06).
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildWorkflows(ApplicationGraph $graph): array
    {
        $workflows = [];
        foreach ($graph->workflows() as $wf) {
            $wfId = (string) $wf['OBJECT_ID'];

            $activities = [];
            $initialId = null;
            foreach ($graph->activitiesForWorkflow($wfId) as $act) {
                $actId = (string) $act['OBJECT_ID'];
                $isInitial = (int) ($act['INITIAL_FLAG'] ?? 0) === 1;
                if ($isInitial && $initialId === null) {
                    $initialId = $actId;
                }
                $activities[$actId] = [
                    'id' => $actId,
                    'code' => (string) $act['OBJECT_CODE'],
                    'name' => (string) $act['OBJECT_NAME'],
                    'type' => (string) ($act['ACTIVITY_TYPE'] ?? 'TASK'),
                    'initial' => $isInitial,
                    'final' => (int) ($act['FINAL_FLAG'] ?? 0) === 1,
                    'order' => (int) ($act['DISPLAY_ORDER'] ?? 0),
                ];
            }

            $transitions = [];
            foreach ($graph->transitionsForWorkflow($wfId) as $tr) {
                $transitions[] = [
                    'id' => (string) $tr['OBJECT_ID'],
                    'code' => (string) $tr['OBJECT_CODE'],
                    'label' => (string) $tr['OBJECT_NAME'],
                    'action' => (string) $tr['ACTION_CODE'],
                    'fromId' => (string) $tr['FROM_ACTIVITY_ID'],
                    'toId' => (string) $tr['TO_ACTIVITY_ID'],
                    'condition' => $tr['CONDITION_EXPR'] ?? null,
                    'requiredRoleId' => ($tr['REQUIRED_ROLE_ID'] ?? '') !== '' ? (string) $tr['REQUIRED_ROLE_ID'] : null,
                    'order' => (int) ($tr['DISPLAY_ORDER'] ?? 0),
                ];
            }
            usort($transitions, static fn (array $a, array $b): int => [$a['order'], $a['code']] <=> [$b['order'], $b['code']]);

            $workflows[$wfId] = [
                'id' => $wfId,
                'code' => (string) $wf['OBJECT_CODE'],
                'name' => (string) $wf['OBJECT_NAME'],
                'datasetId' => (string) $wf['DATASET_ID'],
                'stateColumn' => ($wf['STATE_COLUMN'] ?? '') !== '' ? strtoupper((string) $wf['STATE_COLUMN']) : null,
                'initialActivityId' => $initialId,
                'activities' => $activities,
                'transitions' => $transitions,
            ];
        }

        return $workflows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildMenuTree(ApplicationGraph $graph): array
    {
        $byParent = [];
        foreach ($graph->menus() as $menu) {
            if ((int) ($menu['VISIBLE_FLAG'] ?? 1) !== 1) {
                continue;
            }
            $parent = (string) ($menu['PARENT_MENU_ID'] ?? '');
            $byParent[$parent][] = $menu;
        }

        return $this->menuChildren($byParent, '');
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $byParent
     * @return array<int, array<string, mixed>>
     */
    private function menuChildren(array $byParent, string $parentId): array
    {
        $items = $byParent[$parentId] ?? [];
        usort($items, static fn (array $a, array $b): int => [(int) ($a['DISPLAY_ORDER'] ?? 0), (string) $a['OBJECT_CODE']]
            <=> [(int) ($b['DISPLAY_ORDER'] ?? 0), (string) $b['OBJECT_CODE']]);

        $out = [];
        foreach ($items as $menu) {
            $id = (string) $menu['OBJECT_ID'];
            $out[] = [
                'id' => $id,
                'code' => (string) $menu['OBJECT_CODE'],
                'name' => (string) $menu['OBJECT_NAME'],
                'icon' => $menu['ICON'] ?? null,
                'status' => (string) ($menu['STATUS'] ?? 'PUBLISHED'),
                'order' => (int) ($menu['DISPLAY_ORDER'] ?? 0),
                'pageId' => ($menu['PAGE_ID'] ?? '') !== '' ? (string) $menu['PAGE_ID'] : null,
                'children' => $this->menuChildren($byParent, $id),
            ];
        }

        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildPages(ApplicationGraph $graph): array
    {
        $pages = [];
        $byCode = [];
        $detailRaw = [];
        foreach ($graph->pages() as $page) {
            $pageId = (string) $page['OBJECT_ID'];
            $fields = [];
            foreach ($graph->fieldsForPage($pageId) as $field) {
                if ((int) ($field['VISIBLE_FLAG'] ?? 1) !== 1) {
                    continue;
                }
                $fields[] = [
                    'id' => (string) $field['OBJECT_ID'],
                    'code' => (string) $field['OBJECT_CODE'],
                    'label' => (string) ($field['LABEL'] ?? $field['OBJECT_NAME']),
                    'column' => (string) $field['COLUMN_NAME'],
                    'fieldType' => (string) $field['FIELD_TYPE'],
                    'dataType' => (string) ($field['DATA_TYPE'] ?? 'STRING'),
                    'order' => (int) ($field['DISPLAY_ORDER'] ?? 0),
                    'required' => (int) ($field['REQUIRED_FLAG'] ?? 0) === 1,
                    'readonly' => (int) ($field['READONLY_FLAG'] ?? 0) === 1,
                    'defaultValue' => $field['DEFAULT_VALUE'] ?? null,
                    'status' => (string) ($field['STATUS'] ?? 'PUBLISHED'),
                    'lovId' => ($field['LOV_ID'] ?? '') !== '' ? (string) $field['LOV_ID'] : null,
                    'lovLabelColumn' => ($field['LOV_LABEL_COLUMN'] ?? '') !== '' ? strtoupper((string) $field['LOV_LABEL_COLUMN']) : null,
                    'config' => $this->parseFieldConfig($field['FIELD_CONFIG'] ?? null),
                ];
            }

            usort($fields, static fn (array $a, array $b): int => [$a['order'], $a['code']] <=> [$b['order'], $b['code']]);

            $code = (string) $page['OBJECT_CODE'];
            $pages[$pageId] = [
                'id' => $pageId,
                'code' => $code,
                'name' => (string) $page['OBJECT_NAME'],
                'status' => (string) ($page['STATUS'] ?? 'PUBLISHED'),
                'title' => (string) ($page['TITLE'] ?? $page['OBJECT_NAME']),
                'pageType' => (string) $page['PAGE_TYPE'],
                'layout' => (string) ($page['LAYOUT_TYPE'] ?? 'SINGLE_COLUMN'),
                'datasetId' => ($page['DATASET_ID'] ?? '') !== '' ? (string) $page['DATASET_ID'] : null,
                'fields' => $fields,
                'details' => [],
            ];
            $byCode[$code] = $pageId;
            $detailRaw[$pageId] = $page['DETAIL_CONFIG'] ?? null;
        }

        // Pass kedua: resolve grid detail (header-detail) dari DETAIL_CONFIG.
        foreach ($detailRaw as $pageId => $raw) {
            $pages[$pageId]['details'] = $this->buildPageDetails($raw, $pages, $byCode);
        }

        return $pages;
    }

    /**
     * Bangun daftar grid detail untuk sebuah page dari DETAIL_CONFIG (JSON array).
     * Tiap item: {pageCode, fkColumn, title}. Field grid = field page anak
     * dikurangi kolom FK & STATUS.
     *
     * @param  array<string, array<string, mixed>>  $pages
     * @param  array<string, string>  $byCode
     * @return array<int, array<string, mixed>>
     */
    private function buildPageDetails(mixed $raw, array $pages, array $byCode): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $config = json_decode((string) $raw, true);
        if (! is_array($config)) {
            return [];
        }

        $details = [];
        foreach ($config as $d) {
            if (! is_array($d)) {
                continue;
            }
            $childCode = (string) ($d['pageCode'] ?? '');
            $fk = strtoupper((string) ($d['fkColumn'] ?? ''));
            if ($childCode === '' || $fk === '') {
                continue;
            }
            $childPageId = $byCode[$childCode] ?? null;
            if ($childPageId === null || ! isset($pages[$childPageId])) {
                continue;
            }
            $child = $pages[$childPageId];

            $childFields = array_values(array_filter(
                $child['fields'],
                static function (array $f) use ($fk): bool {
                    $c = strtoupper((string) $f['column']);
                    return $c !== $fk && $c !== 'STATUS';
                },
            ));

            $details[] = [
                'title' => (string) ($d['title'] ?? $child['title']),
                'pageCode' => $childCode,
                'datasetId' => $child['datasetId'],
                'fkColumn' => $fk,
                'fields' => $childFields,
            ];
        }

        return $details;
    }

    /**
     * Parse kolom UI_FIELD.FIELD_CONFIG (JSON) menjadi array config presentasi.
     * Mengembalikan array kosong bila null/invalid.
     *
     * @return array<string, mixed>
     */
    private function parseFieldConfig(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildDatasets(ApplicationGraph $graph): array
    {
        $datasets = [];
        foreach ($graph->datasets() as $ds) {
            $id = (string) $ds['OBJECT_ID'];
            $datasets[$id] = [
                'id' => $id,
                'code' => (string) $ds['OBJECT_CODE'],
                'name' => (string) $ds['OBJECT_NAME'],
                'sourceType' => (string) $ds['SOURCE_TYPE'],
                'sourceObject' => $ds['SOURCE_OBJECT'] ?? null,
                'sourceQuery' => $ds['SOURCE_QUERY'] ?? null,
                'primaryKey' => (string) ($ds['PRIMARY_KEY_COLUMN'] ?? ''),
                'softDelete' => (int) ($ds['SOFT_DELETE_FLAG'] ?? 0) === 1,
            ];
        }

        return $datasets;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function buildRules(ApplicationGraph $graph): array
    {
        // Peta FIELD_ID -> COLUMN_NAME agar runtime memetakan error ke kolom.
        $fieldColumn = [];
        foreach ($graph->fields() as $f) {
            $fieldColumn[(string) $f['OBJECT_ID']] = (string) $f['COLUMN_NAME'];
        }

        $rules = [];
        foreach ($graph->rules() as $rule) {
            $dsId = (string) ($rule['DATASET_ID'] ?? '');
            if ($dsId === '') {
                continue;
            }
            $fieldId = (string) ($rule['FIELD_ID'] ?? '');
            $rules[$dsId][] = [
                'id' => (string) $rule['OBJECT_ID'],
                'code' => (string) $rule['OBJECT_CODE'],
                'targetType' => (string) $rule['TARGET_TYPE'],
                'ruleType' => (string) $rule['RULE_TYPE'],
                'expression' => $rule['RULE_EXPRESSION'] ?? null,
                'message' => $rule['ERROR_MESSAGE'] ?? null,
                'fieldId' => $fieldId !== '' ? $fieldId : null,
                'column' => $fieldColumn[$fieldId] ?? null,
                'order' => (int) ($rule['DISPLAY_ORDER'] ?? 0),
            ];
        }

        foreach ($rules as &$list) {
            usort($list, static fn (array $a, array $b): int => [$a['order'], $a['code']] <=> [$b['order'], $b['code']]);
        }
        unset($list);

        return $rules;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function buildLovs(ApplicationGraph $graph): array
    {
        $lovs = [];
        foreach ($graph->lovs() as $lov) {
            $id = (string) $lov['OBJECT_ID'];
            $lovs[$id] = [
                'id' => $id,
                'code' => (string) $lov['OBJECT_CODE'],
                'lovType' => (string) $lov['LOV_TYPE'],
                'valueColumn' => $lov['VALUE_COLUMN'] ?? null,
                'labelColumn' => $lov['LABEL_COLUMN'] ?? null,
                'sourceQuery' => $lov['SOURCE_QUERY'] ?? null,
            ];
        }

        return $lovs;
    }

    /**
     * Indeks lookup by-code untuk resolusi cepat runtime.
     *
     * @return array<string, array<string, string>>
     */
    private function buildIndex(ApplicationGraph $graph): array
    {
        $menuByCode = [];
        foreach ($graph->menus() as $m) {
            $menuByCode[(string) $m['OBJECT_CODE']] = (string) $m['OBJECT_ID'];
        }
        $pageByCode = [];
        foreach ($graph->pages() as $p) {
            $pageByCode[(string) $p['OBJECT_CODE']] = (string) $p['OBJECT_ID'];
        }
        $datasetByCode = [];
        foreach ($graph->datasets() as $d) {
            $datasetByCode[(string) $d['OBJECT_CODE']] = (string) $d['OBJECT_ID'];
        }
        $workflowByDataset = [];
        foreach ($graph->workflows() as $wf) {
            $workflowByDataset[(string) $wf['DATASET_ID']] = (string) $wf['OBJECT_ID'];
        }
        // Indeks subscription per event: eventCode -> [posisi pada list subscriptions].
        $subscriptionsByEvent = [];
        $subscriptions = $this->buildSubscriptions($graph);
        foreach ($subscriptions as $i => $sub) {
            $subscriptionsByEvent[$sub['eventCode']][] = $i;
        }

        return [
            'menuByCode' => $menuByCode,
            'pageByCode' => $pageByCode,
            'datasetByCode' => $datasetByCode,
            'workflowByDataset' => $workflowByDataset,
            'subscriptionsByEvent' => $subscriptionsByEvent,
        ];
    }

    /**
     * Set OBJECT_ID -> true untuk pengecekan keberadaan.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, bool>
     */
    private function idSet(array $rows): array
    {
        $set = [];
        foreach ($rows as $row) {
            $id = (string) ($row['OBJECT_ID'] ?? '');
            if ($id !== '') {
                $set[$id] = true;
            }
        }

        return $set;
    }
}
