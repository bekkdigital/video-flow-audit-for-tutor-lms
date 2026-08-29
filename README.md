# Video Flow Audit for Tutor LMS

Free, **read-only** video inventory for Tutor LMS — the wordpress.org
sibling of [Video Flow for Tutor LMS](https://wpvideoflow.com/).

It scans every course and lesson and reports every video it finds
(Vimeo, YouTube, Bunny Stream, self-hosted), grouped by course and
lesson, with orphan / duplicate flags. No migration, no API calls, no
writes.

## Repo layout

```
video-flow-audit-for-tutor-lms.php   bootstrap (menu, cache hooks, dormant guard)
includes/compat.php                  paid-plugin coexistence
includes/adapters/tutor.php          Tutor hierarchy -> Video Flow Core adapter
includes/admin-audit-page.php        the read-only screen
assets/audit.{css,js}                screen styling + copy-to-clipboard
lib/video-flow-core/                 bundled, prefix-renamed copy of the shared scanner
readme.txt                           wordpress.org readme (canonical)
build.sh / .distignore               packaging
```

## Development

```bash
composer install
composer run sync-core   # refresh lib/ from ../../packages/video-flow-core
composer run lint
composer run phpstan
./build.sh               # -> ../dist/video-flow-audit-for-tutor-lms-vX.Y.Z.zip
```

See `CLAUDE.md` for the rules and the runtime test checklist.
