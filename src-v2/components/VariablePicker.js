/**
 * Variable Picker Component
 *
 * A modern dropdown for inserting template variables.
 * Supports compact mode for inline use within inputs.
 */

import { useState, useRef, useEffect } from '@wordpress/element';

const VariablePicker = ({
    variables = {},
    onSelect,
    context = 'global',
    buttonLabel = 'Variables',
    disabled = false,
    compact = false, // Compact mode for inline buttons
    isOpen: controlledOpen, // Controlled open state
    onToggle, // For controlled mode
    onClose, // For controlled mode
}) => {
    const [internalOpen, setInternalOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const containerRef = useRef(null);

    // Use controlled or internal state
    const isOpen = controlledOpen !== undefined ? controlledOpen : internalOpen;
    const setIsOpen = (value) => {
        if (controlledOpen !== undefined) {
            if (value) {
                onToggle?.();
            } else {
                onClose?.();
            }
        } else {
            setInternalOpen(value);
        }
    };

    // Close on outside click
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        if (isOpen) {
            document.addEventListener('mousedown', handleClickOutside);
        }
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [isOpen, controlledOpen]);

    // Filter variables by context and search term
    const getFilteredVariables = () => {
        const filtered = {};

        const contextGroups = {
            global: ['global'],
            post: ['global', 'post'],
            taxonomy: ['global', 'taxonomy'],
            archive: ['global', 'archive', 'author'],
            author: ['global', 'author'],
            date: ['global', 'archive'],
            search: ['global'],
            '404': ['global'],
        };

        const allowedGroups = contextGroups[context] || ['global'];

        Object.entries(variables).forEach(([groupKey, group]) => {
            if (!allowedGroups.includes(groupKey)) return;

            const filteredVars = (group.vars || []).filter((v) => {
                if (!searchTerm) return true;
                const term = searchTerm.toLowerCase();
                return (
                    v.tag.toLowerCase().includes(term) ||
                    v.label.toLowerCase().includes(term) ||
                    (v.desc && v.desc.toLowerCase().includes(term))
                );
            });

            if (filteredVars.length > 0) {
                filtered[groupKey] = { ...group, vars: filteredVars };
            }
        });

        return filtered;
    };

    const handleSelect = (variable) => {
        if (onSelect) {
            onSelect(`{{${variable.tag}}}`);
        }
        setIsOpen(false);
        setSearchTerm('');
    };

    const handleToggle = () => {
        if (controlledOpen !== undefined) {
            onToggle?.();
        } else {
            setInternalOpen(!internalOpen);
        }
    };

    const filteredVariables = isOpen ? getFilteredVariables() : {};

    // Compact mode - just an icon button
    if (compact) {
        return (
            <div className="variable-picker variable-picker--compact" ref={containerRef}>
                <button
                    type="button"
                    className="template-input-v2__action-btn template-input-v2__action-btn--vars"
                    onClick={handleToggle}
                    disabled={disabled}
                    title="Insert variable"
                >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M4 7h3a1 1 0 0 0 1-1V3" />
                        <path d="M20 7h-3a1 1 0 0 1-1-1V3" />
                        <path d="M4 17h3a1 1 0 0 1 1 1v3" />
                        <path d="M20 17h-3a1 1 0 0 0-1 1v3" />
                        <path d="M9 12h6" />
                        <path d="M12 9v6" />
                    </svg>
                </button>

                {isOpen && (
                    <div className="variable-picker__dropdown">
                        <div className="variable-picker__search">
                            <input
                                type="text"
                                placeholder="Search variables..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                autoFocus
                            />
                        </div>

                        <div className="variable-picker__groups">
                            {Object.entries(filteredVariables).map(([groupKey, group]) => (
                                <div key={groupKey} className="variable-picker__group">
                                    <div className={`variable-picker__group-label variable-picker__group-label--${groupKey}`}>
                                        {group.label}
                                    </div>
                                    <div className="variable-picker__items">
                                        {group.vars.map((variable) => (
                                            <button
                                                key={variable.tag}
                                                type="button"
                                                className="variable-picker__item"
                                                onClick={() => handleSelect(variable)}
                                            >
                                                <div className="variable-picker__item-header">
                                                    <code className={`variable-picker__tag variable-picker__tag--${groupKey}`}>
                                                        {variable.tag}
                                                    </code>
                                                    <span className="variable-picker__label">
                                                        {variable.label}
                                                    </span>
                                                </div>
                                                {variable.preview && (
                                                    <div className="variable-picker__preview">
                                                        {variable.preview}
                                                    </div>
                                                )}
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            ))}

                            {Object.keys(filteredVariables).length === 0 && (
                                <div className="variable-picker__empty">
                                    No variables found
                                </div>
                            )}
                        </div>
                    </div>
                )}
            </div>
        );
    }

    // Full mode - button with text
    return (
        <div className="variable-picker" ref={containerRef}>
            <button
                type="button"
                className="variable-picker__trigger"
                onClick={handleToggle}
                disabled={disabled}
                title="Insert variable"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" width="16" height="16">
                    <path fillRule="evenodd" d="M4.5 2A2.5 2.5 0 002 4.5v3.879a2.5 2.5 0 00.732 1.767l7.5 7.5a2.5 2.5 0 003.536 0l3.878-3.878a2.5 2.5 0 000-3.536l-7.5-7.5A2.5 2.5 0 008.38 2H4.5zM5 6a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
                </svg>
                <span>{buttonLabel}</span>
            </button>

            {isOpen && (
                <div className="variable-picker__dropdown">
                    <div className="variable-picker__search">
                        <input
                            type="text"
                            placeholder="Search variables..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            autoFocus
                        />
                    </div>

                    <div className="variable-picker__groups">
                        {Object.entries(filteredVariables).map(([groupKey, group]) => (
                            <div key={groupKey} className="variable-picker__group">
                                <div className={`variable-picker__group-label variable-picker__group-label--${groupKey}`}>
                                    {group.label}
                                </div>
                                <div className="variable-picker__items">
                                    {group.vars.map((variable) => (
                                        <button
                                            key={variable.tag}
                                            type="button"
                                            className="variable-picker__item"
                                            onClick={() => handleSelect(variable)}
                                        >
                                            <div className="variable-picker__item-header">
                                                <code className={`variable-picker__tag variable-picker__tag--${groupKey}`}>
                                                    {variable.tag}
                                                </code>
                                                <span className="variable-picker__label">
                                                    {variable.label}
                                                </span>
                                            </div>
                                            {variable.preview && (
                                                <div className="variable-picker__preview">
                                                    {variable.preview}
                                                </div>
                                            )}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        ))}

                        {Object.keys(filteredVariables).length === 0 && (
                            <div className="variable-picker__empty">
                                No variables found
                            </div>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default VariablePicker;
