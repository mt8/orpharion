/**
 * Orpharion admin SPA entry point.
 *
 * Mounts a minimal React tree into the container provided by
 * Orpharion\Admin_Page::render(). Subsequent UI work (issue #18) replaces
 * this placeholder with the full dashboard.
 */

import { createRoot } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import App from './App';
import './index.scss';

const config = window.orpharionConfig || {};
const mount = document.getElementById( config.rootId || 'orpharion-admin-root' );

if ( mount ) {
	createRoot( mount ).render( <App config={ config } /> );
} else {
	// eslint-disable-next-line no-console
	console.warn( __( 'Orpharion mount node not found.', 'orpharion' ) );
}
