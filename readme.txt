=== Video Flow Audit for Tutor LMS ===
Contributors: bekkdigitalstudio
Tags: tutor lms, video audit, video management, vimeo, bunny stream
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free, read-only Tutor LMS video audit plugin that finds Vimeo, YouTube, Bunny Stream and self-hosted videos by course and lesson.

== Description ==

**Video Flow Audit for Tutor LMS is a free, read-only WordPress plugin that scans your Tutor LMS courses and creates an inventory of Vimeo, YouTube, Bunny Stream and self-hosted videos by course and lesson.**

Videos in Tutor LMS can be referenced in several different places: the lesson video field, lesson content, WordPress oEmbed data and self-hosted media files.

Video Flow Audit brings those references together in one administrative view so you can understand your existing Tutor LMS video library before cleaning it up, changing video hosts or migrating videos.

The plugin is intentionally read-only. It does not modify lessons, move files, upload videos or connect to external video-hosting APIs.

= Features =

* Audit video references across Tutor LMS courses and lessons.
* Detect Vimeo video references.
* Detect YouTube video references.
* Detect Bunny Stream video references.
* Detect supported externally embedded Bunny Stream videos.
* Detect supported self-hosted WordPress videos.
* Show videos grouped by course and lesson.
* Filter the course overview by author.
* Show available video titles or filenames.
* Show video IDs with one-click copy.
* Show duration information for supported self-hosted files.
* Flag the same underlying video when it is referenced in multiple places.
* Flag supported WordPress oEmbed references.
* Flag video references associated with a lesson but not currently embedded in an active lesson field or content location.
* Read-only operation — no course content is modified.
* No video-host credentials required.
* No external API calls required.
* No WPVideoFlow account or licence key required.

= What does Video Flow Audit scan? =

Video Flow Audit examines Tutor LMS and WordPress data already stored on your site.

Supported sources and reference locations include:

* Tutor LMS lesson video fields
* Video URLs and embeds stored in lesson content
* WordPress oEmbed cache data
* Vimeo video references
* YouTube video references
* Bunny Stream video references
* Supported externally embedded Bunny Stream videos
* Supported self-hosted videos associated with the WordPress Media Library

The audit reports what it can identify from your WordPress site. It does not log in to Vimeo, YouTube, Bunny Stream or other external video platforms.

= What information does the audit show? =

For each Tutor LMS course with detected videos, the audit can show:

* Course name
* Course author
* Number of detected video references
* Lesson containing the video reference
* Video provider
* Video title or filename where available
* Video ID where available
* Duration for supported self-hosted files
* Usage information when the same underlying video is referenced in multiple places
* Relevant audit flags

= Audit flags =

**Not embedded**

A video reference is associated with a Tutor LMS lesson but is not currently detected in an active lesson video field or content location.

This can occur after an interrupted or changed video workflow.

**From oEmbed cache**

WordPress still contains an oEmbed reference for the video.

For example, a lesson that previously used Vimeo may retain an older Vimeo oEmbed cache entry after the active lesson video has been changed.

**Used in N places**

The same underlying video is referenced from multiple lessons or courses.

This gives you a clearer picture of video usage before replacing, migrating or removing a video.

= Who is Video Flow Audit for? =

Video Flow Audit is designed for Tutor LMS site owners, course creators and WordPress administrators who need to understand their existing course-video setup.

It is particularly useful before:

* Migrating course videos from Vimeo
* Moving videos to Bunny Stream
* Reviewing a mixed video-hosting setup
* Cleaning up an older Tutor LMS site
* Reviewing self-hosted course videos
* Replacing old video references
* Checking whether the same video is used in multiple lessons
* Planning a course-video migration
* Reviewing a Tutor LMS video library before making changes

= Read-only by design =

Video Flow Audit does not:

* Edit Tutor LMS lessons
* Replace video URLs
* Upload videos
* Delete videos
* Move videos between hosting platforms
* Connect to Vimeo
* Connect to YouTube
* Connect to Bunny Stream
* Require external API keys
* Require an account with WPVideoFlow

The audit is based on information already stored on your WordPress site.

= Privacy =

Video Flow Audit does not send your course or video data to WPVideoFlow.

The plugin performs its audit using data stored locally in your WordPress installation.

It does not require API credentials for Vimeo, YouTube or Bunny Stream.

= Need migration and video management too? =

Video Flow Audit is intentionally read-only.

If you also need to migrate Vimeo or self-hosted course videos to Bunny Stream, upload videos through Tutor LMS workflows, reuse existing Bunny Stream videos, or manage your course-video library from a central Video Manager, see:

[Video Flow for Tutor LMS](https://wpvideoflow.com/video-flow-for-tutor-lms/)

More information about the free audit plugin:

[Video Flow Audit for Tutor LMS](https://wpvideoflow.com/video-flow-audit-for-tutor-lms/)

== Installation ==

1. In WordPress, go to **Plugins > Add New**.
2. Search for **Video Flow Audit for Tutor LMS**.
3. Install the plugin.
4. Activate it.
5. Make sure Tutor LMS is active.
6. Go to **Tutor LMS > Video Audit**.
7. Review your courses and detected video references.

You can also download the plugin from WordPress.org and upload it manually to `/wp-content/plugins/`.

== Frequently Asked Questions ==

= Does Video Flow Audit change anything in my Tutor LMS courses? =

No.

Video Flow Audit is strictly read-only. It does not edit lesson content, change Tutor LMS data, move files or modify your video-hosting accounts.

= Does Video Flow Audit work with Tutor LMS Free? =

Yes.

Video Flow Audit works with both Tutor LMS Free and Tutor LMS Pro.

= Can Video Flow Audit find Vimeo videos used in Tutor LMS? =

Yes.

If a supported Vimeo reference is stored in the Tutor LMS or WordPress data scanned by the plugin, Video Flow Audit can identify it and show the course and lesson where it is referenced.

= Can Video Flow Audit find Bunny Stream videos? =

Yes.

Video Flow Audit can identify supported Bunny Stream references already stored in Tutor LMS or WordPress.

It does not require access to your Bunny account or Bunny API credentials.

= Can it find YouTube videos used in Tutor LMS? =

Yes.

Supported YouTube video references stored in the Tutor LMS or WordPress data scanned by the plugin can be included in the audit.

= Can it find self-hosted Tutor LMS videos? =

Yes.

Video Flow Audit can identify supported self-hosted video references, including videos associated with the WordPress Media Library.

= Can it show if the same video is used in several lessons? =

Yes.

When the same underlying video is detected in multiple locations, Video Flow Audit can flag the usage so you can see where it is referenced before making changes.

= Can Video Flow Audit find unused or orphaned videos? =

Video Flow Audit audits video references found in Tutor LMS and WordPress data.

It can identify situations such as a video reference associated with a lesson but not currently embedded in an active lesson field or content location.

It does not perform a complete remote-library orphan scan of your Vimeo, Bunny Stream or other external video-hosting account.

= It shows a Vimeo video that I already migrated. Why? =

WordPress can retain oEmbed cache data for an older video URL.

For example, after changing a lesson from Vimeo to another video source, the old Vimeo reference may still exist in the WordPress oEmbed cache.

Video Flow Audit can identify supported cases like this and flag them as **From oEmbed cache**.

= Does Video Flow Audit send data anywhere? =

No.

The audit is based on information already stored in WordPress.

The plugin does not need to send your Tutor LMS course or video data to WPVideoFlow in order to perform the audit.

= Do I need a Vimeo, YouTube or Bunny Stream account to run the audit? =

No.

Video Flow Audit does not log in to those services or require video-host API credentials.

It reports on supported video references already stored on your WordPress site.

= Can Video Flow Audit migrate videos? =

No.

Video Flow Audit is intentionally read-only.

For supported Vimeo and self-hosted video migration to Bunny Stream and ongoing video-management workflows, see [Video Flow for Tutor LMS](https://wpvideoflow.com/video-flow-for-tutor-lms/).

= Can I use Video Flow Audit alongside Video Flow for Tutor LMS? =

Video Flow for Tutor LMS already includes the audit functionality.

If Video Flow for Tutor LMS is active, the standalone free audit plugin does not need to provide a duplicate audit workflow.

= What happens if I deactivate or uninstall Video Flow Audit? =

Your Tutor LMS course content and video-hosting accounts are not modified by the audit, so deactivating or uninstalling the plugin does not undo or alter your course videos.

= Will Video Flow Audit slow down my public site? =

The video audit is an administrative workflow and does not add a full video-library scan to normal front-end Tutor LMS page loads.

Audit results can be cached briefly to avoid unnecessary repeated scanning while you review them.

== Screenshots ==

1. Tutor LMS course overview showing each course and its detected video count.
2. Per-course Tutor LMS video audit showing lesson, provider, video ID, title or filename, and video usage information.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Added Tutor LMS course and lesson video auditing.
* Added detection for supported Vimeo, YouTube, Bunny Stream and self-hosted video references.
* Added course-level video counts and per-course video breakdowns.
* Added support for relevant WordPress oEmbed cache references.
* Added flags for video references not currently embedded in an active lesson location.
* Added detection of the same underlying video referenced from multiple locations.
* Added read-only operation with no external video-host API requirement.

== Upgrade Notice ==

= 1.0.0 =
Initial release of Video Flow Audit for Tutor LMS.
