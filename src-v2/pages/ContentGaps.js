import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const ContentGaps = ({ onNavigate }) => {
    const [analyzing, setAnalyzing] = useState(false);
    const [results, setResults] = useState(null);
    const [generatingOutline, setGeneratingOutline] = useState(null);
    const [outline, setOutline] = useState(null);
    const [topic, setTopic] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [categories, setCategories] = useState([]);

    useEffect(() => {
        fetchCategories();
    }, []);

    const fetchCategories = async () => {
        try {
            const response = await apiFetch({
                path: '/wp/v2/categories?per_page=100',
            });
            setCategories(response);
        } catch (error) {
            console.error('Failed to fetch categories:', error);
        }
    };

    const handleAnalyze = async () => {
        setAnalyzing(true);
        setResults(null);
        setOutline(null);

        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/tools/content-gaps/analyze',
                method: 'POST',
                data: {
                    topic: topic || null,
                    category_id: selectedCategory || null,
                },
            });

            if (response.success) {
                setResults(response.data);
            }
        } catch (error) {
            console.error('Failed to analyze:', error);
        } finally {
            setAnalyzing(false);
        }
    };

    const handleGenerateOutline = async (gapTopic) => {
        setGeneratingOutline(gapTopic);

        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/tools/content-gaps/outline',
                method: 'POST',
                data: { topic: gapTopic },
            });

            if (response.success) {
                setOutline({
                    topic: gapTopic,
                    ...response.data,
                });
            }
        } catch (error) {
            console.error('Failed to generate outline:', error);
        } finally {
            setGeneratingOutline(null);
        }
    };

    const getPriorityColor = (priority) => {
        switch (priority) {
            case 'high':
                return '#d63638';
            case 'medium':
                return '#dba617';
            case 'low':
                return '#00a32a';
            default:
                return '#757575';
        }
    };

    return (
        <div className="page content-gaps-page">
            <div className="page-header">
                <div>
                    <div className="page-header__breadcrumb">
                        <button
                            type="button"
                            className="breadcrumb-link"
                            onClick={() => onNavigate('tools')}
                        >
                            Tools
                        </button>
                        <span className="breadcrumb-separator">/</span>
                        <span>Content Gaps Finder</span>
                    </div>
                    <h1>Content Gaps Finder</h1>
                    <p>Discover missing topics and content opportunities based on your existing content.</p>
                </div>
            </div>

            <div className="content-gaps-input">
                <div className="input-row">
                    <div className="input-group">
                        <label>Focus Topic (optional)</label>
                        <input
                            type="text"
                            value={topic}
                            onChange={(e) => setTopic(e.target.value)}
                            placeholder="e.g., coffee brewing, SEO, cooking..."
                        />
                    </div>

                    <div className="input-group">
                        <label>Category Filter</label>
                        <select
                            value={selectedCategory}
                            onChange={(e) => setSelectedCategory(e.target.value)}
                        >
                            <option value="">All Categories</option>
                            {categories.map(cat => (
                                <option key={cat.id} value={cat.id}>
                                    {cat.name} ({cat.count})
                                </option>
                            ))}
                        </select>
                    </div>

                    <button
                        type="button"
                        className="button button--primary analyze-button"
                        onClick={handleAnalyze}
                        disabled={analyzing}
                    >
                        {analyzing ? (
                            <>
                                <span className="spinner"></span>
                                Analyzing...
                            </>
                        ) : (
                            <>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
                                    <circle cx="11" cy="11" r="8"/>
                                    <path d="M21 21l-4.35-4.35"/>
                                    <path d="M11 8v6M8 11h6"/>
                                </svg>
                                Find Content Gaps
                            </>
                        )}
                    </button>
                </div>
            </div>

            {analyzing && (
                <div className="content-gaps-loading">
                    <div className="loading-animation">
                        <span className="spinner-large"></span>
                        <p>Analyzing your content...</p>
                        <span className="loading-hint">This may take a moment as we review your posts.</span>
                    </div>
                </div>
            )}

            {results && !analyzing && (
                <div className="content-gaps-results">
                    <div className="results-summary">
                        <div className="summary-stat">
                            <span className="stat-value">{results.posts_analyzed}</span>
                            <span className="stat-label">Posts Analyzed</span>
                        </div>
                        <div className="summary-stat">
                            <span className="stat-value">{results.gaps?.length || 0}</span>
                            <span className="stat-label">Gaps Found</span>
                        </div>
                        <div className="summary-stat">
                            <span className="stat-value">{results.clusters?.length || 0}</span>
                            <span className="stat-label">Content Clusters</span>
                        </div>
                    </div>

                    {results.existing_topics?.length > 0 && (
                        <div className="results-section">
                            <h3>Your Current Topics</h3>
                            <div className="topic-tags">
                                {results.existing_topics.map((topic, idx) => (
                                    <span key={idx} className="topic-tag">{topic}</span>
                                ))}
                            </div>
                        </div>
                    )}

                    {results.gaps?.length > 0 && (
                        <div className="results-section">
                            <h3>Content Gaps</h3>
                            <p className="section-desc">Topics you should consider writing about:</p>
                            <div className="gaps-grid">
                                {results.gaps.map((gap, idx) => (
                                    <div key={idx} className="gap-card">
                                        <div className="gap-header">
                                            <h4 className="gap-title">{gap.topic}</h4>
                                            <span
                                                className="gap-priority"
                                                style={{ backgroundColor: getPriorityColor(gap.priority) }}
                                            >
                                                {gap.priority}
                                            </span>
                                        </div>
                                        <p className="gap-reason">{gap.reason}</p>
                                        {gap.search_volume && (
                                            <div className="gap-meta">
                                                <span className="gap-volume">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="14" height="14">
                                                        <path d="M3 3v18h18"/>
                                                        <path d="M18 9l-5 5-4-4-3 3"/>
                                                    </svg>
                                                    ~{gap.search_volume}/mo
                                                </span>
                                            </div>
                                        )}
                                        <button
                                            type="button"
                                            className="button button--small"
                                            onClick={() => handleGenerateOutline(gap.topic)}
                                            disabled={generatingOutline === gap.topic}
                                        >
                                            {generatingOutline === gap.topic ? (
                                                <>
                                                    <span className="spinner-small"></span>
                                                    Generating...
                                                </>
                                            ) : (
                                                <>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="14" height="14">
                                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                                        <polyline points="14 2 14 8 20 8"/>
                                                        <line x1="16" y1="13" x2="8" y2="13"/>
                                                        <line x1="16" y1="17" x2="8" y2="17"/>
                                                    </svg>
                                                    Generate Outline
                                                </>
                                            )}
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {results.clusters?.length > 0 && (
                        <div className="results-section">
                            <h3>Content Clusters</h3>
                            <p className="section-desc">Group related content for better internal linking:</p>
                            <div className="clusters-list">
                                {results.clusters.map((cluster, idx) => (
                                    <div key={idx} className="cluster-card">
                                        <div className="cluster-header">
                                            <h4>{cluster.name}</h4>
                                            <span className="cluster-count">{cluster.posts?.length || 0} posts</span>
                                        </div>
                                        {cluster.missing_subtopics?.length > 0 && (
                                            <div className="cluster-missing">
                                                <span className="missing-label">Missing subtopics:</span>
                                                <div className="missing-tags">
                                                    {cluster.missing_subtopics.map((sub, subIdx) => (
                                                        <span key={subIdx} className="missing-tag">{sub}</span>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}

            {outline && (
                <div className="outline-modal-backdrop" onClick={() => setOutline(null)}>
                    <div className="outline-modal" onClick={(e) => e.stopPropagation()}>
                        <div className="outline-header">
                            <h3>Content Outline: {outline.topic}</h3>
                            <button
                                type="button"
                                className="outline-close"
                                onClick={() => setOutline(null)}
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="20" height="20">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                            </button>
                        </div>
                        <div className="outline-content">
                            {outline.suggested_title && (
                                <div className="outline-section">
                                    <h4>Suggested Title</h4>
                                    <p className="suggested-title">{outline.suggested_title}</p>
                                </div>
                            )}
                            {outline.meta_description && (
                                <div className="outline-section">
                                    <h4>Meta Description</h4>
                                    <p>{outline.meta_description}</p>
                                </div>
                            )}
                            {outline.outline?.length > 0 && (
                                <div className="outline-section">
                                    <h4>Content Structure</h4>
                                    <ul className="outline-list">
                                        {outline.outline.map((item, idx) => (
                                            <li key={idx} className={`outline-item outline-item--${item.level || 'h2'}`}>
                                                <span className="outline-heading">{item.heading}</span>
                                                {item.points?.length > 0 && (
                                                    <ul className="outline-points">
                                                        {item.points.map((point, pIdx) => (
                                                            <li key={pIdx}>{point}</li>
                                                        ))}
                                                    </ul>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            {outline.target_keywords?.length > 0 && (
                                <div className="outline-section">
                                    <h4>Target Keywords</h4>
                                    <div className="keyword-tags">
                                        {outline.target_keywords.map((kw, idx) => (
                                            <span key={idx} className="keyword-tag">{kw}</span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                        <div className="outline-footer">
                            <button
                                type="button"
                                className="button button--secondary"
                                onClick={() => {
                                    const text = `# ${outline.suggested_title || outline.topic}\n\n${outline.outline?.map(item => `## ${item.heading}\n${item.points?.map(p => `- ${p}`).join('\n') || ''}`).join('\n\n') || ''}`;
                                    navigator.clipboard.writeText(text);
                                }}
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="14" height="14">
                                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                                    <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
                                </svg>
                                Copy Outline
                            </button>
                            <button
                                type="button"
                                className="button button--primary"
                                onClick={() => setOutline(null)}
                            >
                                Done
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {!results && !analyzing && (
                <div className="content-gaps-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" width="64" height="64">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                        <rect x="9" y="3" width="6" height="4" rx="1"/>
                        <path d="M12 12v4M10 14h4"/>
                    </svg>
                    <h3>Find Your Content Opportunities</h3>
                    <p>
                        Enter a focus topic or select a category, then click "Find Content Gaps" to discover
                        what topics you should be writing about based on your existing content.
                    </p>
                </div>
            )}
        </div>
    );
};

export default ContentGaps;
