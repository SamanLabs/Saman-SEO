/**
 * Score Gauge Component
 *
 * Circular progress indicator for SEO score.
 */

const SCORE_COLORS = {
    excellent: '#00a32a',
    good: '#00a32a',
    fair: '#dba617',
    poor: '#d63638',
};

const ScoreGauge = ({ score = 0, level = 'poor' }) => {
    const color = SCORE_COLORS[level] || SCORE_COLORS.poor;
    const circumference = 2 * Math.PI * 20; // radius = 20
    const progress = (score / 100) * circumference;
    const dashOffset = circumference - progress;

    return (
        <div className="wpseopilot-gauge">
            <svg viewBox="0 0 48 48" className="wpseopilot-gauge__svg">
                {/* Background circle */}
                <circle
                    className="wpseopilot-gauge__bg"
                    cx="24"
                    cy="24"
                    r="20"
                    fill="none"
                    strokeWidth="4"
                />
                {/* Progress circle */}
                <circle
                    className="wpseopilot-gauge__progress"
                    cx="24"
                    cy="24"
                    r="20"
                    fill="none"
                    strokeWidth="4"
                    strokeDasharray={circumference}
                    strokeDashoffset={dashOffset}
                    strokeLinecap="round"
                    style={{ stroke: color }}
                />
            </svg>
            <div className="wpseopilot-gauge__value" style={{ color }}>
                {score}
            </div>
        </div>
    );
};

export default ScoreGauge;
