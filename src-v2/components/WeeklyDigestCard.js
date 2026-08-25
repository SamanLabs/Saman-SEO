import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';

const DAYS = [
	'monday',
	'tuesday',
	'wednesday',
	'thursday',
	'friday',
	'saturday',
	'sunday',
];

/**
 * Weekly SEO digest delivery card.
 *
 * Toggles the WP-Cron scheduled email report and offers a one-click test
 * send. Works without Search Console; traffic rows appear once connected.
 */
const WeeklyDigestCard = () => {
	const [ state, setState ] = useState( null );
	const [ enabled, setEnabled ] = useState( false );
	const [ email, setEmail ] = useState( '' );
	const [ day, setDay ] = useState( 'monday' );
	const [ busy, setBusy ] = useState( false );
	const [ message, setMessage ] = useState( '' );
	const [ error, setError ] = useState( '' );

	const applyStatus = useCallback( ( data ) => {
		setState( data );
		setEnabled( !! data.enabled );
		setEmail( data.email || '' );
		setDay( DAYS.includes( data.day ) ? data.day : 'monday' );
	}, [] );

	const fetchStatus = useCallback( async () => {
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/reports/weekly',
			} );
			if ( res?.success ) {
				applyStatus( res.data );
			}
		} catch {
			// Card renders empty state on failure.
		}
	}, [ applyStatus ] );

	useEffect( () => {
		fetchStatus();
	}, [ fetchStatus ] );

	const handleSave = async () => {
		setBusy( true );
		setError( '' );
		setMessage( '' );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/reports/weekly',
				method: 'POST',
				data: { enabled, email, day },
			} );
			if ( res?.success ) {
				applyStatus( res.data );
				setMessage( res.message || __( 'Saved.', 'saman-seo' ) );
			}
		} catch ( err ) {
			setError( err?.message || __( 'Saving failed.', 'saman-seo' ) );
		} finally {
			setBusy( false );
		}
	};

	const handleSendTest = async () => {
		setBusy( true );
		setError( '' );
		setMessage( '' );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/reports/weekly/send-test',
				method: 'POST',
			} );
			if ( res?.success ) {
				applyStatus( res.data );
				setMessage( res.message || __( 'Test sent.', 'saman-seo' ) );
			}
		} catch ( err ) {
			setError( err?.message || __( 'Test send failed.', 'saman-seo' ) );
		} finally {
			setBusy( false );
		}
	};

	return (
		<div className="dashboard-card gsc-card">
			<div className="card-header">
				<h3>{ __( 'Weekly Email Digest', 'saman-seo' ) }</h3>
				{ state?.enabled ? (
					<span className="pill success">
						{ __( 'Scheduled', 'saman-seo' ) }
					</span>
				) : (
					<span className="pill">{ __( 'Off', 'saman-seo' ) }</span>
				) }
			</div>

			<p className="gsc-fineprint" style={ { marginTop: 0 } }>
				{ __(
					'Get a short email every week: score trend, 404s, broken links, redirect usage, and search traffic once Search Console is connected.',
					'saman-seo'
				) }
			</p>

			{ error && (
				<div className="gsc-alert gsc-alert--error" role="alert">
					{ error }
				</div>
			) }
			{ message && (
				<div className="gsc-alert gsc-alert--info">{ message }</div>
			) }

			<label
				className="gsc-digest-toggle"
				htmlFor="saman-weekly-report-enabled"
			>
				<input
					id="saman-weekly-report-enabled"
					type="checkbox"
					checked={ enabled }
					onChange={ ( e ) => setEnabled( e.target.checked ) }
				/>
				<span>{ __( 'Send me the weekly digest', 'saman-seo' ) }</span>
			</label>

			<label className="gsc-field" htmlFor="saman-weekly-report-email">
				<span>
					{ __( 'Recipient (defaults to admin)', 'saman-seo' ) }
				</span>
				<input
					id="saman-weekly-report-email"
					type="email"
					value={ email }
					placeholder={ state?.recipient || '' }
					onChange={ ( e ) => setEmail( e.target.value ) }
				/>
			</label>

			<label className="gsc-field" htmlFor="saman-weekly-report-day">
				<span>{ __( 'Delivery day', 'saman-seo' ) }</span>
				<select
					id="saman-weekly-report-day"
					value={ day }
					onChange={ ( e ) => setDay( e.target.value ) }
				>
					{ DAYS.map( ( d ) => (
						<option key={ d } value={ d }>
							{ d.charAt( 0 ).toUpperCase() + d.slice( 1 ) }
						</option>
					) ) }
				</select>
			</label>

			<div className="gsc-setup-actions">
				<button
					type="button"
					className="button primary"
					disabled={ busy }
					onClick={ handleSave }
				>
					{ busy
						? __( 'Working…', 'saman-seo' )
						: __( 'Save', 'saman-seo' ) }
				</button>
				<button
					type="button"
					className="button"
					disabled={ busy }
					onClick={ handleSendTest }
				>
					{ __( 'Send test email', 'saman-seo' ) }
				</button>
			</div>

			{ state?.enabled && state?.next_run_human && (
				<p className="gsc-fineprint">
					{ sprintf(
						/* translators: %s: human-readable time difference */
						__( 'Next digest in %s.', 'saman-seo' ),
						state.next_run_human
					) }
				</p>
			) }
			{ ! state?.gsc_connected && (
				<p className="gsc-fineprint">
					{ __(
						'Tip: connect Search Console above to include clicks and impressions.',
						'saman-seo'
					) }
				</p>
			) }
		</div>
	);
};

export default WeeklyDigestCard;
