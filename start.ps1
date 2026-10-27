param([switch]$SetupOnly, [int]$Port = 8000)
$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$phpCommand = Get-Command php -ErrorAction SilentlyContinue
$phpBinary = if ($phpCommand) { $phpCommand.Source } else { $null }
if (-not $phpBinary) {
    $herdRoot = Join-Path $env:USERPROFILE '.config\herd\bin'
    if (Test-Path -LiteralPath $herdRoot) {
        $phpBinary = Get-ChildItem -LiteralPath $herdRoot -Directory -Filter 'php*' | Sort-Object Name -Descending | ForEach-Object { Join-Path $_.FullName 'php.exe' } | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1
    }
}
if (-not $phpBinary) { throw 'PHP was not found. Install PHP 8.4.1+ or select PHP 8.5 in Laravel Herd.' }
& $phpBinary 'scripts/check-requirements.php'
if ($LASTEXITCODE -ne 0) { throw 'PHP prerequisite check failed.' }
if (-not (Test-Path -LiteralPath 'vendor/autoload.php')) { throw 'vendor is missing. Run composer install first.' }
if (-not (Test-Path -LiteralPath '.env')) { Copy-Item -LiteralPath '.env.example' -Destination '.env' }
if (-not (Test-Path -LiteralPath 'database/database.sqlite')) { New-Item -ItemType File -Path 'database/database.sqlite' | Out-Null }
foreach ($folder in @('storage/framework/cache/data','storage/framework/sessions','storage/framework/views','storage/logs','bootstrap/cache')) { New-Item -ItemType Directory -Force -Path $folder | Out-Null }
& $phpBinary artisan config:clear
if ($LASTEXITCODE -ne 0) { throw 'Could not clear configuration.' }
if (Select-String -LiteralPath '.env' -Pattern '^APP_KEY=\s*$' -Quiet) {
    & $phpBinary artisan key:generate --force
    if ($LASTEXITCODE -ne 0) { throw 'Could not generate the application key.' }
}
& $phpBinary artisan migrate --force
if ($LASTEXITCODE -ne 0) { throw 'Database migration failed.' }
& $phpBinary artisan db:seed --force
if ($LASTEXITCODE -ne 0) { throw 'Database seeding failed.' }
& $phpBinary artisan view:cache
if ($LASTEXITCODE -ne 0) { throw 'Could not clear compiled views.' }
Write-Host 'Aura Quotes is ready. Database content and existing accounts have been preserved.'
if (-not $SetupOnly) {
    Write-Host "Open http://127.0.0.1:$Port - press Ctrl+C to stop."
    & $phpBinary artisan serve --host=127.0.0.1 --port=$Port
    if ($LASTEXITCODE -ne 0) { throw 'The local server could not start.' }
}
