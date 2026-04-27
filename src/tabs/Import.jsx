import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, CheckboxControl } from '@wordpress/components';

import { api } from '../api';

const Import = () => {
	const [ payload, setPayload ] = useState( '' );
	const [ overwrite, setOverwrite ] = useState( false );
	const [ preview, setPreview ] = useState( null );
	const [ result, setResult ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ busy, setBusy ] = useState( false );

	const onFile = async ( event ) => {
		const file = event.target.files[ 0 ];
		if ( ! file ) return;
		const text = await file.text();
		setPayload( text );
		setPreview( null );
		setResult( null );
		setError( null );
	};

	const runPreview = async () => {
		setBusy( true );
		setError( null );
		try {
			setPreview( await api.importPreview( payload ) );
		} catch ( e ) {
			setError( e.message || String( e ) );
		} finally {
			setBusy( false );
		}
	};

	const runImport = async () => {
		setBusy( true );
		setError( null );
		try {
			setResult( await api.import( payload, overwrite ) );
		} catch ( e ) {
			setError( e.message || String( e ) );
		} finally {
			setBusy( false );
		}
	};

	return (
		<div className="orpharion-import">
			<p>
				<input type="file" accept=".json,application/json" onChange={ onFile } />
			</p>
			<CheckboxControl
				label={ __( 'Overwrite existing rows', 'orpharion' ) }
				checked={ overwrite }
				onChange={ setOverwrite }
			/>
			<div className="orpharion-import__actions">
				<Button variant="secondary" onClick={ runPreview } disabled={ ! payload || busy }>
					{ __( 'Dry run', 'orpharion' ) }
				</Button>
				<Button variant="primary" onClick={ runImport } disabled={ ! payload || busy }>
					{ __( 'Import', 'orpharion' ) }
				</Button>
			</div>
			{ error && <p className="orpharion-error">{ error }</p> }
			{ preview && (
				<p>
					{ sprintf(
						__( 'Preview — add: %1$d, overwrite: %2$d', 'orpharion' ),
						preview.add,
						preview.overwrite
					) }
				</p>
			) }
			{ result && (
				<p>
					{ sprintf(
						__( 'Import complete — added: %1$d, overwritten: %2$d, skipped: %3$d', 'orpharion' ),
						result.added,
						result.overwritten,
						result.skipped
					) }
				</p>
			) }
		</div>
	);
};

export default Import;
