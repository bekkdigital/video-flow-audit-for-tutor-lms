#!/usr/bin/env bash
# Build a distributable ZIP for Video Flow Audit for Tutor LMS.
#
# Usage:
#   ./build.sh          -> version from the plugin header, e.g. v1.0.0
#   ./build.sh beta     -> appends -beta
#   ./build.sh 1.2.3    -> explicit version
#
# Exclusions come from .distignore (single source of truth).

set -euo pipefail

PLUGIN_SLUG="video-flow-audit-for-tutor-lms"
MAIN_FILE="${PLUGIN_SLUG}.php"
PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
DIST_DIR="$(cd "${PLUGIN_DIR}/.." && pwd)/dist"

HEADER_VERSION=$(grep -m1 "Version:" "${PLUGIN_DIR}/${MAIN_FILE}" | sed 's/.*Version:[[:space:]]*//' | tr -d '\r')

if [[ "${1:-}" =~ ^[0-9] ]]; then
    VERSION="v${1}"
elif [[ -n "${1:-}" ]]; then
    VERSION="v${HEADER_VERSION}-${1}"
else
    VERSION="v${HEADER_VERSION}"
fi

ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
mkdir -p "${DIST_DIR}"
rm -f "${DIST_DIR}/${ZIP_NAME}"

python3 - "$PLUGIN_DIR" "$PLUGIN_SLUG" "${DIST_DIR}/${ZIP_NAME}" <<'PYEOF'
import os, sys, zipfile

plugin_dir, plugin_slug, out_path = sys.argv[1], sys.argv[2], sys.argv[3]

# Parse .distignore into a set of repo-relative path prefixes.
ignores = []
with open(os.path.join(plugin_dir, ".distignore")) as fh:
    for line in fh:
        line = line.strip()
        if not line or line.startswith("#"):
            continue
        ignores.append(line.lstrip("/"))

def ignored(rel):
    return any(rel == p or rel.startswith(p + "/") for p in ignores)

count = 0
with zipfile.ZipFile(out_path, "w", zipfile.ZIP_DEFLATED) as zf:
    for root, dirs, files in os.walk(plugin_dir):
        rel_root = os.path.relpath(root, plugin_dir)
        rel_root = "" if rel_root == "." else rel_root
        dirs[:] = [d for d in dirs if not ignored(os.path.join(rel_root, d))]
        for fname in files:
            rel = os.path.join(rel_root, fname) if rel_root else fname
            if ignored(rel):
                continue
            zf.write(os.path.join(root, fname), f"{plugin_slug}/{rel}")
            count += 1

print(f"Built: {out_path}")
print(f"Size:  {os.path.getsize(out_path) // 1024} KB")
print(f"Files: {count}")
PYEOF
