import { useEffect, useState, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, SelectControl, Spinner, Notice } from '@wordpress/components';

import { api } from '../api';

const Quarantine = () => {
	const [ rows, setRows ] = useState( [] );
	const [ status, setStatus ] = useState( 'active' );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ notice, setNotice ] = useState( null );

	const load = useCallback( () => {
		setLoading( true );
		setError( null );
		api
			.listQuarantine( status )
			.then( ( res ) => setRows( res.items || [] ) )
			.catch( ( e ) => setError( e.message || String( e ) ) )
			.finally( () => setLoading( false ) );
	}, [ status ] );

	useEffect( () => {
		load();
	}, [ load ] );

	const restore = ( id ) => {
		api.restoreQuarantine( [ id ] )
			.then( () => {
				setNotice( { type: 'success', message: __( 'Option restored.', 'orpharion' ) } );
				load();
			} )
			.catch( ( e ) => setNotice( { type: 'error', message: e.message || String( e ) } ) );
	};
	const destroy = ( id ) => {
		if (
			! window.confirm(
				__(
					'Permanently delete this quarantined option?',
					'orpharion'
				)
			)
		) {
			return;
		}
		api.deleteQuarantine( [ id ] )
			.then( () => {
				setNotice( { type: 'success', message: __( 'Option permanently deleted.', 'orpharion' ) } );
				load();
			} )
			.catch( ( e ) => setNotice( { type: 'error', message: e.message || String( e ) } ) );
	};

	return (
		<div className="orpharion-quarantine">
			{ notice && (
				<Notice
					status={ notice.type }
					isDismissible
					onDismiss={ () => setNotice( null ) }
				>
					{ notice.message }
				</Notice>
			) }
			<SelectControl
				label={ __( 'Status', 'orpharion' ) }
				value={ status }
				options={ [
					{ label: __( 'Active', 'orpharion' ), value: 'active' },
					{
						label: __( 'Restored', 'orpharion' ),
						value: 'restored',
					},
					{ label: __( 'Deleted', 'orpharion' ), value: 'deleted' },
				] }
				onChange={ setStatus }
			/>
			{ error && <p className="orpharion-error">{ error }</p> }
			{ loading ? (
				<Spinner />
			) : (
				<div className="orpharion-table-scroll">
				<table className="widefat striped">
					<thead>
						<tr>
							<th>{ __( 'Original name', 'orpharion' ) }</th>
							<th>{ __( 'Quarantined at', 'orpharion' ) }</th>
							<th>{ __( 'Expires at', 'orpharion' ) }</th>
							<th>{ __( 'Last accessed', 'orpharion' ) }</th>
							<th>{ __( 'Access count', 'orpharion' ) }</th>
							<th>{ __( 'Accessor', 'orpharion' ) }</th>
							<th>{ __( 'Actions', 'orpharion' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ rows.map( ( row ) => (
							<tr key={ row.id }>
								<td>
									<code>{ row.original_name }</code>
									{ row.still_accessed && (
										<span
											className="orpharion-badge orpharion-badge--warning"
											title={ __(
												'This option was accessed after quarantine. Restore it to keep the site working; auto-expiry is paused until you restore.',
												'orpharion'
											) }
										>
											{ __( 'in use — restore', 'orpharion' ) }
										</span>
									) }
								</td>
								<td>{ row.quarantined_at }</td>
								<td>{ row.expires_at }</td>
								<td>{ row.last_accessed_at || '—' }</td>
								<td>
									{ row.access_count_during_quarantine > 0
										? row.access_count_during_quarantine
										: '—' }
								</td>
								<td>
									{ row.accessor_during_quarantine ? (
										<>
											{ row.accessor_during_quarantine }
											{ row.accessor_type_during_quarantine && (
												<span className="orpharion-accessor-type">
													{ ' ' }
													({ row.accessor_type_during_quarantine })
												</span>
											) }
										</>
									) : (
										'—'
									) }
								</td>
								<td>
									{ 'active' === status && (
										<>
											<Button
												variant="secondary"
												onClick={ () =>
													restore( row.id )
												}
											>
												{ __( 'Restore', 'orpharion' ) }
											</Button>
											{ ! row.still_accessed && (
												<Button
													isDestructive
													onClick={ () =>
														destroy( row.id )
													}
												>
													{ __(
														'Delete',
														'orpharion'
													) }
												</Button>
											) }
										</>
									) }
								</td>
							</tr>
						) ) }
						{ rows.length === 0 && (
							<tr>
								<td colSpan="7">
									{ __( 'No entries.', 'orpharion' ) }
								</td>
							</tr>
						) }
					</tbody>
				</table>
				</div>
			) }
		</div>
	);
};

export default Quarantine;
