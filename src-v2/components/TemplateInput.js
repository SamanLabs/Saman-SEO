/**
 * Template Input Component
 *
 * An input/textarea with:
 * - Variable highlighting overlay showing rendered values
 * - Integrated variable picker
 * - Character counter
 */

import { useState, useRef, useEffect, useMemo } from '@wordpress/element';
import VariablePicker from './VariablePicker';

const TemplateInput = ({
    value = '',
    onChange,
    placeholder = '',
    variables = {},
    variableValues = {}, // Map of variable tag -> actual value
    context = 'global',
    multiline = false,
    maxLength = null,
    label = '',
    helpText = '',
    id,
    disabled = false,
}) => {
    const inputRef = useRef(null);
    const highlightRef = useRef(null);
    const [isFocused, setIsFocused] = useState(false);

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

    // Render the template with highlighted variables
    const renderHighlighted = useMemo(() => {
        if (!value) return '';

        // Replace {{variable}} with highlighted spans
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

            const tag = match[1].trim();
            const renderedValue = variableValues[tag] || variableValues[`{{${tag}}}`];

            parts.push({
                type: 'variable',
                tag: tag,
                raw: match[0],
                rendered: renderedValue || match[0],
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

    // Get the full rendered preview (for display)
    const renderedPreview = useMemo(() => {
        return renderHighlighted
            .map((part) => (part.type === 'variable' ? part.rendered : part.content))
            .join('');
    }, [renderHighlighted]);

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

        // Restore cursor position after the inserted variable
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
        <div className={`template-input ${isFocused ? 'is-focused' : ''} ${disabled ? 'is-disabled' : ''}`}>
            {label && (
                <label className="template-input__label" htmlFor={id}>
                    {label}
                </label>
            )}

            <div className="template-input__wrapper">
                <div className="template-input__container">
                    {/* Highlight overlay - shows rendered values */}
                    <div
                        ref={highlightRef}
                        className={`template-input__highlight ${multiline ? 'multiline' : ''}`}
                        aria-hidden="true"
                    >
                        {renderHighlighted.map((part, index) =>
                            part.type === 'variable' ? (
                                <span key={index} className="template-input__var" title={part.raw}>
                                    {part.rendered}
                                </span>
                            ) : (
                                <span key={index}>{part.content}</span>
                            )
                        )}
                        {/* Invisible character to maintain height when empty */}
                        {!value && <span className="template-input__placeholder">{placeholder}</span>}
                    </div>

                    {/* Actual input (transparent text, visible caret) */}
                    <InputComponent
                        ref={inputRef}
                        id={id}
                        type={multiline ? undefined : 'text'}
                        className={`template-input__field ${multiline ? 'multiline' : ''}`}
                        value={value}
                        onChange={(e) => onChange(e.target.value)}
                        onFocus={() => setIsFocused(true)}
                        onBlur={() => setIsFocused(false)}
                        placeholder=""
                        disabled={disabled}
                        rows={multiline ? 3 : undefined}
                    />
                </div>

                <div className="template-input__actions">
                    <VariablePicker
                        variables={variables}
                        context={context}
                        onSelect={insertVariable}
                        buttonLabel="Variables"
                        disabled={disabled}
                    />
                </div>
            </div>

            {(helpText || maxLength) && (
                <div className="template-input__footer">
                    {helpText && (
                        <span className="template-input__help">{helpText}</span>
                    )}
                    {maxLength && (
                        <span className={`template-input__counter ${isOverLimit ? 'over-limit' : ''}`}>
                            {charCount} / {maxLength}
                        </span>
                    )}
                </div>
            )}
        </div>
    );
};

export default TemplateInput;
