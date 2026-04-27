/**
 * Orpharion admin SPA shell with tab navigation.
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import Dashboard from './tabs/Dashboard';
import OptionsList from './tabs/OptionsList';
import Quarantine from './tabs/Quarantine';
import Import from './tabs/Import';

const TABS = [
	{ id: 'dashboard', label: __( 'Dashboard', 'orpharion' ), component: Dashboard },
	{ id: 'options', label: __( 'Options', 'orpharion' ), component: OptionsList },
	{ id: 'quarantine', label: __( 'Quarantine', 'orpharion' ), component: Quarantine },
	{ id: 'import', label: __( 'Import', 'orpharion' ), component: Import },
];

const App = () => {
	const [ active, setActive ] = useState( 'dashboard' );
	const Current = TABS.find( ( t ) => t.id === active ).component;

	return (
		<div className="orpharion-app">
			<nav className="nav-tab-wrapper orpharion-tabs">
				{ TABS.map( ( tab ) => (
					<a
						key={ tab.id }
						href={ '#' + tab.id }
						className={ 'nav-tab' + ( active === tab.id ? ' nav-tab-active' : '' ) }
						onClick={ ( e ) => {
							e.preventDefault();
							setActive( tab.id );
						} }
					>
						{ tab.label }
					</a>
				) ) }
			</nav>
			<div className="orpharion-tab-panel">
				<Current />
			</div>
		</div>
	);
};

export default App;
