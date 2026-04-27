<?php
/**
 * Export module.
 *
 * @package Orpharion
 */

declare(strict_types=1);

namespace Orpharion;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Builds JSON exports of wp_options rows with their tracking context.
 *
 * See docs/DESIGN.md §4.3.
 */
final class Exporter {

	/**
	 * Version stamp of the export JSON schema.
	 *
	 * 1.1.0 — drops the legacy `score` object from each option entry.
	 *         Import still accepts 1.0.0 payloads (score is ignored).
	 */
	public const FORMAT_VERSION = '1.1.0';

	/**
	 * Builds the export payload for the given option names.
	 *
	 * @param string[] $option_names option_name values to include.
	 *
	 * @return array<string,mixed> Export payload (not yet JSON-encoded).
	 */
	public static function build_export( array $option_names ): array {
		$option_names = array_values( array_unique( array_filter( array_map( 'strval', $option_names ) ) ) );

		$options = array();
		foreach ( $option_names as $name ) {
			$row = self::fetch_option_row( $name );
			if ( null === $row ) {
				continue;
			}
			$options[] = array(
				'option_name'  => $name,
				'option_value' => (string) $row['option_value'],
				'autoload'     => (string) $row['autoload'],
				'tracking'     => self::fetch_tracking_row( $name ),
			);
		}

		return array(
			'version'     => self::FORMAT_VERSION,
			'exported_at' => gmdate( 'c' ),
			'site_url'    => home_url(),
			'wp_version'  => get_bloginfo( 'version' ),
			'options'     => $options,
		);
	}

	/**
	 * Encodes the build_export() result as pretty-printed JSON.
	 *
	 * @param string[] $option_names option_name values to include.
	 */
	public static function to_json( array $option_names ): string {
		$payload = self::build_export( $option_names );
		return (string) wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Resolves a CLI `--output` value to an absolute, writable path under
	 * `wp-content/uploads/orpharion/`.
	 *
	 * Only bare `*.json` filenames are accepted. Paths that contain a
	 * directory component are rejected so a misuse such as
	 * `--output=/var/www/html/exports.json` cannot land option_value
	 * payloads (which may contain API keys, SMTP credentials, etc.) in a
	 * web-accessible location.
	 *
	 * @param string $filename Bare filename ending in `.json`.
	 *
	 * @return string|WP_Error Absolute path on success, WP_Error on rejection.
	 */
	public static function resolve_export_path( string $filename ) {
		if ( '' === $filename ) {
			return new WP_Error(
				'orpharion_export_empty_name',
				__( 'Export filename is empty.', 'orpharion' )
			);
		}

		if ( basename( $filename ) !== $filename ) {
			return new WP_Error(
				'orpharion_export_path_with_directory',
				__( 'Pass a bare filename. Exports always land in wp-content/uploads/orpharion/.', 'orpharion' )
			);
		}

		$sanitized = sanitize_file_name( $filename );
		if ( '' === $sanitized || $sanitized !== $filename ) {
			return new WP_Error(
				'orpharion_export_invalid_name',
				__( 'Export filename contains characters that are not allowed.', 'orpharion' )
			);
		}

		if ( ! str_ends_with( strtolower( $filename ), '.json' ) ) {
			return new WP_Error(
				'orpharion_export_bad_extension',
				__( 'Export filename must end with .json.', 'orpharion' )
			);
		}

		$upload = wp_upload_dir( null, false );
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'orpharion_uploads_unavailable', (string) $upload['error'] );
		}

		$dir = self::ensure_export_dir( (string) $upload['basedir'] );
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}

		return $dir . '/' . $filename;
	}

	/**
	 * Creates `wp-content/uploads/orpharion/` on demand and protects it
	 * from public listing.
	 *
	 * @param string $uploads_basedir Absolute path to wp_upload_dir()['basedir'].
	 *
	 * @return string|WP_Error Absolute export directory or an error.
	 */
	private static function ensure_export_dir( string $uploads_basedir ) {
		$dir = rtrim( $uploads_basedir, '/' ) . '/orpharion';
		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error(
				'orpharion_export_dir_create',
				__( 'Could not create the export directory.', 'orpharion' )
			);
		}

		// Mirror the protections WordPress core puts on its own upload tree.
		$index = $dir . '/index.html';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Require all denied\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return $dir;
	}

	/**
	 * Resolves a suggested filename for a given export payload.
	 *
	 * @param string $site_url home URL of the exporting site.
	 */
	public static function suggested_filename( string $site_url ): string {
		$host = wp_parse_url( $site_url, PHP_URL_HOST );
		$host = is_string( $host ) && '' !== $host ? $host : 'site';
		$host = sanitize_file_name( $host );
		return sprintf( 'orpharion-export-%s-%s.json', $host, gmdate( 'Ymd-His' ) );
	}

	/**
	 * Reads a wp_options row by name.
	 *
	 * @param string $option_name option_name.
	 *
	 * @return array{option_value:string,autoload:string}|null
	 */
	private static function fetch_option_row( string $option_name ): ?array {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT option_value, autoload FROM {$wpdb->options} WHERE option_name = %s", $option_name ),
			ARRAY_A
		);
		// phpcs:enable
		return is_array( $row ) ? $row : null;
	}

	/**
	 * Reads a tracking row by option_name.
	 *
	 * @param string $option_name option_name.
	 *
	 * @return array<string,mixed>|null
	 */
	private static function fetch_tracking_row( string $option_name ): ?array {
		global $wpdb;
		$table = Schema::tracking_table();
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT last_read_at, read_count, last_reader, reader_type FROM {$table} WHERE option_name = %s", $option_name ),
			ARRAY_A
		);
		// phpcs:enable
		if ( ! is_array( $row ) ) {
			return null;
		}
		$row['read_count'] = (int) $row['read_count'];
		return $row;
	}
}
