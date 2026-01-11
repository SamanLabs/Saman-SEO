/**
 * Variable Picker Component
 *
 * A modern dropdown/popover for inserting template variables.
 * Displays variables in groups with descriptions and preview values.
 */

import { useState, useRef, useEffect } from '@wordpress/element';

const VariablePicker = ({
    variables = {},
    onSelect,
    context = 'global', // Which groups to show: 'global', 'post', 'taxonomy', 'archive'
    buttonLabel = 'Insert Variable',
    disabled = false,
}) => {
    const [isOpen, setIsOpen] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const containerRef = useRef(null);

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
    }, [isOpen]);

    // Filter variables by context and search term
    const getFilteredVariables = () => {
        const filtered = {};

        // Determine which groups to show based on context
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
            // Check if this group should be shown
            if (!allowedGroups.includes(groupKey)) return;

            // Filter vars by search term
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

    const filteredVariables = isOpen ? getFilteredVariables() : {};

    return (
        <div className="variable-picker" ref={containerRef}>
            <button
                type="button"
                className="variable-picker__trigger"
                onClick={() => setIsOpen(!isOpen)}
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
                                <div className="variable-picker__group-label">
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
                                                <code className="variable-picker__tag">
                                                    {`{{${variable.tag}}}`}
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
