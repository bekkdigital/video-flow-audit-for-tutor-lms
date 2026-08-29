<?php
/**
 * Coexistence with the paid "Video Flow for Tutor LMS" plugin.
 *
 * The two plugins share the Video Flow Core library but the paid one also
 * defines its own vf_core_* scanner (until it is migrated onto Core). To
 * avoid a fatal redeclaration — and a duplicate menu — this free plugin
 * bails out entirely whenever the paid plugin is active.
 *
 * @package VideoFlowAuditForTutorLMS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is the paid "Video Flow for Tutor LMS" plugin active (site or network)?
 */
function vfaudit_paid_plugin_active(): bool {
	$paid = 'video-flow-for-tutor-lms/video-flow-for-tutor-lms.php';

	$active = (array) get_option( 'active_plugins', array() );
	if ( in_array( $paid, $active, true ) ) {
		return true;
	}

	if ( is_multisite() ) {
		$network = (array) get_site_option( 'active_sitewide_plugins', array() );
		if ( isset( $network[ $paid ] ) ) {
			return true;
		}
	}

	// Belt and braces: the paid plugin defines this.
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
