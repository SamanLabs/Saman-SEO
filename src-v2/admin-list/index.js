/**
 * Saman SEO - Admin List SEO Badge
 *
 * Hydrates SEO score badges in the WordPress admin post list.
 */

import { createRoot } from '@wordpress/element';
import SEOScoreBadge from './components/SEOScoreBadge';
import './admin-list.css';

const INITIALIZED_CLASS = 'saman-seo-badge-initialized';

/**
 * Parse a JSON data attribute, tolerating malformed values.
 *
 * @param {string|undefined} value Raw attribute value.
 * @return {Array} Parsed array, or an empty array.
 */
const parseList = ( value ) => {
	if ( ! value ) {
		return [];
	}
	try {
		const parsed = JSON.parse( value );
		return Array.isArray( parsed ) ? parsed : [];
	} catch {
		return [];
	}
};

/**
 * Initialize SEO badge placeholders that have not been rendered yet.
 *
 * Safe to call repeatedly: placeholders already given a React root are
 * skipped, so only rows added since the last run (Quick Edit, list
 * refresh) are rendered.
 */
const initBadges = () => {
	const placeholders = document.querySelectorAll(
		`.saman-seo-badge-placeholder:not(.${ INITIALIZED_CLASS })`
	);

	placeholders.forEach( ( placeholder ) => {
		// Mark first so a re-entrant call can never create a second root.
		placeholder.classList.add( INITIALIZED_CLASS );

		const data = placeholder.dataset;

		const props = {
			score: parseInt( data.score, 10 ) || 0,
			level: data.level || 'poor',
			label: data.label || '',
			issues: parseList( data.issues ),
			flags: parseList( data.flags ),
		};

		createRoot( placeholder ).render( <SEOScoreBadge { ...props } /> );
	} );
};

// Initialize when DOM is ready
if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initBadges );
} else {
	initBadges();
}

// Quick Edit (inline-save) swaps in a freshly rendered row, and list refreshes
// (fetch-list) replace rows too: render the new placeholders afterwards.
const ROW_REPLACING_ACTION = /(?:^|&)action=(?:inline-save|fetch-list)(?:&|$)/;

if ( typeof window.jQuery !== 'undefined' ) {
	window.jQuery( document ).on( 'ajaxComplete', ( event, xhr, settings ) => {
		if (
			settings &&
			typeof settings.data === 'string' &&
			ROW_REPLACING_ACTION.test( settings.data )
		) {
			setTimeout( initBadges, 0 );
		}
	} );
}
