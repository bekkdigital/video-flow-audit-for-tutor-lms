<?php
/**
 * Video Flow Core — display formatting helpers.
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'vfaudit_core_format_duration' ) ) {
	/**
	 * Seconds -> "H:MM:SS" (with hours) or "M:SS" (without).
	 */
	function vfaudit_core_format_duration( int $seconds ): string {
		if ( $seconds < 0 ) {
			$seconds = 0;
		}
		$h = intdiv( $seconds, 3600 );
		$m = intdiv( $seconds % 3600, 60 );
		$s = $seconds % 60;

		return $h > 0
			? sprintf( '%d:%02d:%02d', $h, $m, $s )
			: sprintf( '%d:%02d', $m, $s );
	}
}
