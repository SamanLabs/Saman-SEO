/**
 * Template Input Component for Editor Sidebar
 *
 * An input/textarea with:
 * - Syntax highlighting for variables (colored {{ }} brackets)
 * - Hover preview showing rendered values
 * - Floating action icons (Variables, AI)
 */

import { useState, useRef, useEffect, useMemo } from '@wordpress/element';
import VariablePicker from './VariablePicker';

// Variable type color mapping
const variableColors = {
    // Global variables - blue
    site_title: 'global',
    tagline: 'global',
    site_url: 'global',
    separator: 'separator',
    current_year: 'global',
    current_month: 'global',
    current_day: 'global',

    // Post variables - violet
    post_title: 'post',
    post_excerpt: 'post',
    post_date: 'post',
    post_modified: 'post',
    post_author: 'post',
    post_id: 'post',
    post_content: 'post',

    // Taxonomy variables - green
    term_title: 'taxonomy',
    term_description: 'taxonomy',
    term_count: 'taxonomy',

    // Author variables - orange
    author_name: 'author',
    author_bio: 'author',

    // Archive variables - teal
    archive_title: 'archive',
    search_query: 'archive',
    page_number: 'archive',
};

// Extract base tag from variable (handles modifiers like "post_title | upper")
const getBaseTag = (fullTag) => {
    const pipeIndex = fullTag.indexOf('|');
    if (pipeIndex > -1) {
        return fullTag.substring(0, pipeIndex).trim();
    }
    return fullTag.trim();
};

const getVariableType = (tag) => {
    const baseTag = getBaseTag(tag);
    return variableColors[baseTag] || 'global';
};

const TemplateInput = ({
    value = '',
    onChange,
    placeholder = '',
    variables = {},
    variableValues = {},
    context = 'post',
    multiline = false,
    maxLength = null,
    label = '',
    helpText = '',
    id,
    disabled = false,
    onAiClick = null,
    showAiButton = true,
    aiEnabled = true,
}) => {
    const inputRef = useRef(null);
    const highlightRef = useRef(null);
    const [isFocused, setIsFocused] = useState(false);
    const [showVariablePicker, setShowVariablePicker] = useState(false);
    const [hoveredVariable, setHoveredVariable] = useState(null);

    // Sync scroll position between input and highlight overlay
    useEffect(() => {
        const input = inputRef.current;
        const highlight = highlightRef.current;
        if (!input || !highlight) return;

        const syncScroll = () => {
            highlight.scrollTop = input.scrollTop;
            highlight.scrollLeft = input.scrollLeft;
        };

        input.addEventListener('scroll', syncScroll);
        return () => input.removeEventListener('scroll', syncScroll);
    }, []);

    // Parse template into parts with syntax highlighting
    const renderHighlighted = useMemo(() => {
        if (!value) return [];

        const parts = [];
        let lastIndex = 0;
        const regex = /\{\{([^}]+)\}\}/g;
        let match;

        while ((match = regex.exec(value)) !== null) {
            // Add text before the variable
            if (match.index > lastIndex) {
                parts.push({
                    type: 'text',
                    content: value.slice(lastIndex, match.index),
                });
            }

            const fullTag = match[1].trim();
            const baseTag = getBaseTag(fullTag);
            const varType = getVariableType(baseTag);
            const previewValue = variableValues[baseTag] || variableValues[`{{${baseTag}}}`];

            parts.push({
                type: 'variable',
                fullTag: fullTag,
                baseTag: baseTag,
                raw: match[0],
                preview: previewValue || baseTag,
                varType: varType,
            });

            lastIndex = regex.lastIndex;
        }

        // Add remaining text
        if (lastIndex < value.length) {
            parts.push({
                type: 'text',
                content: value.slice(lastIndex),
            });
        }

        return parts;
    }, [value, variableValues]);

    // Insert variable at cursor position
    const insertVariable = (variableTag) => {
        const input = inputRef.current;
        if (!input) {
            onChange(value + variableTag);
            return;
        }

        const start = input.selectionStart;
        const end = input.selectionEnd;
        const newValue = value.slice(0, start) + variableTag + value.slice(end);

        onChange(newValue);
        setShowVariablePicker(false);

        requestAnimationFrame(() => {
            const newPos = start + variableTag.length;
            input.setSelectionRange(newPos, newPos);
            input.focus();
        });
    };

    const charCount = value.length;
    const isOverLimit = maxLength && charCount > maxLength;

    const InputComponent = multiline ? 'textarea' : 'input';

    return (
        <div className={`saman-seo-template-input ${isFocused ? 'is-focused' : ''} ${disabled ? 'is-disabled' : ''}`}>
            {label && (
                <div className="saman-seo-template-input__header">
                    <label className="saman-seo-template-input__label" htmlFor={id}>
                        {label}
                    </label>
                    {maxLength && (
                        <span className={`saman-seo-template-input__counter ${isOverLimit ? 'over-limit' : charCount > 0 ? 'has-value' : ''}`}>
                            {charCount}/{maxLength}
                        </span>
                    )}
                </div>
            )}

            <div className="saman-seo-template-input__container">
                {/* Highlight overlay - shows syntax highlighted variables */}
                <div
                    ref={highlightRef}
                    className={`saman-seo-template-input__highlight ${multiline ? 'multiline' : ''}`}
                    aria-hidden="true"
                >
                    {renderHighlighted.map((part, index) =>
                        part.type === 'variable' ? (
                            <span
                                key={index}
                                className={`saman-seo-template-input__var saman-seo-template-input__var--${part.varType}`}
                                onMouseEnter={() => setHoveredVariable({ ...part, index })}
                                onMouseLeave={() => setHoveredVariable(null)}
                            >
                                <span className="saman-seo-template-input__bracket">{'{'}</span>
                                <span className="saman-seo-template-input__bracket">{'{'}</span>
                                <span className="saman-seo-template-input__tag">{part.fullTag}</span>
                                <span className="saman-seo-template-input__bracket">{'}'}</span>
                                <span className="saman-seo-template-input__bracket">{'}'}</span>
                                {hoveredVariable?.index === index && part.preview && (
                                    <span className="saman-seo-template-input__tooltip">
                                        {part.preview}
                                    </span>
                                )}
                            </span>
                        ) : (
                            <span key={index}>{part.content}</span>
                        )
                    )}
                    {!value && <span className="saman-seo-template-input__placeholder">{placeholder}</span>}
                </div>

                {/* Actual input - completely invisible, just for typing */}
                <InputComponent
                    ref={inputRef}
                    id={id}
                    type={multiline ? undefined : 'text'}
                    className={`saman-seo-template-input__field ${multiline ? 'multiline' : ''}`}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    onFocus={() => setIsFocused(true)}
                    onBlur={() => setIsFocused(false)}
                    placeholder=""
                    disabled={disabled}
                    rows={multiline ? 3 : undefined}
                />

                {/* Floating action buttons */}
                <div className="saman-seo-template-input__actions">
                    {showAiButton && (
                        <button
                            type="button"
                            className={`saman-seo-template-input__action-btn saman-seo-template-input__action-btn--ai ${!aiEnabled ? 'is-disabled' : ''}`}
                            onClick={() => {
                                if (aiEnabled && onAiClick) {
                                    onAiClick();
                                } else if (!aiEnabled) {
                                    // Show alert when AI is disabled
                                    if (window.confirm('AI features require WP AI Pilot to be configured.\n\nWould you like to open the AI settings now?')) {
                                        window.open('admin.php?page=wp-ai-pilot', '_blank');
                                    }
                                }
                            }}
                            disabled={disabled}
                            title="Generate with AI"
                        >
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M12 3v1m0 16v1m-9-9h1m16 0h1m-2.636-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707" />
                                <circle cx="12" cy="12" r="4" />
                            </svg>
                        </button>
                    )}
                    <VariablePicker
                        variables={variables}
                        context={context}
                        onSelect={insertVariable}
                        disabled={disabled}
                        isOpen={showVariablePicker}
                        onToggle={() => setShowVariablePicker(!showVariablePicker)}
                        onClose={() => setShowVariablePicker(false)}
                        compact
                    />
                </div>
            </div>

            {helpText && (
                <div className="saman-seo-template-input__footer">
                    <span className="saman-seo-template-input__help">{helpText}</span>
                </div>
            )}
        </div>
    );
};

export default TemplateInput;
