#!/usr/bin/env node
/**
 * Stamp a "generated file" banner onto every compiled bundle under build/.
 *
 * Why this exists: build/ is gitignored, so an edit made directly to a built
 * file is unrecoverable. It is silently replaced by the next `npm run build`,
 * and there is no committed copy to diff against or restore from. The banner
 * puts that warning where someone will actually meet it -- at the top of the
 * file they just opened, including in an installed copy of the plugin.
 *
 * This runs as a post-build step rather than webpack's BannerPlugin because
 * wp-scripts configures Terser with an `output.comments` regex that preserves
 * translator comments and strips everything else, the banner included.
 * Overriding that regex to save the banner would risk the i18n comments, so the
 * banner is added after minification instead. Prepending a block comment cannot
 * change behaviour: comments are allowed ahead of a "use strict" prologue.
 *
 * Idempotent -- re-running will not stack duplicate banners.
 */

const fs = require( 'fs' );
const path = require( 'path' );

const BUILD_DIR = path.join( __dirname, '..', 'build' );
const MARKER = 'GENERATED FILE, DO NOT EDIT';

const BANNER = `/*!
 * Saman SEO - ${ MARKER }.
 *
 * Source lives in src-v2/ (or assets/less/ for CSS). Run \`npm run build\` to
 * regenerate. Edits made here are lost on the next build, and build/ is
 * gitignored, so there is no committed copy to restore from.
 */
`;

/**
 * Collect every .js file under a directory, recursively.
 *
 * @param {string} dir Directory to walk.
 * @return {string[]} Absolute file paths.
 */
function collectBundles( dir ) {
	if ( ! fs.existsSync( dir ) ) {
		return [];
	}

	return fs.readdirSync( dir, { withFileTypes: true } ).flatMap( ( entry ) => {
		const full = path.join( dir, entry.name );

		if ( entry.isDirectory() ) {
			return collectBundles( full );
		}
		// Source maps stay untouched: a banner would shift every mapping.
		return entry.isFile() && entry.name.endsWith( '.js' ) ? [ full ] : [];
	} );
}

const bundles = collectBundles( BUILD_DIR );

if ( ! bundles.length ) {
	// Nothing built yet is not an error; `npm run build` may be mid-chain.
	process.stdout.write( 'prepend-build-banner: no bundles found, skipping\n' );
	process.exit( 0 );
}

let stamped = 0;

bundles.forEach( ( file ) => {
	const contents = fs.readFileSync( file, 'utf8' );

	if ( contents.includes( MARKER ) ) {
		return;
	}

	fs.writeFileSync( file, BANNER + contents );
	stamped += 1;
} );

process.stdout.write(
	`prepend-build-banner: stamped ${ stamped } of ${ bundles.length } bundle(s)\n`
);
