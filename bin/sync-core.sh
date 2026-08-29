#!/usr/bin/env bash
# Refresh the bundled copy of Video Flow Core from the sibling package.
#
# The bundled copy is namespace-prefixed (vf_core_ -> vfaudit_core_) so it
# can never collide with the paid "Video Flow for Tutor LMS" plugin, which
# ships its own unguarded vf_core_* functions until it too moves onto Core
# (Phase D). Run this after changing packages/video-flow-core, then commit
# lib/.
set -euo pipefail

HERE="$(cd "$(dirname "$0")/.." && pwd)"
SRC="${HERE}/../../packages/video-flow-core"
DEST="${HERE}/lib/video-flow-core"

rsync -a --delete \
  --exclude vendor --exclude tests --exclude .github --exclude .git \
  --exclude '.gitignore' --exclude 'phpunit.xml.dist' --exclude 'phpcs.xml.dist' \
  --exclude 'phpstan.neon.dist' --exclude '.editorconfig' --exclude 'CLAUDE.md' \
  --exclude '.phpunit.result.cache' --exclude 'composer.json' --exclude 'composer.lock' \
  "${SRC}/" "${DEST}/"

# Prefix every public identifier so the bundled copy is collision-proof.
find "${DEST}" -name '*.php' -print0 | xargs -0 sed -i \
  -e 's/\bvf_core_/vfaudit_core_/g' \
  -e 's/\bVF_CORE_/VFAUDIT_CORE_/g' \
  -e 's/vf_course_videos_/vfaudit_course_videos_/g' \
  -e 's/vf_course_counts_/vfaudit_course_counts_/g'

echo "Synced + prefixed Video Flow Core -> lib/video-flow-core"
grep -m1 "vfaudit_core_register_package" "${DEST}/video-flow-core.php"
