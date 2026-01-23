/**
 * Metrics Breakdown Component
 *
 * Displays detailed SEO metrics organized by category.
 * Clean, minimal design with subtle indicators.
 */

// SVG Icons for categories
const CategoryIcons = {
    basic: (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M12 20V10M18 20V4M6 20v-4" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
    ),
    keyword: (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35" strokeLinecap="round"/>
        </svg>
    ),
    structure: (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M4 6h16M4 12h16M4 18h10" strokeLinecap="round"/>
        </svg>
    ),
    links: (
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" strokeLinecap="round"/>
            <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71" strokeLinecap="round"/>
        </svg>
    ),
};

// Status icons
const StatusIcons = {
    pass: (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
            <path d="M20 6L9 17l-5-5" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
    ),
    partial: (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
            <path d="M5 12h14" strokeLinecap="round"/>
        </svg>
    ),
    fail: (
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
            <path d="M18 6L6 18M6 6l12 12" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
    ),
};

const MetricsBreakdown = ({ metrics, metricsByCategory, hasKeyphrase }) => {
    const categories = [
        { key: 'basic', label: 'Basic SEO' },
        { key: 'keyword', label: 'Keywords' },
        { key: 'structure', label: 'Content Structure' },
        { key: 'links', label: 'Links & Media' },
    ];

    // Skip keyword category if no keyphrase set
    const visibleCategories = hasKeyphrase
        ? categories
        : categories.filter((c) => c.key !== 'keyword');

    const getGroupPercentage = (items) => {
        if (!items || items.length === 0) return 0;
        const earned = items.reduce((sum, m) => sum + (m.score || 0), 0);
        const max = items.reduce((sum, m) => sum + (m.max || 0), 0);
        return max > 0 ? Math.round((earned / max) * 100) : 0;
    };

    const getPassCount = (items) => {
        if (!items) return { passed: 0, total: 0 };
        const passed = items.filter(m => m.is_pass).length;
        return { passed, total: items.length };
    };

    return (
        <div className="saman-seo-analysis">
            {visibleCategories.map((category) => {
                const group = metricsByCategory?.[category.key];
                if (!group || group.items.length === 0) return null;

                const percentage = getGroupPercentage(group.items);
                const { passed, total } = getPassCount(group.items);
                const status = percentage >= 80 ? 'good' : percentage >= 50 ? 'fair' : 'poor';

                return (
                    <div key={category.key} className="saman-seo-analysis__category">
                        <div className={`saman-seo-analysis__header saman-seo-analysis__header--${status}`}>
                            <span className="saman-seo-analysis__icon">
                                {CategoryIcons[category.key]}
                            </span>
                            <span className="saman-seo-analysis__title">
                                {group.label || category.label}
                            </span>
                            <span className="saman-seo-analysis__count">
                                {passed}/{total}
                            </span>
                        </div>
                        <div className="saman-seo-analysis__items">
                            {group.items.map((metric) => (
                                <MetricItem key={metric.key} metric={metric} />
                            ))}
                        </div>
                    </div>
                );
            })}

            {!hasKeyphrase && (
                <div className="saman-seo-analysis__tip">
                    <div className="saman-seo-analysis__tip-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 16v-4M12 8h.01" strokeLinecap="round"/>
                        </svg>
                    </div>
                    <div className="saman-seo-analysis__tip-content">
                        <strong>Add a focus keyphrase</strong>
                        <span>Unlock keyword analysis with density and placement checks</span>
                    </div>
                </div>
            )}
        </div>
    );
};

const MetricItem = ({ metric }) => {
    const statusClass = metric.is_pass
        ? 'pass'
        : metric.score > 0
        ? 'partial'
        : 'fail';

    return (
        <div className={`saman-seo-analysis__item saman-seo-analysis__item--${statusClass}`}>
            <span className={`saman-seo-analysis__status saman-seo-analysis__status--${statusClass}`}>
                {StatusIcons[statusClass]}
            </span>
            <div className="saman-seo-analysis__content">
                <span className="saman-seo-analysis__label">{metric.label}</span>
                {metric.status && (
                    <span className="saman-seo-analysis__detail">{metric.status}</span>
                )}
            </div>
        </div>
    );
};

export default MetricsBreakdown;
