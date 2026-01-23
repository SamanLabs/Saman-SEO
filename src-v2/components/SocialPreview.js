/**
 * Social Preview Components
 *
 * Reusable Facebook and Twitter/X preview cards for social sharing.
 */

/**
 * Facebook Preview Card
 *
 * @param {Object} props
 * @param {string} props.image - Image URL
 * @param {string} props.title - Title text
 * @param {string} props.description - Description text
 * @param {string} props.domain - Site domain (e.g., "example.com")
 */
export const FacebookPreview = ({ image, title, description, domain }) => {
    return (
        <div className="social-preview social-preview--facebook">
            <div className="social-preview__header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877f2">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span>Facebook</span>
            </div>
            <div className="social-preview__card">
                <div
                    className="social-preview__image"
                    style={{ backgroundImage: image ? `url(${image})` : 'none' }}
                >
                    {!image && (
                        <div className="social-preview__placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" opacity="0.3">
                                <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                            </svg>
                        </div>
                    )}
                </div>
                <div className="social-preview__body">
                    <span className="social-preview__domain">{domain}</span>
                    <span className="social-preview__title">{title || 'Your Page Title'}</span>
                    <span className="social-preview__desc">{description || 'Your page description will appear here when shared on social media platforms.'}</span>
                </div>
            </div>
        </div>
    );
};

/**
 * Twitter/X Preview Card
 *
 * @param {Object} props
 * @param {string} props.image - Image URL
 * @param {string} props.title - Title text
 * @param {string} props.description - Description text
 * @param {string} props.domain - Site domain (e.g., "example.com")
 * @param {string} props.cardType - Card type: "summary" or "summary_large_image"
 */
export const TwitterPreview = ({ image, title, description, domain, cardType = 'summary_large_image' }) => {
    const isSummaryCard = cardType === 'summary';

    return (
        <div className={`social-preview social-preview--twitter ${isSummaryCard ? 'social-preview--summary' : ''}`}>
            <div className="social-preview__header">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="#000">
                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
                <span>X (Twitter)</span>
            </div>
            {isSummaryCard ? (
                <div className="social-preview__card social-preview__card--horizontal">
                    <div
                        className="social-preview__image social-preview__image--square"
                        style={{ backgroundImage: image ? `url(${image})` : 'none' }}
                    >
                        {!image && (
                            <div className="social-preview__placeholder">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" opacity="0.3">
                                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                </svg>
                            </div>
                        )}
                    </div>
                    <div className="social-preview__body">
                        <span className="social-preview__title">{title || 'Your Page Title'}</span>
                        <span className="social-preview__desc">{description || 'Your page description will appear here.'}</span>
                        <span className="social-preview__domain">{domain}</span>
                    </div>
                </div>
            ) : (
                <div className="social-preview__card">
                    <div
                        className="social-preview__image"
                        style={{ backgroundImage: image ? `url(${image})` : 'none' }}
                    >
                        {!image && (
                            <div className="social-preview__placeholder">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" opacity="0.3">
                                    <path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/>
                                </svg>
                            </div>
                        )}
                    </div>
                    <div className="social-preview__body">
                        <span className="social-preview__title">{title || 'Your Page Title'}</span>
                        <span className="social-preview__desc">{description || 'Your page description will appear here when shared on social media platforms.'}</span>
                        <span className="social-preview__domain">{domain}</span>
                    </div>
                </div>
            )}
        </div>
    );
};

/**
 * Combined Social Previews Component
 * Shows both Facebook and Twitter previews side by side or stacked.
 *
 * @param {Object} props
 * @param {string} props.image - Image URL
 * @param {string} props.title - Title text (or OG title)
 * @param {string} props.description - Description text (or OG description)
 * @param {string} props.twitterTitle - Optional separate Twitter title
 * @param {string} props.twitterDescription - Optional separate Twitter description
 * @param {string} props.domain - Site domain
 * @param {string} props.twitterCardType - Twitter card type
 * @param {string} props.layout - "sidebar" for vertical stack, "grid" for side by side
 */
export const SocialPreviews = ({
    image,
    title,
    description,
    twitterTitle,
    twitterDescription,
    domain,
    twitterCardType = 'summary_large_image',
    layout = 'sidebar'
}) => {
    const containerStyle = layout === 'grid' ? {
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
        gap: '24px',
    } : {};

    return (
        <div style={containerStyle}>
            <FacebookPreview
                image={image}
                title={title}
                description={description}
                domain={domain}
            />
            <TwitterPreview
                image={image}
                title={twitterTitle || title}
                description={twitterDescription || description}
                domain={domain}
                cardType={twitterCardType}
            />
        </div>
    );
};

export default SocialPreviews;
