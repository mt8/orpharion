import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';

import { api } from '../api';

const Dashboard = () => {
	const [ stats, setStats ] = useState( null );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		api.stats()
			.then( setStats )
			.catch( ( e ) => setError( e.message || String( e ) ) );
	}, [] );

	if ( error ) {
		return <p className="orpharion-error">{ error }</p>;
	}
	if ( ! stats ) {
		return <Spinner />;
	}

	const cards = [
		{ label: __( 'Total options', 'orpharion' ), value: stats.total_options },
		{ label: __( 'Autoload payload', 'orpharion' ), value: stats.autoload_total_size_human },
	];

	return (
		<div className="orpharion-dashboard">
			<div className="orpharion-cards">
				{ cards.map( ( card ) => (
					<div className="orpharion-card" key={ card.label }>
						<div className="orpharion-card__label">{ card.label }</div>
						<div className="orpharion-card__value">{ card.value }</div>
					</div>
				) ) }
			</div>
		</div>
	);
};

export default Dashboard;
