/**
 * SEO Score Badge Component
 *
 * Displays a compact SEO score with visual indicator for admin post lists.
 */

const SEOScoreBadge = ({ score, level, label, issues, flags }) => {
    const getScoreColor = () => {
        if (score >= 70) return '#22c55e'; // green
        if (score >= 40) return '#eab308'; // yellow
        return '#ef4444'; // red
    };

    const getLevelClass = () => {
        if (level === 'good') return 'wpseopilot-badge--good';
        if (level === 'fair') return 'wpseopilot-badge--fair';
        return 'wpseopilot-badge--poor';
    };

    // Calculate circle progress
    const radius = 18;
    const circumference = 2 * Math.PI * radius;
    const progress = (score / 100) * circumference;
    const dashOffset = circumference - progress;

    return (
        <div className="wpseopilot-list-badge">
            <div className={`wpseopilot-badge-ring ${getLevelClass()}`}>
                <svg width="44" height="44" viewBox="0 0 44 44">
                    {/* Background circle */}
                    <circle
                        cx="22"
                        cy="22"
                        r={radius}
                        fill="none"
                        stroke="#e5e7eb"
                        strokeWidth="4"
                    />
                    {/* Progress circle */}
                    <circle
                        cx="22"
                        cy="22"
                        r={radius}
                        fill="none"
                        stroke={getScoreColor()}
                        strokeWidth="4"
                        strokeLinecap="round"
                        strokeDasharray={circumference}
                        strokeDashoffset={dashOffset}
                        transform="rotate(-90 22 22)"
                        style={{ transition: 'stroke-dashoffset 0.3s ease' }}
                    />
                </svg>
                <span className="wpseopilot-badge-score">{score}</span>
            </div>
            <div className="wpseopilot-badge-info">
                <span className={`wpseopilot-badge-label ${getLevelClass()}`}>
                    {label}
                </span>
                {issues && issues.length > 0 && (
                    <span className="wpseopilot-badge-issues">
                        {issues.slice(0, 2).join(' • ')}
                        {issues.length > 2 && ` +${issues.length - 2}`}
                    </span>
                )}
                {flags && flags.length > 0 && (
                    <span className="wpseopilot-badge-flags">
                        {flags.join(', ')}
                    </span>
                )}
            </div>
        </div>
    );
};

export default SEOScoreBadge;
