<?php
/**
 * Smoke tests for the plugin bootstrap.
 *
 * @package Orpharion
 */

declare(strict_types=1);

namespace Orpharion\Tests;

use WP_UnitTestCase;

/**
 * Basic bootstrap smoke test.
 *
 * @coversNothing
 */
class PluginBootstrapTest extends WP_UnitTestCase {

	/**
	 * The plugin defines its version constant.
	 */
	public function test_version_constant_is_defined(): void {
		$this->assertTrue( defined( 'ORPHARION_VERSION' ) );
		$this->assertIsString( ORPHARION_VERSION );
	}

	/**
	 * The main class is loaded via autoload.
	 */
	public function test_plugin_class_autoloaded(): void {
		$this->assertTrue( class_exists( \Orpharion\Plugin::class ) );
	}
}
