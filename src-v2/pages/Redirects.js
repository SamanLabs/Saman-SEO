import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
const STATUS_CODES = [
	{
		value: 301,
		label: __( '301 Permanent', 'saman-seo' ),
	},
	{
		value: 302,
		label: __( '302 Temporary', 'saman-seo' ),
	},
	{
		value: 307,
		label: '307',
	},
	{
		value: 410,
		label: __( '410 Gone', 'saman-seo' ),
	},
];

// Empty form state
const emptyForm = {
	source: '',
	target: '',
	status_code: 301,
	is_regex: false,
	group_name: '',
	start_date: '',
	end_date: '',
	notes: '',
};

/**
 * Parse a simple PCRE-like pattern into an AST for example generation.
 *
 * @param {string} pattern Regex source.
 * @returns {object} AST node.
 */
const parseRegexPattern = ( pattern ) => {
	let i = 0;
	const parseSequence = ( endChar ) => {
		const nodes = [];
		while ( i < pattern.length ) {
			const c = pattern[ i ];
			if ( c === endChar || ( endChar === null && c === ')' ) ) {
				break;
			}
			if ( c === '|' ) {
				const left =
					nodes.length === 1
						? nodes[ 0 ]
						: {
								type: 'seq',
								nodes,
						  };
				i++;
				const right = parseSequence( endChar );
				return {
					type: 'alt',
					branches: [ left, right ],
				};
			}
			const node = parseAtom();
			if ( ! node ) {
				continue;
			}
			const quant = parseQuantifier();
			if ( quant ) {
				nodes.push( {
					type: 'quant',
					node,
					min: quant.min,
					max: quant.max,
				} );
			} else {
				nodes.push( node );
			}
		}
		if ( nodes.length === 0 ) {
			return {
				type: 'empty',
			};
		}
		if ( nodes.length === 1 ) {
			return nodes[ 0 ];
		}
		return {
			type: 'seq',
			nodes,
		};
	};
	const parseAtom = () => {
		if ( i >= pattern.length ) {
			return null;
		}
		const c = pattern[ i ];
		if ( c === '^' || c === '$' ) {
			i++;
			return {
				type: 'anchor',
			};
		}
		if ( c === '\\' ) {
			i += 2;
			const n = pattern[ i - 1 ];
			let value = n;
			if ( n === 'd' ) value = '1';
			else if ( n === 'D' ) value = 'a';
			else if ( n === 'w' ) value = 'a';
			else if ( n === 'W' ) value = '-';
			else if ( n === 's' ) value = ' ';
			else if ( n === 'S' ) value = 'a';
			else if ( n === 't' ) value = '\t';
			else if ( n === 'n' ) value = '\n';
			else if ( n === 'r' ) value = '\r';
			else if ( n === 'b' || n === 'B' ) value = '';
			return {
				type: 'literal',
				value,
			};
		}
		if ( c === '.' ) {
			i++;
			return {
				type: 'literal',
				value: 'a',
			};
		}
		if ( c === '[' ) {
			i++;
			let negated = false;
			if ( pattern[ i ] === '^' ) {
				negated = true;
				i++;
			}
			let chars = '';
			while ( i < pattern.length && pattern[ i ] !== ']' ) {
				if ( pattern[ i ] === '\\' ) {
					chars += pattern[ i + 1 ] || '';
					i += 2;
				} else {
					chars += pattern[ i ];
					i++;
				}
			}
			if ( pattern[ i ] === ']' ) {
				i++;
			}
			let value = 'a';
			if ( ! negated && chars ) {
				for ( let k = 0; k < chars.length; k++ ) {
					if ( chars[ k + 1 ] === '-' && k + 2 < chars.length ) {
						value = chars[ k ];
						break;
					}
					value = chars[ k ];
					break;
				}
			}
			return {
				type: 'literal',
				value,
			};
		}
		if ( c === '(' ) {
			i++;
			if ( pattern.substr( i, 2 ) === '?:' ) {
				i += 2;
				const node = parseSequence( ')' );
				if ( pattern[ i ] === ')' ) {
					i++;
				}
				return node;
			}
			if (
				pattern.substr( i, 2 ) === '?=' ||
				pattern.substr( i, 2 ) === '?!'
			) {
				i += 2;
				parseSequence( ')' );
				if ( pattern[ i ] === ')' ) {
					i++;
				}
				return {
					type: 'empty',
				};
			}
			if (
				pattern.substr( i, 3 ) === '?<=' ||
				pattern.substr( i, 3 ) === '?<!'
			) {
				i += 3;
				parseSequence( ')' );
				if ( pattern[ i ] === ')' ) {
					i++;
				}
				return {
					type: 'empty',
				};
			}
			if ( pattern[ i ] === '?' ) {
				const end = pattern.indexOf( ')', i );
				if ( end !== -1 ) {
					i = end + 1;
				}
				return {
					type: 'empty',
				};
			}
			const node = parseSequence( ')' );
			if ( pattern[ i ] === ')' ) {
				i++;
			}
			return {
				type: 'group',
				node,
			};
		}
		i++;
		return {
			type: 'literal',
			value: c,
		};
	};
	const parseQuantifier = () => {
		if ( i >= pattern.length ) {
			return null;
		}
		const c = pattern[ i ];
		if ( c === '?' ) {
			i++;
			return {
				min: 0,
				max: 1,
			};
		}
		if ( c === '*' ) {
			i++;
			return {
				min: 0,
				max: Infinity,
			};
		}
		if ( c === '+' ) {
			i++;
			return {
				min: 1,
				max: Infinity,
			};
		}
		if ( c === '{' ) {
			const end = pattern.indexOf( '}', i );
			if ( end === -1 ) {
				return null;
			}
			const spec = pattern.slice( i + 1, end );
			i = end + 1;
			const parts = spec.split( ',' ).map( ( s ) => s.trim() );
			if ( parts.length === 1 ) {
				const n = parseInt( parts[ 0 ], 10 );
				if ( ! isNaN( n ) ) {
					return {
						min: n,
						max: n,
					};
				}
			} else if ( parts.length === 2 ) {
				const min = parseInt( parts[ 0 ], 10 );
				const max = parts[ 1 ] ? parseInt( parts[ 1 ], 10 ) : Infinity;
				if ( ! isNaN( min ) ) {
					return {
						min,
						max,
					};
				}
			}
		}
		return null;
	};
	return parseSequence( null );
};

/**
 * Generate a few example strings that a regex pattern could match.
 *
 * @param {string} pattern Regex source.
 * @param {number} count   Number of examples to generate.
 * @returns {string[]} Example strings.
 */
const generateRegexExamples = ( pattern, count = 3 ) => {
	if ( ! pattern || typeof pattern !== 'string' ) {
		return [];
	}
	const ast = parseRegexPattern( pattern );
	const examples = [];
	const seen = new Set();
	const emit = ( str ) => {
		if ( examples.length >= count ) {
			return false;
		}
		if ( str && ! seen.has( str ) ) {
			seen.add( str );
			examples.push( str );
		}
		return examples.length < count;
	};
	const generate = ( node, limit ) => {
		if ( ! node || node.type === 'empty' || node.type === 'anchor' ) {
			return [ '' ];
		}
		if ( node.type === 'literal' ) {
			return [ node.value ];
		}
		if ( node.type === 'group' ) {
			return generate( node.node, limit );
		}
		if ( node.type === 'quant' ) {
			const base = generate( node.node, limit );
			let reps = 1;
			if ( node.min > 0 ) {
				reps = node.min;
			} else if ( node.max > 0 ) {
				reps = 1;
			} else {
				reps = 0;
			}
			return [ base[ 0 ].repeat( reps ) ];
		}
		if ( node.type === 'seq' ) {
			let result = [ '' ];
			for ( const child of node.nodes ) {
				const childStrings = generate( child, limit );
				const combined = [];
				for ( const prefix of result ) {
					for ( const suffix of childStrings ) {
						combined.push( prefix + suffix );
						if ( combined.length >= limit ) {
							break;
						}
					}
					if ( combined.length >= limit ) {
						break;
					}
				}
				result = combined;
			}
			return result.slice( 0, limit );
		}
		if ( node.type === 'alt' ) {
			const result = [];
			for ( const branch of node.branches ) {
				result.push( ...generate( branch, limit ) );
				if ( result.length >= limit ) {
					break;
				}
			}
			return result.slice( 0, limit );
		}
		return [ '' ];
	};
	for ( const str of generate( ast, count ) ) {
		if ( ! emit( str ) ) {
			break;
		}
	}
	return examples;
};

/**
 * Build a tooltip title showing example matches for a regex redirect.
 *
 * @param {string} source Regex source pattern.
 * @returns {string|null} Tooltip text or null if no examples.
 */
const buildRegexTooltip = ( source ) => {
	const examples = generateRegexExamples( source, 3 );
	if ( examples.length === 0 ) {
		return null;
	}
	return `Example matches:\n${ examples
		.map( ( ex ) => `• ${ ex }` )
		.join( '\n' ) }`;
};
const Redirects = () => {
	// Redirects state
	const [ redirects, setRedirects ] = useState( [] );
	const [ redirectsLoading, setRedirectsLoading ] = useState( true );
	const [ pagination, setPagination ] = useState( {
		page: 1,
		per_page: 50,
		total: 0,
		total_pages: 1,
	} );

	// Form state
	const [ formData, setFormData ] = useState( {
		...emptyForm,
	} );
	const [ editingId, setEditingId ] = useState( null );
	const [ showModal, setShowModal ] = useState( false );
	const [ formLoading, setFormLoading ] = useState( false );
	const [ formError, setFormError ] = useState( '' );
	const [ chainWarnings, setChainWarnings ] = useState( [] );
	const [ showAdvanced, setShowAdvanced ] = useState( false );

	// Filters state
	const [ search, setSearch ] = useState( '' );
	const [ filterGroup, setFilterGroup ] = useState( '' );
	const [ filterStatus, setFilterStatus ] = useState( '' );
	const [ groups, setGroups ] = useState( [] );

	// Bulk selection
	const [ selectedIds, setSelectedIds ] = useState( [] );
	const [ bulkLoading, setBulkLoading ] = useState( false );

	// Import/Export
	const [ showImport, setShowImport ] = useState( false );
	const [ importData, setImportData ] = useState( '' );
	const [ importFormat, setImportFormat ] = useState( 'json' );
	const [ importOverwrite, setImportOverwrite ] = useState( false );
	const [ importLoading, setImportLoading ] = useState( false );
	const [ importResult, setImportResult ] = useState( null );

	// Slug suggestions state
	const [ suggestions, setSuggestions ] = useState( [] );
	const [ suggestionsLoading, setSuggestionsLoading ] = useState( true );

	// Summary stats
	const [ summary, setSummary ] = useState( {
		total: 0,
		active_count: 0,
		total_hits: 0,
		top_redirect: null,
	} );
	const [ summaryLoading, setSummaryLoading ] = useState( true );
	const fileInputRef = useRef( null );

	// Fetch redirects with filters
	const fetchRedirects = useCallback(
		async ( page = 1 ) => {
			setRedirectsLoading( true );
			try {
				const params = new URLSearchParams();
				params.append( 'page', page );
				params.append( 'per_page', pagination.per_page );
				if ( search ) params.append( 'search', search );
				if ( filterGroup ) params.append( 'group', filterGroup );
				if ( filterStatus )
					params.append( 'status_code', filterStatus );
				const response = await apiFetch( {
					path: `/saman-seo/v1/redirects?${ params }`,
				} );
				if ( response.success ) {
					setRedirects( response.data.items || [] );
					setPagination( ( prev ) => ( {
						...prev,
						page: response.data.page,
						total: response.data.total,
						total_pages: response.data.total_pages,
					} ) );
				}
			} catch ( error ) {
				console.error( 'Failed to fetch redirects:', error );
			} finally {
				setRedirectsLoading( false );
			}
		},
		[ search, filterGroup, filterStatus, pagination.per_page ]
	);

	// Fetch summary stats
	const fetchSummary = useCallback( async () => {
		setSummaryLoading( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/redirects/summary',
			} );
			if ( response.success ) {
				setSummary( {
					total: response.data.total || 0,
					active_count: response.data.active_count || 0,
					total_hits: response.data.total_hits || 0,
					top_redirect: response.data.top_redirect || null,
				} );
			}
		} catch ( error ) {
			console.error( 'Failed to fetch redirect summary:', error );
		} finally {
			setSummaryLoading( false );
		}
	}, [] );

	// Fetch groups
	const fetchGroups = useCallback( async () => {
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/redirects/groups',
			} );
			if ( response.success ) {
				setGroups( response.data || [] );
			}
		} catch ( error ) {
			console.error( 'Failed to fetch groups:', error );
		}
	}, [] );

	// Fetch slug suggestions
	const fetchSuggestions = useCallback( async () => {
		setSuggestionsLoading( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/slug-suggestions',
			} );
			if ( response.success ) {
				setSuggestions( response.data );
			}
		} catch ( error ) {
			console.error( 'Failed to fetch suggestions:', error );
		} finally {
			setSuggestionsLoading( false );
		}
	}, [] );

	// Load data on mount
	useEffect( () => {
		fetchRedirects();
		fetchGroups();
		fetchSuggestions();
		fetchSummary();

		// Check if there's a redirect source from 404 Log page
		const storedSource = sessionStorage.getItem(
			'Saman_seo_redirect_source'
		);
		if ( storedSource ) {
			setFormData( ( prev ) => ( {
				...prev,
				source: storedSource,
			} ) );
			setShowModal( true );
			sessionStorage.removeItem( 'Saman_seo_redirect_source' );
		}
	}, [] );

	// Refetch when filters change
	useEffect( () => {
		const timer = setTimeout( () => {
			fetchRedirects( 1 );
		}, 300 );
		return () => clearTimeout( timer );
	}, [ search, filterGroup, filterStatus ] );

	// Validate chain/loop
	const validateChain = async ( source, target ) => {
		if ( ! source || ! target ) return;
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/redirects/validate-chain',
				method: 'POST',
				data: {
					source,
					target,
					exclude_id: editingId || 0,
				},
			} );
			if ( response.success ) {
				setChainWarnings( response.data.warnings || [] );
			}
		} catch ( error ) {
			console.error( 'Chain validation failed:', error );
		}
	};

	// Debounced chain validation
	useEffect( () => {
		const timer = setTimeout( () => {
			validateChain( formData.source, formData.target );
		}, 500 );
		return () => clearTimeout( timer );
	}, [ formData.source, formData.target ] );

	// Update form field
	const updateForm = ( field, value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			[ field ]: value,
		} ) );
		setFormError( '' );
	};

	// Open modal for creating
	const openCreateModal = () => {
		setFormData( {
			...emptyForm,
		} );
		setEditingId( null );
		setShowModal( true );
		setChainWarnings( [] );
		setShowAdvanced( false );
		setFormError( '' );
	};

	// Open modal for editing
	const openEditModal = ( redirect ) => {
		setFormData( {
			source: redirect.source || '',
			target: redirect.target || '',
			status_code: redirect.status_code || 301,
			is_regex: redirect.is_regex || false,
			group_name: redirect.group_name || '',
			start_date: redirect.start_date
				? redirect.start_date.slice( 0, 16 )
				: '',
			end_date: redirect.end_date ? redirect.end_date.slice( 0, 16 ) : '',
			notes: redirect.notes || '',
		} );
		setEditingId( redirect.id );
		setShowModal( true );
		setChainWarnings( [] );
		setShowAdvanced(
			!! redirect.start_date || !! redirect.end_date || !! redirect.notes
		);
		setFormError( '' );
	};

	// Close modal
	const closeModal = () => {
		setShowModal( false );
		setEditingId( null );
		setFormData( {
			...emptyForm,
		} );
		setChainWarnings( [] );
		setFormError( '' );
	};

	// Save redirect (create or update)
	const handleSaveRedirect = async ( e ) => {
		e.preventDefault();
		setFormLoading( true );
		setFormError( '' );
		try {
			const data = {
				...formData,
			};
			// Convert empty strings to null for dates
			if ( ! data.start_date ) data.start_date = null;
			if ( ! data.end_date ) data.end_date = null;
			let response;
			if ( editingId ) {
				response = await apiFetch( {
					path: `/saman-seo/v1/redirects/${ editingId }`,
					method: 'PUT',
					data,
				} );
			} else {
				response = await apiFetch( {
					path: '/saman-seo/v1/redirects',
					method: 'POST',
					data,
				} );
			}
			if ( response.success ) {
				closeModal();
				fetchRedirects( pagination.page );
				fetchGroups();
				fetchSuggestions();
				fetchSummary();
			} else {
				setFormError(
					response.message ||
						__( 'Failed to save redirect', 'saman-seo' )
				);
			}
		} catch ( error ) {
			setFormError(
				error.message || __( 'Failed to save redirect', 'saman-seo' )
			);
		} finally {
			setFormLoading( false );
		}
	};

	// Delete redirect
	const handleDeleteRedirect = async ( id ) => {
		if (
			! window.confirm(
				__(
					'Are you sure you want to delete this redirect?',
					'saman-seo'
				)
			)
		) {
			return;
		}
		try {
			await apiFetch( {
				path: `/saman-seo/v1/redirects/${ id }`,
				method: 'DELETE',
			} );
			fetchRedirects( pagination.page );
			fetchGroups();
			fetchSummary();
		} catch ( error ) {
			console.error( 'Failed to delete redirect:', error );
		}
	};

	// Bulk delete
	const handleBulkDelete = async () => {
		if ( selectedIds.length === 0 ) return;
		if (
			! window.confirm(
				sprintf(
					/* translators: %s: placeholder */ __(
						'Delete %s selected redirect(s)?',
						'saman-seo'
					),
					selectedIds.length
				)
			)
		)
			return;
		setBulkLoading( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/redirects/bulk-delete',
				method: 'POST',
				data: {
					ids: selectedIds,
				},
			} );
			setSelectedIds( [] );
			fetchRedirects( pagination.page );
			fetchGroups();
			fetchSummary();
		} catch ( error ) {
			console.error( 'Failed to bulk delete:', error );
		} finally {
			setBulkLoading( false );
		}
	};

	// Toggle selection
	const toggleSelect = ( id ) => {
		setSelectedIds( ( prev ) =>
			prev.includes( id )
				? prev.filter( ( i ) => i !== id )
				: [ ...prev, id ]
		);
	};

	// Toggle all selection
	const toggleSelectAll = () => {
		if ( selectedIds.length === redirects.length ) {
			setSelectedIds( [] );
		} else {
			setSelectedIds( redirects.map( ( r ) => r.id ) );
		}
	};

	// Export redirects
	const handleExport = async ( format ) => {
		try {
			const response = await apiFetch( {
				path: `/saman-seo/v1/redirects/export?format=${ format }`,
			} );
			if ( response.success ) {
				const blob = new Blob( [ response.data.content ], {
					type: format === 'json' ? 'application/json' : 'text/csv',
				} );
				const url = URL.createObjectURL( blob );
				const a = document.createElement( 'a' );
				a.href = url;
				a.download = response.data.filename;
				a.click();
				URL.revokeObjectURL( url );
			}
		} catch ( error ) {
			console.error( 'Export failed:', error );
		}
	};

	// Import redirects
	const handleImport = async () => {
		if ( ! importData.trim() ) return;
		setImportLoading( true );
		setImportResult( null );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/redirects/import',
				method: 'POST',
				data: {
					format: importFormat,
					data: importData,
					overwrite: importOverwrite,
				},
			} );
			if ( response.success ) {
				setImportResult( response.data );
				fetchRedirects( 1 );
				fetchGroups();
				fetchSummary();
			} else {
				setImportResult( {
					error: response.message,
				} );
			}
		} catch ( error ) {
			setImportResult( {
				error: error.message,
			} );
		} finally {
			setImportLoading( false );
		}
	};

	// Handle file upload
	const handleFileUpload = ( e ) => {
		const file = e.target.files?.[ 0 ];
		if ( ! file ) return;
		const reader = new FileReader();
		reader.onload = ( event ) => {
			setImportData( event.target.result );
			setImportFormat( file.name.endsWith( '.csv' ) ? 'csv' : 'json' );
		};
		reader.readAsText( file );
	};

	// Apply slug suggestion
	const handleApplySuggestion = async ( key ) => {
		try {
			const response = await apiFetch( {
				path: `/saman-seo/v1/slug-suggestions/${ key }/apply`,
				method: 'POST',
			} );
			if ( response.success ) {
				setSuggestions( suggestions.filter( ( s ) => s.key !== key ) );
				fetchRedirects();
				fetchSummary();
			}
		} catch ( error ) {
			console.error( 'Failed to apply suggestion:', error );
		}
	};

	// Dismiss slug suggestion
	const handleDismissSuggestion = async ( key ) => {
		try {
			await apiFetch( {
				path: `/saman-seo/v1/slug-suggestions/${ key }/dismiss`,
				method: 'POST',
			} );
			setSuggestions( suggestions.filter( ( s ) => s.key !== key ) );
		} catch ( error ) {
			console.error( 'Failed to dismiss suggestion:', error );
		}
	};

	// Use suggestion to prefill form
	const handleUseSuggestion = ( suggestion ) => {
		setFormData( ( prev ) => ( {
			...prev,
			source: suggestion.source,
			target: suggestion.target,
			status_code: 301,
		} ) );
		setShowModal( true );
	};

	// Format date
	const formatDate = ( dateStr ) => {
		if ( ! dateStr || dateStr === '0000-00-00 00:00:00' ) return '-';
		const date = new Date( dateStr );
		return (
			date.toLocaleDateString() +
			', ' +
			date.toLocaleTimeString( [], {
				hour: '2-digit',
				minute: '2-digit',
			} )
		);
	};

	// Format short date for display
	const formatShortDate = ( dateStr ) => {
		if ( ! dateStr ) return null;
		const date = new Date( dateStr );
		return date.toLocaleDateString();
	};
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Redirects', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Create and manage URL redirects to maintain SEO value when URLs change.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="page-header-actions">
					<button
						type="button"
						className="button ghost"
						onClick={ () => setShowImport( true ) }
					>
						{ __( 'Import', 'saman-seo' ) }
					</button>
					<div className="dropdown">
						<button type="button" className="button ghost">
							{ __( 'Export', 'saman-seo' ) }
						</button>
						<div className="dropdown-menu">
							<button
								type="button"
								className="dropdown-menu__item"
								onClick={ () => handleExport( 'json' ) }
							>
								{ __( 'Export as JSON', 'saman-seo' ) }
							</button>
							<button
								type="button"
								className="dropdown-menu__item"
								onClick={ () => handleExport( 'csv' ) }
							>
								{ __( 'Export as CSV', 'saman-seo' ) }
							</button>
						</div>
					</div>
					<button
						type="button"
						className="button primary"
						onClick={ openCreateModal }
					>
						{ __( 'Add Redirect', 'saman-seo' ) }
					</button>
				</div>
			</div>

			<section className="page-body">
				{ /* Summary + Filters */ }
				<div className="page-toolbar">
					<div className="page-summary">
						<div className="stat-list">
							<div className="stat-list__item">
								<span className="stat-list__value">
									{ summaryLoading
										? '...'
										: summary.total.toLocaleString() }
								</span>
								<span className="stat-list__label">
									{ __( 'Total redirects', 'saman-seo' ) }
								</span>
							</div>
							<div className="stat-list__item">
								<span className="stat-list__value">
									{ summaryLoading
										? '...'
										: summary.active_count.toLocaleString() }
								</span>
								<span className="stat-list__label">
									{ __( 'Active', 'saman-seo' ) }
								</span>
							</div>
							<div className="stat-list__item">
								<span className="stat-list__value">
									{ summaryLoading
										? '...'
										: summary.total_hits.toLocaleString() }
								</span>
								<span className="stat-list__label">
									{ __( 'Total hits', 'saman-seo' ) }
								</span>
							</div>
							<div
								className="stat-list__item"
								title={
									summary.top_redirect
										? summary.top_redirect.source
										: ''
								}
							>
								<span className="stat-list__value">
									{ summaryLoading
										? '...'
										: summary.top_redirect
										? summary.top_redirect.hits.toLocaleString()
										: '0' }
								</span>
								<span className="stat-list__label">
									{ __( 'Top hits', 'saman-seo' ) }
								</span>
							</div>
						</div>
					</div>
					<div className="page-toolbar__filters">
						<input
							type="search"
							placeholder={ __(
								'Search redirects\u2026',
								'saman-seo'
							) }
							value={ search }
							onChange={ ( e ) => setSearch( e.target.value ) }
							className="search-input"
						/>
						<select
							value={ filterGroup }
							onChange={ ( e ) =>
								setFilterGroup( e.target.value )
							}
						>
							<option value="">
								{ __( 'All groups', 'saman-seo' ) }
							</option>
							{ groups.map( ( group ) => (
								<option key={ group } value={ group }>
									{ group }
								</option>
							) ) }
						</select>
						<select
							value={ filterStatus }
							onChange={ ( e ) =>
								setFilterStatus( e.target.value )
							}
						>
							<option value="">
								{ __( 'All status', 'saman-seo' ) }
							</option>
							{ STATUS_CODES.map( ( code ) => (
								<option key={ code.value } value={ code.value }>
									{ code.label }
								</option>
							) ) }
						</select>
						<span className="page-toolbar__count">
							{ pagination.total }{ ' ' }
							{ __( 'redirect', 'saman-seo' ) }
							{ pagination.total !== 1 ? 's' : '' }
						</span>
					</div>
				</div>

				{ /* Slug Change Suggestions */ }
				{ ! suggestionsLoading && suggestions.length > 0 && (
					<div className="alert-card warning">
						<div className="alert-header">
							<h3>
								{ __( 'Detected slug changes', 'saman-seo' ) }
							</h3>
						</div>
						<p className="muted">
							{ __(
								'The following posts have changed their URL structure. Create redirects to prevent 404 errors.',
								'saman-seo'
							) }
						</p>
						<div className="table-wrap">
							<table className="data-table data-table--compact">
								<thead>
									<tr>
										<th className="col-url">
											{ __( 'Old path', 'saman-seo' ) }
										</th>
										<th className="col-url">
											{ __( 'New target', 'saman-seo' ) }
										</th>
										<th className="col-actions">
											{ __( 'Actions', 'saman-seo' ) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ suggestions.map( ( suggestion ) => (
										<tr key={ suggestion.key }>
											<td className="url-cell">
												<code>
													{ suggestion.source }
												</code>
											</td>
											<td className="url-cell">
												<a
													href={ suggestion.target }
													target="_blank"
													rel="noopener noreferrer"
												>
													{ suggestion.target }
												</a>
											</td>
											<td className="action-cell">
												<div className="table-actions">
													<button
														type="button"
														className="table-actions__primary"
														onClick={ () =>
															handleApplySuggestion(
																suggestion.key
															)
														}
													>
														{ __(
															'Apply',
															'saman-seo'
														) }
													</button>
													<button
														type="button"
														className="table-actions__secondary"
														onClick={ () =>
															handleUseSuggestion(
																suggestion
															)
														}
													>
														{ __(
															'Edit',
															'saman-seo'
														) }
													</button>
													<button
														type="button"
														className="table-actions__danger"
														onClick={ () =>
															handleDismissSuggestion(
																suggestion.key
															)
														}
													>
														{ __(
															'Dismiss',
															'saman-seo'
														) }
													</button>
												</div>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>
					</div>
				) }

				{ /* Bulk actions */ }
				{ selectedIds.length > 0 && (
					<div className="bulk-actions">
						<span>
							{ selectedIds.length }{ ' ' }
							{ __( 'selected', 'saman-seo' ) }
						</span>
						<button
							type="button"
							className="button danger small"
							onClick={ handleBulkDelete }
							disabled={ bulkLoading }
						>
							{ bulkLoading
								? __( 'Deleting\u2026', 'saman-seo' )
								: __( 'Delete Selected', 'saman-seo' ) }
						</button>
						<button
							type="button"
							className="link-button"
							onClick={ () => setSelectedIds( [] ) }
						>
							{ __( 'Clear Selection', 'saman-seo' ) }
						</button>
					</div>
				) }

				{ /* Redirects Table */ }
				{ redirectsLoading ? (
					<div className="loading-state">
						{ __( 'Loading redirects\u2026', 'saman-seo' ) }
					</div>
				) : redirects.length === 0 ? (
					<div className="empty-state">
						<div className="empty-state__icon">
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="1.5"
								width="48"
								height="48"
							>
								<path d="M9 18l6-6-6-6" />
								<path d="M15 6l-6 6 6 6" opacity="0.5" />
							</svg>
						</div>
						<h3>{ __( 'No redirects found', 'saman-seo' ) }</h3>
						<p>
							{ search || filterGroup || filterStatus
								? __(
										'Try adjusting your filters.',
										'saman-seo'
								  )
								: __(
										'Add your first redirect to get started.',
										'saman-seo'
								  ) }
						</p>
						{ ! search && ! filterGroup && ! filterStatus && (
							<button
								type="button"
								className="button primary"
								onClick={ openCreateModal }
							>
								{ __( 'Add Redirect', 'saman-seo' ) }
							</button>
						) }
					</div>
				) : (
					<>
						<div className="table-wrap">
							<table className="data-table data-table--compact">
								<thead>
									<tr>
										<th className="checkbox-col">
											<input
												type="checkbox"
												checked={
													selectedIds.length ===
														redirects.length &&
													redirects.length > 0
												}
												onChange={ toggleSelectAll }
											/>
										</th>
										<th className="col-url">
											{ __( 'Source', 'saman-seo' ) }
										</th>
										<th className="col-url">
											{ __( 'Target', 'saman-seo' ) }
										</th>
										<th className="col-status">
											{ __( 'Status', 'saman-seo' ) }
										</th>
										<th className="col-numeric">
											{ __( 'Hits', 'saman-seo' ) }
										</th>
										<th className="col-date">
											{ __( 'Last hit', 'saman-seo' ) }
										</th>
										<th className="col-actions">
											{ __( 'Actions', 'saman-seo' ) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ redirects.map( ( redirect ) => (
										<tr
											key={ redirect.id }
											className={
												! redirect.is_active
													? 'inactive-row'
													: ''
											}
										>
											<td className="checkbox-col">
												<input
													type="checkbox"
													checked={ selectedIds.includes(
														redirect.id
													) }
													onChange={ () =>
														toggleSelect(
															redirect.id
														)
													}
												/>
											</td>
											<td className="url-cell">
												<span className="url-cell__path">
													<code>
														{ redirect.source }
													</code>
												</span>
												<span className="url-cell__badges">
													{ redirect.is_regex && (
														<span
															className="pill info small"
															title={ buildRegexTooltip(
																redirect.source
															) }
														>
															{ __(
																'Regex',
																'saman-seo'
															) }
														</span>
													) }
													{ redirect.group_name && (
														<span className="pill muted small">
															{
																redirect.group_name
															}
														</span>
													) }
												</span>
											</td>
											<td className="url-cell">
												<a
													href={ redirect.target }
													target="_blank"
													rel="noopener noreferrer"
													className="truncate-link"
												>
													{ redirect.target }
												</a>
											</td>
											<td className="status-cell">
												<span
													className={ `pill ${
														redirect.status_code ===
														301
															? 'success'
															: redirect.status_code ===
															  410
															? 'danger'
															: 'warning'
													}` }
												>
													{ redirect.status_code }
												</span>
											</td>
											<td className="numeric-cell">
												{ redirect.hits }
											</td>
											<td className="date-cell">
												{ formatDate(
													redirect.last_hit
												) }
												{ ! redirect.is_active && (
													<div className="text-muted text-small">
														{ redirect.start_date &&
														new Date(
															redirect.start_date
														) > new Date()
															? sprintf(
																	/* translators: %s: placeholder */ __(
																		'Starts: %s',
																		'saman-seo'
																	),
																	formatShortDate(
																		redirect.start_date
																	)
															  )
															: sprintf(
																	/* translators: %s: placeholder */ __(
																		'Ended: %s',
																		'saman-seo'
																	),
																	formatShortDate(
																		redirect.end_date
																	)
															  ) }
													</div>
												) }
											</td>
											<td className="action-cell">
												<div className="table-actions">
													<button
														type="button"
														className="table-actions__secondary"
														onClick={ () =>
															openEditModal(
																redirect
															)
														}
													>
														{ __(
															'Edit',
															'saman-seo'
														) }
													</button>
													<button
														type="button"
														className="table-actions__danger"
														onClick={ () =>
															handleDeleteRedirect(
																redirect.id
															)
														}
													>
														{ __(
															'Delete',
															'saman-seo'
														) }
													</button>
												</div>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>

						{ /* Pagination */ }
						{ pagination.total_pages > 1 && (
							<div className="pagination">
								<button
									type="button"
									className="pagination-btn"
									disabled={ pagination.page <= 1 }
									onClick={ () =>
										fetchRedirects( pagination.page - 1 )
									}
								>
									{ __( '\u2039 Previous', 'saman-seo' ) }
								</button>
								<span className="pagination-info">
									{ __( 'Page', 'saman-seo' ) }{ ' ' }
									{ pagination.page }{ ' ' }
									{ __( 'of', 'saman-seo' ) }{ ' ' }
									{ pagination.total_pages }
								</span>
								<button
									type="button"
									className="pagination-btn"
									disabled={
										pagination.page >=
										pagination.total_pages
									}
									onClick={ () =>
										fetchRedirects( pagination.page + 1 )
									}
								>
									{ __( 'Next \u203A', 'saman-seo' ) }
								</button>
							</div>
						) }
					</>
				) }
			</section>

			{ /* Create/Edit Modal */ }
			{ __( 'e.g., migration, campaign', 'saman-seo' ) }

			{ /* Import Modal */ }
			{ __( 'Paste JSON or CSV data here\u2026', 'saman-seo' ) }
		</div>
	);
};
export default Redirects;
