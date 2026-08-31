=== Video Flow Audit for Tutor LMS ===
Contributors: bekkdigitalstudio
Tags: tutor lms, video, vimeo, youtube, bunny stream
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

See every video in your Tutor LMS courses — Vimeo, YouTube, Bunny Stream or self-hosted — in one place, grouped by course and lesson.

== Description ==

Videos in Tutor LMS end up in a lot of different places: the lesson video
modal, raw URLs in lesson content, the WordPress oEmbed cache, self-hosted
files in your media library. **Video Flow Audit for Tutor LMS** scans all
of them and shows you a single, tidy inventory.

For every course you get:

* A count of every video connected to the course.
* A per-course breakdown: which lesson, which provider (Vimeo, YouTube,
  Bunny Stream, or local/self-hosted), the title or filename, the video
  ID, and the duration for self-hosted files.
* Flags for videos that are tracked against a lesson but not actually
  embedded anywhere ("Not embedded"), and for the same video being used
  in more than one place.

The plugin is **strictly read-only**. It never changes your lessons,
never uploads anything, and makes no external API calls — everything is
detected from data already stored on your site.

= Upgrading =

Once you know what you have, [Video Flow for Tutor LMS](https://wpvideoflow.com/)
moves it to Bunny Stream and manages it — without breaking a single lesson
embed:

* Upload videos to Bunny Stream directly from the Tutor course builder — large,
  resumable uploads.
* Migrate existing Vimeo and self-hosted videos to Bunny Stream, one at a time
  or in bulk.
* Every migrated video keeps its exact place in the lesson; embeds are rewritten
  for you.
* A full Video Manager: transcode status, durations, per-video view counts, and
  orphaned-video detection.
* Library-wide views and average watch time for the last 7, 30, or 90 days.
* Custom video thumbnails, and automatic Tutor lesson completion when a learner
  finishes watching.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` or install it from
   Plugins → Add New.
2. Activate it. Tutor LMS must be active.
3. Go to **Tutor LMS → Video Audit**.

== Frequently Asked Questions ==

= Does this change my lessons or videos? =

No. It only reads. It never writes to lesson content, post meta, or any
video host.

= Does it send data anywhere? =

No. There are no external requests. Vimeo, YouTube and Bunny detection is
pure pattern-matching on content and meta already in your database.

= It shows a Vimeo video that I already migrated. Why? =

WordPress keeps an oEmbed cache. A migrated lesson can still have the old
Vimeo reference cached; the audit flags those rows as "From oEmbed cache".

= I have Video Flow for Tutor LMS installed. =

Then this plugin stays dormant — the paid plugin already includes the
audit view. You can deactivate this one.

== Screenshots ==

1. Course list with per-course video counts.
2. Per-course breakdown by lesson and provider.

== Changelog ==

= 1.0.0 =
* Initial release.
