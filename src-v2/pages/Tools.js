import { useState } from '@wordpress/element';

// AI Assistants
const aiAssistants = [
    {
        id: 'general-seo',
        name: 'SEO Assistant',
        description: 'Your helpful SEO buddy for all things search optimization. Ask about meta tags, keywords, content, and more.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
                <path d="M11 8v6M8 11h6"/>
            </svg>
        ),
        color: '#2271b1',
    },
    {
        id: 'seo-reporter',
        name: 'SEO Reporter',
        description: 'Get quick reports on your site\'s SEO health. Find issues, track improvements, and get actionable tips.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                <rect x="9" y="3" width="6" height="4" rx="1"/>
                <path d="M9 12h6M9 16h6"/>
            </svg>
        ),
        color: '#00a32a',
    },
];

// AI-Powered Tools
const aiTools = [
    {
        id: 'bulk-editor',
        name: 'Smart Bulk Editor',
        description: 'Edit SEO titles and descriptions in bulk with AI-powered suggestions. Spreadsheet view for efficient editing.',
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
        name: 'Content Gaps Finder',
        description: 'AI finds what you should write about. Discover missing topics and get content outlines automatically.',
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
        description: 'Visual drag-and-drop schema creation. Generate structured data for rich search results with AI assistance.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M16 18l2-2-2-2M8 18l-2-2 2-2M14 4l-4 16"/>
            </svg>
        ),
        color: '#8b5cf6',
    },
];

// Tools data sorted by popularity
const tools = [
    // Popular Tools (working)
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
        popular: true,
    },
    // More Tools (working)
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
        popular: false,
    },
    {
        id: 'robots-txt',
        name: 'Robots.txt Editor',
        description: 'View and edit your robots.txt file. Control how search engines crawl your site.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <path d="M9 9h6M9 13h6M9 17h4"/>
            </svg>
        ),
        color: '#64748b',
        popular: false,
        comingSoon: true,
    },
    {
        id: 'htaccess-editor',
        name: '.htaccess Editor',
        description: 'Safely edit your .htaccess file. Manage redirects, security rules, and server configuration.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>
            </svg>
        ),
        color: '#be185d',
        popular: false,
        comingSoon: true,
    },
    {
        id: 'sitemap-validator',
        name: 'Sitemap Validator',
        description: 'Test and validate your XML sitemap. Ensure all URLs are accessible and properly formatted.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
        ),
        color: '#0d9488',
        popular: false,
        comingSoon: true,
    },
    {
        id: 'meta-validator',
        name: 'Meta Tag Tester',
        description: 'Test any URL for SEO meta tags. Validate titles, descriptions, OG tags, and Twitter cards.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/>
                <rect x="8" y="2" width="8" height="4" rx="1"/>
                <path d="M9 12h6M9 16h6"/>
            </svg>
        ),
        color: '#7c3aed',
        popular: false,
        comingSoon: true,
    },
    {
        id: 'schema-validator',
        name: 'Schema Validator',
        description: 'Test structured data on any page. Validate JSON-LD, Microdata, and RDFa markup.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M4 6h16M4 12h16M4 18h10"/>
                <circle cx="19" cy="18" r="3"/>
                <path d="M19 16v2h2"/>
            </svg>
        ),
        color: '#ea580c',
        popular: false,
        comingSoon: true,
    },
    {
        id: 'heading-analyzer',
        name: 'Heading Analyzer',
        description: 'Analyze heading structure of any page. Check H1-H6 hierarchy and find issues.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M4 12h8M4 18V6M12 18V6M20 7v10M16 7h8M16 12h6"/>
            </svg>
        ),
        color: '#0284c7',
        popular: false,
        comingSoon: true,
    },
    {
        id: 'link-checker',
        name: 'Broken Link Checker',
        description: 'Scan pages for broken links. Find and fix dead links before they hurt your SEO.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M15 7h3a5 5 0 010 10h-3m-6 0H6a5 5 0 010-10h3"/>
                <line x1="8" y1="12" x2="16" y2="12"/>
                <path d="M2 2l20 20" strokeLinecap="round"/>
            </svg>
        ),
        color: '#dc2626',
        popular: false,
        comingSoon: true,
    },
    {
        id: 'page-speed',
        name: 'Page Speed Test',
        description: 'Test page load performance. Get Core Web Vitals scores and optimization tips.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 6v6l4 2"/>
            </svg>
        ),
        color: '#16a34a',
        popular: false,
        comingSoon: true,
    },
    {
        id: 'mobile-test',
        name: 'Mobile Friendly Test',
        description: 'Check if pages are mobile-friendly. Identify viewport and touch issues.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="5" y="2" width="14" height="20" rx="2"/>
                <path d="M12 18h.01"/>
            </svg>
        ),
        color: '#2563eb',
        popular: false,
        comingSoon: true,
    },
    {
        id: 'image-optimizer',
        name: 'Image SEO Checker',
        description: 'Analyze images for SEO. Check alt texts, file sizes, and compression opportunities.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <rect x="3" y="3" width="18" height="18" rx="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <path d="M21 15l-5-5L5 21"/>
            </svg>
        ),
        color: '#f59e0b',
        popular: false,
        comingSoon: true,
    },
    // Coming Soon
    {
        id: 'schema-generator',
        name: 'Schema Generator',
        description: 'Generate structured data markup for rich snippets. Support for articles, products, FAQs, and more.',
        icon: (
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M16 18l2-2-2-2M8 18l-2-2 2-2M14 4l-4 16"/>
            </svg>
        ),
        color: '#8b5cf6',
        popular: false,
        comingSoon: true,
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
        popular: false,
        comingSoon: true,
    },
];

const Tools = ({ onNavigate }) => {
    const [hoveredTool, setHoveredTool] = useState(null);

    const handleToolClick = (tool) => {
        if (tool.comingSoon) return;

        // Map tool IDs to view IDs (most map directly)
        const viewMap = {
            'redirects': 'redirects',
            '404-log': '404-log',
            'audit': 'audit',
            'ai-assistant': 'ai-assistant',
            'internal-linking': 'internal-linking',
            'robots-txt': 'robots-txt',
            'htaccess-editor': 'htaccess-editor',
            'sitemap-validator': 'sitemap-validator',
            'meta-validator': 'meta-validator',
            'schema-validator': 'schema-validator',
            'heading-analyzer': 'heading-analyzer',
            'link-checker': 'link-checker',
            'page-speed': 'page-speed',
            'mobile-test': 'mobile-test',
            'image-optimizer': 'image-optimizer',
            'bulk-editor': 'bulk-editor',
            'content-gaps': 'content-gaps',
            'schema-builder': 'schema-builder',
        };

        const viewId = viewMap[tool.id];
        if (viewId && onNavigate) {
            onNavigate(viewId);
        }
    };

    const handleAssistantClick = (assistant) => {
        if (onNavigate) {
            onNavigate('assistants');
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

            {/* AI-Powered Tools */}
            <section className="tools-section">
                <h2 className="tools-section__title">AI-Powered Tools</h2>
                <div className="tools-grid tools-grid--large">
                    {aiTools.map((tool) => (
                        <button
                            key={tool.id}
                            type="button"
                            className={`tool-card ${hoveredTool === tool.id ? 'tool-card--hover' : ''}`}
                            onClick={() => handleToolClick(tool)}
                            onMouseEnter={() => setHoveredTool(tool.id)}
                            onMouseLeave={() => setHoveredTool(null)}
                        >
                            <div className="tool-card__icon" style={{ backgroundColor: `${tool.color}15`, color: tool.color }}>
                                {tool.icon}
                            </div>
                            <div className="tool-card__content">
                                <div className="tool-card__header">
                                    <h3 className="tool-card__name">{tool.name}</h3>
                                    <span className="tool-card__badge tool-card__badge--ai">AI</span>
                                </div>
                                <p className="tool-card__desc">{tool.description}</p>
                            </div>
                            <div className="tool-card__arrow" style={{ color: tool.color }}>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M9 18l6-6-6-6"/>
                                </svg>
                            </div>
                        </button>
                    ))}
                </div>
            </section>

            {/* AI Assistants */}
            <section className="tools-section">
                <h2 className="tools-section__title">AI Assistants</h2>
                <div className="tools-grid tools-grid--large">
                    {aiAssistants.map((assistant) => (
                        <button
                            key={assistant.id}
                            type="button"
                            className={`tool-card ${hoveredTool === assistant.id ? 'tool-card--hover' : ''}`}
                            onClick={() => handleAssistantClick(assistant)}
                            onMouseEnter={() => setHoveredTool(assistant.id)}
                            onMouseLeave={() => setHoveredTool(null)}
                        >
                            <div className="tool-card__icon" style={{ backgroundColor: `${assistant.color}15`, color: assistant.color }}>
                                {assistant.icon}
                            </div>
                            <div className="tool-card__content">
                                <div className="tool-card__header">
                                    <h3 className="tool-card__name">{assistant.name}</h3>
                                    <span className="tool-card__badge tool-card__badge--ai">AI</span>
                                </div>
                                <p className="tool-card__desc">{assistant.description}</p>
                            </div>
                            <div className="tool-card__arrow" style={{ color: assistant.color }}>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M9 18l6-6-6-6"/>
                                </svg>
                            </div>
                        </button>
                    ))}
                </div>
            </section>

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
