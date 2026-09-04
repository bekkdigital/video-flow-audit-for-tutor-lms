<?php
/**
 * Video Flow Core — package entry point / version-negotiating loader.
 *
 * A consuming plugin bundles its own copy of this package. When more than
 * one active plugin bundles it, each copy runs this file at plugin-load
 * time, registers its version, and the newest copy wins — its
 * src/bootstrap.php is the only one that actually defines the vfaudit_core_*
 * functions.
 *
 * This mirrors the loader pattern used by Action Scheduler and similar
 * shared libraries. No classes, no namespace — the Video Flow family is
 * deliberately flat.
 *
 * @package VideoFlowCore
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'vfaudit_core_register_package' ) ) {

	// Holds every registered copy: version string => absolute bootstrap path.
	// Populated by vfaudit_core_register_package() below, drained by
	// vfaudit_core_load_registered_package().
	$GLOBALS['vfaudit_core_packages'] = array();

	/**
	 * Register one bundled copy of Video Flow Core as a load candidate.
	 *
	 * @param string $version        Semver of this copy (hard-coded at the call site below).
	 * @param string $bootstrap_path Absolute path to this copy's src/bootstrap.php.
	 */
	function vfaudit_core_register_package( string $version, string $bootstrap_path ): void {
		$GLOBALS['vfaudit_core_packages'][ $version ] = $bootstrap_path;
	}

	/**
	 * Load the highest registered version exactly once.
	 *
	 * Runs early on plugins_loaded so the vfaudit_core_* API is available before
	 * any plugin's own plugins_loaded (default priority) callbacks fire.
	 */
	function vfaudit_core_load_registered_package(): void {
		if ( defined( 'VFAUDIT_CORE_LOADED' ) ) {
			return;
		}

		$packages = $GLOBALS['vfaudit_core_packages'] ?? array();
		if ( empty( $packages ) ) {
			return;
		}

		uksort( $packages, 'version_compare' );
		end( $packages );

		$version   = (string) key( $packages );
		$bootstrap = (string) current( $packages );

		if ( ! is_readable( $bootstrap ) ) {
			return;
		}

		define( 'VFAUDIT_CORE_LOADED', $version );
		require_once $bootstrap;
	}

	// Priority 3: late enough that every plugin has required its bundled copy
	// at file scope, early enough to beat default-priority plugins_loaded work.
	// Guarded so the file is also safe to load outside WordPress (unit tests,
	// static analysis, Composer's files autoloader running early).
	if ( function_exists( 'add_action' ) ) {
		add_action( 'plugins_loaded', 'vfaudit_core_load_registered_package', 3 );
	}
}

// This copy's version. Bump in lockstep with the composer.json "version" /
// git tag whenever the package is released.
vfaudit_core_register_package( '1.0.1', __DIR__ . '/src/bootstrap.php' );
