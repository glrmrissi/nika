#!/usr/bin/env pwsh
#Requires -Version 5.1

<#
.SYNOPSIS
    Quick-start script for local development on Windows.

.DESCRIPTION
    Checks PHP/composer, ensures .env and dependencies exist, runs migrations and seeds, then starts the PHP dev server.

.PARAMETER Setup
    Force full setup even if .env already exists.

.PARAMETER Host
    Host to bind the dev server to (default: localhost).

.PARAMETER Port
    Port to bind the dev server to (default: 8000).

.EXAMPLE
    .\scripts\dev.ps1
    .\scripts\dev.ps1 -Setup
    .\scripts\dev.ps1 -Port 8080
#>

[CmdletBinding()]
param(
    [switch]$Setup,
    [string]$Host = 'localhost',
    [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'

function Test-CommandAvailable {
    param([string]$Name)
    $null -ne (Get-Command $Name -ErrorAction SilentlyContinue)
}

if (-not (Test-CommandAvailable 'php')) {
    throw "php is not installed or not in PATH."
}

if (-not (Test-CommandAvailable 'composer')) {
    throw "composer is not installed or not in PATH."
}

if ($Setup -or -not (Test-Path '.env')) {
    Write-Host "==> Bootstrapping environment..." -ForegroundColor Cyan
    Copy-Item '.env.example' '.env' -ErrorAction SilentlyContinue
    composer install
    php bin/console doctrine:database:create --if-not-exists
    php bin/console doctrine:migrations:migrate --no-interaction
    php bin/console app:kanji:seed
    php bin/console app:admin:create
    php bin/console cache:clear
    Write-Host "==> Setup complete." -ForegroundColor Green
}

Write-Host "==> Starting dev server at http://${Host}:${Port}" -ForegroundColor Cyan
php -S "${Host}:${Port}" -t public
