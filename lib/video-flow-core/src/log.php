<?php
/**
 * Video Flow Core — logging primitives.
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'VFAUDIT_CORE_DEBUG' ) ) {
	// Piggy-back on the host plugin's VF_DEBUG when it set one, otherwise off.
	define( 'VFAUDIT_CORE_DEBUG', defined( 'VF_DEBUG' ) && VF_DEBUG );
}

if ( ! function_exists( 'vfaudit_core_log' ) ) {
	/**
	 * Log an important lifecycle event. Always writes.
	 *
	 * @param string $message Message body; a "[VF CORE]" prefix is added.
	 */
	function vfaudit_core_log( string $message ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- this IS the package's logging primitive.
		error_log( '[VF CORE] ' . $message );
	}
}

if ( ! function_exists( 'vfaudit_core_log_debug' ) ) {
	/**
	 * Log a routine diagnostic trace. Only writes when VFAUDIT_CORE_DEBUG is true.
	 *
	 * @param string $message Message body; a "[VF CORE DEBUG]" prefix is added.
	 */
	function vfaudit_core_log_debug( string $message ): void {
		if ( VFAUDIT_CORE_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- gated behind VFAUDIT_CORE_DEBUG.
			error_log( '[VF CORE DEBUG] ' . $message );
		}
	}
}
