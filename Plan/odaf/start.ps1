#Requires -Version 5.1
<#
.SYNOPSIS
    Menjalankan server aplikasi ODAF (Oracle + PHP/Laravel) via Docker Compose.

.DESCRIPTION
    Membangun & menghidupkan container 'odaf-oracle' dan 'odaf-app', menunggu
    sampai runtime siap, lalu menampilkan URL. Container app otomatis melakukan
    composer install, key:generate, dan menjalankan `php artisan serve`.

.PARAMETER Rebuild
    Paksa build ulang image aplikasi (dipakai bila Dockerfile/dependensi berubah).

.PARAMETER Fresh
    DESTRUKTIF: hapus volume database (reset total) sebelum start. Init DDL/seed
    di database/init dijalankan ulang dari awal.

.PARAMETER Compile
    Setelah siap, kompilasi & aktifkan aplikasi demo (odaf:compile ODAF_DEMO).

.PARAMETER Port
    Port host untuk runtime (default: dari .env APP_PORT, atau 8080).

.PARAMETER TimeoutSec
    Batas waktu menunggu runtime siap (default 300 detik).

.EXAMPLE
    .\start.ps1
.EXAMPLE
    .\start.ps1 -Rebuild -Compile
.EXAMPLE
    .\start.ps1 -Fresh
#>
[CmdletBinding()]
param(
    [switch]$Rebuild,
    [switch]$Fresh,
    [switch]$Compile,
    [int]$Port = 0,
    [int]$TimeoutSec = 300
)

$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot

function Write-Step($msg) { Write-Host "[odaf] $msg" -ForegroundColor Cyan }
function Write-Ok($msg)   { Write-Host "[odaf] $msg" -ForegroundColor Green }
function Write-Warn2($msg) { Write-Host "[odaf] $msg" -ForegroundColor Yellow }

# --- 1. Pastikan Docker berjalan -------------------------------------------
try {
    docker version --format '{{.Server.Version}}' 1>$null 2>$null
    if ($LASTEXITCODE -ne 0) { throw }
} catch {
    Write-Error "Docker tidak terdeteksi / daemon tidak berjalan. Jalankan Docker Desktop lalu ulangi."
    exit 1
}

# --- 2. Tentukan perintah compose (plugin v2 vs binari lama) ----------------
$composeCmd = $null
docker compose version 1>$null 2>$null
if ($LASTEXITCODE -eq 0) {
    $composeCmd = @('docker', 'compose')
} else {
    $legacy = Get-Command docker-compose -ErrorAction SilentlyContinue
    if ($legacy) { $composeCmd = @('docker-compose') }
}
if (-not $composeCmd) {
    Write-Error "Docker Compose tidak ditemukan (butuh 'docker compose' atau 'docker-compose')."
    exit 1
}

function Invoke-Compose { & $composeCmd[0] ($composeCmd[1..($composeCmd.Count-1)] + $args) }

# --- 3. Tentukan port host dari .env bila tidak diberikan --------------------
if ($Port -le 0) {
    $Port = 8080
    if (Test-Path .env) {
        $m = Select-String -Path .env -Pattern '^\s*APP_PORT\s*=\s*(\d+)' -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($m) { $Port = [int]$m.Matches[0].Groups[1].Value }
    }
}
$baseUrl = "http://localhost:$Port"

# --- 4. Reset volume (opsional, destruktif) ---------------------------------
if ($Fresh) {
    Write-Warn2 "Mode -Fresh: menghapus volume database (data akan hilang)..."
    Invoke-Compose down -v
}

# --- 5. Hidupkan container --------------------------------------------------
$upArgs = @('up', '-d')
if ($Rebuild -or $Fresh) { $upArgs += '--build' }
Write-Step ("menjalankan: " + ($composeCmd -join ' ') + ' ' + ($upArgs -join ' '))
Invoke-Compose @upArgs
if ($LASTEXITCODE -ne 0) { Write-Error "Gagal menghidupkan container."; exit 1 }

# --- 6. Tunggu runtime siap -------------------------------------------------
Write-Step "menunggu runtime siap di $baseUrl (maks $TimeoutSec dtk; init DB pertama bisa 1-3 menit)..."
$deadline = (Get-Date).AddSeconds($TimeoutSec)
$ready = $false
while ((Get-Date) -lt $deadline) {
    try {
        $resp = Invoke-WebRequest -Uri "$baseUrl/health/db" -UseBasicParsing -TimeoutSec 5
        if ($resp.StatusCode -eq 200 -and $resp.Content -match 'reachable') { $ready = $true; break }
    } catch {
        # Belum siap; coba endpoint root sebagai fallback.
        try {
            $r2 = Invoke-WebRequest -Uri $baseUrl -UseBasicParsing -TimeoutSec 5
            if ($r2.StatusCode -ge 200 -and $r2.StatusCode -lt 500) { $ready = $true; break }
        } catch { }
    }
    Start-Sleep -Seconds 4
    Write-Host '.' -NoNewline
}
Write-Host ''

if (-not $ready) {
    Write-Warn2 "Runtime belum merespons dalam $TimeoutSec dtk. Container mungkin masih init."
    Write-Warn2 ("Pantau log: " + ($composeCmd -join ' ') + " logs -f app  (dan  logs -f oracle)")
    exit 2
}
Write-Ok "runtime siap."

# --- 7. Kompilasi aplikasi demo (opsional) ----------------------------------
if ($Compile) {
    Write-Step "kompilasi & aktivasi ODAF_DEMO..."
    Invoke-Compose exec -T app php artisan odaf:compile ODAF_DEMO --activate
}

# --- 8. Ringkasan -----------------------------------------------------------
Write-Ok "ODAF berjalan."
Write-Host ""
Write-Host "  Runtime   : $baseUrl" -ForegroundColor White
Write-Host "  Login     : $baseUrl/login   (admin / password)" -ForegroundColor White
Write-Host "  Studio    : $baseUrl/studio" -ForegroundColor White
Write-Host "  Manual    : $baseUrl/manual" -ForegroundColor White
Write-Host "  Cek DB    : $baseUrl/health/db" -ForegroundColor White
Write-Host ""
Write-Host ("  Log       : " + ($composeCmd -join ' ') + " logs -f app") -ForegroundColor DarkGray
Write-Host ("  Stop      : " + ($composeCmd -join ' ') + " stop") -ForegroundColor DarkGray
