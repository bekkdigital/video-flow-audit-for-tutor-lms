<?php
// PHPStan CLI bootstrap. Not loaded by WordPress and not shipped (see
// .distignore). PHPStan's WordPress extension defines ABSPATH before this
// runs, so the guard below is a no-op during analysis but still blocks
// direct web access.
defined( 'ABSPATH' ) || exit;

defined( 'VFAUDIT_VERSION' ) || define( 'VFAUDIT_VERSION', '1.0.0' );
defined( 'VFAUDIT_PLUGIN_FILE' ) || define( 'VFAUDIT_PLUGIN_FILE', '/tmp/wp/plugin.php' );
defined( 'VFAUDIT_MENU_SLUG' ) || define( 'VFAUDIT_MENU_SLUG', 'tutor-video-flow-audit' );
defined( 'VFAUDIT_CORE_LOADED' ) || define( 'VFAUDIT_CORE_LOADED', '1.0.0' );
defined( 'VFAUDIT_CORE_DEBUG' ) || define( 'VFAUDIT_CORE_DEBUG', false );
defined( 'MINUTE_IN_SECONDS' ) || define( 'MINUTE_IN_SECONDS', 60 );
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core constant, declared for static analysis only.
defined( 'WP_UNINSTALL_PLUGIN' ) || define( 'WP_UNINSTALL_PLUGIN', true );

if ( ! function_exists( 'tutor_utils' ) ) {
	function tutor_utils() {} // phpcs:ignore
}
if ( ! function_exists( 'tutor' ) ) {
	function tutor() {} // phpcs:ignore
}
