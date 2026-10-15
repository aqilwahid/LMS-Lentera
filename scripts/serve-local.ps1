param([int]$Port = 8000)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    $portablePhp = Join-Path $env:USERPROFILE '.codex/lentera-tools/php'
    if (Test-Path "$portablePhp/php.exe") { $env:PATH = "$portablePhp;$env:PATH" }
}
Push-Location $projectRoot
try { & php artisan serve --host=127.0.0.1 --port=$Port } finally { Pop-Location }
