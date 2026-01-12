/**
 * SEO Panel Component
 *
 * Main panel containing all SEO fields and previews with AI and Variables support.
 */

import { useState, useCallback } from '@wordpress/element';
import { Button } from '@wordpress/components';
import SearchPreview from './SearchPreview';
import ScoreGauge from './ScoreGauge';
import TemplateInput from './TemplateInput';
import AiGenerateModal from './AiGenerateModal';
import MetricsBreakdown from './MetricsBreakdown';

const SEOPanel = ({
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
    const [aiModal, setAiModal] = useState({
        isOpen: false,
        fieldType: 'title',
        onApply: null,
    });

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
        <div className="wpseopilot-editor-panel">
            {/* Score Header */}
            <div className="wpseopilot-score-header">
                <ScoreGauge score={seoScore?.score || 0} level={seoScore?.level || 'poor'} />
                <div className="wpseopilot-score-info">
                    <div className="wpseopilot-score-label">SEO Score</div>
                    <div className="wpseopilot-score-status">
                        {seoScore?.issues?.length > 0
                            ? `${seoScore.issues.length} issue${seoScore.issues.length !== 1 ? 's' : ''} found`
                            : 'Looking good!'}
                    </div>
                    {!seoMeta.focus_keyphrase && (
                        <div className="wpseopilot-keyphrase-hint">
                            Add keyphrase for full analysis
                        </div>
                    )}
                </div>
            </div>

            {/* Tab Navigation */}
            <div className="wpseopilot-tabs">
                <button
                    type="button"
                    className={`wpseopilot-tab ${activeTab === 'general' ? 'active' : ''}`}
                    onClick={() => setActiveTab('general')}
                >
                    General
                </button>
                <button
                    type="button"
                    className={`wpseopilot-tab ${activeTab === 'analysis' ? 'active' : ''}`}
                    onClick={() => setActiveTab('analysis')}
                >
                    Analysis
                </button>
                <button
                    type="button"
                    className={`wpseopilot-tab ${activeTab === 'advanced' ? 'active' : ''}`}
                    onClick={() => setActiveTab('advanced')}
                >
                    Advanced
                </button>
                <button
                    type="button"
                    className={`wpseopilot-tab ${activeTab === 'social' ? 'active' : ''}`}
                    onClick={() => setActiveTab('social')}
                >
                    Social
                </button>
            </div>

            {/* General Tab */}
            {activeTab === 'general' && (
                <div className="wpseopilot-tab-content">
                    {/* Search Preview */}
                    <div className="wpseopilot-preview-section">
                        <label className="wpseopilot-section-label">Search Preview</label>
                        <SearchPreview
                            title={effectiveTitle}
                            description={effectiveDescription}
                            url={postUrl}
                        />
                    </div>

                    {/* Focus Keyphrase */}
                    <div className="wpseopilot-field wpseopilot-field--keyphrase">
                        <div className="wpseopilot-field-header">
                            <label>Focus Keyphrase</label>
                        </div>
                        <input
                            type="text"
                            className="wpseopilot-field-input"
                            value={seoMeta.focus_keyphrase || ''}
                            onChange={(e) => updateMeta('focus_keyphrase', e.target.value)}
                            placeholder="Enter your target keyword"
                        />
                        <p className="wpseopilot-field-help">The main keyword you want this page to rank for</p>
                    </div>

                    {/* SEO Title with AI and Variables */}
                    <TemplateInput
                        label="SEO Title"
                        id="wpseopilot-seo-title"
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
                        id="wpseopilot-meta-desc"
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
                        <div className="wpseopilot-issues">
                            <label className="wpseopilot-section-label">Issues</label>
                            <ul className="wpseopilot-issues-list">
                                {seoScore.issues.slice(0, 5).map((issue, idx) => (
                                    <li key={idx} className={`wpseopilot-issue wpseopilot-issue--${issue.severity || 'warning'}`}>
                                        <span className="wpseopilot-issue-icon">
                                            {issue.severity === 'high' ? '!' : '?'}
                                        </span>
                                        <span className="wpseopilot-issue-text">{issue.message}</span>
                                    </li>
                                ))}
                            </ul>
                            {seoScore.issues.length > 5 && (
                                <button
                                    type="button"
                                    className="wpseopilot-view-all-link"
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
                <div className="wpseopilot-tab-content">
                    <MetricsBreakdown
                        metrics={seoScore?.metrics || []}
                        metricsByCategory={seoScore?.metrics_by_category}
                        hasKeyphrase={!!seoMeta.focus_keyphrase}
                    />
                </div>
            )}

            {/* Advanced Tab */}
            {activeTab === 'advanced' && (
                <div className="wpseopilot-tab-content">
                    {/* Canonical URL */}
                    <div className="wpseopilot-field">
                        <div className="wpseopilot-field-header">
                            <label>Canonical URL</label>
                        </div>
                        <input
                            type="url"
                            className="wpseopilot-field-input"
                            value={seoMeta.canonical || ''}
                            onChange={(e) => updateMeta('canonical', e.target.value)}
                            placeholder={postUrl}
                        />
                        <p className="wpseopilot-field-help">Leave empty to use the default URL</p>
                    </div>

                    {/* Robots Settings */}
                    <div className="wpseopilot-robots-section">
                        <label className="wpseopilot-section-label">Search Engine Visibility</label>

                        <label className="wpseopilot-toggle">
                            <input
                                type="checkbox"
                                checked={seoMeta.noindex || false}
                                onChange={(e) => updateMeta('noindex', e.target.checked)}
                            />
                            <span className="wpseopilot-toggle-slider"></span>
                            <span className="wpseopilot-toggle-label">
                                Hide from search results
                                <small>Add noindex meta tag</small>
                            </span>
                        </label>

                        <label className="wpseopilot-toggle">
                            <input
                                type="checkbox"
                                checked={seoMeta.nofollow || false}
                                onChange={(e) => updateMeta('nofollow', e.target.checked)}
                            />
                            <span className="wpseopilot-toggle-slider"></span>
                            <span className="wpseopilot-toggle-label">
                                Don't follow links
                                <small>Add nofollow meta tag</small>
                            </span>
                        </label>
                    </div>

                    {/* Robots Preview */}
                    <div className="wpseopilot-robots-preview">
                        <label className="wpseopilot-section-label">Robots Meta</label>
                        <code className="wpseopilot-robots-code">
                            {seoMeta.noindex || seoMeta.nofollow
                                ? `${seoMeta.noindex ? 'noindex' : 'index'}, ${seoMeta.nofollow ? 'nofollow' : 'follow'}`
                                : 'index, follow (default)'}
                        </code>
                    </div>
                </div>
            )}

            {/* Social Tab */}
            {activeTab === 'social' && (
                <div className="wpseopilot-tab-content">
                    {/* Social Preview */}
                    <div className="wpseopilot-social-preview">
                        <label className="wpseopilot-section-label">Social Preview</label>
                        <div className="wpseopilot-social-card">
                            <div className="wpseopilot-social-image">
                                {seoMeta.og_image || featuredImage ? (
                                    <img src={seoMeta.og_image || featuredImage} alt="" />
                                ) : (
                                    <div className="wpseopilot-social-placeholder">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                                            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" strokeWidth="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                                            <path d="M21 15l-5-5L5 21" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                        </svg>
                                        <span>No image set</span>
                                    </div>
                                )}
                            </div>
                            <div className="wpseopilot-social-content">
                                <div className="wpseopilot-social-url">{new URL(postUrl).hostname}</div>
                                <div className="wpseopilot-social-title">{effectiveTitle}</div>
                                <div className="wpseopilot-social-desc">{effectiveDescription || 'No description available'}</div>
                            </div>
                        </div>
                    </div>

                    {/* OG Image */}
                    <div className="wpseopilot-field">
                        <div className="wpseopilot-field-header">
                            <label>Social Image URL</label>
                        </div>
                        <input
                            type="url"
                            className="wpseopilot-field-input"
                            value={seoMeta.og_image || ''}
                            onChange={(e) => updateMeta('og_image', e.target.value)}
                            placeholder="https://..."
                        />
                        <p className="wpseopilot-field-help">1200x630 recommended. Leave empty to use featured image.</p>
                        {!seoMeta.og_image && featuredImage && (
                            <p className="wpseopilot-field-note">
                                Using featured image as fallback
                            </p>
                        )}
                    </div>

                    {/* Media Library Button */}
                    <Button
                        variant="secondary"
                        className="wpseopilot-media-button"
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
