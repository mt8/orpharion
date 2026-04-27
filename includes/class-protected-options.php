<?php
/**
 * Central registry for option names that Orpharion refuses to touch.
 *
 * @package Orpharion
 */

declare(strict_types=1);

namespace Orpharion;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for protected option-name rules.
 *
 * Three categories are considered protected:
 *
 *  - **core**: names WordPress core manages (see `CoreOptions`).
 *  - **internal**: names Orpharion itself stores (`orpharion_*`).
 *  - **quarantine rename**: rows whose name starts with the quarantine
 *    rename prefix (`_orpharion_q__`) and whose lifecycle is owned by the
 *    manifest table.
 *
 * Every destructive module (`Cleaner`, `Quarantine`, `Importer`) routes
 * its guard decisions through this class, and the options list endpoint
 * derives its `NOT LIKE` filter from the same rules. Normalization matches
 * the `wp_options.option_name` collation semantics (case-insensitive, and
 * trailing whitespace is ignored because MySQL VARCHAR uses PAD SPACE in
 * WHERE clauses) so a non-canonical spelling cannot slip past the guard
 * while still matching the stored row.
 *
 * See docs/DESIGN.md §4 / §7.
 */
final class ProtectedOptions {

	/**
	 * Prefix reserved for Orpharion's own plugin options.
	 */
	public const INTERNAL_PREFIX = 'orpharion_';

	/**
	 * Normalizes an option name for guard comparison.
	 *
	 * The result is what the DB's default collation would compare the name
	 * against: lower-cased, with trailing whitespace stripped.
	 *
	 * @param string $option_name Raw name from user input or a payload.
	 */
	public static function normalize( string $option_name ): string {
		return strtolower( rtrim( $option_name ) );
	}

	/**
	 * True when the name is in the WordPress core list.
	 *
	 * @param string $option_name Option name to test.
	 */
	public static function is_core( string $option_name ): bool {
		return CoreOptions::contains( $option_name );
	}

	/**
	 * True when the name belongs to Orpharion's own plugin options.
	 *
	 * @param string $option_name Option name to test.
	 */
	public static function is_internal( string $option_name ): bool {
		return 0 === strpos( self::normalize( $option_name ), self::INTERNAL_PREFIX );
	}

	/**
	 * True when the name is in the quarantine rename namespace.
	 *
	 * @param string $option_name Option name to test.
	 */
	public static function is_quarantine_rename( string $option_name ): bool {
		return 0 === strpos( self::normalize( $option_name ), Quarantine::RENAME_PREFIX );
	}

	/**
	 * True when the name falls into any protected category.
	 *
	 * @param string $option_name Option name to test.
	 */
	public static function is_protected( string $option_name ): bool {
		return null !== self::protected_reason( $option_name );
	}

	/**
	 * Human-readable reason if the name is protected, or null if not.
	 *
	 * Used by every destructive module (`Cleaner`, `Quarantine`, `Importer`)
	 * to surface a consistent skip reason to the operator.
	 *
	 * @param string $option_name Option name to test.
	 */
	public static function protected_reason( string $option_name ): ?string {
		$normalized = self::normalize( $option_name );
		if ( CoreOptions::contains( $normalized ) ) {
			return sprintf(
				/* translators: %s: option name. */
				__( 'Skipped core option: %s', 'orpharion' ),
				$option_name
			);
		}
		if ( 0 === strpos( $normalized, Quarantine::RENAME_PREFIX ) ) {
			return sprintf(
				/* translators: %s: option name. */
				__( 'Skipped quarantine-managed option: %s', 'orpharion' ),
				$option_name
			);
		}
		if ( 0 === strpos( $normalized, self::INTERNAL_PREFIX ) ) {
			return sprintf(
				/* translators: %s: option name. */
				__( 'Skipped Orpharion internal option: %s', 'orpharion' ),
				$option_name
			);
		}
		return null;
	}

	/**
	 * Returns SQL `option_name NOT LIKE` fragments that mirror the
	 * protected-namespace rules, so the main list endpoint's filter is
	 * derived from the same source as the destructive-module guards.
	 *
	 * Underscores in the prefix constants are LIKE wildcards and are
	 * escaped here via `$wpdb->esc_like()` so the pattern matches only the
	 * intended literal prefix. The patterns only reference plugin-owned
	 * constants, so no user-controlled values are embedded.
	 *
	 * @return string[] WHERE-ready `NOT LIKE` fragments.
	 */
	public static function not_like_fragments(): array {
		global $wpdb;
		$quarantine = $wpdb->esc_like( Quarantine::RENAME_PREFIX ) . '%';
		$internal   = $wpdb->esc_like( self::INTERNAL_PREFIX ) . '%';
		return array(
			"option_name NOT LIKE '{$quarantine}'",
			"option_name NOT LIKE '{$internal}'",
		);
	}
}
