# Video Flow Audit for Tutor LMS

Free, **read-only** video inventory for Tutor LMS.

It scans every course and lesson and reports every video it finds
(Vimeo, YouTube, Bunny Stream, self-hosted), grouped by course and
lesson, with orphan / duplicate flags. No migration, no external API
calls, no writes.

The [WordPress.org listing](https://wordpress.org/plugins/video-flow-audit-for-tutor-lms/)
is the place to download and rate it.

## Repo layout

```
video-flow-audit-for-tutor-lms.php   bootstrap (menu, cache hooks, coexistence guard)
includes/compat.php                  companion-plugin coexistence
includes/adapters/tutor.php          Tutor hierarchy -> Video Flow Core adapter
includes/admin-audit-page.php        the read-only screen
assets/audit.{css,js}                screen styling + copy-to-clipboard
lib/video-flow-core/                 bundled, prefix-renamed copy of the shared scanner
readme.txt                           WordPress.org readme (canonical)
build.sh / .distignore               packaging
```

## Development

```bash
composer install
composer run lint       # PHPCS (WordPress standard)
composer run phpstan
./build.sh              # -> a distributable zip

# Refresh the bundled scanner from a Video Flow Core checkout:
VF_CORE_SRC=/path/to/video-flow-core composer run sync-core
```

## Contributing

Bug reports and pull requests are welcome. Please run `composer run lint`
and `composer run phpstan` before opening a PR, and keep the plugin
strictly read-only — it must never write to lessons, post meta, or call
an external service.

## License

GPL-2.0-or-later.
