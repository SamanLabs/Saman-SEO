/**
 * SEO Panel Component
 *
 * Main panel containing all SEO fields and previews with AI and Variables support.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import SearchPreview from './SearchPreview';
import ScoreGauge from './ScoreGauge';
import TemplateInput from './TemplateInput';
import AiGenerateModal from './AiGenerateModal';
import MetricsBreakdown from './MetricsBreakdown';

// Quick template presets for the editor
const quickTemplates = [
    { id: 'standard', name: 'Standard', title: '{{post_title}} | {{site_title}}', description: '{{post_excerpt}}' },
    { id: 'keyword', name: 'Keyword Focus', title: '{{post_title}} - Guide', description: 'Learn about {{post_title}}. {{post_excerpt}}' },
    { id: 'how_to', name: 'How-To', title: 'How to {{post_title}}', description: 'Learn how to {{post_title}} with this guide.' },
    { id: 'list', name: 'List Post', title: 'Best {{post_title}}', description: 'Discover the best {{post_title}}. {{post_excerpt}}' },
];

const SEOPanel = ({
    postId,
    seoMeta,
    updateMeta,
    seoScore,
    effectiveTitle,
    effectiveDescription,
    postUrl,
    postTitle,
    postContent,
    featuredImage,
    hasChanges,
    variables,
    variableValues,
    aiEnabled,
    aiProvider = 'none',
    aiPilot = null,
}) => {
    const [activeTab, setActiveTab] = useState('general');
    const [showTemplates, setShowTemplates] = useState(false);
    const [aiModal, setAiModal] = useState({
        isOpen: false,
        fieldType: 'title',
        onApply: null,
    });

    // Indexing state
    const [indexingStatus, setIndexingStatus] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [indexError, setIndexError] = useState(null);

    // Fetch indexing status
    useEffect(() => {
        if (!postId) return;

        apiFetch({ path: `/saman-seo/v1/indexnow/post-status/${postId}` })
            .then((response) => {
                if (response.success) {
                    setIndexingStatus(response.data);
                }
            })
            .catch(() => {
                // Ignore errors - IndexNow might not be enabled
            });
    }, [postId]);

    // Handle request indexing
    const handleRequestIndexing = useCallback(async () => {
        if (!postId || isSubmitting) return;

        setIsSubmitting(true);
        setIndexError(null);

        try {
            const response = await apiFetch({
                path: `/saman-seo/v1/indexnow/submit-post/${postId}`,
                method: 'POST',
            });

            if (response.success) {
                // Refresh status after submission
                const statusResponse = await apiFetch({
                    path: `/saman-seo/v1/indexnow/post-status/${postId}`,
                });
                if (statusResponse.success) {
                    setIndexingStatus(statusResponse.data);
                }
            } else {
                setIndexError(response.message || 'Failed to submit for indexing');
            }
        } catch (err) {
            setIndexError(err.message || 'Failed to submit for indexing');
        } finally {
            setIsSubmitting(false);
        }
    }, [postId, isSubmitting]);

    // Apply template handler
    const applyTemplate = useCallback((template) => {
        updateMeta('title', template.title);
        updateMeta('description', template.description);
        setShowTemplates(false);
    }, [updateMeta]);

    // Character limits
    const TITLE_MAX = 60;
    const DESC_MAX = 160;

    const titleLength = (seoMeta.title || '').length;
    const descLength = (seoMeta.description || '').length;

    const getTitleStatus = () => {
        if (titleLength === 0) return 'empty';
        if (titleLength < 30) return 'short';
        if (titleLength > TITLE_MAX) return 'long';
        return 'good';
    };

    const getDescStatus = () => {
        if (descLength === 0) return 'empty';
        if (descLength < 70) return 'short';
        if (descLength > DESC_MAX) return 'long';
        return 'good';
    };

    // AI Modal handlers
    const openAiModal = useCallback((fieldType, onApply) => {
        setAiModal({
            isOpen: true,
            fieldType,
            onApply,
        });
    }, []);

    const closeAiModal = useCallback(() => {
        setAiModal({
            isOpen: false,
            fieldType: 'title',
            onApply: null,
        });
    }, []);

    const handleAiGenerate = useCallback((result) => {
        if (aiModal.onApply && result) {
            aiModal.onApply(result);
        }
        closeAiModal();
    }, [aiModal, closeAiModal]);

    return (
        <div className="saman-seo-editor-panel">
            {/* Score Header */}
            <div className="saman-seo-score-header">
                <ScoreGauge score={seoScore?.score || 0} level={seoScore?.level || 'poor'} />
                <div className="saman-seo-score-info">
                    <div className="saman-seo-score-label">SEO Score</div>
                    <div className="saman-seo-score-status">
                        {seoScore?.issues?.length > 0
                            ? `${seoScore.issues.length} issue${seoScore.issues.length !== 1 ? 's' : ''} found`
                            : 'Looking good!'}
                    </div>
                    {!seoMeta.focus_keyphrase && (
                        <div className="saman-seo-keyphrase-hint">
                            Add keyphrases for full analysis
                        </div>
                    )}
                    {seoMeta.focus_keyphrase && (seoMeta.secondary_keyphrases?.length > 0) && (
                        <div className="saman-seo-keyphrase-hint" style={{ color: '#00a32a' }}>
                            {1 + seoMeta.secondary_keyphrases.length} keywords tracked
                        </div>
                    )}
                </div>
            </div>

            {/* Tab Navigation */}
            <div className="saman-seo-tabs">
                <button
                    type="button"
                    className={`saman-seo-tab ${activeTab === 'general' ? 'active' : ''}`}
                    onClick={() => setActiveTab('general')}
                >
                    General
                </button>
                <button
                    type="button"
                    className={`saman-seo-tab ${activeTab === 'analysis' ? 'active' : ''}`}
                    onClick={() => setActiveTab('analysis')}
                >
                    Analysis
                </button>
                <button
                    type="button"
                    className={`saman-seo-tab ${activeTab === 'advanced' ? 'active' : ''}`}
                    onClick={() => setActiveTab('advanced')}
                >
                    Advanced
                </button>
                <button
                    type="button"
                    className={`saman-seo-tab ${activeTab === 'social' ? 'active' : ''}`}
                    onClick={() => setActiveTab('social')}
                >
                    Social
                </button>
            </div>

            {/* General Tab */}
            {activeTab === 'general' && (
                <div className="saman-seo-tab-content">
                    {/* Search Preview */}
                    <div className="saman-seo-preview-section">
                        <label className="saman-seo-section-label">Search Preview</label>
                        <SearchPreview
                            title={effectiveTitle}
                            description={effectiveDescription}
                            url={postUrl}
                        />
                    </div>

                    {/* Focus Keyphrases - Multi-keyword support */}
                    <div className="saman-seo-field saman-seo-field--keyphrases">
                        <div className="saman-seo-field-header">
                            <label>Focus Keyphrases</label>
                            <span className="saman-seo-field-count">
                                {1 + (seoMeta.secondary_keyphrases?.length || 0)}/5
                            </span>
                        </div>

                        {/* Primary Keyphrase */}
                        <div className="saman-seo-keyphrase-item saman-seo-keyphrase-primary">
                            <span className="saman-seo-keyphrase-badge">Primary</span>
                            <input
                                type="text"
                                className="saman-seo-field-input"
                                value={seoMeta.focus_keyphrase || ''}
                                onChange={(e) => updateMeta('focus_keyphrase', e.target.value)}
                                placeholder="Enter your main target keyword"
                            />
                        </div>

                        {/* Secondary Keyphrases */}
                        {(seoMeta.secondary_keyphrases || []).map((keyphrase, index) => (
                            <div key={index} className="saman-seo-keyphrase-item saman-seo-keyphrase-secondary">
                                <span className="saman-seo-keyphrase-badge">#{index + 2}</span>
                                <input
                                    type="text"
                                    className="saman-seo-field-input"
                                    value={keyphrase}
                                    onChange={(e) => {
                                        const updated = [...(seoMeta.secondary_keyphrases || [])];
                                        updated[index] = e.target.value;
                                        updateMeta('secondary_keyphrases', updated);
                                    }}
                                    placeholder={`Secondary keyword ${index + 1}`}
                                />
                                <button
                                    type="button"
                                    className="saman-seo-keyphrase-remove"
                                    onClick={() => {
                                        const updated = (seoMeta.secondary_keyphrases || []).filter((_, i) => i !== index);
                                        updateMeta('secondary_keyphrases', updated);
                                    }}
                                    aria-label="Remove keyphrase"
                                >
                                    ×
                                </button>
                            </div>
                        ))}

                        {/* Add Button - Max 4 secondary (5 total) */}
                        {(seoMeta.secondary_keyphrases?.length || 0) < 4 && (
                            <button
                                type="button"
                                className="saman-seo-keyphrase-add"
                                onClick={() => {
                                    const current = seoMeta.secondary_keyphrases || [];
                                    updateMeta('secondary_keyphrases', [...current, '']);
                                }}
                            >
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                                Add Secondary Keyphrase
                            </button>
                        )}

                        <p className="saman-seo-field-help">
                            Add up to 5 keyphrases to optimize your content for multiple search terms
                        </p>
                    </div>

                    {/* Quick Templates */}
                    <div className="saman-seo-field saman-seo-field--templates">
                        <div className="saman-seo-templates-header">
                            <button
                                type="button"
                                className={`saman-seo-templates-toggle ${showTemplates ? 'active' : ''}`}
                                onClick={() => setShowTemplates(!showTemplates)}
                            >
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <path d="M7 7h10M7 12h10M7 17h6"/>
                                </svg>
                                Quick Templates
                                <svg
                                    width="12"
                                    height="12"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    style={{ transform: showTemplates ? 'rotate(180deg)' : 'none', transition: 'transform 0.15s' }}
                                >
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>
                        </div>
                        {showTemplates && (
                            <div className="saman-seo-templates-list">
                                {quickTemplates.map((template) => (
                                    <button
                                        key={template.id}
                                        type="button"
                                        className="saman-seo-template-item"
                                        onClick={() => applyTemplate(template)}
                                    >
                                        <span className="saman-seo-template-name">{template.name}</span>
                                        <span className="saman-seo-template-preview">{template.title}</span>
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* SEO Title with AI and Variables */}
                    <TemplateInput
                        label="SEO Title"
                        id="saman-seo-seo-title"
                        value={seoMeta.title || ''}
                        onChange={(value) => updateMeta('title', value)}
                        placeholder={postTitle || 'Enter SEO title'}
                        maxLength={TITLE_MAX}
                        variables={variables}
                        variableValues={variableValues}
                        context="post"
                        showAiButton={true}
                        aiEnabled={aiEnabled}
                        onAiClick={() => openAiModal('title', (val) => updateMeta('title', val))}
                    />

                    {/* Meta Description with AI and Variables */}
                    <TemplateInput
                        label="Meta Description"
                        id="saman-seo-meta-desc"
                        value={seoMeta.description || ''}
                        onChange={(value) => updateMeta('description', value)}
                        placeholder="Enter meta description"
                        maxLength={DESC_MAX}
                        multiline
                        variables={variables}
                        variableValues={variableValues}
                        context="post"
                        showAiButton={true}
                        aiEnabled={aiEnabled}
                        onAiClick={() => openAiModal('description', (val) => updateMeta('description', val))}
                    />

                    {/* Quick Analysis */}
                    {seoScore?.issues?.length > 0 && (
                        <div className="saman-seo-issues">
                            <label className="saman-seo-section-label">Issues</label>
                            <ul className="saman-seo-issues-list">
                                {seoScore.issues.slice(0, 5).map((issue, idx) => (
                                    <li key={idx} className={`saman-seo-issue saman-seo-issue--${issue.severity || 'warning'}`}>
                                        <span className="saman-seo-issue-icon">
                                            {issue.severity === 'high' ? '!' : '?'}
                                        </span>
                                        <span className="saman-seo-issue-text">{issue.message}</span>
                                    </li>
                                ))}
                            </ul>
                            {seoScore.issues.length > 5 && (
                                <button
                                    type="button"
                                    className="saman-seo-view-all-link"
                                    onClick={() => setActiveTab('analysis')}
                                >
                                    View all {seoScore.issues.length} issues →
                                </button>
                            )}
                        </div>
                    )}
                </div>
            )}

            {/* Analysis Tab */}
            {activeTab === 'analysis' && (
                <div className="saman-seo-tab-content">
                    <MetricsBreakdown
                        metrics={seoScore?.metrics || []}
                        metricsByCategory={seoScore?.metrics_by_category}
                        hasKeyphrase={!!seoMeta.focus_keyphrase}
                    />
                </div>
            )}

            {/* Advanced Tab */}
            {activeTab === 'advanced' && (
                <div className="saman-seo-tab-content">
                    {/* Canonical URL */}
                    <div className="saman-seo-field">
                        <div className="saman-seo-field-header">
                            <label>Canonical URL</label>
                        </div>
                        <input
                            type="url"
                            className="saman-seo-field-input"
                            value={seoMeta.canonical || ''}
                            onChange={(e) => updateMeta('canonical', e.target.value)}
                            placeholder={postUrl}
                        />
                        <p className="saman-seo-field-help">Leave empty to use the default URL</p>
                    </div>

                    {/* Robots Settings */}
                    <div className="saman-seo-robots-section">
                        <label className="saman-seo-section-label">Search Engine Visibility</label>

                        <label className="saman-seo-toggle">
                            <input
                                type="checkbox"
                                checked={seoMeta.noindex || false}
                                onChange={(e) => updateMeta('noindex', e.target.checked)}
                            />
                            <span className="saman-seo-toggle-slider"></span>
                            <span className="saman-seo-toggle-label">
                                Hide from search results
                                <small>Add noindex meta tag</small>
                            </span>
                        </label>

                        <label className="saman-seo-toggle">
                            <input
                                type="checkbox"
                                checked={seoMeta.nofollow || false}
                                onChange={(e) => updateMeta('nofollow', e.target.checked)}
                            />
                            <span className="saman-seo-toggle-slider"></span>
                            <span className="saman-seo-toggle-label">
                                Don't follow links
                                <small>Add nofollow meta tag</small>
                            </span>
                        </label>
                    </div>

                    {/* Robots Preview */}
                    <div className="saman-seo-robots-preview">
                        <label className="saman-seo-section-label">Robots Meta</label>
                        <code className="saman-seo-robots-code">
                            {seoMeta.noindex || seoMeta.nofollow
                                ? `${seoMeta.noindex ? 'noindex' : 'index'}, ${seoMeta.nofollow ? 'nofollow' : 'follow'}`
                                : 'index, follow (default)'}
                        </code>
                    </div>

                    {/* Instant Indexing Section */}
                    <div className="saman-seo-indexing-section">
                        <label className="saman-seo-section-label">Instant Indexing</label>

                        {indexingStatus && !indexingStatus.indexnow_enabled && (
                            <div className="saman-seo-indexing-notice saman-seo-indexing-notice--info">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 16v-4M12 8h.01"/>
                                </svg>
                                <span>Enable IndexNow in Settings to use instant indexing</span>
                            </div>
                        )}

                        {indexingStatus && indexingStatus.indexnow_enabled && (
                            <>
                                {/* Indexing Status */}
                                {indexingStatus.has_been_indexed && indexingStatus.last_submission && (
                                    <div className={`saman-seo-indexing-status saman-seo-indexing-status--${indexingStatus.last_submission.status}`}>
                                        <div className="saman-seo-indexing-status-icon">
                                            {indexingStatus.last_submission.status === 'success' ? (
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                    <path d="M20 6L9 17l-5-5"/>
                                                </svg>
                                            ) : (
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <path d="M15 9l-6 6M9 9l6 6"/>
                                                </svg>
                                            )}
                                        </div>
                                        <div className="saman-seo-indexing-status-text">
                                            <strong>
                                                {indexingStatus.last_submission.status === 'success' ? 'Submitted' : 'Failed'}
                                            </strong>
                                            <span>{indexingStatus.last_submission.time_ago}</span>
                                        </div>
                                    </div>
                                )}

                                {!indexingStatus.has_been_indexed && (
                                    <div className="saman-seo-indexing-status saman-seo-indexing-status--never">
                                        <div className="saman-seo-indexing-status-icon">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M12 6v6l4 2"/>
                                            </svg>
                                        </div>
                                        <div className="saman-seo-indexing-status-text">
                                            <strong>Not submitted</strong>
                                            <span>Request indexing to notify search engines</span>
                                        </div>
                                    </div>
                                )}

                                {/* Error message */}
                                {indexError && (
                                    <div className="saman-seo-indexing-notice saman-seo-indexing-notice--error">
                                        {indexError}
                                    </div>
                                )}

                                {/* Request Indexing Button */}
                                <Button
                                    variant="secondary"
                                    className="saman-seo-indexing-button"
                                    onClick={handleRequestIndexing}
                                    disabled={isSubmitting}
                                >
                                    {isSubmitting ? (
                                        <>
                                            <span className="saman-seo-indexing-spinner" />
                                            Submitting...
                                        </>
                                    ) : (
                                        <>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                                            </svg>
                                            Request Indexing
                                        </>
                                    )}
                                </Button>

                                <p className="saman-seo-field-help">
                                    Submit this URL to search engines via IndexNow for faster discovery.
                                    {indexingStatus.total_submissions > 0 && (
                                        <> Submitted {indexingStatus.total_submissions} time{indexingStatus.total_submissions !== 1 ? 's' : ''}.</>
                                    )}
                                </p>
                            </>
                        )}
                    </div>
                </div>
            )}

            {/* Social Tab */}
            {activeTab === 'social' && (
                <div className="saman-seo-tab-content">
                    {/* Social Preview */}
                    <div className="saman-seo-social-preview">
                        <label className="saman-seo-section-label">Social Preview</label>
                        <div className="saman-seo-social-card">
                            <div className="saman-seo-social-image">
                                {seoMeta.og_image || featuredImage ? (
                                    <img src={seoMeta.og_image || featuredImage} alt="" />
                                ) : (
                                    <div className="saman-seo-social-placeholder">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                                            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" strokeWidth="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                                            <path d="M21 15l-5-5L5 21" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                        </svg>
                                        <span>No image set</span>
                                    </div>
                                )}
                            </div>
                            <div className="saman-seo-social-content">
                                <div className="saman-seo-social-url">{new URL(postUrl).hostname}</div>
                                <div className="saman-seo-social-title">{effectiveTitle}</div>
                                <div className="saman-seo-social-desc">{effectiveDescription || 'No description available'}</div>
                            </div>
                        </div>
                    </div>

                    {/* OG Image */}
                    <div className="saman-seo-field">
                        <div className="saman-seo-field-header">
                            <label>Social Image URL</label>
                        </div>
                        <input
                            type="url"
                            className="saman-seo-field-input"
                            value={seoMeta.og_image || ''}
                            onChange={(e) => updateMeta('og_image', e.target.value)}
                            placeholder="https://..."
                        />
                        <p className="saman-seo-field-help">1200x630 recommended. Leave empty to use featured image.</p>
                        {!seoMeta.og_image && featuredImage && (
                            <p className="saman-seo-field-note">
                                Using featured image as fallback
                            </p>
                        )}
                    </div>

                    {/* Media Library Button */}
                    <Button
                        variant="secondary"
                        className="saman-seo-media-button"
                        onClick={() => {
                            const frame = wp.media({
                                title: 'Select Social Image',
                                button: { text: 'Use Image' },
                                multiple: false,
                            });
                            frame.on('select', () => {
                                const attachment = frame.state().get('selection').first().toJSON();
                                updateMeta('og_image', attachment.url);
                            });
                            frame.open();
                        }}
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style={{ marginRight: '6px' }}>
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" strokeWidth="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                            <path d="M21 15l-5-5L5 21" stroke="currentColor" strokeWidth="2"/>
                        </svg>
                        Select Image
                    </Button>
                </div>
            )}

            {/* AI Generate Modal */}
            <AiGenerateModal
                isOpen={aiModal.isOpen}
                onClose={closeAiModal}
                onGenerate={handleAiGenerate}
                fieldType={aiModal.fieldType}
                currentValue={aiModal.fieldType === 'title' ? seoMeta.title : seoMeta.description}
                postTitle={postTitle}
                postContent={postContent}
                variableValues={variableValues}
                aiProvider={aiProvider}
                aiPilot={aiPilot}
            />
        </div>
    );
};

export default SEOPanel;
