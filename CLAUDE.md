# CLAUDE.md — Video Flow Audit for Tutor LMS

## What this is

The **free wordpress.org** sibling of `video-flow-for-tutor-lms`. It shows
a read-only inventory of every video in every Tutor LMS course (Vimeo,
YouTube, Bunny Stream, self-hosted), reusing the paid plugin's Video
Manager view — stripped of all migration, Bunny API and write actions —
and points the user at the paid plugin to upgrade.

Created August 2026. See `/home/arild/.claude/plans/` for the build plan.

## Architecture

- The scanner is **not** in this repo. It lives in the shared
  `bekkdigital/video-flow-core` package
  (`../../packages/video-flow-core`), bundled here under
  `lib/video-flow-core/` with a `vf_core_` → **`vfaudit_core_`** prefix
  rename (see `bin/sync-core.sh`). The prefix makes the bundled copy
  collision-proof against the paid plugin, which still ships its own
  unguarded `vf_core_*` until Phase D.
- `includes/adapters/tutor.php` teaches Core about Tutor's hierarchy.
- `includes/admin-audit-page.php` is the read-only UI (`vfaudit_*`).
- `includes/compat.php` — when the paid plugin is active this plugin goes
  fully dormant (loads nothing, shows one notice).

## Hard rules

1. **Read-only. Always.** No Bunny/Vimeo/YouTube API calls, no writes to
   lessons or meta, no migration. The whole wordpress.org pitch is "it
   only looks". If a feature needs to write or call out, it belongs in the
   paid plugin.
2. **No phone-home, no external HTTP, no self-hosted updater.**
   wordpress.org forbids it and `readme.txt` promises none.
3. **One upgrade call-out, not a nag.** `vfaudit_render_upgrade_panel()`
   is the only promotional surface. Don't sprinkle upsells.
4. Everything user-facing goes through `esc_html__( …,
   'video-flow-audit-for-tutor-lms' )`. Text domain === slug.
5. Own prefix everywhere: `vfaudit_` functions, `VFAUDIT_` constants,
   `vfaudit_core_*` in the bundled lib. Never `vf_` / `vf_core_` in this
   repo's own PHP.
6. To change the scanner: edit `../../packages/video-flow-core`, run
   `composer run sync-core`, commit `lib/`. Don't hand-edit `lib/`.

## Verify

```bash
composer install
composer run lint && composer run phpstan
./build.sh                       # -> ../dist/…zip  (check it is ~25 KB, no dev files)
```

Runtime: symlink into `/var/www/pulskuren/wp/wp-content/plugins/`,
`wp plugin activate video-flow-audit-for-tutor-lms` (paid plugin
deactivated), open **Tutor LMS → Video Audit**, compare counts against the
paid plugin's Video Manager for the same courses. Then activate the paid
plugin too and confirm: no fatal, audit menu gone, dormant notice shown.

## wordpress.org

- `readme.txt` is the canonical readme; `Stable tag` must match the header
  `Version:`.
- `.distignore` is the single source of truth for packaging (`build.sh`
  reads it).
- First release: create the wp.org plugin (slug
  `video-flow-audit-for-tutor-lms`), add `SVN_USERNAME` / `SVN_PASSWORD`
  secrets, drop assets in `.wordpress-org/`, then `git tag v1.0.0`.
