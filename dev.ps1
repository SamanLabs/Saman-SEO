#Requires -Version 5.1
<#
.SYNOPSIS
    Local validation script that mirrors the GitHub Actions code-quality checks.

.DESCRIPTION
    Runs the same lint/build checks CI runs on push/PR so you can catch issues
    before publishing. Stops on the first failing step unless -ContinueOnError
    is passed.

.EXAMPLE
    .\dev.ps1
    .\dev.ps1 -ContinueOnError
#>
[CmdletBinding()]
param(
    [switch]$ContinueOnError
)

$ErrorActionPreference = 'Stop'
$script:hasFailures = $false

# -----------------------------------------------------------------------------
# Helpers
# -----------------------------------------------------------------------------
function Write-Section($Message) {
    Write-Host "`n==> $Message" -ForegroundColor Cyan
}

function Write-Ok($Message) {
    Write-Host "    [OK] $Message" -ForegroundColor Green
}

function Write-Fail($Message) {
    Write-Host "    [FAIL] $Message" -ForegroundColor Red
}

function Write-Warn($Message) {
    Write-Host "    [WARN] $Message" -ForegroundColor Yellow
}

function Invoke-Check {
    param(
        [string]$Name,
        [scriptblock]$Script
    )

    Write-Section $Name
    try {
        & $Script
        Write-Ok $Name
    }
    catch {
        Write-Fail "$Name failed: $_"
        $script:hasFailures = $true
        if (-not $ContinueOnError) {
            throw
        }
    }
}

function Test-Command($Command) {
    $null = Get-Command $Command -ErrorAction Stop
}

# -----------------------------------------------------------------------------
# Preflight
# -----------------------------------------------------------------------------
Write-Host "Saman SEO dev validation" -ForegroundColor Cyan
Write-Host "Run from the plugin root directory." -ForegroundColor DarkGray

$required = @('php', 'npm')
foreach ($cmd in $required) {
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        Write-Fail "$cmd is required but not found in PATH."
        exit 1
    }
}

# Composer vendor tools are expected after `composer install`.
$phpcs = 'vendor/bin/phpcs'
$usePhpcs = Test-Path $phpcs
if (-not $usePhpcs) {
    Write-Warn "$phpcs not found. Run 'composer install' first to enable PHPCS."
}

# -----------------------------------------------------------------------------
# Checks
# -----------------------------------------------------------------------------
Invoke-Check 'PHP syntax check' {
    $files = Get-ChildItem -Path 'includes', 'saman-seo.php' -Recurse -Filter '*.php' -ErrorAction Stop
    $failed = @()
    foreach ($file in $files) {
        $output = php -l $file.FullName 2>&1
        if ($LASTEXITCODE -ne 0) {
            $failed += $file.FullName
            Write-Host $output -ForegroundColor Red
        }
    }
    if ($failed.Count -gt 0) {
        throw "$($failed.Count) PHP file(s) failed syntax check."
    }
}

if ($usePhpcs) {
    Invoke-Check 'PHP Coding Standards (PHPCS)' {
        & php -d memory_limit=512M $phpcs --standard=phpcs.xml.dist --report-full --report-summary --runtime-set ignore_warnings_on_exit 1 includes/
        if ($LASTEXITCODE -ne 0) {
            throw "PHPCS reported errors."
        }
    }
}
else {
    Write-Section 'PHP Coding Standards (PHPCS)'
    Write-Warn 'Skipped because vendor/bin/phpcs is missing.'
}

Invoke-Check 'npm install (if needed)' {
    if (-not (Test-Path 'node_modules')) {
        npm ci
        if ($LASTEXITCODE -ne 0) { throw "npm ci failed." }
    }
    else {
        Write-Ok 'node_modules already present'
    }
}

Invoke-Check 'JavaScript / asset build' {
    npm run build
    if ($LASTEXITCODE -ne 0) { throw "npm run build failed." }
}

# JS lint is run in CI with continue-on-error, so treat it as a warning here too.
Invoke-Check 'JavaScript lint' {
    npm run lint:js
    if ($LASTEXITCODE -ne 0) {
        Write-Warn 'npm run lint:js reported issues. CI allows this to fail, but you may want to run "npm run format:js".'
    }
}

# CSS lint is not enforced in CI, but run it as informational.
Invoke-Check 'CSS lint' {
    npm run lint:css
    if ($LASTEXITCODE -ne 0) {
        Write-Warn 'CSS lint reported issues. This is not enforced in CI.'
    }
}

# -----------------------------------------------------------------------------
# Summary
# -----------------------------------------------------------------------------
Write-Host "`n========================================" -ForegroundColor Cyan
if ($script:hasFailures) {
    Write-Host "VALIDATION FAILED" -ForegroundColor Red
    Write-Host "Fix the failures above before pushing." -ForegroundColor DarkGray
    exit 1
}
else {
    Write-Host "ALL CHECKS PASSED" -ForegroundColor Green
    exit 0
}
