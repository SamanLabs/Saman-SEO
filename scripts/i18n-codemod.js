/**
 * Codemod to wrap user-facing strings in src-v2 with @wordpress/i18n.
 */

const fs = require( 'fs' );
const path = require( 'path' );
const parser = require( '@babel/parser' );
const traverse = require( '@babel/traverse' ).default;
const generate = require( '@babel/generator' ).default;
const t = require( '@babel/types' );

const DOMAIN = 'saman-seo';

const SRC_DIR = path.resolve( __dirname, '..', 'src-v2' );

const JSX_TRANSlatable_ATTRS = new Set( [
	'label',
	'title',
	'placeholder',
	'alt',
	'aria-label',
	'ariaLabel',
	'aria-placeholder',
	'aria-describedby',
	'help',
	'hint',
	'tooltip',
	'confirmText',
	'cancelText',
] );

const DISPLAY_OBJECT_KEYS = new Set( [
	'label',
	'name',
	'description',
	'desc',
	'title',
	'text',
	'message',
	'hint',
	'placeholder',
	'stats',
	'badge',
	'help',
	'subtext',
	'summary',
	'caption',
	'tooltip',
	'short',
	'options',
] );

const MESSAGE_CALLS = new Set( [
	'alert',
	'confirm',
	'prompt',
	'setError',
	'setFormError',
	'setSaveMessage',
] );

const DISPLAY_ARRAY_PUSH = new Set( [
	'warnings',
	'errors',
	'issues',
	'messages',
	'suggestions',
	'recommendations',
	'tips',
	'notes',
	'descriptions',
	'reasons',
] );

const LABEL_CONST_SUFFIXES = [ '_LABELS', '_TITLES', '_TEXTS', '_MESSAGES' ];

const UTILITY_METHODS = new Set( [
	'join',
	'split',
	'replace',
	'replaceAll',
	'includes',
	'indexOf',
	'lastIndexOf',
	'startsWith',
	'endsWith',
	'match',
	'search',
	'padStart',
	'padEnd',
	'repeat',
	'slice',
	'substr',
	'substring',
	'toLowerCase',
	'toUpperCase',
	'trim',
	'trimStart',
	'trimEnd',
	'concat',
	'localeCompare',
	'charAt',
	'charCodeAt',
	'codePointAt',
	'normalize',
] );

const UTILITY_GLOBALS = new Set( [
	'String',
	'Number',
	'Boolean',
	'Date',
	'JSON',
	'Object',
	'Array',
	'parseInt',
	'parseFloat',
	'isNaN',
	'isFinite',
	'encodeURI',
	'encodeURIComponent',
	'decodeURI',
	'decodeURIComponent',
] );

function isI18nCall( callee ) {
	if ( ! t.isIdentifier( callee ) && ! t.isMemberExpression( callee ) ) {
		return false;
	}
	const name = t.isIdentifier( callee )
		? callee.name
		: callee.property && callee.property.name;
	return [ '__', '_x', '_n', '_nx', 'sprintf' ].includes( name );
}

function isUtilityCall( callee ) {
	if ( t.isMemberExpression( callee ) && t.isIdentifier( callee.property ) ) {
		return UTILITY_METHODS.has( callee.property.name );
	}
	if ( t.isIdentifier( callee ) ) {
		return UTILITY_GLOBALS.has( callee.name );
	}
	return false;
}

function isMessageCall( callee ) {
	if ( t.isIdentifier( callee ) ) {
		return MESSAGE_CALLS.has( callee.name );
	}
	if (
		t.isMemberExpression( callee ) &&
		t.isIdentifier( callee.object ) &&
		callee.object.name === 'window' &&
		t.isIdentifier( callee.property )
	) {
		return MESSAGE_CALLS.has( callee.property.name );
	}
	return false;
}

function getMessageCallName( callee ) {
	if ( t.isIdentifier( callee ) ) return callee.name;
	if ( t.isMemberExpression( callee ) && t.isIdentifier( callee.property ) ) {
		return callee.property.name;
	}
	return null;
}

function getJSXAttrName( attrNode ) {
	if ( t.isJSXIdentifier( attrNode.name ) ) return attrNode.name.name;
	if ( t.isJSXNamespacedName( attrNode.name ) ) return attrNode.name.name.name;
	return null;
}

function getObjectKeyName( keyNode ) {
	if ( t.isIdentifier( keyNode ) ) return keyNode.name;
	if ( t.isStringLiteral( keyNode ) ) return keyNode.value;
	return null;
}

function getFunctionName( fnPath ) {
	if ( ! fnPath ) return null;
	if ( t.isFunctionDeclaration( fnPath.node ) && fnPath.node.id ) {
		return fnPath.node.id.name;
	}
	// arrow function assigned to variable
	if (
		t.isVariableDeclarator( fnPath.parent ) &&
		t.isIdentifier( fnPath.parent.id )
	) {
		return fnPath.parent.id.name;
	}
	return null;
}

function isDescendantOfNode( childPath, ancestorNode ) {
	let current = childPath;
	while ( current ) {
		if ( current.node === ancestorNode ) return true;
		current = current.parentPath;
	}
	return false;
}

function findTranslatableContext( path ) {
	let current = path;
	while ( current ) {
		// Stop at existing i18n calls to avoid double wrapping.
		if ( current.isCallExpression() && isI18nCall( current.node.callee ) ) {
			return null;
		}

		if ( current.isCallExpression() ) {
			const callee = current.node.callee;
			if ( isMessageCall( callee ) ) {
				const args = current.node.arguments;
				if ( args.length > 0 && isDescendantOfNode( path, args[ 0 ] ) ) {
					return {
						type: 'call-arg',
						name: getMessageCallName( callee ),
					};
				}
			}
			// warnings.push( '...' )
			if (
				t.isMemberExpression( callee ) &&
				t.isIdentifier( callee.object ) &&
				t.isIdentifier( callee.property ) &&
				callee.property.name === 'push' &&
				DISPLAY_ARRAY_PUSH.has( callee.object.name )
			) {
				const args = current.node.arguments;
				if ( args.length > 0 && isDescendantOfNode( path, args[ 0 ] ) ) {
					return {
						type: 'array-push',
						name: callee.object.name,
					};
				}
			}
			// String/array utility calls are never translatable.
			if ( isUtilityCall( callee ) ) {
				return null;
			}
			// Other non-display calls stop traversal.
			return null;
		}

		if ( current.isJSXAttribute() ) {
			const name = getJSXAttrName( current.node );
			if ( JSX_TRANSlatable_ATTRS.has( name ) ) {
				return { type: 'jsx-attr', name };
			}
			return null;
		}

		if ( current.isJSXExpressionContainer() ) {
			const parent = current.parentPath;
			if ( parent.isJSXAttribute() ) {
				const name = getJSXAttrName( parent.node );
				if ( JSX_TRANSlatable_ATTRS.has( name ) ) {
					return { type: 'jsx-attr', name };
				}
				return null;
			}
			if ( parent.isJSXElement() || parent.isJSXFragment() ) {
				return { type: 'jsx-child' };
			}
		}

		if ( current.isObjectProperty() ) {
			const key = getObjectKeyName( current.node.key );
			if ( DISPLAY_OBJECT_KEYS.has( key ) ) {
				return { type: 'object-value', key };
			}
			// label constants like ISSUE_TYPE_LABELS
			if ( current.parentPath && current.parentPath.isObjectExpression() ) {
				const varPath = current.parentPath.parentPath;
				if ( varPath && varPath.isVariableDeclarator() && t.isIdentifier( varPath.node.id ) ) {
					const varName = varPath.node.id.name;
					if ( LABEL_CONST_SUFFIXES.some( ( suffix ) => varName.endsWith( suffix ) ) ) {
						return { type: 'label-const', key: varName };
					}
				}
			}
			return null;
		}

		if ( current.isArrayExpression() ) {
			const parent = current.parentPath;
			if ( parent && parent.isObjectProperty() ) {
				const key = getObjectKeyName( parent.node.key );
				if ( DISPLAY_OBJECT_KEYS.has( key ) ) {
					return { type: 'array-display', key };
				}
			}
		}

		if ( current.isReturnStatement() ) {
			const fn = current.getFunctionParent();
			const fnName = getFunctionName( fn );
			if ( fnName === 'formatDate' || fnName === 'formatShortDate' ) {
				return { type: 'return-formatDate', fnName };
			}
		}

		current = current.parentPath;
	}
	return null;
}

function hasDisplayLetter( value ) {
	return /[A-Za-z]/.test( value );
}

function shouldSkipString( value ) {
	if ( typeof value !== 'string' ) return true;
	if ( value === '' ) return true;
	if ( value.length === 1 ) return true;
	if ( ! hasDisplayLetter( value ) ) return true;
	return false;
}

function normalizeTranslatableString( value ) {
	return value.replace( /\s+/g, ' ' ).replace( /\.{3}/g, '\u2026' );
}

function makeI18nCall( value ) {
	const normalized = normalizeTranslatableString( value ).trim();
	if ( ! hasDisplayLetter( normalized ) ) {
		return t.stringLiteral( value );
	}
	return t.callExpression( t.identifier( '__' ), [
		t.stringLiteral( normalized ),
		t.stringLiteral( DOMAIN ),
	] );
}

function templateToI18n( templateNode ) {
	const expressions = templateNode.expressions;
	const quasis = templateNode.quasis;
	if ( expressions.length === 0 ) {
		return makeI18nCall( quasis[ 0 ].value.cooked || quasis[ 0 ].value.raw );
	}
	let formatString = '';
	for ( let i = 0; i < quasis.length; i++ ) {
		const cooked = quasis[ i ].value.cooked || quasis[ i ].value.raw;
		formatString += normalizeTranslatableString( cooked ).replace( /%/g, '%%' );
		if ( i < expressions.length ) {
			formatString += expressions.length === 1 ? '%s' : `%${ i + 1 }$s`;
		}
	}
	formatString = formatString.trim();

	const placeholders =
		expressions.length === 1
			? [ '%s' ]
			: expressions.map( ( _, i ) => `%${ i + 1 }$s` );
	const translatorComment = ` translators: ${ placeholders
		.map( ( p ) => `${ p }: placeholder` )
		.join( ', ' )} `;

	const inner = t.callExpression( t.identifier( '__' ), [
		t.stringLiteral( formatString ),
		t.stringLiteral( DOMAIN ),
	] );
	inner.leadingComments = [ { type: 'CommentBlock', value: translatorComment } ];

	return t.callExpression( t.identifier( 'sprintf' ), [ inner, ...expressions ] );
}

function hasI18nImport( ast, specifier ) {
	let found = false;
	traverse( ast, {
		ImportDeclaration( path ) {
			if ( path.node.source.value !== '@wordpress/i18n' ) return;
			path.node.specifiers.forEach( ( spec ) => {
				if ( t.isImportSpecifier( spec ) && t.isIdentifier( spec.imported ) && spec.imported.name === specifier ) {
					found = true;
				}
			} );
		},
	} );
	return found;
}

function addI18nImport( ast, needsSprintf ) {
	const needs = [ '__' ];
	if ( needsSprintf ) needs.push( 'sprintf' );
	const missing = needs.filter( ( s ) => ! hasI18nImport( ast, s ) );
	if ( missing.length === 0 ) return;

	let existing = null;
	let lastImport = null;
	traverse( ast, {
		ImportDeclaration( path ) {
			lastImport = path.node;
			if ( path.node.source.value === '@wordpress/i18n' ) {
				existing = path.node;
			}
		},
	} );

	if ( existing ) {
		const existingNames = new Set();
		existing.specifiers.forEach( ( spec ) => {
			if ( t.isImportSpecifier( spec ) && t.isIdentifier( spec.imported ) ) {
				existingNames.add( spec.imported.name );
			}
		} );
		missing.forEach( ( name ) => {
			if ( ! existingNames.has( name ) ) {
				existing.specifiers.push(
					t.importSpecifier( t.identifier( name ), t.identifier( name ) )
				);
			}
		} );
	} else {
		const decl = t.importDeclaration(
			missing.map( ( name ) =>
				t.importSpecifier( t.identifier( name ), t.identifier( name ) )
			),
			t.stringLiteral( '@wordpress/i18n' )
		);
		if ( lastImport ) {
			// Insert after the last import to keep imports grouped.
			const body = ast.program.body;
			const idx = body.indexOf( lastImport );
			body.splice( idx + 1, 0, decl );
		} else {
			ast.program.body.unshift( decl );
		}
	}
}

function replaceWithI18n( path, call, ctx ) {
	if ( ctx && ctx.type === 'jsx-attr' ) {
		let container = path;
		while ( container && ! container.isJSXExpressionContainer() ) {
			container = container.parentPath;
		}
		if ( container ) {
			container.replaceWith( t.jsxExpressionContainer( call ) );
		} else {
			path.replaceWith( t.jsxExpressionContainer( call ) );
		}
	} else {
		path.replaceWith( call );
	}
}

function processFile( filePath ) {
	const source = fs.readFileSync( filePath, 'utf8' );
	let ast;
	try {
		ast = parser.parse( source, {
			sourceType: 'module',
			plugins: [ 'jsx' ],
		} );
	} catch ( err ) {
		console.error( `Parse error in ${ filePath }:`, err.message );
		return { changed: false };
	}

	let needsI18n = false;
	let needsSprintf = false;

	traverse( ast, {
		JSXText( path ) {
			const raw = path.node.value;
			const trimmed = raw.trim();
			if ( ! trimmed ) return;
			if ( ! hasDisplayLetter( trimmed ) ) return;
			// Skip text inside <style>, <script>, <code>, <pre>, or <svg> tags.
			let parent = path.parentPath;
			while ( parent ) {
				if ( parent.isJSXElement() ) {
					const name = parent.node.openingElement.name;
					if (
						t.isJSXIdentifier( name ) &&
						[ 'style', 'script', 'code', 'pre', 'svg' ].includes( name.name )
					) {
						return;
					}
				}
				parent = parent.parentPath;
			}
			// Skip code-like template markers.
			if ( trimmed.includes( '{{' ) || trimmed.includes( '}}' ) ) return;

			const call = makeI18nCall( trimmed );
			if ( t.isStringLiteral( call ) ) {
				return;
			}
			// Preserve surrounding whitespace by splitting if needed.
			const leading = raw.match( /^\s*/ )[ 0 ];
			const trailing = raw.match( /\s*$/ )[ 0 ];
			const replacement = [];
			if ( leading ) {
				replacement.push( t.jsxText( leading ) );
			}
			replacement.push( t.jsxExpressionContainer( call ) );
			if ( trailing ) {
				replacement.push( t.jsxText( trailing ) );
			}
			if ( replacement.length === 1 ) {
				path.replaceWith( replacement[ 0 ] );
			} else {
				path.replaceWithMultiple( replacement );
			}
			needsI18n = true;
		},
		StringLiteral( path ) {
			if ( shouldSkipString( path.node.value ) ) return;
			const ctx = findTranslatableContext( path );
			if ( ! ctx ) return;
			const replacement = makeI18nCall( path.node.value );
			if ( t.isStringLiteral( replacement ) ) return;
			replaceWithI18n( path, replacement, ctx );
			needsI18n = true;
		},
		TemplateLiteral( path ) {
			// Only translate template literals with some display text.
			const cooked = path.node.quasis.map( ( q ) => q.value.cooked || q.value.raw ).join( '' );
			if ( ! hasDisplayLetter( cooked.trim() ) ) return;
			const ctx = findTranslatableContext( path );
			if ( ! ctx ) return;
			const replacement = templateToI18n( path.node );
			if ( t.isStringLiteral( replacement ) ) return;
			if ( t.isCallExpression( replacement ) && t.isIdentifier( replacement.callee ) && replacement.callee.name === 'sprintf' ) {
				needsSprintf = true;
			}
			needsI18n = true;
			replaceWithI18n( path, replacement, ctx );
		},
	} );

	if ( ! needsI18n ) {
		return { changed: false };
	}

	addI18nImport( ast, needsSprintf );

	const output = generate( ast, { retainLines: false, compact: false, quotes: 'single' }, source ).code;
	if ( output === source ) {
		return { changed: false };
	}
	fs.writeFileSync( filePath, output, 'utf8' );
	return { changed: true, needsSprintf };
}

function getJsFiles( dir ) {
	const files = [];
	const entries = fs.readdirSync( dir, { withFileTypes: true } );
	for ( const entry of entries ) {
		const full = path.join( dir, entry.name );
		if ( entry.isDirectory() ) {
			files.push( ...getJsFiles( full ) );
		} else if ( entry.isFile() && entry.name.endsWith( '.js' ) ) {
			files.push( full );
		}
	}
	return files;
}

const files = getJsFiles( SRC_DIR );
let changedCount = 0;
for ( const file of files ) {
	const rel = path.relative( process.cwd(), file );
	try {
		const result = processFile( file );
		if ( result.changed ) {
			changedCount++;
			console.log( `✓ ${ rel }` );
		}
	} catch ( err ) {
		console.error( `✗ ${ rel }:`, err.message );
	}
}
console.log( `\nDone. ${ changedCount } file(s) changed.` );
