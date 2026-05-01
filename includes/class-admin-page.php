<?php
/**
 * Admin page registration and SPA mount point.
 *
 * @package Orpharion
 */

declare(strict_types=1);

namespace Orpharion;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the top-level "Orpharion" admin menu and enqueues the React app bundle.
 */
final class Admin_Page {

	/**
	 * Slug used for the admin page hook and menu entry.
	 */
	public const MENU_SLUG = 'orpharion';

	/**
	 * DOM id that the React app mounts into.
	 */
	public const ROOT_ID = 'orpharion-admin-root';

	/**
	 * Hook suffix produced by add_menu_page() for the Orpharion screen.
	 */
	public const HOOK_SUFFIX = 'toplevel_page_' . self::MENU_SLUG;

	/**
	 * Style handle for the menu-icon opacity override.
	 *
	 * Loaded on every admin page (not only the Orpharion screen) because
	 * the top-level menu is rendered everywhere in wp-admin.
	 */
	private const MENU_ICON_STYLE_HANDLE = 'orpharion-menu';

	/**
	 * Registers admin hooks.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_menu_icon_style' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Adds the top-level Orpharion menu with its branded icon.
	 */
	public static function add_menu(): void {
		add_menu_page(
			__( 'Orpharion', 'orpharion' ),
			__( 'Orpharion', 'orpharion' ),
			'manage_options',
			self::MENU_SLUG,
			array( self::class, 'render' ),
			self::menu_icon(),
			80
		);
	}

	/**
	 * Enqueues the menu-icon opacity override on every admin page.
	 *
	 * WordPress dims menu icon images with opacity:0.6 by default; this
	 * override keeps the branded colors at their intended intensity. The
	 * rule is attached to a no-source registered style via
	 * wp_add_inline_style() so it goes through the standard stylesheet
	 * pipeline rather than a raw <style> tag in admin_head.
	 */
	public static function enqueue_menu_icon_style(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		wp_register_style( self::MENU_ICON_STYLE_HANDLE, false, array(), ORPHARION_VERSION );
		wp_enqueue_style( self::MENU_ICON_STYLE_HANDLE );
		wp_add_inline_style(
			self::MENU_ICON_STYLE_HANDLE,
			'#adminmenu .toplevel_page_' . self::MENU_SLUG . ' .wp-menu-image img{opacity:1;}'
		);
	}

	/**
	 * Returns the admin menu icon URL, or a dashicon fallback.
	 *
	 * A plain URL is used instead of a base64 data URI so that wp.svgPainter
	 * (wp-admin/js/svg-painter.js) does not rewrite every `fill` attribute to
	 * the admin color scheme's base color. The painter only scans elements
	 * whose background-image starts with `data:image/svg+xml;base64,`.
	 *
	 * @return string
	 */
	private static function menu_icon(): string {
		$icon_path = ORPHARION_DIR . 'assets/orpharion-menu-icon.svg';
		if ( is_readable( $icon_path ) ) {
			return ORPHARION_URL . 'assets/orpharion-menu-icon.svg';
		}
		return 'dashicons-admin-generic';
	}

	/**
	 * Renders the SPA mount point.
	 */
	public static function render(): void {
		echo '<div class="wrap">';
		echo '<h1 class="orpharion-title">';
		echo '<img class="orpharion-title__logo" src="' . esc_url( ORPHARION_URL . 'assets/orpharion-icon.svg' ) . '" alt="" />';
		echo '<span>' . esc_html__( 'Orpharion', 'orpharion' ) . '</span>';
		echo '</h1>';
		echo '<div id="' . esc_attr( self::ROOT_ID ) . '">';
		echo '<p>' . esc_html__( 'Loading Orpharion…', 'orpharion' ) . '</p>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Enqueues the React bundle on the Orpharion admin screen only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( self::HOOK_SUFFIX !== $hook_suffix ) {
			return;
		}

		$asset_file = ORPHARION_DIR . 'build/index.asset.php';
		if ( ! file_exists( $asset_file ) ) {
			// Build has not been produced yet; surface a notice and bail.
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-warning"><p>';
					echo esc_html__( 'Orpharion admin UI build not found. Run `npm run build` in the plugin directory.', 'orpharion' );
					echo '</p></div>';
				}
			);
			return;
		}

		$asset = include $asset_file;
		$deps  = isset( $asset['dependencies'] ) && is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array();
		$ver   = isset( $asset['version'] ) ? (string) $asset['version'] : ORPHARION_VERSION;

		wp_enqueue_script(
			'orpharion-admin',
			ORPHARION_URL . 'build/index.js',
			$deps,
			$ver,
			true
		);
		wp_set_script_translations( 'orpharion-admin', 'orpharion', ORPHARION_DIR . 'languages' );

		if ( file_exists( ORPHARION_DIR . 'build/index.css' ) ) {
			wp_enqueue_style(
				'orpharion-admin',
				ORPHARION_URL . 'build/index.css',
				array(),
				$ver
			);
		}

		wp_localize_script(
			'orpharion-admin',
			'orpharionConfig',
			array(
				'restNamespace' => Rest_Controller::NAMESPACE_V1,
				'restRoot'      => esc_url_raw( rest_url( Rest_Controller::NAMESPACE_V1 . '/' ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'rootId'        => self::ROOT_ID,
			)
		);
	}
}
