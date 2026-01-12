/**
 * Google Search Preview Component
 *
 * Displays a realistic Google search result preview.
 */

const SearchPreview = ({ title, description, url }) => {
    // Truncate title if too long (Google shows ~60 chars)
    const displayTitle = title.length > 60 ? title.substring(0, 57) + '...' : title;

    // Truncate description if too long (Google shows ~160 chars)
    const displayDesc = description.length > 160
        ? description.substring(0, 157) + '...'
        : description || 'No description provided. Add a meta description to control what appears here.';

    // Format URL for display
    const formatUrl = (urlString) => {
        try {
            const urlObj = new URL(urlString);
            const path = urlObj.pathname === '/' ? '' : urlObj.pathname;
            return `${urlObj.hostname}${path}`;
        } catch {
            return urlString;
        }
    };

    return (
        <div className="wpseopilot-search-preview">
            {/* Favicon and URL breadcrumb */}
            <div className="wpseopilot-search-preview__breadcrumb">
                <div className="wpseopilot-search-preview__favicon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2"/>
                        <path d="M12 6v6l4 2" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                    </svg>
                </div>
                <div className="wpseopilot-search-preview__url-container">
                    <span className="wpseopilot-search-preview__site-name">
                        {(() => {
                            try {
                                return new URL(url).hostname.replace('www.', '');
                            } catch {
                                return 'example.com';
                            }
                        })()}
                    </span>
                    <span className="wpseopilot-search-preview__url">{formatUrl(url)}</span>
                </div>
                <button type="button" className="wpseopilot-search-preview__menu" aria-label="More options">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="5" r="1.5" fill="currentColor"/>
                        <circle cx="12" cy="12" r="1.5" fill="currentColor"/>
                        <circle cx="12" cy="19" r="1.5" fill="currentColor"/>
                    </svg>
                </button>
            </div>

            {/* Title */}
            <h3 className="wpseopilot-search-preview__title">
                {displayTitle || 'Page Title'}
            </h3>

            {/* Description */}
            <p className="wpseopilot-search-preview__description">
                {displayDesc}
            </p>
        </div>
    );
};

export default SearchPreview;
