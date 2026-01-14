import { useState } from '@wordpress/element';

// Most Popular Tools - Practical SEO tools that work NOW
const popularTools = [
    {
        id: 'redirects',
        name: 'Redirects',
        description: 'Create and manage URL redirects. Handle 301, 302, 307 redirects with regex support, import/export, and analytics.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M9 18l6-6-6-6"/>
                <path d="M15 6l-6 6 6 6" opacity="0.5"/>
            </svg>
        ),
        color: '#2271b1',
        stats: 'Import/Export, Regex, Groups',
    },
    {
        id: '404-log',
        name: '404 Monitor',
        description: 'Track broken links in real-time. Get smart redirect suggestions, filter bots, and create redirects with one click.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4m0 4h.01"/>
            </svg>
        ),
        color: '#d63638',
        stats: 'Real-time, Smart Suggestions',
    },
    {
        id: 'instant-indexing',
        name: 'Instant Indexing',
        description: 'Submit URLs to search engines via IndexNow. Bulk submit posts for faster discovery by Bing, Yandex, and more.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
            </svg>
        ),
        color: '#0891b2',
        stats: 'IndexNow, Bulk Submit',
    },
    {
        id: 'audit',
        name: 'SEO Audit',
        description: 'Comprehensive 14-factor SEO analysis. Get actionable recommendations to improve rankings and fix issues.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
                <path d="M9 12l2 2 4-4"/>
            </svg>
        ),
        color: '#00a32a',
        stats: '14-Factor Analysis',
    },
];

// AI-Powered Tools
const aiTools = [
    {
        id: 'bulk-editor',
        name: 'Smart Bulk Editor',
        description: 'Edit SEO titles and descriptions in bulk. Spreadsheet view for efficient editing across all your content.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
        ),
        color: '#6366f1',
    },
    {
        id: 'content-gaps',
        name: 'Content Gaps',
        description: 'Discover missing topics and content opportunities. Find what your competitors cover that you don\'t.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
                <path d="M12 12v4M10 14h4"/>
            </svg>
        ),
        color: '#059669',
    },
    {
        id: 'schema-builder',
        name: 'Schema Builder',
        description: 'Visual schema creation for rich search results. Generate JSON-LD structured data with ease.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M16 18l2-2-2-2M8 18l-2-2 2-2M14 4l-4 16"/>
            </svg>
        ),
        color: '#8b5cf6',
    },
    {
        id: 'ai-assistant',
        name: 'AI Assistant',
        description: 'Chat with AI about SEO. Get suggestions for titles, descriptions, and content optimization.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"/>
                <circle cx="9" cy="13" r="1"/>
                <circle cx="15" cy="13" r="1"/>
                <path d="M9 17h6"/>
            </svg>
        ),
        color: '#ec4899',
    },
];

// More Working Tools
const moreTools = [
    {
        id: 'internal-linking',
        name: 'Internal Linking',
        description: 'Discover and manage internal link opportunities. Build a stronger site structure.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
            </svg>
        ),
        color: '#0891b2',
    },
    {
        id: 'link-health',
        name: 'Link Health',
        description: 'Scan for broken links and orphan pages. Find and fix link issues.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M15 7h3a5 5 0 010 10h-3m-6 0H6a5 5 0 010-10h3"/>
                <line x1="8" y1="12" x2="16" y2="12"/>
                <path d="M9 12l2 2 4-4"/>
            </svg>
        ),
        color: '#059669',
    },
    {
        id: 'image-seo',
        name: 'Image SEO',
        description: 'Bulk edit alt text for all images. Find and fix missing alt texts.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <path d="M21 15l-5-5L5 21"/>
            </svg>
        ),
        color: '#f59e0b',
    },
    {
        id: 'robots-txt',
        name: 'robots.txt Editor',
        description: 'Edit your robots.txt with validation and presets. Test if URLs are blocked.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M9 9h6M9 13h6M9 17h4"/>
            </svg>
        ),
        color: '#64748b',
    },
    {
        id: 'local-seo',
        name: 'Local SEO',
        description: 'Manage business locations with schema. Multi-location support.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
        ),
        color: '#dc2626',
    },
    {
        id: 'sitemap',
        name: 'Sitemap Settings',
        description: 'Configure XML sitemaps including news and video sitemaps.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        ),
        color: '#0d9488',
        navigateTo: 'sitemap',
    },
    {
        id: 'schema-validator',
        name: 'Schema Validator',
        description: 'Test structured data on any page. Validate JSON-LD markup.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M4 6h16M4 12h16M4 18h10"/>
                <circle cx="19" cy="18" r="3"/>
                <path d="M19 16v2h2"/>
            </svg>
        ),
        color: '#ea580c',
    },
    {
        id: 'htaccess-editor',
        name: '.htaccess Editor',
        description: 'Safely edit your .htaccess file with backups and presets.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
            </svg>
        ),
        color: '#be185d',
    },
    {
        id: 'mobile-friendly',
        name: 'Mobile Friendly',
        description: 'Check if pages are mobile-friendly. Identify viewport and touch issues.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="5" y="2" width="14" height="20" rx="2"/>
                <path d="M12 18h.01"/>
            </svg>
        ),
        color: '#2563eb',
    },
];

// Coming Soon Tools
const comingSoonTools = [
    {
        id: 'search-console',
        name: 'Search Console',
        description: 'Connect Google Search Console. View clicks, impressions, CTR, and index status.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
                <path d="M11 8v6M8 11h6"/>
            </svg>
        ),
        color: '#4285f4',
    },
    {
        id: 'import-export',
        name: 'Import / Export',
        description: 'Migrate from Yoast, Rank Math, or AIOSEO. Export all settings.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
        ),
        color: '#7c3aed',
    },
    {
        id: 'keyword-tracker',
        name: 'Keyword Tracker',
        description: 'Monitor keyword rankings over time. Track your positions in search results.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M3 3v18h18"/>
                <path d="M18 9l-5 5-4-4-3 3"/>
            </svg>
        ),
        color: '#059669',
    },
    {
        id: 'page-speed',
        name: 'Page Speed',
        description: 'Test page load performance. Get Core Web Vitals scores and tips.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </svg>
        ),
        color: '#16a34a',
    },
    {
        id: 'heading-analyzer',
        name: 'Heading Analyzer',
        description: 'Analyze heading structure. Check H1-H6 hierarchy and find issues.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M4 12h8M4 18V6M12 18V6M20 7v10M16 7h8M16 12h6"/>
            </svg>
        ),
        color: '#0284c7',
    },
];

const Tools = ({ onNavigate }) => {
    const [hoveredTool, setHoveredTool] = useState(null);

    const handleToolClick = (tool) => {
        if (tool.comingSoon) return;

        const viewId = tool.navigateTo || tool.id;
        if (onNavigate) {
            onNavigate(viewId);
        }
    };

    const ToolCard = ({ tool, size = 'large', showBadge = null }) => (
        <button
            type="button"
            className={`tool-card ${size === 'compact' ? 'tool-card--compact' : ''} ${tool.comingSoon ? 'tool-card--disabled' : ''} ${hoveredTool === tool.id ? 'tool-card--hover' : ''}`}
            onClick={() => handleToolClick(tool)}
            onMouseEnter={() => setHoveredTool(tool.id)}
            onMouseLeave={() => setHoveredTool(null)}
            disabled={tool.comingSoon}
        >
            <div className={`tool-card__icon ${size === 'compact' ? 'tool-card__icon--small' : ''}`} style={{ backgroundColor: `${tool.color}15`, color: tool.color }}>
                {tool.icon}
            </div>
            <div className="tool-card__content">
                <div className="tool-card__header">
                    <h3 className="tool-card__name">{tool.name}</h3>
                    {showBadge && (
                        <span className={`tool-card__badge ${showBadge === 'AI' ? 'tool-card__badge--ai' : ''}`}>
                            {showBadge}
                        </span>
                    )}
                    {tool.comingSoon && (
                        <span className="tool-card__badge">Soon</span>
                    )}
                </div>
                <p className="tool-card__desc">{tool.description}</p>
                {tool.stats && size !== 'compact' && (
                    <div className="tool-card__stats">{tool.stats}</div>
                )}
            </div>
            {size !== 'compact' && !tool.comingSoon && (
                <div className="tool-card__arrow" style={{ color: tool.color }}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </div>
            )}
        </button>
    );

    return (
        <div className="page">
            {/* Hero Header */}
            <div className="page-header page-header--hero">
                <div className="page-header__content">
                    <h1>SEO Tools Hub</h1>
                    <p>All your SEO tools in one place. From redirects and 404 monitoring to AI-powered content optimization.</p>
                </div>
            </div>

            {/* Popular Tools - Practical SEO */}
            <section className="tools-section">
                <div className="tools-section__header">
                    <h2 className="tools-section__title">Most Popular</h2>
                    <p className="tools-section__subtitle">Essential tools for everyday SEO management</p>
                </div>
                <div className="tools-grid tools-grid--large">
                    {popularTools.map((tool) => (
                        <ToolCard key={tool.id} tool={tool} size="large" />
                    ))}
                </div>
            </section>

            {/* AI Tools */}
            <section className="tools-section">
                <div className="tools-section__header">
                    <h2 className="tools-section__title">AI-Powered</h2>
                    <p className="tools-section__subtitle">Smart tools to speed up your workflow</p>
                </div>
                <div className="tools-grid tools-grid--large">
                    {aiTools.map((tool) => (
                        <ToolCard key={tool.id} tool={tool} size="large" showBadge="AI" />
                    ))}
                </div>
            </section>

            {/* More Tools */}
            <section className="tools-section">
                <div className="tools-section__header">
                    <h2 className="tools-section__title">More Tools</h2>
                    <p className="tools-section__subtitle">Additional utilities for complete SEO coverage</p>
                </div>
                <div className="tools-grid tools-grid--medium">
                    {moreTools.map((tool) => (
                        <ToolCard key={tool.id} tool={tool} size="medium" />
                    ))}
                </div>
            </section>

            {/* Coming Soon */}
            <section className="tools-section tools-section--muted">
                <div className="tools-section__header">
                    <h2 className="tools-section__title">Coming Soon</h2>
                    <p className="tools-section__subtitle">New tools in development</p>
                </div>
                <div className="tools-grid tools-grid--small">
                    {comingSoonTools.map((tool) => (
                        <ToolCard key={tool.id} tool={{ ...tool, comingSoon: true }} size="compact" />
                    ))}
                </div>
            </section>
        </div>
    );
};

export default Tools;
