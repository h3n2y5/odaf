<?php

declare(strict_types=1);

namespace Tests\Support;

use Odaf\Metadata\ApplicationGraph;

/**
 * Pembuat fixture ApplicationGraph untuk pengujian compiler tanpa database.
 */
final class GraphFactory
{
    /**
     * Graph valid minimal: 1 aplikasi, 1 dataset, 1 halaman FORM + 2 field,
     * 1 menu, dan 1 rule REQUIRED.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function valid(array $overrides = []): ApplicationGraph
    {
        $application = array_merge([
            'OBJECT_ID' => 'APP0000000000000000000000000010',
            'OBJECT_CODE' => 'DEMO',
            'OBJECT_NAME' => 'Demo App',
            'VERSION_NO' => 1,
            'STATUS' => 'PUBLISHED',
        ], $overrides);

        $datasets = [[
            'OBJECT_ID' => 'DS00000000000000000000000000020',
            'OBJECT_CODE' => 'DS_CUSTOMER',
            'OBJECT_NAME' => 'Customer',
            'SOURCE_TYPE' => 'TABLE',
            'SOURCE_OBJECT' => 'CUSTOMER',
            'PRIMARY_KEY_COLUMN' => 'CUSTOMER_ID',
            'SOFT_DELETE_FLAG' => 1,
        ]];

        $pages = [[
            'OBJECT_ID' => 'PG00000000000000000000000000030',
            'APPLICATION_ID' => $application['OBJECT_ID'],
            'DATASET_ID' => 'DS00000000000000000000000000020',
            'OBJECT_CODE' => 'PAGE_CUSTOMER',
            'OBJECT_NAME' => 'Customer Form',
            'TITLE' => 'Customer',
            'PAGE_TYPE' => 'FORM',
            'LAYOUT_TYPE' => 'TWO_COLUMN',
        ]];

        $fields = [
            [
                'OBJECT_ID' => 'FD00000000000000000000000000031',
                'PAGE_ID' => 'PG00000000000000000000000000030',
                'OBJECT_CODE' => 'CUSTOMER_CODE',
                'OBJECT_NAME' => 'Code',
                'LABEL' => 'Kode',
                'COLUMN_NAME' => 'CUSTOMER_CODE',
                'FIELD_TYPE' => 'TEXT',
                'DATA_TYPE' => 'STRING',
                'DISPLAY_ORDER' => 10,
                'REQUIRED_FLAG' => 1,
                'VISIBLE_FLAG' => 1,
            ],
            [
                'OBJECT_ID' => 'FD00000000000000000000000000032',
                'PAGE_ID' => 'PG00000000000000000000000000030',
                'OBJECT_CODE' => 'CUSTOMER_NAME',
                'OBJECT_NAME' => 'Name',
                'LABEL' => 'Nama',
                'COLUMN_NAME' => 'CUSTOMER_NAME',
                'FIELD_TYPE' => 'TEXT',
                'DATA_TYPE' => 'STRING',
                'DISPLAY_ORDER' => 20,
                'REQUIRED_FLAG' => 1,
                'VISIBLE_FLAG' => 1,
            ],
        ];

        $menus = [[
            'OBJECT_ID' => 'MN00000000000000000000000000012',
            'MODULE_ID' => 'MD00000000000000000000000000011',
            'PARENT_MENU_ID' => '',
            'PAGE_ID' => 'PG00000000000000000000000000030',
            'OBJECT_CODE' => 'MENU_CUSTOMER',
            'OBJECT_NAME' => 'Customer',
            'DISPLAY_ORDER' => 10,
            'VISIBLE_FLAG' => 1,
        ]];

        $rules = [[
            'OBJECT_ID' => 'VR00000000000000000000000000041',
            'OBJECT_CODE' => 'VAL_CODE_REQ',
            'OBJECT_NAME' => 'Code Required',
            'TARGET_TYPE' => 'FIELD',
            'DATASET_ID' => 'DS00000000000000000000000000020',
            'FIELD_ID' => 'FD00000000000000000000000000031',
            'RULE_TYPE' => 'REQUIRED',
            'RULE_EXPRESSION' => null,
            'ERROR_MESSAGE' => 'Kode wajib diisi.',
            'DISPLAY_ORDER' => 0,
        ]];

        return new ApplicationGraph(
            application: $application,
            modules: [[
                'OBJECT_ID' => 'MD00000000000000000000000000011',
                'APPLICATION_ID' => $application['OBJECT_ID'],
                'OBJECT_CODE' => 'MASTER',
                'OBJECT_NAME' => 'Master',
                'DISPLAY_ORDER' => 10,
            ]],
            menus: $menus,
            pages: $pages,
            fields: $fields,
            datasets: $datasets,
            lovs: [],
            rules: $rules,
        );
    }

    /**
     * Graph valid + workflow approval untuk dataset Customer.
     *
     * DRAFT (initial) --SUBMIT--> PENDING (approval) --APPROVE--> APPROVED (final)
     *
     * @param  array<string, mixed>  $options  finalOnApproved=false untuk menguji WF-004
     */
    public static function withWorkflow(array $options = []): ApplicationGraph
    {
        $base = self::valid();
        $datasetId = 'DS00000000000000000000000000020';
        $finalOnApproved = $options['finalOnApproved'] ?? true;
        $approveTo = $options['approveTo'] ?? 'AC00000000000000000000000000053';

        $workflows = [[
            'OBJECT_ID' => 'WF00000000000000000000000000050',
            'OBJECT_CODE' => 'WF_CUSTOMER_APPROVAL',
            'OBJECT_NAME' => 'Customer Approval',
            'DATASET_ID' => $datasetId,
            'STATE_COLUMN' => null,
        ]];

        $activities = [
            self::activity('AC00000000000000000000000000051', 'DRAFT', 'START', initial: 1, final: 0, order: 10),
            self::activity('AC00000000000000000000000000052', 'PENDING', 'APPROVAL', initial: 0, final: 0, order: 20),
            self::activity('AC00000000000000000000000000053', 'APPROVED', 'END', initial: 0, final: $finalOnApproved ? 1 : 0, order: 30),
        ];

        $transitions = [
            self::transition('TR00000000000000000000000000054', 'T_SUBMIT', 'Ajukan', 'AC00000000000000000000000000051', 'AC00000000000000000000000000052', 'SUBMIT', 10),
            self::transition('TR00000000000000000000000000055', 'T_APPROVE', 'Setujui', 'AC00000000000000000000000000052', $approveTo, 'APPROVE', 20),
        ];

        return new ApplicationGraph(
            application: $base->attributes(),
            modules: $base->modules(),
            menus: $base->menus(),
            pages: $base->pages(),
            fields: $base->fields(),
            datasets: $base->datasets(),
            lovs: $base->lovs(),
            rules: $base->rules(),
            permissions: [],
            workflows: $workflows,
            activities: $activities,
            transitions: $transitions,
        );
    }

    /**
     * Graph valid + workflow + notifikasi (subscription pada event workflow).
     */
    public static function withNotifications(): ApplicationGraph
    {
        $wf = self::withWorkflow();

        $notifications = [[
            'OBJECT_ID' => 'NT00000000000000000000000000060',
            'OBJECT_CODE' => 'NTF_CUST_SUBMITTED',
            'OBJECT_NAME' => 'Customer Diajukan',
            'DEFAULT_CHANNEL' => 'IN_APP',
            'SUBJECT_TEMPLATE' => 'Persetujuan: {{entity}}',
            'BODY_TEMPLATE' => 'Customer {{entity}} diajukan oleh {{actor}}.',
        ]];

        $subscriptions = [[
            'OBJECT_ID' => 'NS00000000000000000000000000065',
            'OBJECT_CODE' => 'SUB_CUST_SUBMIT',
            'OBJECT_NAME' => 'Notifikasi Pengajuan',
            'EVENT_CODE' => 'WF.WF_CUSTOMER_APPROVAL.SUBMIT',
            'NOTIFICATION_ID' => 'NT00000000000000000000000000060',
            'RECIPIENT_TYPE' => 'ROLE',
            'RECIPIENT_REF' => 'ADMIN',
            'CHANNEL' => 'IN_APP',
            'DISPLAY_ORDER' => 10,
        ]];

        return new ApplicationGraph(
            application: $wf->attributes(),
            modules: $wf->modules(),
            menus: $wf->menus(),
            pages: $wf->pages(),
            fields: $wf->fields(),
            datasets: $wf->datasets(),
            lovs: $wf->lovs(),
            rules: $wf->rules(),
            permissions: [],
            workflows: $wf->workflows(),
            activities: $wf->activities(),
            transitions: $wf->transitions(),
            notifications: $notifications,
            subscriptions: $subscriptions,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function activity(string $id, string $code, string $type, int $initial, int $final, int $order): array
    {
        return [
            'OBJECT_ID' => $id,
            'WORKFLOW_ID' => 'WF00000000000000000000000000050',
            'OBJECT_CODE' => $code,
            'OBJECT_NAME' => $code,
            'ACTIVITY_TYPE' => $type,
            'INITIAL_FLAG' => $initial,
            'FINAL_FLAG' => $final,
            'DISPLAY_ORDER' => $order,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function transition(string $id, string $code, string $label, string $from, string $to, string $action, int $order): array
    {
        return [
            'OBJECT_ID' => $id,
            'WORKFLOW_ID' => 'WF00000000000000000000000000050',
            'OBJECT_CODE' => $code,
            'OBJECT_NAME' => $label,
            'FROM_ACTIVITY_ID' => $from,
            'TO_ACTIVITY_ID' => $to,
            'ACTION_CODE' => $action,
            'CONDITION_EXPR' => null,
            'REQUIRED_ROLE_ID' => '',
            'DISPLAY_ORDER' => $order,
        ];
    }

    /**
     * Graph rusak: halaman mereferensi dataset yang tidak dimuat.
     */
    public static function brokenDatasetRef(): ApplicationGraph
    {
        $graph = self::valid();

        return new ApplicationGraph(
            application: $graph->attributes(),
            modules: $graph->modules(),
            menus: $graph->menus(),
            pages: [[
                'OBJECT_ID' => 'PG00000000000000000000000000030',
                'APPLICATION_ID' => $graph->objectId(),
                'DATASET_ID' => 'DSFFFFFFFFFFFFFFFFFFFFFFFFFFFFF', // tak dikenal
                'OBJECT_CODE' => 'PAGE_CUSTOMER',
                'OBJECT_NAME' => 'Customer Form',
                'PAGE_TYPE' => 'FORM',
                'LAYOUT_TYPE' => 'SINGLE_COLUMN',
            ]],
            fields: $graph->fields(),
            datasets: [], // dataset sengaja dikosongkan
            lovs: [],
            rules: [],
        );
    }
}
