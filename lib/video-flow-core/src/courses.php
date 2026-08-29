<?php
/**
 * Video Flow Core — course-level roll-ups for the audit list view.
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'vfaudit_core_get_courses_with_video_counts' ) ) {

	/**
	 * Every course that has at least one detected video, with its count.
	 * Result is cached for 5 minutes (this runs a full scan per course).
	 *
	 * @param int $author_id Optional author filter (0 = all).
	 * @return array<int,object{course_id:int,video_count:int}>
	 */
	function vfaudit_core_get_courses_with_video_counts( int $author_id = 0 ): array {
		if ( ! vfaudit_core_has_adapter() ) {
			return array();
		}

		// Only 0 (all) or a real user id — stops a CSRF'd ?author_id=<n> loop
		// from writing a transient per bogus value.
		if ( $author_id > 0 && ! get_userdata( $author_id ) ) {
			return array();
		}

		$cache_key = 'vfaudit_course_counts_' . ( $author_id > 0 ? $author_id : 'all' );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$rows = array();
		foreach ( vfaudit_core_adapter_list_course_ids( $author_id ) as $course_id ) {
			$count = count( vfaudit_core_get_course_videos( $course_id ) );
			if ( $count < 1 ) {
				continue;
			}
			$row              = new stdClass();
			$row->course_id   = $course_id;
			$row->video_count = $count;
			$rows[]           = $row;
		}

		set_transient( $cache_key, $rows, 5 * MINUTE_IN_SECONDS );
		return $rows;
	}

	/**
	 * Drop the cached course-count roll-ups (all author variants).
	 */
	function vfaudit_core_invalidate_course_counts_cache(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- targeted transient cleanup, no WP API for wildcard transient delete.
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_vfaudit_course_counts\_%' OR option_name LIKE '\_transient\_timeout\_vfaudit_course_counts\_%'"
		);
	}
}
