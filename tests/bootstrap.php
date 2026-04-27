<?php
/**
 * PHPUnit bootstrap for the Orpharion test suite.
 *
 * Uses the WordPress test harness mounted inside the wp-env tests container
 * (/wordpress-phpunit/includes/).
 *
 * @package Orpharion
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite

$orpharion_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( false === $orpharion_tests_dir ) {
	$orpharion_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $orpharion_tests_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "Could not find WordPress test suite at {$orpharion_tests_dir}.\n" );
	fwrite( STDERR, "Run tests via: npx wp-env run tests-cli --env-cwd=wp-content/plugins/orpharion composer test\n" );
	exit( 1 );
}

require_once $orpharion_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function orpharion_manually_load_plugin(): void {
	require dirname( __DIR__ ) . '/orpharion.php';
}
tests_add_filter( 'muplugins_loaded', 'orpharion_manually_load_plugin' );

require $orpharion_tests_dir . '/includes/bootstrap.php';

// phpcs:enable
