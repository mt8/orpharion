<?php
/**
 * ProtectedOptions helper tests.
 *
 * @package Orpharion
 */

declare(strict_types=1);

namespace Orpharion\Tests;

use Orpharion\ProtectedOptions;
use Orpharion\Quarantine;
use WP_UnitTestCase;

/**
 * Covers the central protected-namespace rule set.
 *
 * @coversDefaultClass \Orpharion\ProtectedOptions
 */
class ProtectedOptionsTest extends WP_UnitTestCase {

	/**
	 * Core option names are protected regardless of casing or trailing space.
	 */
	public function test_core_option_is_protected_in_any_canonical_spelling(): void {
		$this->assertTrue( ProtectedOptions::is_core( 'siteurl' ) );
		$this->assertTrue( ProtectedOptions::is_core( 'SITEURL' ) );
		$this->assertTrue( ProtectedOptions::is_core( 'SiteUrl ' ) );
		$this->assertTrue( ProtectedOptions::is_protected( 'siteurl ' ) );
	}

	/**
	 * Orpharion's own plugin-option namespace is covered.
	 */
	public function test_internal_namespace_is_protected(): void {
		$this->assertTrue( ProtectedOptions::is_internal( 'orpharion_sampling_rate' ) );
		$this->assertTrue( ProtectedOptions::is_internal( 'ORPHARION_DB_VERSION' ) );
		$this->assertTrue( ProtectedOptions::is_protected( 'Orpharion_Internal ' ) );
		$this->assertFalse( ProtectedOptions::is_internal( 'my_plugin_orpharion_setting' ) );
	}

	/**
	 * The quarantine rename namespace is covered.
	 */
	public function test_quarantine_rename_namespace_is_protected(): void {
		$name = Quarantine::RENAME_PREFIX . 'some_option';
		$this->assertTrue( ProtectedOptions::is_quarantine_rename( $name ) );
		$this->assertTrue( ProtectedOptions::is_quarantine_rename( strtoupper( $name ) ) );
		$this->assertTrue( ProtectedOptions::is_protected( $name . ' ' ) );
	}

	/**
	 * Regular third-party option names are not protected.
	 */
	public function test_unrelated_names_are_not_protected(): void {
		$this->assertFalse( ProtectedOptions::is_protected( 'my_plugin_setting' ) );
		$this->assertFalse( ProtectedOptions::is_protected( 'woocommerce_settings' ) );
	}

	/**
	 * Reason message returned per protected category, null otherwise.
	 */
	public function test_protected_reason_returns_a_message_per_category(): void {
		$this->assertNotNull( ProtectedOptions::protected_reason( 'siteurl' ) );
		$this->assertNotNull( ProtectedOptions::protected_reason( 'orpharion_sampling_rate' ) );
		$this->assertNotNull(
			ProtectedOptions::protected_reason( Quarantine::RENAME_PREFIX . 'x' )
		);
		$this->assertNull( ProtectedOptions::protected_reason( 'my_plugin_setting' ) );
	}

	/**
	 * WHERE-ready `NOT LIKE` fragments cover both protected prefixes with
	 * their underscores escaped as LIKE literals.
	 */
	public function test_not_like_fragments_cover_both_namespaces(): void {
		$fragments = ProtectedOptions::not_like_fragments();
		$this->assertCount( 2, $fragments );
		$joined = implode( ' ', $fragments );
		$this->assertStringContainsString( 'option_name NOT LIKE', $joined );
		$this->assertStringContainsString( '\\_orpharion\\_q\\_\\_', $joined );
		$this->assertStringContainsString( 'orpharion\\_', $joined );
	}
}
