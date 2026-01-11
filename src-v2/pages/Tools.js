import { useState } from '@wordpress/element';

// Tools data sorted by popularity
const tools = [
    {
        id: 'redirects',
        name: 'Redirects',
        description: 'Create and manage URL redirects. Handle 301, 302, and 307 redirects to maintain SEO value when URLs change.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M9 18l6-6-6-6"/>
                <path d="M15 6l-6 6 6 6" opacity="0.5"/>
            </svg>
        ),
        color: '#2271b1',
        stats: { label: 'Active', value: '12' },
        popular: true,
    },
    {
        id: '404-log',
        name: '404 Log',
        description: 'Track and fix broken links. Monitor 404 errors in real-time and quickly create redirects to fix them.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4m0 4h.01"/>
            </svg>
        ),
        color: '#d63638',
        stats: { label: 'Errors', value: '3' },
        popular: true,
    },
    {
        id: 'audit',
        name: 'SEO Audit',
        description: 'Analyze your site for SEO issues. Get actionable recommendations to improve rankings and fix problems.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
                <path d="M9 12l2 2 4-4"/>
            </svg>
        ),
        color: '#00a32a',
        stats: { label: 'Score', value: '87' },
        popular: true,
    },
    {
        id: 'ai-assistant',
        name: 'AI Assistant',
        description: 'Generate SEO-optimized content with AI. Create titles, descriptions, and content suggestions automatically.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"/>
                <circle cx="9" cy="13" r="1"/>
                <circle cx="15" cy="13" r="1"/>
                <path d="M9 17h6"/>
            </svg>
        ),
        color: '#8b5cf6',
        stats: { label: 'Credits', value: '250' },
        popular: true,
    },
    {
        id: 'internal-linking',
        name: 'Internal Linking',
        description: 'Discover and manage internal link opportunities. Build a stronger site structure for better SEO.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
            </svg>
        ),
        color: '#0891b2',
        stats: { label: 'Links', value: '156' },
        popular: false,
    },
    {
        id: 'schema-generator',
        name: 'Schema Generator',
        description: 'Generate structured data markup for rich snippets. Support for articles, products, FAQs, and more.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M4 6h16M4 12h16M4 18h10"/>
                <path d="M18 18l2-2-2-2"/>
            </svg>
        ),
        color: '#ea580c',
        stats: { label: 'Types', value: '8' },
        popular: false,
    },
    {
        id: 'keyword-tracker',
        name: 'Keyword Tracker',
        description: 'Monitor keyword rankings over time. Track your positions in search results and see trends.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M3 3v18h18"/>
                <path d="M18 9l-5 5-4-4-3 3"/>
            </svg>
        ),
        color: '#059669',
        stats: { label: 'Keywords', value: '24' },
        popular: false,
        comingSoon: true,
    },
    {
        id: 'bulk-editor',
        name: 'Bulk Editor',
        description: 'Edit SEO titles and descriptions in bulk. Make changes to multiple posts at once efficiently.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
        ),
        color: '#6366f1',
        stats: { label: 'Posts', value: '45' },
        popular: false,
        comingSoon: true,
    },
];

const Tools = ({ onNavigate }) => {
    const [hoveredTool, setHoveredTool] = useState(null);

    const handleToolClick = (tool) => {
        if (tool.comingSoon) return;

        // Map tool IDs to view IDs
        const viewMap = {
            'redirects': 'redirects',
            '404-log': '404-log',
            'audit': 'audit',
            'ai-assistant': 'ai-assistant',
            'internal-linking': 'internal-linking',
        };

        const viewId = viewMap[tool.id];
        if (viewId && onNavigate) {
            onNavigate(viewId);
        }
    };

    // Separate popular and other tools
    const popularTools = tools.filter(t => t.popular);
    const otherTools = tools.filter(t => !t.popular);

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Tools</h1>
                    <p>SEO utilities and helpers to optimize your website.</p>
                </div>
            </div>

            {/* Popular Tools */}
            <section className="tools-section">
                <h2 className="tools-section__title">Popular Tools</h2>
                <div className="tools-grid tools-grid--large">
                    {popularTools.map((tool) => (
                        <button
                            key={tool.id}
                            type="button"
                            className={`tool-card ${tool.comingSoon ? 'tool-card--disabled' : ''} ${hoveredTool === tool.id ? 'tool-card--hover' : ''}`}
                            onClick={() => handleToolClick(tool)}
                            onMouseEnter={() => setHoveredTool(tool.id)}
                            onMouseLeave={() => setHoveredTool(null)}
                            disabled={tool.comingSoon}
                        >
                            <div className="tool-card__icon" style={{ backgroundColor: `${tool.color}15`, color: tool.color }}>
                                {tool.icon}
                            </div>
                            <div className="tool-card__content">
                                <div className="tool-card__header">
                                    <h3 className="tool-card__name">{tool.name}</h3>
                                    {tool.comingSoon && (
                                        <span className="tool-card__badge">Coming Soon</span>
                                    )}
                                </div>
                                <p className="tool-card__desc">{tool.description}</p>
                            </div>
                            {tool.stats && !tool.comingSoon && (
                                <div className="tool-card__stats" style={{ color: tool.color }}>
                                    <span className="tool-card__stat-value">{tool.stats.value}</span>
                                    <span className="tool-card__stat-label">{tool.stats.label}</span>
                                </div>
                            )}
                            <div className="tool-card__arrow" style={{ color: tool.color }}>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M9 18l6-6-6-6"/>
                                </svg>
                            </div>
                        </button>
                    ))}
                </div>
            </section>

            {/* More Tools */}
            <section className="tools-section">
                <h2 className="tools-section__title">More Tools</h2>
                <div className="tools-grid tools-grid--small">
                    {otherTools.map((tool) => (
                        <button
                            key={tool.id}
                            type="button"
                            className={`tool-card tool-card--compact ${tool.comingSoon ? 'tool-card--disabled' : ''}`}
                            onClick={() => handleToolClick(tool)}
                            disabled={tool.comingSoon}
                        >
                            <div className="tool-card__icon tool-card__icon--small" style={{ backgroundColor: `${tool.color}15`, color: tool.color }}>
                                {tool.icon}
                            </div>
                            <div className="tool-card__content">
                                <div className="tool-card__header">
                                    <h3 className="tool-card__name">{tool.name}</h3>
                                    {tool.comingSoon && (
                                        <span className="tool-card__badge">Soon</span>
                                    )}
                                </div>
                                <p className="tool-card__desc">{tool.description}</p>
                            </div>
                        </button>
                    ))}
                </div>
            </section>
        </div>
    );
};

export default Tools;
