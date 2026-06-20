import { __ } from '@wordpress/i18n';
import { stripUnreplacedVariables } from '../utils/template';
/**
 * Search Preview Component
 *
 * Displays a preview of how content will appear in search results.
 * Style inspired by search engine result pages.
 */

const SearchPreview = ( {
	title = '',
	description = '',
	url = '',
	domain = '',
	favicon = '',
	maxTitleLength = 60,
	maxDescriptionLength = 160,
} ) => {
	const safeTitle = stripUnreplacedVariables( title );
	const safeDescription = stripUnreplacedVariables( description );
	const titleLength = safeTitle.length;
	const descriptionLength = safeDescription.length;
	const isTitleOverLimit = titleLength > maxTitleLength;
	const isDescriptionOverLimit = descriptionLength > maxDescriptionLength;

	// Truncate for display if over limit
	const displayTitle = isTitleOverLimit
		? safeTitle.substring( 0, maxTitleLength ) + '...'
		: safeTitle;
	const displayDescription = isDescriptionOverLimit
		? safeDescription.substring( 0, maxDescriptionLength ) + '...'
		: safeDescription;
	return (
		<div className="search-preview">
			<div className="search-preview__header">
				<span className="search-preview__label">
					{ __( 'Search Result Preview', 'saman-seo' ) }
				</span>
			</div>
			<div className="search-preview__body">
				<div className="search-preview__url">
					{ favicon ? (
						<img
							src={ favicon }
							alt=""
							className="search-preview__favicon"
						/>
					) : (
						<span className="search-preview__favicon-placeholder" />
					) }
					<span className="search-preview__domain">
						{ domain || url }
					</span>
				</div>
				<div className="search-preview__title">
					{ displayTitle || __( 'Page Title', 'saman-seo' ) }
				</div>
				<div className="search-preview__description">
					{ displayDescription ||
						__(
							'Meta description will appear here\u2026',
							'saman-seo'
						) }
				</div>
			</div>
			<div className="search-preview__footer">
				<span
					className={ `search-preview__counter ${
						isTitleOverLimit ? 'over-limit' : ''
					}` }
				>
					<strong>{ titleLength }</strong> / { maxTitleLength }{ ' ' }
					{ __( 'chars (title)', 'saman-seo' ) }
				</span>
				<span
					className={ `search-preview__counter ${
						isDescriptionOverLimit ? 'over-limit' : ''
					}` }
				>
					<strong>{ descriptionLength }</strong> /{ ' ' }
					{ maxDescriptionLength }{ ' ' }
					{ __( 'chars (description)', 'saman-seo' ) }
				</span>
			</div>
		</div>
	);
};
export default SearchPreview;
