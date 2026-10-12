#!/usr/bin/env bash
set -euo pipefail
# Install Gibbon core separately and overlay QuranHifz without modifying Gibbon internals.
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
REF="${GIBBON_REF:-v31.0.00}"
if [[ -d "$ROOT/core" ]]; then
  echo "core/ exists. Refusing to overwrite. Remove it explicitly if needed." >&2
  exit 1
fi
git clone --depth 1 --branch "$REF" https://github.com/GibbonEdu/core.git "$ROOT/core"
mkdir -p "$ROOT/core/modules/QuranHifz"
cp -R "$ROOT/modules/QuranHifz/." "$ROOT/core/modules/QuranHifz/"
echo "Gibbon source installed in core/ at $REF."
echo "Follow the official Gibbon setup instructions (Composer + DB + ./up.sh),"
echo "then install QuranHifz in System Admin > Manage Modules."
