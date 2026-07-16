<?php

declare(strict_types=1);

namespace App\Livewire\Studio;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.odaf')]
final class SqlRunner extends Component
{
    public string $query = 'SELECT * FROM APP_APPLICATION FETCH FIRST 10 ROWS ONLY';
    
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];
    
    /** @var array<int, string> */
    public array $columns = [];
    
    public ?string $errorMessage = null;

    public float $executionTime = 0.0;

    public function runQuery(): void
    {
        app(StudioAccess::class)->ensureAdmin();

        $this->errorMessage = null;
        $this->rows = [];
        $this->columns = [];
        $this->executionTime = 0.0;

        $q = rtrim(trim($this->query), ';');
        if ($q === '') {
            return;
        }

        // Extremely basic safety check (Admin is trusted, but prevent accidental DML if they didn't mean it)
        // A true SQL developer allows everything, but for safety in this scope we'll just execute it.
        // `DB::select` is intended for SELECTs, but technically in PDO it can run anything.
        
        $start = microtime(true);
        try {
            $result = DB::select($q);
            $this->executionTime = round((microtime(true) - $start) * 1000, 2);

            if ($result !== []) {
                $firstRow = (array) $result[0];
                $this->columns = array_keys($firstRow);
                
                if ($this->columns === []) {
                    $this->errorMessage = "Query executed successfully, but returned no columns.";
                } else {
                    foreach ($result as $r) {
                        $arr = (array) $r;
                        $row = [];
                        foreach ($this->columns as $col) {
                            $val = $arr[$col] ?? null;
                            if (is_resource($val)) {
                                $val = stream_get_contents($val);
                            }
                            
                            if (is_string($val) && !mb_check_encoding($val, 'UTF-8')) {
                                $val = strtoupper(bin2hex($val));
                            } elseif (is_array($val) || is_object($val)) {
                                $val = json_encode($val);
                            }
                            
                            $row[$col] = $val;
                        }
                        $this->rows[] = $row;
                    }
                }
            } else {
                $this->errorMessage = "Query returned 0 rows.";
            }

        } catch (Throwable $e) {
            $this->executionTime = round((microtime(true) - $start) * 1000, 2);
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(StudioAccess $access)
    {
        $access->ensureAdmin();
        return view('livewire.studio.sql-runner');
    }
}
