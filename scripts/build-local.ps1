param(
    [string]$BuildDirectory = (Join-Path $env:LOCALAPPDATA 'Lentera/frontend-build')
)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
# Google Drive may reject npm extraction. Build dependencies on a local disk.
if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
    $portableNode = Join-Path $env:USERPROFILE '.codex/lentera-tools/node-v22.22.0-win-x64'
    if (Test-Path "$portableNode/node.exe") { $env:PATH = "$portableNode;$env:PATH" }
}
New-Item -ItemType Directory -Force $BuildDirectory | Out-Null
Copy-Item -LiteralPath "$projectRoot/package.json","$projectRoot/package-lock.json","$projectRoot/vite.config.js" -Destination $BuildDirectory -Force
Copy-Item -LiteralPath "$projectRoot/resources" -Destination $BuildDirectory -Recurse -Force
New-Item -ItemType Directory -Force "$BuildDirectory/public" | Out-Null
Push-Location $BuildDirectory
try {
    & npm.cmd ci --ignore-scripts
    if ($LASTEXITCODE -ne 0) { throw 'npm ci gagal' }
    & npm.cmd run build
    if ($LASTEXITCODE -ne 0) { throw 'Vite build gagal' }
} finally { Pop-Location }
Copy-Item -LiteralPath "$BuildDirectory/public/build" -Destination "$projectRoot/public" -Recurse -Force
Push-Location $projectRoot
try {
    & node scripts/sync-assets.mjs
    if ($LASTEXITCODE -ne 0) { throw 'Sinkronisasi aset gagal' }
} finally { Pop-Location }
