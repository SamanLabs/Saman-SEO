/**
 * AI Generate Modal Component for Editor Sidebar
 *
 * Modal for generating SEO content (titles, descriptions) with AI
 */

import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const AiGenerateModal = ({
    isOpen,
    onClose,
    onGenerate,
    fieldType = 'title', // 'title' or 'description'
    currentValue = '',
    variableValues = {},
    context = {},
    postTitle = '',
    postContent = '',
    aiProvider = 'none', // 'wp-ai-pilot', 'native', or 'none'
    aiPilot = null, // { installed, active, ready, settingsUrl }
}) => {
    const [isGenerating, setIsGenerating] = useState(false);
    const [error, setError] = useState(null);
    const [includeContext, setIncludeContext] = useState(true);
    const [customPrompt, setCustomPrompt] = useState('');
    const [generatedResult, setGeneratedResult] = useState(null);

    // Build context string from available data
    const buildContextString = useCallback(() => {
        if (!includeContext) return '';

        const contextParts = [];

        // Add post info
        if (postTitle) {
            contextParts.push(`Post title: ${postTitle}`);
        }

        // Add context info
        if (context.type) {
            contextParts.push(`Content type: ${context.type}`);
        }

        // Add variable values
        const relevantVars = Object.entries(variableValues)
            .filter(([key, value]) => value && typeof value === 'string')
            .map(([key, value]) => `${key.replace(/_/g, ' ')}: ${value}`);

        if (relevantVars.length > 0) {
            contextParts.push(...relevantVars);
        }

        // Add snippet of post content if available
        if (postContent) {
            const contentSnippet = postContent.replace(/<[^>]*>/g, '').substring(0, 500);
            if (contentSnippet) {
                contextParts.push(`Content preview: ${contentSnippet}...`);
            }
        }

        return contextParts.join('\n');
    }, [includeContext, variableValues, context, postTitle, postContent]);

    const handleGenerate = async () => {
        setIsGenerating(true);
        setError(null);
        setGeneratedResult(null);

        try {
            // Build the content for AI
            let content = '';

            if (customPrompt) {
                content = customPrompt + '\n\n' + buildContextString();
            } else {
                content = buildContextString() || 'Generate SEO metadata for a website.';
            }

            // Add field-specific instructions
            if (fieldType === 'title') {
                content += '\n\nGenerate an SEO-optimized title (max 60 characters).';
            } else {
                content += '\n\nGenerate an SEO-optimized meta description (max 155 characters).';
            }

            const response = await apiFetch({
                path: '/saman-seo/v1/ai/generate',
                method: 'POST',
                data: {
                    content,
                    type: fieldType,
                },
            });

            if (response.success && response.data) {
                const result = fieldType === 'title' ? response.data.title : response.data.description;
                setGeneratedResult(result);
            } else {
                setError(response.message || 'Failed to generate content');
            }
        } catch (err) {
            setError(err.message || 'An error occurred during generation');
        } finally {
            setIsGenerating(false);
        }
    };

    const handleApply = () => {
        if (generatedResult) {
            onGenerate(generatedResult);
            handleClose();
        }
    };

    const handleClose = () => {
        setGeneratedResult(null);
        setError(null);
        setCustomPrompt('');
        onClose();
    };

    if (!isOpen) return null;

    // Show configuration notice if AI is not configured
    if (aiProvider === 'none') {
        return (
            <div className="saman-seo-ai-modal-overlay" onClick={handleClose}>
                <div className="saman-seo-ai-modal saman-seo-ai-modal--notice" onClick={(e) => e.stopPropagation()}>
                    <div className="saman-seo-ai-modal__header">
                        <h3>
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 8v4m0 4h.01" />
                            </svg>
                            AI Not Configured
                        </h3>
                        <button
                            type="button"
                            className="saman-seo-ai-modal__close"
                            onClick={handleClose}
                            aria-label="Close"
                        >
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M18 6L6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div className="saman-seo-ai-modal__body">
                        {aiPilot?.installed ? (
                            <div className="saman-seo-ai-notice saman-seo-ai-notice--warning">
                                <div className="saman-seo-ai-notice__icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                    </svg>
                                </div>
                                <div className="saman-seo-ai-notice__content">
                                    <h4>WP AI Pilot Needs Configuration</h4>
                                    <p>WP AI Pilot is installed but not yet configured. Add an API key to enable AI-powered SEO suggestions.</p>
                                </div>
                            </div>
                        ) : (
                            <div className="saman-seo-ai-notice saman-seo-ai-notice--info">
                                <div className="saman-seo-ai-notice__icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                    </svg>
                                </div>
                                <div className="saman-seo-ai-notice__content">
                                    <h4>Enhance with WP AI Pilot</h4>
                                    <p>Install WP AI Pilot to unlock AI-powered title and meta description generation.</p>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="saman-seo-ai-modal__footer">
                        <button
                            type="button"
                            className="saman-seo-ai-modal__btn saman-seo-ai-modal__btn--secondary"
                            onClick={handleClose}
                        >
                            Cancel
                        </button>
                        {aiPilot?.installed ? (
                            <a
                                href={aiPilot.settingsUrl}
                                className="saman-seo-ai-modal__btn saman-seo-ai-modal__btn--primary"
                            >
                                Configure WP AI Pilot
                            </a>
                        ) : (
                            <a
                                href="/wp-admin/plugin-install.php?s=wp+ai+pilot&tab=search"
                                className="saman-seo-ai-modal__btn saman-seo-ai-modal__btn--primary"
                            >
                                Install WP AI Pilot
                            </a>
                        )}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="saman-seo-ai-modal-overlay" onClick={handleClose}>
            <div className="saman-seo-ai-modal" onClick={(e) => e.stopPropagation()}>
                <div className="saman-seo-ai-modal__header">
                    <h3>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M12 3v1m0 16v1m-9-9h1m16 0h1m-2.636-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707" />
                            <circle cx="12" cy="12" r="4" />
                        </svg>
                        Generate {fieldType === 'title' ? 'Title' : 'Description'}
                    </h3>
                    {aiProvider === 'wp-ai-pilot' && (
                        <span className="saman-seo-ai-badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                            WP AI Pilot
                        </span>
                    )}
                    <button
                        type="button"
                        className="saman-seo-ai-modal__close"
                        onClick={handleClose}
                        aria-label="Close"
                    >
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div className="saman-seo-ai-modal__body">
                    {/* Context Toggle */}
                    <div className="saman-seo-ai-modal__option">
                        <label className="saman-seo-ai-modal__checkbox">
                            <input
                                type="checkbox"
                                checked={includeContext}
                                onChange={(e) => setIncludeContext(e.target.checked)}
                            />
                            <span className="saman-seo-ai-modal__checkbox-label">
                                Include post content as context
                            </span>
                        </label>
                    </div>

                    {/* Context Preview */}
                    {includeContext && postTitle && (
                        <div className="saman-seo-ai-modal__context-preview">
                            <div className="saman-seo-ai-modal__context-item">
                                <span className="saman-seo-ai-modal__context-key">Post:</span>
                                <span className="saman-seo-ai-modal__context-value">{postTitle}</span>
                            </div>
                        </div>
                    )}

                    {/* Custom Prompt */}
                    <div className="saman-seo-ai-modal__field">
                        <label htmlFor="ai-custom-prompt">Custom instructions (optional)</label>
                        <textarea
                            id="ai-custom-prompt"
                            value={customPrompt}
                            onChange={(e) => setCustomPrompt(e.target.value)}
                            placeholder={`e.g., "Focus on ${fieldType === 'title' ? 'including keywords' : 'highlighting benefits'}"`}
                            rows={2}
                        />
                    </div>

                    {/* Error Message */}
                    {error && (
                        <div className="saman-seo-ai-modal__error">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 8v4m0 4h.01" />
                            </svg>
                            {error}
                        </div>
                    )}

                    {/* Generated Result */}
                    {generatedResult && (
                        <div className="saman-seo-ai-modal__result">
                            <label>Generated {fieldType === 'title' ? 'title' : 'description'}:</label>
                            <div className="saman-seo-ai-modal__result-box">
                                <p>{generatedResult}</p>
                                <span className="saman-seo-ai-modal__char-count">
                                    {generatedResult.length} characters
                                </span>
                            </div>
                        </div>
                    )}
                </div>

                <div className="saman-seo-ai-modal__footer">
                    <button
                        type="button"
                        className="saman-seo-ai-modal__btn saman-seo-ai-modal__btn--secondary"
                        onClick={handleClose}
                    >
                        Cancel
                    </button>

                    {generatedResult ? (
                        <>
                            <button
                                type="button"
                                className="saman-seo-ai-modal__btn"
                                onClick={handleGenerate}
                                disabled={isGenerating}
                            >
                                Regenerate
                            </button>
                            <button
                                type="button"
                                className="saman-seo-ai-modal__btn saman-seo-ai-modal__btn--primary"
                                onClick={handleApply}
                            >
                                Apply
                            </button>
                        </>
                    ) : (
                        <button
                            type="button"
                            className="saman-seo-ai-modal__btn saman-seo-ai-modal__btn--primary"
                            onClick={handleGenerate}
                            disabled={isGenerating}
                        >
                            {isGenerating ? (
                                <>
                                    <span className="saman-seo-ai-modal__spinner"></span>
                                    Generating...
                                </>
                            ) : (
                                <>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M12 3v1m0 16v1m-9-9h1m16 0h1m-2.636-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707" />
                                        <circle cx="12" cy="12" r="4" />
                                    </svg>
                                    Generate
                                </>
                            )}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
};

export default AiGenerateModal;
