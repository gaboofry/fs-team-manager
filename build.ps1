# PowerShell Build & Release Automation Script
$ErrorActionPreference = 'Stop'

Write-Host '==================================================' -ForegroundColor Cyan
Write-Host ' [INFO] Fabriel Software Team-Manager - Build & Pack' -ForegroundColor Cyan
Write-Host '==================================================' -ForegroundColor Cyan

# 1. Node.js & npm pruefen
if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    Write-Host '[ERROR] Node.js / npm ist nicht installiert oder nicht im PATH!' -ForegroundColor Red
    Exit 1
}

# 2. Dependencies pruefen
if (-not (Test-Path 'node_modules')) {
    Write-Host '[INFO] Installiere npm-Abhaengigkeiten (@wordpress/scripts)...' -ForegroundColor Yellow
    npm install
} else {
    Write-Host '[OK] npm-Abhaengigkeiten bereits vorhanden.' -ForegroundColor Green
}

# 3. Assets kompilieren
Write-Host '[INFO] Kompiliere React/JSX, SCSS und Block-Assets...' -ForegroundColor Yellow
npm run build

if (-not (Test-Path 'build')) {
    Write-Host '[ERROR] Build fehlgeschlagen: Ordner build wurde nicht erstellt!' -ForegroundColor Red
    Exit 1
}

# Die block.json-Dateien werden von @wordpress/scripts selbst nach build/ kopiert;
# ein zusaetzliches Spiegeln wuerde nur eine abweichende Formatierung erzeugen.

# 3b. Uebersetzungsvorlage erzeugen (benoetigt WP-CLI)
if (Get-Command wp -ErrorAction SilentlyContinue) {
    Write-Host '[INFO] Erzeuge languages/fabriel-team-manager.pot...' -ForegroundColor Yellow
    wp i18n make-pot . languages/fabriel-team-manager.pot --slug=fabriel-team-manager --domain=fabriel-team-manager --exclude=node_modules,vendor,src
    wp i18n make-json languages --no-purge --pretty-print
} else {
    Write-Host '[WARN] WP-CLI nicht gefunden - languages/*.pot wird nicht aktualisiert.' -ForegroundColor Yellow
}

# 4. ZIP Distribution erstellen (Linux/WordPress-kompatibel mit Forward-Slashes)
$ZipName = 'fabriel-team-manager.zip'
$ZipPath = Join-Path (Get-Location) $ZipName
if (Test-Path $ZipPath) {
    Remove-Item $ZipPath -Force
}

$TempDir = Join-Path $env:TEMP ('fs-tm-dist-' + [guid]::NewGuid().ToString('N'))
$PluginDistDir = Join-Path $TempDir 'fabriel-team-manager'

New-Item -ItemType Directory -Path $PluginDistDir -Force | Out-Null

Write-Host '[INFO] Sammle Produktionsdateien...' -ForegroundColor Yellow

# Produktionsdateien inkl. Quellcode (src/) und Build-Konfiguration. Damit laesst sich
# build/ jederzeit nachvollziehbar neu erzeugen (WordPress.org-Richtlinie 4).
# build.sh und build.ps1 gehoeren NICHT in das ZIP: der Plugin Check meldet
# ausfuehrbare Skripte als "application_detected". Sie bleiben im oeffentlichen
# Repository, auf das readme.txt verweist.
foreach ($item in @(
    'fabriel-team-manager.php', 'uninstall.php', 'index.php', 'readme.txt', 'license.txt',
    'includes', 'languages', 'build', 'src',
    'package.json', 'package-lock.json', 'webpack.config.js', 'composer.json'
)) {
    if (Test-Path $item) {
        Copy-Item $item -Destination $PluginDistDir -Recurse
    }
}

# Hinweis: Banner- und Screenshot-Bilder gehoeren NICHT in das Plugin-ZIP. Sie liegen
# unter .wordpress-org/ und werden separat in das assets/-Verzeichnis des WordPress.org
# SVN-Repositories eingecheckt (siehe readme.txt, Abschnitt "Source code & development").

Write-Host '[INFO] Erstelle Linux-kompatibles ZIP-Archiv...' -ForegroundColor Yellow

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$ZipArchive = [System.IO.Compression.ZipFile]::Open($ZipPath, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    Get-ChildItem -Path $PluginDistDir -Recurse | Where-Object { -not $_.PSIsContainer } | ForEach-Object {
        $relPath = $_.FullName.Substring($TempDir.Length + 1).Replace('\', '/')
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($ZipArchive, $_.FullName, $relPath, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
} finally {
    $ZipArchive.Dispose()
    Remove-Item -Path $TempDir -Recurse -Force
}

Write-Host '==================================================' -ForegroundColor Green
Write-Host '[SUCCESS] Erfolgreich abgeschlossen!' -ForegroundColor Green
Write-Host ('[INFO] Zip-Archiv erstellt: ' + $ZipName) -ForegroundColor Green
Write-Host '==================================================' -ForegroundColor Green
