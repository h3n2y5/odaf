<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Visual Document Builder — Section-based document print template designer.
 *
 * Pengguna membangun dokumen cetakan dari blok-blok visual (sections)
 * tanpa perlu menulis HTML. System akan meng-generate HTML/CSS secara otomatis.
 */
#[Layout('layouts.odaf')]
final class DocumentDesigner extends Component
{
    public string $appId;
    public string $pageId;
    public string $pageName = '';

    // Template metadata
    public ?string $templateId = null;
    public string $objectCode = '';
    public string $objectName = '';
    public string $pageSize = 'A4';
    public string $orientation = 'PORTRAIT';
    public bool $isDefault = false;

    public array $templates = [];

    // Available fields from page metadata
    public array $availableFields = [];
    public array $availableDetails = [];

    // Section-based builder state
    public array $sections = [];

    // Active tab: 'visual' or 'html'
    public string $activeTab = 'visual';
    public string $htmlContent = '';
    public string $cssContent = '';

    // Section types
    public const SECTION_TYPES = [
        'two_column'  => ['label' => '2 Kolom (Kiri-Kanan)', 'icon' => 'columns', 'max' => 2],
        'letterhead'  => ['label' => 'Kop Surat', 'icon' => 'building', 'max' => 1],
        'doc_info'    => ['label' => 'Info Dokumen', 'icon' => 'info', 'max' => 3],
        'field_table' => ['label' => 'Tabel Field', 'icon' => 'grid', 'max' => 3],
        'table'       => ['label' => 'Tabel Detail (Berulang)', 'icon' => 'table', 'max' => 3],
        'summary'     => ['label' => 'Ringkasan/Total', 'icon' => 'calculator', 'max' => 2],
        'signatures'  => ['label' => 'Tanda Tangan', 'icon' => 'pen', 'max' => 1],
        'notes'       => ['label' => 'Catatan/Footer', 'icon' => 'note', 'max' => 2],
        'qrcode'      => ['label' => 'QR Code', 'icon' => 'qr', 'max' => 1],
        'separator'   => ['label' => 'Garis Pemisah', 'icon' => 'line', 'max' => 5],
    ];

    public function mount(string $appId, string $pageId): void
    {
        $this->appId = $appId;
        $this->pageId = $pageId;
        $this->loadPageInfo();
        $this->loadTemplates();
    }

    private function loadPageInfo(): void
    {
        $page = collect(DB::select("SELECT * FROM ODAF.UI_PAGE WHERE OBJECT_ID = HEXTORAW(?)", [$this->pageId]))->first();

        $pageArr = $page ? (array) $page : [];
        if (!empty($pageArr)) {
            $this->pageName = $pageArr['object_name'] ?? $pageArr['OBJECT_NAME'] ?? '';

            $fields = DB::select("SELECT COLUMN_NAME FROM ODAF.UI_FIELD WHERE PAGE_ID = HEXTORAW(?) ORDER BY DISPLAY_ORDER", [$this->pageId]);
            foreach ($fields as $f) {
                $this->availableFields[] = $f->column_name ?? $f->COLUMN_NAME;
            }

            // Detail config
            $rawDetailConfig = $pageArr['detail_config'] ?? $pageArr['DETAIL_CONFIG'] ?? null;
            if (!empty($rawDetailConfig)) {
                $jsonDetailConfig = is_resource($rawDetailConfig) ? stream_get_contents($rawDetailConfig) : $rawDetailConfig;
                $decoded = json_decode($jsonDetailConfig, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $d) {
                        if (!isset($d['pageCode'])) continue;
                        $childPage = collect(DB::select("SELECT RAWTOHEX(OBJECT_ID) as ID, OBJECT_CODE, OBJECT_NAME FROM ODAF.UI_PAGE WHERE OBJECT_CODE = ?", [$d['pageCode']]))->first();
                        if ($childPage) {
                            $childId = $childPage->id ?? $childPage->ID;
                            $childFields = DB::select("SELECT COLUMN_NAME FROM ODAF.UI_FIELD WHERE PAGE_ID = HEXTORAW(?) ORDER BY DISPLAY_ORDER", [$childId]);
                            $this->availableDetails[] = [
                                'code' => $childPage->object_code ?? $childPage->OBJECT_CODE,
                                'name' => $childPage->object_name ?? $childPage->OBJECT_NAME,
                                'fields' => array_map(fn($cf) => $cf->column_name ?? $cf->COLUMN_NAME, $childFields),
                            ];
                        }
                    }
                }
            }
        }
    }

    private function loadTemplates(): void
    {
        $rows = DB::select("
            SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, OBJECT_CODE, OBJECT_NAME, PAGE_SIZE, ORIENTATION, IS_DEFAULT, HTML_CONTENT, CSS_CONTENT, SECTION_CONFIG
            FROM ODAF.RPT_TEMPLATE WHERE PAGE_ID = HEXTORAW(?)
        ", [$this->pageId]);

        $this->templates = [];
        foreach ($rows as $row) {
            $arr = (array) $row;
            $this->templates[] = [
                'OBJECT_ID' => $arr['object_id'] ?? $arr['OBJECT_ID'],
                'OBJECT_CODE' => $arr['object_code'] ?? $arr['OBJECT_CODE'],
                'OBJECT_NAME' => $arr['object_name'] ?? $arr['OBJECT_NAME'],
                'IS_DEFAULT' => $arr['is_default'] ?? $arr['IS_DEFAULT'],
            ];
        }
    }

    // ─── Section Management ──────────────────────────────────────────

    public function addSection(string $type): void
    {
        $meta = self::SECTION_TYPES[$type] ?? null;
        if (!$meta) return;

        // Cek max instances
        $count = count(array_filter($this->sections, fn($s) => $s['type'] === $type));
        if ($count >= $meta['max']) return;

        $section = ['type' => $type, 'config' => $this->defaultConfig($type)];
        $this->sections[] = $section;
        $this->regenerateHtml();
    }

    public function removeSection(int $index): void
    {
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);
        $this->regenerateHtml();
    }

    public function moveSectionUp(int $index): void
    {
        if ($index <= 0) return;
        $temp = $this->sections[$index - 1];
        $this->sections[$index - 1] = $this->sections[$index];
        $this->sections[$index] = $temp;
        $this->regenerateHtml();
    }

    public function moveSectionDown(int $index): void
    {
        if ($index >= count($this->sections) - 1) return;
        $temp = $this->sections[$index + 1];
        $this->sections[$index + 1] = $this->sections[$index];
        $this->sections[$index] = $temp;
        $this->regenerateHtml();
    }

    public function updateSectionConfig(int $index, string $key, mixed $value): void
    {
        if (!isset($this->sections[$index])) return;
        $this->sections[$index]['config'][$key] = $value;
        $this->regenerateHtml();
    }

    public function addFieldToDocInfo(int $sectionIndex, string $fieldName): void
    {
        if (!isset($this->sections[$sectionIndex])) return;
        $fields = $this->sections[$sectionIndex]['config']['fields'] ?? [];
        if (!in_array($fieldName, $fields)) {
            $fields[] = $fieldName;
            $this->sections[$sectionIndex]['config']['fields'] = $fields;
            $this->regenerateHtml();
        }
    }

    public function removeFieldFromDocInfo(int $sectionIndex, int $fieldIndex): void
    {
        if (!isset($this->sections[$sectionIndex])) return;
        unset($this->sections[$sectionIndex]['config']['fields'][$fieldIndex]);
        $this->sections[$sectionIndex]['config']['fields'] = array_values($this->sections[$sectionIndex]['config']['fields']);
        $this->regenerateHtml();
    }

    public function toggleDetailColumn(int $sectionIndex, string $column): void
    {
        if (!isset($this->sections[$sectionIndex])) return;
        $cols = $this->sections[$sectionIndex]['config']['columns'] ?? [];
        if (in_array($column, $cols)) {
            $cols = array_values(array_diff($cols, [$column]));
        } else {
            $cols[] = $column;
        }
        $this->sections[$sectionIndex]['config']['columns'] = $cols;
        $this->regenerateHtml();
    }

    // ─── Default Configs ─────────────────────────────────────────────

    private function defaultConfig(string $type): array
    {
        return match ($type) {
            'two_column' => [
                'leftFields' => [],
                'rightFields' => [],
                'leftTitle' => '',
                'rightTitle' => '',
            ],
            'letterhead' => [
                'companyName' => 'Nama Perusahaan',
                'address' => 'Alamat perusahaan',
                'phone' => '',
            ],
            'doc_info' => [
                'title' => strtoupper($this->pageName ?: 'DOKUMEN'),
                'fields' => [],
                'gridColumns' => 2,
            ],
            'field_table' => [
                'columns' => [],
                'headerLabels' => [],
            ],
            'table' => [
                'detailIndex' => 0,
                'columns' => [],
                'showNumber' => true,
            ],
            'summary' => [
                'fields' => [],
                'label' => 'Total',
            ],
            'signatures' => [
                'count' => 2,
                'labels' => ['Dibuat Oleh', 'Disetujui Oleh'],
            ],
            'notes' => [
                'text' => '',
            ],
            'qrcode' => [
                'position' => 'top-right',
                'size' => 120,
            ],
            'separator' => [
                'style' => 'solid',
            ],
            default => [],
        };
    }

    // ─── Presets ──────────────────────────────────────────────────────

    public function applyPreset(string $preset): void
    {
        if ($preset === 'purchase_order') {
            $this->sections = [];
            $this->objectName = 'Purchase Order Print';
            $this->objectCode = 'RPT_PO_' . strtoupper(substr(uniqid(), -4));

            // Kop Surat
            $this->sections[] = ['type' => 'letterhead', 'config' => [
                'companyName' => 'PT. Nexus Corporation',
                'address' => 'Jl. Sudirman Kav 1, Jakarta 10220',
                'phone' => '(021) 555-0100',
            ]];

            // QR Code
            $this->sections[] = ['type' => 'qrcode', 'config' => [
                'position' => 'top-right',
                'size' => 120,
            ]];

            // Info
            $infoFields = array_intersect($this->availableFields, ['PO_NO', 'PO_DATE', 'SUPPLIER_NAME', 'SUPPLIER_CODE', 'STATUS', 'REMARKS']);
            if (empty($infoFields)) {
                $infoFields = array_slice($this->availableFields, 0, 4);
            }
            $this->sections[] = ['type' => 'doc_info', 'config' => [
                'title' => 'PURCHASE ORDER',
                'fields' => array_values($infoFields),
            ]];

            // Tabel Detail
            if (!empty($this->availableDetails)) {
                $det = $this->availableDetails[0];
                $this->sections[] = ['type' => 'table', 'config' => [
                    'detailIndex' => 0,
                    'columns' => $det['fields'],
                    'showNumber' => true,
                ]];
            }

            // Ringkasan
            $totalFields = array_intersect($this->availableFields, ['TOTAL_AMOUNT', 'GRAND_TOTAL', 'TAX_AMOUNT', 'DISCOUNT']);
            $this->sections[] = ['type' => 'summary', 'config' => [
                'fields' => array_values($totalFields),
                'label' => 'Total',
            ]];

            // Tanda Tangan
            $this->sections[] = ['type' => 'signatures', 'config' => [
                'count' => 2,
                'labels' => ['Dibuat Oleh', 'Disetujui Oleh'],
            ]];

            $this->regenerateHtml();
        } elseif ($preset === 'invoice') {
            $this->sections = [];
            $this->objectName = 'Invoice Print';
            $this->objectCode = 'RPT_INV_' . strtoupper(substr(uniqid(), -4));

            $this->sections[] = ['type' => 'letterhead', 'config' => $this->defaultConfig('letterhead')];
            $this->sections[] = ['type' => 'doc_info', 'config' => ['title' => 'INVOICE', 'fields' => array_slice($this->availableFields, 0, 4)]];
            if (!empty($this->availableDetails)) {
                $this->sections[] = ['type' => 'table', 'config' => ['detailIndex' => 0, 'columns' => $this->availableDetails[0]['fields'], 'showNumber' => true]];
            }
            $this->sections[] = ['type' => 'signatures', 'config' => ['count' => 2, 'labels' => ['Hormat Kami', 'Penerima']]];
            $this->regenerateHtml();
        }
    }

    // ─── HTML Generator ──────────────────────────────────────────────

    public function addFieldToTwoColumn(int $sectionIndex, string $side, string $fieldName): void
    {
        if (!isset($this->sections[$sectionIndex])) return;
        $key = $side === 'right' ? 'rightFields' : 'leftFields';
        $fields = $this->sections[$sectionIndex]['config'][$key] ?? [];
        if (!in_array($fieldName, $fields)) {
            $fields[] = $fieldName;
            $this->sections[$sectionIndex]['config'][$key] = $fields;
            $this->regenerateHtml();
        }
    }

    public function removeFieldFromTwoColumn(int $sectionIndex, string $side, int $fieldIndex): void
    {
        if (!isset($this->sections[$sectionIndex])) return;
        $key = $side === 'right' ? 'rightFields' : 'leftFields';
        unset($this->sections[$sectionIndex]['config'][$key][$fieldIndex]);
        $this->sections[$sectionIndex]['config'][$key] = array_values($this->sections[$sectionIndex]['config'][$key]);
        $this->regenerateHtml();
    }

    public function toggleFieldTableColumn(int $sectionIndex, string $column): void
    {
        if (!isset($this->sections[$sectionIndex])) return;
        $cols = $this->sections[$sectionIndex]['config']['columns'] ?? [];
        if (in_array($column, $cols)) {
            $cols = array_values(array_diff($cols, [$column]));
        } else {
            $cols[] = $column;
        }
        $this->sections[$sectionIndex]['config']['columns'] = $cols;
        $this->regenerateHtml();
    }

    public function regenerateHtml(): void
    {
        $html = '';
        $css = $this->baseCSS();

        foreach ($this->sections as $section) {
            $html .= match ($section['type']) {
                'two_column' => $this->renderTwoColumn($section['config']),
                'letterhead' => $this->renderLetterhead($section['config']),
                'doc_info' => $this->renderDocInfo($section['config']),
                'field_table' => $this->renderFieldTable($section['config']),
                'table' => $this->renderTable($section['config']),
                'summary' => $this->renderSummary($section['config']),
                'signatures' => $this->renderSignatures($section['config']),
                'notes' => $this->renderNotes($section['config']),
                'qrcode' => $this->renderQrCode($section['config']),
                'separator' => $this->renderSeparator($section['config']),
                default => '',
            };
        }

        $this->htmlContent = $html;
        $this->cssContent = $css;
    }

    private function baseCSS(): string
    {
        return <<<'CSS'
body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1e293b; line-height: 1.5; margin: 0; padding: 20px; }
.doc-row { display: flex; gap: 20px; margin-bottom: 20px; }
.doc-row .doc-col { flex: 1; }
.doc-row .doc-col h4 { margin: 0 0 8px; font-size: 13px; color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; }
.doc-letterhead { border-bottom: 3px solid #1e40af; padding-bottom: 15px; margin-bottom: 20px; }
.doc-letterhead h1 { margin: 0; font-size: 22px; color: #1e40af; }
.doc-letterhead p { margin: 4px 0 0; color: #64748b; font-size: 12px; }
.doc-title { text-align: center; font-size: 18px; font-weight: bold; color: #1e293b; margin: 20px 0 15px; letter-spacing: 2px; }
.doc-info-grid { display: grid; gap: 6px 30px; margin-bottom: 25px; font-size: 13px; }
.doc-info-grid.cols-1 { grid-template-columns: auto 1fr; }
.doc-info-grid.cols-2 { grid-template-columns: auto 1fr auto 1fr; }
.doc-info-grid.cols-3 { grid-template-columns: auto 1fr auto 1fr auto 1fr; }
.doc-info-grid .label { color: #64748b; font-weight: 600; white-space: nowrap; }
.doc-info-grid .value { color: #1e293b; }
.doc-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.doc-table th { background: #f1f5f9; color: #334155; padding: 8px 10px; border: 1px solid #cbd5e1; font-size: 12px; text-align: left; }
.doc-table td { padding: 7px 10px; border: 1px solid #e2e8f0; font-size: 12px; }
.doc-table tr:nth-child(even) td { background: #f8fafc; }
.doc-table tfoot th { background: #e2e8f0; font-size: 13px; }
.doc-summary { text-align: right; margin-bottom: 30px; }
.doc-summary .row { display: flex; justify-content: flex-end; gap: 30px; padding: 4px 0; }
.doc-summary .row .label { color: #64748b; font-weight: 600; min-width: 150px; text-align: right; }
.doc-summary .row .value { min-width: 120px; text-align: right; font-weight: bold; }
.doc-signatures { display: flex; justify-content: space-between; margin-top: 60px; page-break-inside: avoid; }
.doc-signatures .sig-block { text-align: center; width: 180px; }
.doc-signatures .sig-block .sig-label { font-size: 12px; color: #64748b; margin-bottom: 70px; }
.doc-signatures .sig-block .sig-line { border-top: 1px solid #94a3b8; padding-top: 5px; font-size: 12px; color: #334155; }
.doc-notes { margin-top: 20px; padding: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 11px; color: #64748b; }
.doc-qr { position: absolute; top: 20px; right: 20px; text-align: center; }
.doc-qr .qr-label { font-size: 9px; color: #94a3b8; margin-top: 4px; }
.doc-separator { border: none; border-top: 1px solid #cbd5e1; margin: 15px 0; }
.doc-separator.dashed { border-top-style: dashed; }
.doc-separator.double { border-top: 3px double #cbd5e1; }
CSS;
    }

    private function renderLetterhead(array $c): string
    {
        $name = e($c['companyName'] ?? '');
        $addr = e($c['address'] ?? '');
        $phone = e($c['phone'] ?? '');
        $phoneLine = $phone ? "<p>{$phone}</p>" : '';
        return <<<HTML
<div class="doc-letterhead">
    <h1>{$name}</h1>
    <p>{$addr}</p>
    {$phoneLine}
</div>
HTML;
    }

    private function renderTwoColumn(array $c): string
    {
        $leftTitle = e($c['leftTitle'] ?? '');
        $rightTitle = e($c['rightTitle'] ?? '');
        $leftFields = $c['leftFields'] ?? [];
        $rightFields = $c['rightFields'] ?? [];

        $leftHtml = $leftTitle ? "<h4>{$leftTitle}</h4>" : '';
        foreach ($leftFields as $f) {
            $label = str_replace('_', ' ', ucwords(strtolower($f), '_'));
            $leftHtml .= "<div><span style=\"color:#64748b;font-weight:600\">{$label}:</span> {{ {$f} }}</div>\n";
        }

        $rightHtml = $rightTitle ? "<h4>{$rightTitle}</h4>" : '';
        foreach ($rightFields as $f) {
            $label = str_replace('_', ' ', ucwords(strtolower($f), '_'));
            $rightHtml .= "<div><span style=\"color:#64748b;font-weight:600\">{$label}:</span> {{ {$f} }}</div>\n";
        }

        return <<<HTML
<div class="doc-row">
    <div class="doc-col">{$leftHtml}</div>
    <div class="doc-col">{$rightHtml}</div>
</div>
HTML;
    }

    private function renderDocInfo(array $c): string
    {
        $title = e($c['title'] ?? 'DOKUMEN');
        $fields = $c['fields'] ?? [];
        $cols = (int) ($c['gridColumns'] ?? 2);
        $colClass = "cols-{$cols}";
        $rows = '';
        foreach ($fields as $f) {
            $label = str_replace('_', ' ', ucwords(strtolower($f), '_'));
            $rows .= "<div class=\"label\">{$label}:</div><div class=\"value\">{{ {$f} }}</div>\n";
        }
        $titleHtml = $title ? "<div class=\"doc-title\">{$title}</div>\n" : '';
        return <<<HTML
{$titleHtml}<div class="doc-info-grid {$colClass}">
{$rows}</div>
HTML;
    }

    private function renderFieldTable(array $c): string
    {
        $columns = $c['columns'] ?? [];
        if (empty($columns)) return '';

        $ths = '';
        $tds = '';
        foreach ($columns as $col) {
            $label = $c['headerLabels'][$col] ?? str_replace('_', ' ', ucwords(strtolower($col), '_'));
            $ths .= "<th>{$label}</th>\n";
            $tds .= "<td>{{ {$col} }}</td>\n";
        }

        return <<<HTML
<table class="doc-table">
    <thead><tr>{$ths}</tr></thead>
    <tbody><tr>{$tds}</tr></tbody>
</table>
HTML;
    }

    private function renderTable(array $c): string
    {
        $detailIndex = (int) ($c['detailIndex'] ?? 0);
        $columns = $c['columns'] ?? [];
        $showNumber = (bool) ($c['showNumber'] ?? true);

        $det = $this->availableDetails[$detailIndex] ?? null;
        if (!$det || empty($columns)) return '';

        $code = $det['code'];

        // Header
        $ths = $showNumber ? '<th align="center" style="width:40px">No</th>' : '';
        foreach ($columns as $col) {
            $label = str_replace('_', ' ', ucwords(strtolower($col), '_'));
            $ths .= "<th>{$label}</th>\n";
        }

        // Body row
        $tds = $showNumber ? '<td align="center">{{ @index }}</td>' : '';
        foreach ($columns as $col) {
            $tds .= "<td>{{ {$col} }}</td>\n";
        }

        return <<<HTML
<table class="doc-table">
    <thead><tr>{$ths}</tr></thead>
    <tbody>
        {{#{$code}}}
        <tr>{$tds}</tr>
        {{/{$code}}}
    </tbody>
</table>
HTML;
    }

    private function renderSummary(array $c): string
    {
        $fields = $c['fields'] ?? [];
        if (empty($fields)) return '';

        $rows = '';
        foreach ($fields as $f) {
            $label = str_replace('_', ' ', ucwords(strtolower($f), '_'));
            $rows .= "<div class=\"row\"><div class=\"label\">{$label}:</div><div class=\"value\">{{ {$f} }}</div></div>\n";
        }
        return "<div class=\"doc-summary\">\n{$rows}</div>\n";
    }

    private function renderSignatures(array $c): string
    {
        $count = (int) ($c['count'] ?? 2);
        $labels = $c['labels'] ?? [];
        $blocks = '';
        for ($i = 0; $i < $count; $i++) {
            $label = e($labels[$i] ?? 'Tanda Tangan ' . ($i + 1));
            $blocks .= <<<HTML
<div class="sig-block">
    <div class="sig-label">{$label}</div>
    <div class="sig-line">(_________________)</div>
</div>
HTML;
        }
        return "<div class=\"doc-signatures\">\n{$blocks}</div>\n";
    }

    private function renderNotes(array $c): string
    {
        $text = e($c['text'] ?? '');
        if (empty($text)) return '';
        return "<div class=\"doc-notes\">{$text}</div>\n";
    }

    private function renderQrCode(array $c): string
    {
        $size = (int) ($c['size'] ?? 120);
        return <<<HTML
<div class="doc-qr" style="width:{$size}px">
    {{ QR_CODE }}
    <div class="qr-label">Scan untuk verifikasi</div>
</div>
HTML;
    }

    private function renderSeparator(array $c): string
    {
        $style = $c['style'] ?? 'solid';
        $cls = $style === 'dashed' ? ' dashed' : ($style === 'double' ? ' double' : '');
        return "<hr class=\"doc-separator{$cls}\">\n";
    }

    // ─── Template CRUD ───────────────────────────────────────────────

    public function editTemplate(string $id): void
    {
        $t = collect(DB::select("SELECT * FROM ODAF.RPT_TEMPLATE WHERE OBJECT_ID = HEXTORAW(?)", [$id]))->first();
        if (!$t) return;

        $arr = (array) $t;
        $this->templateId = $id;
        $this->objectCode = $arr['object_code'] ?? $arr['OBJECT_CODE'] ?? '';
        $this->objectName = $arr['object_name'] ?? $arr['OBJECT_NAME'] ?? '';
        $this->pageSize = $arr['page_size'] ?? $arr['PAGE_SIZE'] ?? 'A4';
        $this->orientation = $arr['orientation'] ?? $arr['ORIENTATION'] ?? 'PORTRAIT';
        $this->isDefault = ($arr['is_default'] ?? $arr['IS_DEFAULT'] ?? 0) == 1;

        $html = $arr['html_content'] ?? $arr['HTML_CONTENT'] ?? '';
        $css = $arr['css_content'] ?? $arr['CSS_CONTENT'] ?? '';
        $sectionJson = $arr['section_config'] ?? $arr['SECTION_CONFIG'] ?? null;

        $this->htmlContent = is_resource($html) ? stream_get_contents($html) : ($html ?? '');
        $this->cssContent = is_resource($css) ? stream_get_contents($css) : ($css ?? '');

        if ($sectionJson) {
            $raw = is_resource($sectionJson) ? stream_get_contents($sectionJson) : $sectionJson;
            $decoded = json_decode($raw, true);
            $this->sections = is_array($decoded) ? $decoded : [];
        } else {
            $this->sections = [];
        }

        $this->activeTab = !empty($this->sections) ? 'visual' : 'html';
    }

    public function newTemplate(): void
    {
        $this->reset('templateId', 'objectCode', 'objectName', 'htmlContent', 'cssContent', 'isDefault', 'sections');
        $this->pageSize = 'A4';
        $this->orientation = 'PORTRAIT';
        $this->objectCode = 'RPT_' . strtoupper(substr(uniqid(), -6));
        $this->activeTab = 'visual';
    }

    public function saveTemplate(): void
    {
        $this->validate([
            'objectCode' => 'required|string|max:100',
            'objectName' => 'required|string|max:200',
        ]);

        if ($this->isDefault) {
            DB::update("UPDATE ODAF.RPT_TEMPLATE SET IS_DEFAULT = 0 WHERE PAGE_ID = HEXTORAW(?)", [$this->pageId]);
        }

        $isDefaultVal = $this->isDefault ? 1 : 0;
        $sectionJson = json_encode($this->sections, JSON_UNESCAPED_UNICODE);

        if ($this->templateId) {
            DB::update("
                UPDATE ODAF.RPT_TEMPLATE SET
                    OBJECT_CODE = ?, OBJECT_NAME = ?, PAGE_SIZE = ?, ORIENTATION = ?,
                    HTML_CONTENT = ?, CSS_CONTENT = ?, IS_DEFAULT = ?, SECTION_CONFIG = ?
                WHERE OBJECT_ID = HEXTORAW(?)
            ", [
                strtoupper($this->objectCode), $this->objectName, $this->pageSize, $this->orientation,
                $this->htmlContent, $this->cssContent, $isDefaultVal, $sectionJson,
                $this->templateId,
            ]);
        } else {
            DB::insert("
                INSERT INTO ODAF.RPT_TEMPLATE (
                    OBJECT_ID, APPLICATION_ID, PAGE_ID, OBJECT_CODE, OBJECT_NAME,
                    PAGE_SIZE, ORIENTATION, HTML_CONTENT, CSS_CONTENT, IS_DEFAULT, STATUS, SECTION_CONFIG
                ) VALUES (
                    SYS_GUID(), HEXTORAW(?), HEXTORAW(?), ?, ?, ?, ?, ?, ?, ?, 'PUBLISHED', ?
                )
            ", [
                $this->appId, $this->pageId,
                strtoupper($this->objectCode), $this->objectName, $this->pageSize, $this->orientation,
                $this->htmlContent, $this->cssContent, $isDefaultVal, $sectionJson,
            ]);
        }

        $this->loadTemplates();
        session()->flash('doc_success', 'Template berhasil disimpan!');
    }

    public function deleteTemplate(string $id): void
    {
        DB::delete("DELETE FROM ODAF.RPT_TEMPLATE WHERE OBJECT_ID = HEXTORAW(?)", [$id]);
        $this->loadTemplates();
        $this->newTemplate();
    }

    public function render()
    {
        return view('livewire.studio.designer.document-designer', [
            'sectionTypes' => self::SECTION_TYPES,
        ]);
    }
}
