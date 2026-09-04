<?php
/**
 * Coexistence with the commercial "Video Flow for Tutor LMS" plugin,
 * which already includes this audit view.
 *
 * To avoid a duplicate menu — and any chance of a function-name clash
 * between bundled libraries — this plugin goes dormant (loads nothing,
 * shows one notice) whenever that plugin is active.
 *
 * @package VideoFlowAuditForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the commercial "Video Flow for Tutor LMS" plugin active (site or network)?
 */
function vfaudit_companion_plugin_active(): bool {
	$companion = 'video-flow-for-tutor-lms/video-flow-for-tutor-lms.php';

	$active = (array) get_option( 'active_plugins', array() );
	if ( in_array( $companion, $active, true ) ) {
		return true;
	}

	if ( is_multisite() ) {
		$network = (array) get_site_option( 'active_sitewide_plugins', array() );
		if ( isset( $network[ $companion ] ) ) {
			return true;
		}
	}

	// Belt and braces: the companion plugin defines this.
	return function_exists( 'vf_video_flow_page' );
}

/**
 * One-time admin notice shown while dormant.
 */
function vfaudit_render_dormant_notice(): void {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	echo '<div class="notice notice-info is-dismissible"><p>';
	echo esc_html__(
		'Video Flow for Tutor LMS is active — it already includes the video audit view. You can safely deactivate Video Flow Audit for Tutor LMS.',
		'video-flow-audit-for-tutor-lms'
	);
	echo '</p></div>';
}
