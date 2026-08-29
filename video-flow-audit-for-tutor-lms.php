<?php
/**
 * Plugin Name:       Video Flow Audit for Tutor LMS
 * Plugin URI:        https://wpvideoflow.com/video-flow-audit-for-tutor-lms/
 * Description:       Audit Tutor LMS course videos and identify video configuration and usage issues.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.0
 * Author:            Bekk Digital Studio
 * Author URI:        https://wpvideoflow.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       video-flow-audit-for-tutor-lms
 * Requires Plugins:  tutor
 */

defined( 'ABSPATH' ) || exit;

define( 'VFAUDIT_VERSION', '1.0.0' );
define( 'VFAUDIT_PLUGIN_FILE', __FILE__ );
define( 'VFAUDIT_MENU_SLUG', 'tutor-video-flow-audit' );

require_once plugin_dir_path( __FILE__ ) . 'includes/compat.php';

// The paid "Video Flow for Tutor LMS" already ships this audit view (and a
// full-featured Video Manager). When it is active, stay completely dormant —
// don't load Core, don't register a menu — and point the user at it once.
if ( vfaudit_paid_plugin_active() ) {
	add_action( 'admin_notices', 'vfaudit_render_dormant_notice' );
	return;
}

require_once plugin_dir_path( __FILE__ ) . 'lib/video-flow-core/video-flow-core.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/adapters/tutor.php';

if ( is_admin() ) {
	require_once plugin_dir_path( __FILE__ ) . 'includes/admin-audit-page.php';
}

/**
 * Register the "Video Audit" screen under the Tutor LMS menu.
 */
add_action(
	'admin_menu',
	function () {
		$hook = add_submenu_page(
			'tutor',
			__( 'Video Flow Audit for Tutor LMS', 'video-flow-audit-for-tutor-lms' ),
			__( 'Video Audit', 'video-flow-audit-for-tutor-lms' ),
			'manage_options',
			VFAUDIT_MENU_SLUG,
			'vfaudit_render_page'
		);
		if ( $hook ) {
			$GLOBALS['vfaudit_page_hook'] = $hook;
		}
	},
	99
);

/**
 * Bust Core's scan caches when a course / lesson / topic changes, so the
 * audit view never shows a stale list. Scoped to the post types that can
 * hold a video.
 *
 * @param int $post_id Saved / deleted post ID.
 */
function vfaudit_bust_scan_caches( $post_id ): void {
	if ( ! function_exists( 'vfaudit_core_invalidate_course_videos_cache' ) ) {
		return;
	}
	if ( ! in_array( get_post_type( (int) $post_id ), array( 'courses', 'lesson', 'topics' ), true ) ) {
		return;
	}
	vfaudit_core_invalidate_course_videos_cache( (int) $post_id );
	vfaudit_core_invalidate_course_counts_cache();
}
add_action( 'save_post', 'vfaudit_bust_scan_caches' );
add_action( 'deleted_post', 'vfaudit_bust_scan_caches' );
add_action( 'trashed_post', 'vfaudit_bust_scan_caches' );
add_action( 'untrashed_post', 'vfaudit_bust_scan_caches' );
