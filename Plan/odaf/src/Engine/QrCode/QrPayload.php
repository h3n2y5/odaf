<?php

declare(strict_types=1);

namespace Odaf\Engine\QrCode;

/**
 * Value object representing a QR Code payload — data yang di-encode ke dalam QR.
 *
 * Payload berisi informasi navigasi (app/page/key) dan opsional: workflow action,
 * cross-document FK column, serta custom field data.
 */
final readonly class QrPayload
{
    /**
     * @param  array<string, mixed>  $extraFields  Data field tambahan (untuk CUSTOM_DATA type)
     */
    public function __construct(
        public string $appCode,
        public string $pageCode,
        public string $entityKey,
        public ?string $targetAppCode = null,
        public ?string $targetPageCode = null,
        public ?string $action = null,
        public ?string $fkColumn = null,
        public array $extraFields = [],
        public ?int $timestamp = null,
    ) {}

    /**
     * Bangun query string parameters (tanpa signature).
     *
     * @return array<string, string>
     */
    public function toQueryParams(): array
    {
        $params = [];

        // Target berbeda dari source (cross-document).
        if ($this->targetAppCode !== null && $this->targetAppCode !== $this->appCode) {
            $params['tapp'] = $this->targetAppCode;
        }
        if ($this->targetPageCode !== null) {
            $params['tpage'] = $this->targetPageCode;
        }
        if ($this->action !== null && $this->action !== '') {
            $params['act'] = $this->action;
        }
        if ($this->fkColumn !== null && $this->fkColumn !== '') {
            $params['fk'] = $this->fkColumn;
        }
        if (! empty($this->extraFields)) {
            $params['data'] = base64_encode(json_encode($this->extraFields, JSON_THROW_ON_ERROR));
        }

        $params['ts'] = (string) ($this->timestamp ?? time());

        return $params;
    }

    /**
     * Parse query parameters kembali ke payload (tanpa validasi signature).
     *
     * @param  array<string, string>  $query
     */
    public static function fromQueryParams(string $appCode, string $pageCode, string $entityKey, array $query): self
    {
        $extraFields = [];
        if (isset($query['data']) && $query['data'] !== '') {
            try {
                $decoded = json_decode(base64_decode($query['data'], true) ?: '', true, 8, JSON_THROW_ON_ERROR);
                $extraFields = is_array($decoded) ? $decoded : [];
            } catch (\JsonException) {
                $extraFields = [];
            }
        }

        return new self(
            appCode: $appCode,
            pageCode: $pageCode,
            entityKey: $entityKey,
            targetAppCode: isset($query['tapp']) && $query['tapp'] !== '' ? $query['tapp'] : null,
            targetPageCode: isset($query['tpage']) && $query['tpage'] !== '' ? $query['tpage'] : null,
            action: isset($query['act']) && $query['act'] !== '' ? $query['act'] : null,
            fkColumn: isset($query['fk']) && $query['fk'] !== '' ? $query['fk'] : null,
            extraFields: $extraFields,
            timestamp: isset($query['ts']) ? (int) $query['ts'] : null,
        );
    }
}
