# Video Flow Core

LMS-agnostic, **read-only** video scanner shared by the Video Flow plugin
family (Tutor LMS, LearnDash, LifterLMS, …).

Given a course ID, Core walks the course and every lesson/topic in its
curriculum and reports every video it finds — Vimeo, YouTube, Bunny
Stream, or a local/self-hosted file — with no HTTP calls and no database
writes.

## Design

- **Flat, no classes, no namespace** — matches the rest of the family.
  All public functions are prefixed `vf_core_`.
- **Adapter-driven.** Core knows nothing about any specific LMS. The host
  plugin registers an adapter (post types, course → lesson hierarchy,
  curriculum ordering) via `vf_core_register_lms_adapter()`.
- **Read-only.** `vf_core_get_course_videos()` performs no external
  requests and writes nothing. Plugins that can talk to Bunny enrich
  Bunny rows via the `vf_core_enrich_videos` filter.
- **Version-negotiating loader.** Each consuming plugin bundles its own
  copy under `vendor/bekkdigital/video-flow-core/`. When more than one is
  active, the newest copy wins (see `video-flow-core.php`).

## Consuming it

```php
// In your plugin's main file, after WordPress is loaded:
require_once __DIR__ . '/vendor/bekkdigital/video-flow-core/video-flow-core.php';

// On init / plugins_loaded:
vf_core_register_lms_adapter( 'tutor', array(
    'label'                => 'Tutor LMS',
    'course_post_type'     => 'courses',
    'content_post_types'   => array( 'lesson', 'topics' ),
    'get_content_post_ids' => 'my_plugin_tutor_curriculum_ids',
    'resolve_course_id'    => 'my_plugin_tutor_resolve_course_id',
) );

// Anywhere after plugins_loaded priority 3:
$videos = vf_core_get_course_videos_cached( $course_id );
```

## Public API

| Function | Purpose |
|---|---|
| `vf_core_register_lms_adapter( $key, $adapter )` | Register the LMS adapter |
| `vf_core_get_course_videos( $course_id )` | Fresh scan, one row per (video_id, post_id) |
| `vf_core_get_course_videos_cached( $course_id )` | 5-minute transient wrapper |
| `vf_core_invalidate_course_videos_cache( $post_id )` | Bust the cache for the owning course |
| `vf_core_get_courses_with_video_counts( $author_id = 0 )` | Course roll-ups for a list view |
| `vf_core_get_video_usages( $video_id )` | Every post a Bunny video is attached to |
| `vf_core_video_is_embedded_in_post( $video_id, $post_id )` | Orphan check |
| `vf_core_format_duration( $seconds )` | `H:MM:SS` / `M:SS` |

Provider helpers (`vf_core_extract_vimeo_ids`, `vf_core_extract_youtube_id`,
`vf_core_extract_bunny_guid`, …) live in `src/providers.php`.

## Development

```bash
composer install
composer run test      # PHPUnit (Brain\Monkey, no WordPress needed)
composer run lint      # PHPCS (WordPress standard)
composer run phpstan   # level 5
```

## Changelog

### 1.0.1
- Hardening: scalar-check every post-meta value before a string/int cast, so
  an injected serialized object cannot reach `__toString()`.
- `vf_core_get_course_videos[_cached]()` now ignores IDs that are not a real
  course post (prevents transient churn from a crafted `?course_id=`).
- `vf_core_get_courses_with_video_counts()` ignores a non-existent `author_id`.

### 1.0.0
- Initial extraction from `video-flow-for-tutor-lms`.
