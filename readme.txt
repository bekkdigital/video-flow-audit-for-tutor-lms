=== Video Flow Audit for Tutor LMS ===
Contributors: bekkdigitalstudio
Tags: tutor lms, video audit, vimeo, youtube, bunny stream
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free, read-only audit of every video and image in your Tutor LMS courses — Vimeo, YouTube, Bunny Stream, self-hosted and course images.

== Description ==

**Video Flow Audit for Tutor LMS scans your Tutor LMS courses and builds an inventory of every video it can find — Vimeo, YouTube, Bunny Stream or self-hosted — and every image the course uses, grouped by course and lesson.**

Videos in Tutor LMS get referenced in several places: the lesson video field, raw URLs and embeds in lesson content, the WordPress oEmbed cache, and self-hosted files in the Media Library. Video Flow Audit pulls all of those references into one admin screen, so you can see what your course-video library actually looks like before you clean it up, switch hosts, or migrate.

The plugin is **read-only by design**. It never edits a lesson, moves a file, uploads a video, or contacts an external video host. Everything it reports is read from data already stored in your WordPress database — no account, no licence key, no API credentials.

= What it shows =

**Course overview** — every course, with its video count. Filter by author.

**Per-course breakdown** — for each video: the lesson it is on, the provider (Vimeo, YouTube, Bunny Stream, self-hosted, or an externally embedded Bunny video), the title or filename, the video ID (one click to copy), and the duration for self-hosted files.

**Audit flags:**

* **Not embedded** — the video is tracked against a lesson but is not actually in any active lesson field or content. Usually the leftover of an interrupted upload or a changed video.
* **From oEmbed cache** — WordPress is still holding an oEmbed entry for an old video URL, for example a Vimeo reference on a lesson you have since moved elsewhere.
* **Used in N places** — the same underlying video is referenced from more than one lesson or course, so you know the blast radius before you replace or delete it.

**Course images** — a second tab per course lists every image the course uses: featured images, Tutor lesson attachments, and images inside course and lesson content. For each image you see a thumbnail, the file name and dimensions, the file size, the alt text, and exactly where it is used. Flags:

* **Missing alt text** — the image has no alt text in the Media Library.
* **Large file** — the image is over 500 KB and worth compressing.
* **Not in Media Library** — the image is hot-linked from another site or a path WordPress does not know about.

= What it detects =

Video Flow Audit reads Tutor LMS and WordPress data already on your site:

* Tutor LMS lesson video fields
* Video URLs and embeds in lesson content
* The WordPress oEmbed cache
* Self-hosted videos in the Media Library
* Featured images, Tutor lesson attachments and images in course/lesson content

It identifies Vimeo, YouTube and Bunny Stream references, externally embedded Bunny Stream videos, and self-hosted WordPress videos. It does not log in to Vimeo, YouTube, Bunny Stream or any other platform, and it does not perform a remote orphan scan of your video-host account — it reports what your WordPress site references.

= Who it is for =

Tutor LMS site owners, course creators and administrators who need to understand an existing course-video setup — typically before migrating off Vimeo, moving to Bunny Stream, cleaning up an older site, or reviewing a mixed hosting setup.

= Privacy =

Video Flow Audit sends nothing anywhere. It makes no external requests, requires no API credentials for Vimeo, YouTube or Bunny Stream, and does not send your course or video data to WPVideoFlow. The audit runs entirely on data stored in your WordPress installation.

= Need to migrate and manage videos too? =

Video Flow Audit only reports. If you also need to migrate Vimeo and self-hosted videos to Bunny Stream, upload through the Tutor course builder, reuse existing Bunny videos, fix alt text in place, serve course images from Bunny Storage, or manage everything from one Course Media screen, see **[Video Flow for Tutor LMS](https://wpvideoflow.com/video-flow-for-tutor-lms/)**. It already includes this audit view, so if it is active, Video Flow Audit stays dormant.

More on the free plugin: [Video Flow Audit for Tutor LMS](https://wpvideoflow.com/video-flow-audit-for-tutor-lms/)

== Installation ==

1. In WordPress, go to **Plugins → Add New** and search for **Video Flow Audit for Tutor LMS** (or upload the plugin zip).
2. Install and activate it. Tutor LMS (free or Pro) must be active.
3. Go to **Tutor LMS → Course Media Audit** and review your courses.

== Frequently Asked Questions ==

= Does it change anything in my courses? =

No. Video Flow Audit is strictly read-only — it never edits lessons, moves files, or touches your video-hosting accounts. Deactivating or uninstalling it does not alter your course videos.

= Does it work with Tutor LMS Free? =

Yes — both Tutor LMS Free and Tutor LMS Pro.

= What video sources can it detect? =

Vimeo, YouTube and Bunny Stream references, externally embedded Bunny Stream videos, and self-hosted videos in the WordPress Media Library — wherever they are stored in the Tutor LMS lesson video field, lesson content, or the WordPress oEmbed cache.

= It shows a Vimeo video I already migrated. Why? =

WordPress keeps an oEmbed cache. After you change a lesson away from Vimeo, the old Vimeo reference can linger in that cache. Video Flow Audit flags those rows as **From oEmbed cache**.

= Can it find unused or orphaned videos? =

It flags videos that are tracked against a lesson but not embedded in any active lesson field or content ("Not embedded"). It does not scan your remote Vimeo or Bunny Stream account for unused uploads.

= Do I need a Vimeo, YouTube or Bunny Stream account? =

No. It never logs in to those services and needs no API keys.

= Does it send data anywhere? =

No. No external requests, and nothing is sent to WPVideoFlow.

= Will it slow down my site? =

No. The audit is an admin-only screen and adds nothing to front-end page loads. Results are cached briefly while you review them.

= Can it fix missing alt text? =

No — the audit only reports. Alt text can be edited in the WordPress Media Library, or directly from the image list in [Video Flow for Tutor LMS](https://wpvideoflow.com/video-flow-for-tutor-lms/).

= Can it migrate videos? =

No — that is [Video Flow for Tutor LMS](https://wpvideoflow.com/video-flow-for-tutor-lms/).

== Screenshots ==

1. Course overview showing each course and its detected video count.
2. Per-course breakdown showing lesson, provider, video ID, title or filename, and usage flags.
3. Per-course image list with file size, alt text and where each image is used.

== Changelog ==

= 1.1.0 =
* New: Images tab per course — featured images, Tutor lesson attachments and images in content, with file size, alt text and where each image is used.
* New: "Missing alt text", "Large file" and "Not in Media Library" flags.
* The course list now shows every course, including courses without videos.
* Renamed the screen to "Course Media Audit" (Tutor LMS menu).
* Detects Bunny Stream videos played from b-cdn.net playback URLs.

= 1.0.0 =
* Initial public release.
* Audits Tutor LMS courses and lessons for video references.
* Detects Vimeo, YouTube, Bunny Stream and self-hosted videos.
* Course video counts and per-course breakdowns, filterable by author.
* "Not embedded", "From oEmbed cache" and "used in N places" flags.
* Read-only — no external video-host API required.

== Upgrade Notice ==

= 1.1.0 =
Adds a read-only image audit per course (missing alt text, large files) and renames the screen to Course Media Audit.

= 1.0.0 =
Initial release of Video Flow Audit for Tutor LMS.
