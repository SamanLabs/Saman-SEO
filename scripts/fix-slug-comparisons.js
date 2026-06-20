/**
 * Revert translation wrappers inside === / !== comparisons.
 * These strings are internal slugs/values, not user-facing text.
 */

const fs = require( 'fs' );
const path = require( 'path' );
const parser = require( '@babel/parser' );
const traverse = require( '@babel/traverse' ).default;
const generate = require( '@babel/generator' ).default;
const t = require( '@babel/types' );

const SRC_DIR = path.resolve( __dirname, '..', 'src-v2' );

function isI18nCall( node ) {
	return (
		t.isCallExpression( node ) &&
		t.isIdentifier( node.callee ) &&
		node.callee.name === '__' &&
		node.arguments.length >= 1 &&
		t.isStringLiteral( node.arguments[ 0 ] )
	);
}

function isNonLiteralOperand( node ) {
	return (
		t.isIdentifier( node ) ||
		t.isMemberExpression( node ) ||
		t.isCallExpression( node ) ||
		t.isOptionalMemberExpression( node ) ||
		t.isOptionalCallExpression( node )
	);
}

function processFile( filePath ) {
	const source = fs.readFileSync( filePath, 'utf8' );
	let ast;
	try {
		ast = parser.parse( source, { sourceType: 'module', plugins: [ 'jsx' ] } );
	} catch ( err ) {
		console.error( `Parse error in ${ filePath }:`, err.message );
		return false;
	}

	let changed = false;
	traverse( ast, {
		BinaryExpression( path ) {
			if ( path.node.operator !== '===' && path.node.operator !== '!==' ) {
				return;
			}
			const left = path.node.left;
			const right = path.node.right;
			if ( isI18nCall( left ) && isNonLiteralOperand( right ) ) {
				path.node.left = t.stringLiteral( left.arguments[ 0 ].value );
				changed = true;
			} else if ( isI18nCall( right ) && isNonLiteralOperand( left ) ) {
				path.node.right = t.stringLiteral( right.arguments[ 0 ].value );
				changed = true;
			}
		},
	} );

	if ( ! changed ) return false;
	const output = generate( ast, { retainLines: false, compact: false, quotes: 'single' }, source ).code;
	fs.writeFileSync( filePath, output, 'utf8' );
	return true;
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

let count = 0;
for ( const file of getJsFiles( SRC_DIR ) ) {
	const rel = path.relative( process.cwd(), file );
	try {
		if ( processFile( file ) ) {
			count++;
			console.log( `✓ ${ rel }` );
		}
	} catch ( err ) {
		console.error( `✗ ${ rel }:`, err.message );
	}
}
console.log( `\nDone. ${ count } file(s) fixed.` );
