<?php
/**
 * Orpharion uninstall script.
 *
 * Runs when the plugin is deleted from the WordPress admin. Drops every
 * artifact the plugin installs so no orphan data remains on the site.
 *
 * @package Orpharion
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Runs the uninstall cleanup. Wrapped so the top-level file scope stays clean.
 */
function orpharion_run_uninstall(): void {
	global $wpdb;

	$quarantine_table = $wpdb->prefix . 'options_quarantine';
	$rename_prefix    = '_orpharion_q__';

	// Restore any currently active quarantine renames so we do not orphan
	// renamed wp_options rows when the manifest table is dropped. The
	// plugin's classes are not autoloaded during uninstall, so the logic
	// is inlined here.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$active_manifest = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT original_name, original_autoload FROM {$quarantine_table} WHERE status = %s",
			'active'
		),
		ARRAY_A
	);
	// phpcs:enable
	if ( is_array( $active_manifest ) ) {
		foreach ( $active_manifest as $row ) {
			$original = (string) $row['original_name'];
			$renamed  = $rename_prefix . $original;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->options,
				array(
					'option_name' => $original,
					'autoload'    => (string) $row['original_autoload'],
				),
				array( 'option_name' => $renamed ),
				array( '%s', '%s' ),
				array( '%s' )
			);
		}
	}

	// Drop custom tables.
	$tables = array(
		$wpdb->prefix . 'options_tracking',
		$quarantine_table,
	);
	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table );
	}

	// Delete plugin options and transients.
	delete_option( 'orpharion_db_version' );
	delete_option( 'orpharion_sampling_rate' );
	delete_option( 'orpharion_quarantine_expiry_days' );
	delete_option( 'orpharion_quarantine_expiry_action' );
	delete_transient( 'orpharion_tracking_enabled' );

	// Clear the cron job.
	$timestamp = wp_next_scheduled( 'orpharion_quarantine_check' );
	if ( false !== $timestamp ) {
		wp_unschedule_event( $timestamp, 'orpharion_quarantine_check' );
	}
	wp_clear_scheduled_hook( 'orpharion_quarantine_check' );
}

orpharion_run_uninstall();
