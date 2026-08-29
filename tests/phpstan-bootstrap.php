<?php
// Constants defined by the plugin's main file / bundled Core that analysis needs.
defined( 'ABSPATH' ) || define( 'ABSPATH', '/tmp/wp/' );
defined( 'VFAUDIT_VERSION' ) || define( 'VFAUDIT_VERSION', '1.0.0' );
defined( 'VFAUDIT_PLUGIN_FILE' ) || define( 'VFAUDIT_PLUGIN_FILE', '/tmp/wp/plugin.php' );
defined( 'VFAUDIT_MENU_SLUG' ) || define( 'VFAUDIT_MENU_SLUG', 'tutor-video-flow-audit' );
defined( 'VFAUDIT_CORE_LOADED' ) || define( 'VFAUDIT_CORE_LOADED', '1.0.0' );
defined( 'VFAUDIT_CORE_DEBUG' ) || define( 'VFAUDIT_CORE_DEBUG', false );
defined( 'MINUTE_IN_SECONDS' ) || define( 'MINUTE_IN_SECONDS', 60 );
defined( 'WP_UNINSTALL_PLUGIN' ) || define( 'WP_UNINSTALL_PLUGIN', true );

if ( ! function_exists( 'tutor_utils' ) ) {
	function tutor_utils() {} // phpcs:ignore
}
if ( ! function_exists( 'tutor' ) ) {
	function tutor() {} // phpcs:ignore
}
