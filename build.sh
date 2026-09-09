#!/usr/bin/env bash
set -e

# Farbdefinitionen
CYAN='\033[0;36m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

echo -e "${CYAN}==================================================${NC}"
echo -e "${CYAN} [INFO] Fabriel Software Team-Manager - Build & Pack${NC}"
echo -e "${CYAN}==================================================${NC}"

# 1. Node.js & npm pruefen
if ! command -v npm >/dev/null 2>&1; then
    echo -e "${RED}[ERROR] Node.js / npm ist nicht installiert oder nicht im PATH!${NC}"
    exit 1
fi

# 2. Dependencies pruefen
if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}[INFO] Installiere npm-Abhaengigkeiten (@wordpress/scripts)...${NC}"
    npm install
else
    echo -e "${GREEN}[OK] npm-Abhaengigkeiten bereits vorhanden.${NC}"
fi

# 3. Assets kompilieren
echo -e "${YELLOW}[INFO] Kompiliere React/JSX, SCSS und Block-Assets...${NC}"
npm run build

if [ ! -d "build" ]; then
    echo -e "${RED}[ERROR] Build fehlgeschlagen: Ordner build wurde nicht erstellt!${NC}"
    exit 1
fi

# Die block.json-Dateien werden von @wordpress/scripts selbst nach build/ kopiert;
# ein zusaetzliches Spiegeln wuerde nur eine abweichende Formatierung erzeugen.

# 3b. Uebersetzungsvorlage erzeugen (benoetigt WP-CLI)
if command -v wp >/dev/null 2>&1; then
    echo -e "${YELLOW}[INFO] Erzeuge languages/fabriel-team-manager.pot...${NC}"
    wp i18n make-pot . languages/fabriel-team-manager.pot --slug=fabriel-team-manager --domain=fabriel-team-manager --exclude=node_modules,vendor,src
    wp i18n make-json languages --no-purge --pretty-print
else
    echo -e "${YELLOW}[WARN] WP-CLI nicht gefunden - languages/*.pot wird nicht aktualisiert.${NC}"
fi

# 4. ZIP Distribution erstellen
ZIP_NAME="fabriel-team-manager.zip"
ZIP_PATH="$(pwd)/$ZIP_NAME"

if [ -f "$ZIP_PATH" ]; then
    rm -f "$ZIP_PATH"
fi

if ! command -v zip >/dev/null 2>&1; then
    echo -e "${RED}[ERROR] Das Paket 'zip' ist nicht installiert (z.B. sudo apt install zip).${NC}"
    exit 1
fi

# Temporaeres Verzeichnis anlegen und automatisches Cleanup bei Skript-Ende garantieren
TEMP_DIR="$(mktemp -d 2>/dev/null || mktemp -d -t 'fs-tm-dist')"
trap 'rm -rf "$TEMP_DIR"' EXIT

PLUGIN_DIST_DIR="$TEMP_DIR/fabriel-team-manager"
mkdir -p "$PLUGIN_DIST_DIR"

echo -e "${YELLOW}[INFO] Sammle Produktionsdateien...${NC}"

# Produktionsdateien inkl. Quellcode (src/) und Build-Konfiguration. Damit laesst sich
# build/ jederzeit nachvollziehbar neu erzeugen (WordPress.org-Richtlinie 4).
# build.sh und build.ps1 gehoeren NICHT in das ZIP: der Plugin Check meldet
# ausfuehrbare Skripte als "application_detected". Sie bleiben im oeffentlichen
# Repository, auf das readme.txt verweist.
for item in fabriel-team-manager.php uninstall.php index.php readme.txt license.txt includes languages build src package.json package-lock.json webpack.config.js composer.json; do
    if [ -e "$item" ]; then
        cp -R "$item" "$PLUGIN_DIST_DIR/"
    fi
done

# Hinweis: Banner- und Screenshot-Bilder gehoeren NICHT in das Plugin-ZIP. Sie liegen
# unter .wordpress-org/ und werden separat in das assets/-Verzeichnis des WordPress.org
# SVN-Repositories eingecheckt (siehe readme.txt, Abschnitt "Source code & development").

echo -e "${YELLOW}[INFO] Erstelle Linux-kompatibles ZIP-Archiv...${NC}"

(cd "$TEMP_DIR" && zip -r -q "$ZIP_PATH" "fabriel-team-manager")

echo -e "${GREEN}==================================================${NC}"
echo -e "${GREEN}[SUCCESS] Erfolgreich abgeschlossen!${NC}"
echo -e "${GREEN}[INFO] Zip-Archiv erstellt: $ZIP_NAME${NC}"
echo -e "${GREEN}==================================================${NC}"
