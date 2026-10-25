#!/usr/bin/env bash
# Build the CODEX Connector WordPress plugin zip.
#   scripts/build-wp-plugin.sh  ->  web/public/downloads/codex-connector.zip
# The zip contains a single top-level folder "codex-connector/" (what WordPress expects).
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC_PARENT="$ROOT/integrations/wordpress"
SLUG="codex-connector"
OUT_DIR="$ROOT/web/public/downloads"
OUT="$OUT_DIR/$SLUG.zip"

[ -f "$SRC_PARENT/$SLUG/$SLUG.php" ] || { echo "Plugin not found in $SRC_PARENT/$SLUG" >&2; exit 1; }

# Lint every PHP file first when php is available.
if command -v php >/dev/null 2>&1; then
  find "$SRC_PARENT/$SLUG" -name '*.php' -print0 | while IFS= read -r -d '' f; do
    php -l "$f" >/dev/null || { echo "PHP syntax error in $f" >&2; exit 1; }
  done
fi

# Recompile the Arabic translation if gettext is installed.
if command -v msgfmt >/dev/null 2>&1 && [ -f "$SRC_PARENT/$SLUG/languages/$SLUG-ar.po" ]; then
  msgfmt -o "$SRC_PARENT/$SLUG/languages/$SLUG-ar.mo" "$SRC_PARENT/$SLUG/languages/$SLUG-ar.po"
fi

mkdir -p "$OUT_DIR"
rm -f "$OUT"

(
  cd "$SRC_PARENT"
  zip -qr "$OUT" "$SLUG" \
    -x "*/.DS_Store" "*/.git*" "*/node_modules/*" "*/vendor/*" "*/tests/*" \
       "*/.editorconfig" "*/phpcs.xml*" "*/composer.json" "*/composer.lock" \
       "*/package.json" "*/package-lock.json" "*.log" "*/.idea/*" "*/.vscode/*"
)

echo "Built $OUT ($(du -h "$OUT" | cut -f1))"
