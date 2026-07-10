<?php

declare(strict_types=1);

namespace Odaf\Engine\Render;

use Odaf\Engine\Render\Contracts\RendererInterface;
use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-04 Renderer — target HTML/view-model (Vol.3 Bab 14).
 *
 * Mengubah model halaman terkompilasi menjadi view-model presentasi yang
 * dikonsumsi Blade/Livewire. Renderer bebas logika bisnis: ia hanya memetakan
 * field metadata ke deskriptor widget dan menyisipkan nilai data.
 */
final class HtmlRenderer implements RendererInterface
{
    /** Peta FIELD_TYPE metadata -> widget presentasi. */
    private const WIDGET_MAP = [
        'TEXT' => 'text',
        'TEXTAREA' => 'textarea',
        'NUMBER' => 'number',
        'DECIMAL' => 'number',
        'DATE' => 'date',
        'DATETIME' => 'datetime-local',
        'EMAIL' => 'email',
        'PASSWORD' => 'password',
        'CHECKBOX' => 'checkbox',
        'COMBO' => 'select',
        'SELECT' => 'select',
        'LOV' => 'select',
    ];

    public function target(): string
    {
        return 'html';
    }

    /**
     * @param  array<string, mixed>  $runtimePage
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function renderPage(ExecutionContextInterface $context, array $runtimePage, array $data = []): mixed
    {
        $fields = [];
        foreach (($runtimePage['fields'] ?? []) as $field) {
            $column = strtoupper((string) $field['column']);
            $lovId = ($field['lovId'] ?? '') !== '' ? (string) $field['lovId'] : null;
            $config = is_array($field['config'] ?? null) ? $field['config'] : [];
            $fieldType = (string) $field['fieldType'];

            $fields[] = [
                'id' => (string) $field['id'],
                'code' => (string) $field['code'],
                'label' => (string) $field['label'],
                'column' => $column,
                // Field dengan LOV selalu dirender sebagai dropdown.
                'widget' => $lovId !== null ? 'select' : $this->widgetFor($fieldType, $config),
                'dataType' => (string) ($field['dataType'] ?? 'STRING'),
                'required' => (bool) ($field['required'] ?? false),
                'readonly' => (bool) ($field['readonly'] ?? false),
                'value' => $data[$column] ?? ($field['defaultValue'] ?? null),
                'lovId' => $lovId,
                'lovLabelColumn' => ($field['lovLabelColumn'] ?? '') !== '' ? strtoupper((string) $field['lovLabelColumn']) : null,
                'options' => [],
                'config' => $config,
            ];
        }

        return [
            'code' => (string) $runtimePage['code'],
            'title' => (string) ($runtimePage['title'] ?? $runtimePage['name']),
            'pageType' => (string) $runtimePage['pageType'],
            'layout' => (string) ($runtimePage['layout'] ?? 'SINGLE_COLUMN'),
            'columns' => $this->columnCount((string) ($runtimePage['layout'] ?? 'SINGLE_COLUMN')),
            'datasetId' => $runtimePage['datasetId'] ?? null,
            'fields' => $fields,
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function widgetFor(string $fieldType, array $config = []): string
    {
        $type = strtoupper($fieldType);

        // DATE/DATETIME: opsi "dengan jam" menentukan widget date vs datetime-local.
        if (in_array($type, ['DATE', 'DATETIME'], true)) {
            $withTime = (bool) ($config['withTime'] ?? ($type === 'DATETIME'));

            return $withTime ? 'datetime-local' : 'date';
        }

        return self::WIDGET_MAP[$type] ?? 'text';
    }

    private function columnCount(string $layout): int
    {
        return match (strtoupper($layout)) {
            'TWO_COLUMN' => 2,
            'THREE_COLUMN' => 3,
            default => 1,
        };
    }
}
