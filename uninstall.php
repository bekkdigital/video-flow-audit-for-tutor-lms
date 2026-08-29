<?php
/**
 * Uninstall cleanup for Video Flow Audit for Tutor LMS.
 *
 * This plugin stores nothing of its own except Core's short-lived scan
 * caches. It never touches Tutor LMS data (`_video`, `_oembed_*`,
 * `_tutor_course_id_for_lesson`) or the paid plugin's meta.
 *
 * @package VideoFlowAuditForTutorLMS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- one-off transient sweep on uninstall.
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '\_transient\_vf_course_videos\_%'
	    OR option_name LIKE '\_transient\_timeout\_vf_course_videos\_%'
	    OR option_name LIKE '\_transient\_vf_course_counts\_%'
	    OR option_name LIKE '\_transient\_timeout\_vf_course_counts\_%'"
);
