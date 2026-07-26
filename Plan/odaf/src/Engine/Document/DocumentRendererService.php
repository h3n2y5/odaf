<?php

declare(strict_types=1);

namespace Odaf\Engine\Document;

/**
 * Simple Mustache-style Template Renderer for ODAF Document Designer.
 *
 * Mendukung:
 * - Variabel: {{ NAMA_KOLOM }}
 * - Block iterasi: {{#DETAIL_ITEMS}} ... {{/DETAIL_ITEMS}}
 */
final class DocumentRendererService
{
    /**
     * Render template HTML dengan data (array bersarang).
     *
     * @param string $template HTML content dengan {{ tags }}
     * @param array<string, mixed> $data Data record (header & array details)
     * @return string HTML yang sudah dirender
     */
    public function render(string $template, array $data): string
    {
        // 1. Render blocks (iteration)
        // Pattern: {{#SECTION_NAME}} ... content ... {{/SECTION_NAME}}
        $pattern = '/\{\{#([a-zA-Z0-9_]+)\}\}(.*?)\{\{\/\1\}\}/s';
        
        $template = preg_replace_callback($pattern, function ($matches) use ($data) {
            $sectionName = $matches[1];
            $innerTemplate = $matches[2];
            
            // Cek apakah section ada di data dan berupa array berindeks (list of rows)
            if (isset($data[$sectionName]) && is_array($data[$sectionName])) {
                $output = '';
                foreach ($data[$sectionName] as $row) {
                    if (is_array($row)) {
                        // Gabungkan row data dengan parent data agar parent vars masih bisa diakses
                        // (Meskipun prioritas pada variabel row anak)
                        $mergedRowData = array_merge($data, $row);
                        $output .= $this->renderVariables($innerTemplate, $mergedRowData);
                    }
                }
                return $output;
            }
            
            // Jika kosong, hilangkan block
            return '';
        }, $template);

        // 2. Render flat variables
        return $this->renderVariables($template, $data);
    }

    /**
     * Ganti {{ VARIABLE }} dengan nilai dari data.
     * 
     * @param string $template
     * @param array<string, mixed> $data
     * @return string
     */
    private function renderVariables(string $template, array $data): string
    {
        $pattern = '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/';
        
        return preg_replace_callback($pattern, function ($matches) use ($data) {
            $varName = $matches[1];
            
            // Khusus: {{ QR_CODE }} biasanya dirender terpisah atau diinject sbg HTML
            // Nilai dikonversi ke string
            if (isset($data[$varName])) {
                $val = $data[$varName];
                if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                    return (string) $val;
                }
            }
            
            return ''; // Kosong jika tidak ditemukan
        }, $template) ?? $template;
    }
}
