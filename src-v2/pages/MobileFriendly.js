/**
 * Mobile Friendly Test Page
 *
 * Check if pages are mobile-friendly and identify issues.
 */

import { useState, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { KpiBand, MetricCard } from '../components/Kpi';

// Pill tone and label for a check result or an issue severity.
const checkPills = {
	pass: { tone: 'success', label: __( 'Pass', 'saman-seo' ) },
	warning: { tone: 'warning', label: __( 'Warning', 'saman-seo' ) },
	fail: { tone: 'danger', label: __( 'Fail', 'saman-seo' ) },
};
const severityPills = {
	critical: { tone: 'danger', label: __( 'Critical', 'saman-seo' ) },
	warning: { tone: 'warning', label: __( 'Warning', 'saman-seo' ) },
	info: { tone: '', label: __( 'Info', 'saman-seo' ) },
};

const MobileFriendly = ( { onNavigate } ) => {
	const [ url, setUrl ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ results, setResults ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ recentTests, setRecentTests ] = useState( [] );

	// Load recent tests
	useEffect( () => {
		const fetchRecent = async () => {
			try {
				const response = await apiFetch( {
					path: '/saman-seo/v1/mobile-test/recent',
				} );
				if ( response.success ) {
					setRecentTests( response.data || [] );
				}
			} catch ( err ) {
				// Ignore errors for recent tests
			}
		};
		fetchRecent();
	}, [] );

	// Run mobile test
	const handleTest = useCallback( async () => {
		if ( ! url.trim() ) {
			setError( __( 'Please enter a URL to test', 'saman-seo' ) );
			return;
		}
		setLoading( true );
		setError( null );
		setResults( null );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/mobile-test/analyze',
				method: 'POST',
				data: {
					url: url.trim(),
				},
			} );
			if ( response.success ) {
				setResults( response.data );
				// Update recent tests
				const newTest = {
					url: response.data.url,
					score: response.data.score,
					date: new Date().toLocaleString(),
				};
				setRecentTests( ( prev ) => [
					newTest,
					...prev.slice( 0, 4 ),
				] );
			} else {
				setError(
					response.message ||
						__( 'Failed to analyze page', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Failed to analyze page', 'saman-seo' )
			);
		} finally {
			setLoading( false );
		}
	}, [ url ] );

	// Test homepage
	const handleTestHomepage = useCallback( () => {
		const siteUrl =
			window.samanSeoV2Settings?.adminUrl?.replace( '/wp-admin/', '' ) ||
			'';
		if ( siteUrl ) {
			setUrl( siteUrl );
			setTimeout( () => {
				document.getElementById( 'test-btn' )?.click();
			}, 100 );
		}
	}, [] );

	// Status tone for a 0-100 score.
	const getScoreTone = ( score ) => {
		if ( score >= 90 ) {
			return 'success';
		}
		if ( score >= 70 ) {
			return 'warning';
		}
		return 'danger';
	};

	const getStatusLabel = ( status ) => {
		if ( status === 'pass' ) {
			return __( 'Mobile friendly', 'saman-seo' );
		}
		if ( status === 'warning' ) {
			return __( 'Needs improvement', 'saman-seo' );
		}
		return __( 'Not mobile friendly', 'saman-seo' );
	};

	// The band is always on the page so the frame does not jump when a
	// result arrives; before the first run its tiles show a dash.
	const dash = '\u2013';
	const checks = results?.checks || [];
	const issues = results?.issues || [];
	const passedCount = checks.filter( ( c ) => c.status === 'pass' ).length;
	const reviewCount = checks.length - passedCount;
	return (
		<div className="page mobile-friendly-page">
			{ ( loading || results ) &&
				( loading ? (
					<KpiBand loading count={ 4 } />
				) : (
					<KpiBand label={ __( 'Mobile test summary', 'saman-seo' ) }>
						<MetricCard
							label={ __( 'Mobile score', 'saman-seo' ) }
							value={ results ? results.score : dash }
							unit={ results ? '/ 100' : undefined }
							tone={
								results
									? getScoreTone( results.score )
									: undefined
							}
							sub={
								results
									? getStatusLabel( results.status )
									: undefined
							}
						/>
						<MetricCard
							label={ __( 'Checks passed', 'saman-seo' ) }
							value={ results ? passedCount : dash }
						/>
						<MetricCard
							label={ __( 'Checks to review', 'saman-seo' ) }
							value={ results ? reviewCount : dash }
							tone={ reviewCount > 0 ? 'warning' : undefined }
						/>
						<MetricCard
							label={ __( 'Issues found', 'saman-seo' ) }
							value={ results ? issues.length : dash }
							tone={ issues.length > 0 ? 'danger' : undefined }
						/>
					</KpiBand>
				) ) }

			<div className="page-header">
				<div>
					<h1>{ __( 'Mobile friendly test', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Check if your pages are optimized for mobile devices.',
							'saman-seo'
						) }
					</p>
				</div>
				{ results && (
					<div className="header-actions">
						<a
							href={ `https://search.google.com/test/mobile-friendly?url=${ encodeURIComponent(
								results.url
							) }` }
							target="_blank"
							rel="noopener noreferrer"
							className="button"
						>
							{ __( 'Google mobile test', 'saman-seo' ) }
						</a>
						<a
							href={ `https://pagespeed.web.dev/report?url=${ encodeURIComponent(
								results.url
							) }` }
							target="_blank"
							rel="noopener noreferrer"
							className="button"
						>
							{ __( 'PageSpeed Insights', 'saman-seo' ) }
						</a>
					</div>
				) }
			</div>

			{ /* URL Input */ }
			<section className="panel">
				<h3>{ __( 'URL to test', 'saman-seo' ) }</h3>
				<p className="panel-desc">
					{ __(
						'Analyzes viewport, touch targets, font sizes, and more',
						'saman-seo'
					) }
				</p>
				<div className="input-group">
					<input
						type="url"
						className="tool-input"
						aria-label={ __( 'URL to test', 'saman-seo' ) }
						placeholder={ __(
							'Enter URL to test (e.g., https://example.com)',
							'saman-seo'
						) }
						value={ url }
						onChange={ ( e ) => setUrl( e.target.value ) }
						onKeyDown={ ( e ) => e.key === 'Enter' && handleTest() }
					/>
					<button
						type="button"
						className="button"
						onClick={ handleTestHomepage }
					>
						{ __( 'Test homepage', 'saman-seo' ) }
					</button>
					<button
						id="test-btn"
						type="button"
						className="button primary"
						onClick={ handleTest }
						disabled={ loading }
					>
						{ loading ? (
							<>
								<span className="spinner-small" />
								{ __( 'Testing\u2026', 'saman-seo' ) }
							</>
						) : (
							<>
								<svg
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									width="16"
									height="16"
								>
									<rect
										x="5"
										y="2"
										width="14"
										height="20"
										rx="2"
									/>
									<path d="M12 18h.01" />
								</svg>
								{ __( 'Test page', 'saman-seo' ) }
							</>
						) }
					</button>
				</div>
			</section>

			{ /* Error */ }
			{ error && (
				<div className="alert-banner alert-banner--error">
					<svg
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="2"
						width="20"
						height="20"
					>
						<circle cx="12" cy="12" r="10" />
						<path d="M12 8v4m0 4h.01" />
					</svg>
					<span>{ error }</span>
				</div>
			) }

			{ /* Checks */ }
			{ results && checks.length > 0 && (
				<section className="improvements-group">
					<h2 className="improvements-group__title">
						{ __( 'Checks performed', 'saman-seo' ) }
					</h2>
					<p className="group-note">{ results.url }</p>
					<ul className="improvements-list">
						{ checks.map( ( check, index ) => {
							const pill =
								checkPills[ check.status ] ||
								checkPills.warning;
							return (
								<li key={ index } className="improvement-row">
									<div className="improvement-row__main">
										<div className="improvement-row__title">
											<h3>{ check.name }</h3>
										</div>
										<p className="improvement-row__desc">
											{ check.detail }
										</p>
									</div>
									<span className={ `pill ${ pill.tone }` }>
										{ pill.label }
									</span>
								</li>
							);
						} ) }
					</ul>
				</section>
			) }

			{ /* Issues */ }
			{ results && issues.length > 0 && (
				<section className="improvements-group">
					<h2 className="improvements-group__title">
						{ __( 'Issues found (', 'saman-seo' ) }
						{ issues.length })
					</h2>
					<ul className="improvements-list">
						{ issues.map( ( issue, index ) => {
							const pill =
								severityPills[ issue.severity ] ||
								severityPills.info;
							return (
								<li key={ index } className="improvement-row">
									<div className="improvement-row__main">
										<div className="improvement-row__title">
											<h3>{ issue.title }</h3>
											<span
												className={ `pill ${ pill.tone }`.trim() }
											>
												{ pill.label }
											</span>
										</div>
										<p className="improvement-row__desc">
											{ issue.description }
										</p>
										{ issue.fix && (
											<p className="improvement-row__desc">
												<strong>
													{ __(
														'Fix:',
														'saman-seo'
													) }
												</strong>{ ' ' }
												{ issue.fix }
											</p>
										) }
									</div>
								</li>
							);
						} ) }
					</ul>
				</section>
			) }

			{ /* Recent Tests */ }
			{ ! results && recentTests.length > 0 && (
				<section className="improvements-group">
					<h2 className="improvements-group__title">
						{ __( 'Recent tests', 'saman-seo' ) }
					</h2>
					<ul className="improvements-list">
						{ recentTests.map( ( test, index ) => (
							<li key={ index } className="improvement-row">
								<div className="improvement-row__main">
									<div className="improvement-row__title">
										<h3>{ test.url }</h3>
										<span
											className={ `pill ${ getScoreTone(
												test.score
											) }` }
										>
											{ test.score }/100
										</span>
									</div>
									{ test.date && (
										<p className="improvement-row__desc">
											{ test.date }
										</p>
									) }
								</div>
								<button
									type="button"
									className="button small"
									onClick={ () => setUrl( test.url ) }
								>
									{ __( 'Use URL', 'saman-seo' ) }
								</button>
							</li>
						) ) }
					</ul>
				</section>
			) }
		</div>
	);
};
export default MobileFriendly;
