<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * AccessEvaluator: Mengevaluasi izin pada level Page (termasuk Tab) dan Field
 * berdasarkan tabel SEC_ACCESS dan SEC_USER_ROLE di runtime.
 */
final class AccessEvaluator
{
    private const LEVEL_WEIGHTS = [
        'FULL' => 4,
        'READONLY' => 3,
        'MASKED' => 2,
        'NONE' => 1,
        'DEFAULT' => 4, // Jika tidak ada aturan, default-nya FULL
    ];

    /** @var array<string, string> */
    private array $evaluatedCache = [];
    private bool $loaded = false;

    public function __construct(private readonly AccessControlRepository $repo)
    {
    }

    /**
     * Mendapatkan tingkat izin untuk sebuah halaman (Page/Tab) (FULL, READONLY, MASKED, NONE).
     */
    public function pageAccess(string $pageId): string
    {
        return $this->evaluate('PAGE', $pageId);
    }

    /**
     * Mendapatkan tingkat izin untuk sebuah field (FULL, READONLY, MASKED, NONE).
     */
    public function fieldAccess(string $fieldId): string
    {
        return $this->evaluate('FIELD', $fieldId);
    }

    private function evaluate(string $type, string $targetId): string
    {
        $this->ensureLoaded();
        
        $key = strtoupper($type) . ':' . strtoupper(str_replace('-', '', $targetId));
        return $this->evaluatedCache[$key] ?? 'FULL';
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;

        if (!$this->repo->isInstalled()) {
            return;
        }

        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $userIdRaw = str_replace('-', '', (string) $userId);

        // Ambil semua aturan untuk semua role yang dimiliki user saat ini (yang valid).
        $rows = DB::select("
            SELECT A.OBJECT_TYPE, RAWTOHEX(A.TARGET_OBJECT_ID) AS TARGET_ID, A.ACCESS_LEVEL
            FROM SEC_ACCESS A
            JOIN SEC_USER_ROLE UR ON UR.ROLE_ID = A.ROLE_ID
            WHERE UR.USER_ID = HEXTORAW(?)
              AND (UR.VALID_FROM IS NULL OR UR.VALID_FROM <= SYSDATE)
              AND (UR.VALID_TO IS NULL OR UR.VALID_TO >= SYSDATE)
              AND (A.VALID_FROM IS NULL OR A.VALID_FROM <= SYSDATE)
              AND (A.VALID_TO IS NULL OR A.VALID_TO >= SYSDATE)
        ", [$userIdRaw]);

        // Least Restrictive Policy (Ambil nilai terbesar dari LEVEL_WEIGHTS)
        $temp = [];
        foreach ($rows as $row) {
            $key = strtoupper((string) $row->object_type) . ':' . strtoupper((string) $row->target_id);
            $level = strtoupper((string) $row->access_level);
            
            $currentWeight = self::LEVEL_WEIGHTS[$level] ?? 4;
            $existingWeight = isset($temp[$key]) ? (self::LEVEL_WEIGHTS[$temp[$key]] ?? 4) : 0;
            
            if ($currentWeight > $existingWeight) {
                $temp[$key] = $level;
            }
        }

        $this->evaluatedCache = $temp;
    }
}
