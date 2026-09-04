#!/usr/bin/env bash
# Refresh the bundled copy of Video Flow Core.
#
# The bundled copy is namespace-prefixed (vf_core_ -> vfaudit_core_) so it
# can never redeclare functions from any other plugin that also bundles
# Video Flow Core. Run this after changing the Video Flow Core source,
# then commit lib/.
#
# Point VF_CORE_SRC at the Video Flow Core checkout; defaults to a sibling
# "video-flow-core" directory next to this plugin's parent folder.
set -euo pipefail

HERE="$(cd "$(dirname "$0")/.." && pwd)"
SRC="${VF_CORE_SRC:-${HERE}/../../packages/video-flow-core}"
DEST="${HERE}/lib/video-flow-core"

rsync -a --delete \
  --exclude vendor --exclude tests --exclude .github --exclude .git \
  --exclude '.gitignore' --exclude 'phpunit.xml.dist' --exclude 'phpcs.xml.dist' \
  --exclude 'phpstan.neon.dist' --exclude '.editorconfig' \
  --exclude 'CLAUDE.md' --exclude 'AGENTS.md' --exclude 'README.md' \
  --exclude '.phpunit.result.cache' --exclude 'composer.json' --exclude 'composer.lock' \
  "${SRC}/" "${DEST}/"

# Prefix every public identifier so the bundled copy is collision-proof.
find "${DEST}" -name '*.php' -print0 | xargs -0 sed -i \
  -e 's/\bvf_core_/vfaudit_core_/g' \
  -e 's/\bVF_CORE_/VFAUDIT_CORE_/g' \
  -e 's/vf_course_videos/vfaudit_course_videos/g' \
  -e 's/vf_course_counts/vfaudit_course_counts/g'

echo "Synced + prefixed Video Flow Core -> lib/video-flow-core"
grep -m1 "vfaudit_core_register_package" "${DEST}/video-flow-core.php"
