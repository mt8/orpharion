/**
 * Optrion admin SPA shell.
 */

import { __ } from '@wordpress/i18n';

const App = ( { config } ) => {
	return (
		<div className="optrion-app">
			<p>{ __( 'Optrion UI build is ready. Dashboard components land in issue #18.', 'optrion' ) }</p>
			<p className="optrion-app__meta">
				{ __( 'REST base: ', 'optrion' ) }
				<code>{ config.restRoot }</code>
			</p>
		</div>
	);
};

export default App;
