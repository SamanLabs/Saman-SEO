/**
 * Search Preview Component
 *
 * Displays a preview of how content will appear in search results.
 * Style inspired by search engine result pages.
 */

const SearchPreview = ({
    title = '',
    description = '',
    url = '',
    domain = '',
    favicon = '',
    maxTitleLength = 60,
    maxDescriptionLength = 155,
}) => {
    const titleLength = title.length;
    const descriptionLength = description.length;
    const isTitleOverLimit = titleLength > maxTitleLength;
    const isDescriptionOverLimit = descriptionLength > maxDescriptionLength;

    // Truncate for display if over limit
    const displayTitle = isTitleOverLimit
        ? title.substring(0, maxTitleLength) + '...'
        : title;
    const displayDescription = isDescriptionOverLimit
        ? description.substring(0, maxDescriptionLength) + '...'
        : description;

    return (
        <div className="search-preview">
            <div className="search-preview__header">
                <span className="search-preview__label">Search Result Preview</span>
            </div>
            <div className="search-preview__body">
                <div className="search-preview__url">
                    {favicon ? (
                        <img
                            src={favicon}
                            alt=""
                            className="search-preview__favicon"
                        />
                    ) : (
                        <span className="search-preview__favicon-placeholder" />
                    )}
                    <span className="search-preview__domain">
                        {domain || url}
                    </span>
                </div>
                <div className="search-preview__title">
                    {displayTitle || 'Page Title'}
                </div>
                <div className="search-preview__description">
                    {displayDescription || 'Meta description will appear here...'}
                </div>
            </div>
            <div className="search-preview__footer">
                <span className={`search-preview__counter ${isTitleOverLimit ? 'over-limit' : ''}`}>
                    <strong>{titleLength}</strong> / {maxTitleLength} chars (title)
                </span>
                <span className={`search-preview__counter ${isDescriptionOverLimit ? 'over-limit' : ''}`}>
                    <strong>{descriptionLength}</strong> / {maxDescriptionLength} chars (description)
                </span>
            </div>
        </div>
    );
};

export default SearchPreview;
