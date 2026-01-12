/**
 * Metrics Breakdown Component
 *
 * Displays detailed SEO metrics organized by category.
 * Shows score/max for each metric with progress bars and pass/fail indicators.
 */

const MetricsBreakdown = ({ metrics, metricsByCategory, hasKeyphrase }) => {
    const categories = [
        { key: 'basic', icon: '📊', color: '#2271b1', label: 'Basic SEO' },
        { key: 'keyword', icon: '🔑', color: '#7c3aed', label: 'Keywords' },
        { key: 'structure', icon: '📝', color: '#059669', label: 'Structure' },
        { key: 'links', icon: '🔗', color: '#ea580c', label: 'Links & Media' },
    ];

    // Skip keyword category if no keyphrase set
    const visibleCategories = hasKeyphrase
        ? categories
        : categories.filter((c) => c.key !== 'keyword');

    const calculateGroupScore = (items) => {
        if (!items || items.length === 0) return '0/0';
        const earned = items.reduce((sum, m) => sum + (m.score || 0), 0);
        const max = items.reduce((sum, m) => sum + (m.max || 0), 0);
        return `${earned}/${max}`;
    };

    const getGroupPercentage = (items) => {
        if (!items || items.length === 0) return 0;
        const earned = items.reduce((sum, m) => sum + (m.score || 0), 0);
        const max = items.reduce((sum, m) => sum + (m.max || 0), 0);
        return max > 0 ? Math.round((earned / max) * 100) : 0;
    };

    return (
        <div className="wpseopilot-metrics-breakdown">
            {visibleCategories.map((category) => {
                const group = metricsByCategory?.[category.key];
                if (!group || group.items.length === 0) return null;

                const percentage = getGroupPercentage(group.items);
                const groupStatus =
                    percentage >= 80 ? 'good' : percentage >= 50 ? 'fair' : 'poor';

                return (
                    <div key={category.key} className="wpseopilot-metric-group">
                        <div
                            className="wpseopilot-metric-group__header"
                            style={{ borderLeftColor: category.color }}
                        >
                            <span className="wpseopilot-metric-group__icon">
                                {category.icon}
                            </span>
                            <span className="wpseopilot-metric-group__label">
                                {group.label || category.label}
                            </span>
                            <span
                                className={`wpseopilot-metric-group__score wpseopilot-metric-group__score--${groupStatus}`}
                            >
                                {calculateGroupScore(group.items)}
                            </span>
                        </div>
                        <ul className="wpseopilot-metric-list">
                            {group.items.map((metric) => (
                                <MetricItem key={metric.key} metric={metric} />
                            ))}
                        </ul>
                    </div>
                );
            })}

            {!hasKeyphrase && (
                <div className="wpseopilot-keyphrase-notice">
                    <span className="wpseopilot-keyphrase-notice__icon">💡</span>
                    <div className="wpseopilot-keyphrase-notice__content">
                        <strong>Add a focus keyphrase</strong>
                        <p>
                            Set a target keyword to unlock 5 additional optimization
                            checks including keyword density and placement analysis.
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
};

const MetricItem = ({ metric }) => {
    const scorePercent =
        metric.max > 0 ? (metric.score / metric.max) * 100 : 0;
    const statusClass = metric.is_pass
        ? 'pass'
        : metric.score > 0
        ? 'partial'
        : 'fail';

    return (
        <li
            className={`wpseopilot-metric-item wpseopilot-metric-item--${statusClass}`}
        >
            <div className="wpseopilot-metric-item__header">
                <span
                    className={`wpseopilot-metric-item__indicator wpseopilot-metric-item__indicator--${statusClass}`}
                />
                <span className="wpseopilot-metric-item__label">
                    {metric.label}
                </span>
                <span className="wpseopilot-metric-item__score">
                    {metric.score}/{metric.max}
                </span>
            </div>
            <div className="wpseopilot-metric-item__status">{metric.status}</div>
            <div className="wpseopilot-metric-item__bar">
                <div
                    className={`wpseopilot-metric-item__fill wpseopilot-metric-item__fill--${statusClass}`}
                    style={{ width: `${scorePercent}%` }}
                />
            </div>
        </li>
    );
};

export default MetricsBreakdown;
