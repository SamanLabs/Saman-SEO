/**
 * Template rendering utilities.
 */

/**
 * Apply a string modifier to a value.
 *
 * @param {string} value    Value to modify.
 * @param {string} modifier Modifier name (upper, lower, capitalize, trim).
 * @return {string} Modified value.
 */
export const applyModifier = ( value, modifier ) => {
	if ( ! value || ! modifier ) {
		return value;
	}
	const mod = modifier.trim().toLowerCase();
	switch ( mod ) {
		case 'upper':
		case 'uppercase':
			return String( value ).toUpperCase();
		case 'lower':
		case 'lowercase':
			return String( value ).toLowerCase();
		case 'capitalize':
		case 'title':
			return String( value ).replace( /\b\w/g, ( c ) => c.toUpperCase() );
		case 'trim':
			return String( value ).trim();
		default:
			return value;
	}
};

/**
 * Render a template, replacing {{variables}} with their values.
 * Unreplaced variables are stripped so raw {{...}} never leaks into previews.
 *
 * @param {string} template         Template string with {{variables}}.
 * @param {Object} values           Variable values keyed by tag name.
 * @param {Object} options          Options.
 * @param {string} options.fallback Fallback string used when rendered output is empty.
 * @return {string} Rendered, safe string.
 */
export const renderTemplatePreview = (
	template,
	values = {},
	options = {}
) => {
	if ( ! template ) {
		return '';
	}
	let preview = template.replace( /\{\{([^}]+)\}\}/g, ( match, content ) => {
		const trimmedContent = content.trim();
		const pipeIndex = trimmedContent.indexOf( '|' );
		if ( pipeIndex > -1 ) {
			const baseTag = trimmedContent.substring( 0, pipeIndex ).trim();
			const modifier = trimmedContent.substring( pipeIndex + 1 ).trim();
			const baseValue = values[ baseTag ];
			if ( baseValue !== undefined ) {
				return applyModifier( baseValue, modifier );
			}
			return '';
		}
		return values[ trimmedContent ] !== undefined
			? values[ trimmedContent ]
			: '';
	} );

	// Safety net: remove any remaining {{...}} tokens.
	preview = preview.replace( /\{\{[^}]*\}\}/g, '' );

	// Tidy whitespace left behind by removed tokens.
	preview = preview.replace( /\s+/g, ' ' ).trim();

	if ( ! preview && options.fallback !== undefined ) {
		return options.fallback;
	}
	return preview;
};

/**
 * Strip unreplaced template variables from a string.
 *
 * @param {string} value Input string.
 * @return {string} Cleaned string.
 */
export const stripUnreplacedVariables = ( value ) => {
	if ( ! value ) {
		return '';
	}
	return value
		.replace( /\{\{[^}]*\}\}/g, '' )
		.replace( /%[a-zA-Z0-9_\-]+%/g, '' )
		.replace( /\s+/g, ' ' )
		.trim();
};
