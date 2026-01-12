import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const schemaTypes = [
    { id: 'Article', name: 'Article', icon: '📄', description: 'News, blog posts, and articles' },
    { id: 'Product', name: 'Product', icon: '🛍️', description: 'E-commerce products' },
    { id: 'LocalBusiness', name: 'Local Business', icon: '🏪', description: 'Physical business location' },
    { id: 'FAQPage', name: 'FAQ Page', icon: '❓', description: 'Frequently asked questions' },
    { id: 'HowTo', name: 'How To', icon: '📝', description: 'Step-by-step instructions' },
    { id: 'Recipe', name: 'Recipe', icon: '🍳', description: 'Cooking recipes' },
    { id: 'Event', name: 'Event', icon: '📅', description: 'Events and conferences' },
    { id: 'Person', name: 'Person', icon: '👤', description: 'Author or person profile' },
    { id: 'Organization', name: 'Organization', icon: '🏢', description: 'Company or organization' },
    { id: 'WebSite', name: 'Website', icon: '🌐', description: 'Website information' },
    { id: 'BreadcrumbList', name: 'Breadcrumbs', icon: '🔗', description: 'Navigation breadcrumbs' },
    { id: 'VideoObject', name: 'Video', icon: '🎬', description: 'Video content' },
];

const schemaFields = {
    Article: [
        { key: 'headline', label: 'Headline', type: 'text', required: true },
        { key: 'description', label: 'Description', type: 'textarea' },
        { key: 'author', label: 'Author Name', type: 'text' },
        { key: 'datePublished', label: 'Date Published', type: 'date' },
        { key: 'dateModified', label: 'Date Modified', type: 'date' },
        { key: 'image', label: 'Image URL', type: 'url' },
    ],
    Product: [
        { key: 'name', label: 'Product Name', type: 'text', required: true },
        { key: 'description', label: 'Description', type: 'textarea' },
        { key: 'image', label: 'Image URL', type: 'url' },
        { key: 'brand', label: 'Brand', type: 'text' },
        { key: 'sku', label: 'SKU', type: 'text' },
        { key: 'price', label: 'Price', type: 'number' },
        { key: 'priceCurrency', label: 'Currency', type: 'text', placeholder: 'USD' },
        { key: 'availability', label: 'Availability', type: 'select', options: ['InStock', 'OutOfStock', 'PreOrder'] },
    ],
    LocalBusiness: [
        { key: 'name', label: 'Business Name', type: 'text', required: true },
        { key: 'description', label: 'Description', type: 'textarea' },
        { key: 'image', label: 'Image URL', type: 'url' },
        { key: 'telephone', label: 'Phone', type: 'tel' },
        { key: 'streetAddress', label: 'Street Address', type: 'text' },
        { key: 'addressLocality', label: 'City', type: 'text' },
        { key: 'addressRegion', label: 'State/Region', type: 'text' },
        { key: 'postalCode', label: 'Postal Code', type: 'text' },
        { key: 'addressCountry', label: 'Country', type: 'text' },
    ],
    FAQPage: [
        { key: 'faqs', label: 'FAQ Items', type: 'faq-list' },
    ],
    HowTo: [
        { key: 'name', label: 'Title', type: 'text', required: true },
        { key: 'description', label: 'Description', type: 'textarea' },
        { key: 'totalTime', label: 'Total Time', type: 'text', placeholder: 'PT30M (30 minutes)' },
        { key: 'steps', label: 'Steps', type: 'steps-list' },
    ],
    Recipe: [
        { key: 'name', label: 'Recipe Name', type: 'text', required: true },
        { key: 'description', label: 'Description', type: 'textarea' },
        { key: 'image', label: 'Image URL', type: 'url' },
        { key: 'prepTime', label: 'Prep Time', type: 'text', placeholder: 'PT15M' },
        { key: 'cookTime', label: 'Cook Time', type: 'text', placeholder: 'PT30M' },
        { key: 'recipeYield', label: 'Servings', type: 'text' },
        { key: 'recipeIngredient', label: 'Ingredients', type: 'list' },
        { key: 'recipeInstructions', label: 'Instructions', type: 'steps-list' },
    ],
    Event: [
        { key: 'name', label: 'Event Name', type: 'text', required: true },
        { key: 'description', label: 'Description', type: 'textarea' },
        { key: 'startDate', label: 'Start Date', type: 'datetime-local' },
        { key: 'endDate', label: 'End Date', type: 'datetime-local' },
        { key: 'location', label: 'Location Name', type: 'text' },
        { key: 'streetAddress', label: 'Address', type: 'text' },
        { key: 'image', label: 'Image URL', type: 'url' },
    ],
    Person: [
        { key: 'name', label: 'Name', type: 'text', required: true },
        { key: 'jobTitle', label: 'Job Title', type: 'text' },
        { key: 'description', label: 'Bio', type: 'textarea' },
        { key: 'image', label: 'Photo URL', type: 'url' },
        { key: 'email', label: 'Email', type: 'email' },
        { key: 'url', label: 'Website', type: 'url' },
    ],
    Organization: [
        { key: 'name', label: 'Organization Name', type: 'text', required: true },
        { key: 'description', label: 'Description', type: 'textarea' },
        { key: 'logo', label: 'Logo URL', type: 'url' },
        { key: 'url', label: 'Website', type: 'url' },
        { key: 'telephone', label: 'Phone', type: 'tel' },
        { key: 'email', label: 'Email', type: 'email' },
    ],
    WebSite: [
        { key: 'name', label: 'Site Name', type: 'text', required: true },
        { key: 'url', label: 'URL', type: 'url', required: true },
        { key: 'description', label: 'Description', type: 'textarea' },
        { key: 'potentialAction', label: 'Enable Site Search', type: 'checkbox' },
    ],
    BreadcrumbList: [
        { key: 'items', label: 'Breadcrumb Items', type: 'breadcrumb-list' },
    ],
    VideoObject: [
        { key: 'name', label: 'Video Title', type: 'text', required: true },
        { key: 'description', label: 'Description', type: 'textarea' },
        { key: 'thumbnailUrl', label: 'Thumbnail URL', type: 'url' },
        { key: 'uploadDate', label: 'Upload Date', type: 'date' },
        { key: 'duration', label: 'Duration', type: 'text', placeholder: 'PT5M30S' },
        { key: 'contentUrl', label: 'Video URL', type: 'url' },
        { key: 'embedUrl', label: 'Embed URL', type: 'url' },
    ],
};

const SchemaBuilder = ({ onNavigate }) => {
    const [selectedType, setSelectedType] = useState(null);
    const [formData, setFormData] = useState({});
    const [generatedSchema, setGeneratedSchema] = useState(null);
    const [validation, setValidation] = useState(null);
    const [generating, setGenerating] = useState(false);
    const [validating, setValidating] = useState(false);
    const [saving, setSaving] = useState(false);
    const [postUrl, setPostUrl] = useState('');
    const [detecting, setDetecting] = useState(false);
    const [copied, setCopied] = useState(false);

    const handleTypeSelect = (type) => {
        setSelectedType(type);
        setFormData({});
        setGeneratedSchema(null);
        setValidation(null);
    };

    const handleFieldChange = (key, value) => {
        setFormData(prev => ({
            ...prev,
            [key]: value,
        }));
    };

    const handleDetectSchema = async () => {
        if (!postUrl) return;

        setDetecting(true);
        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/tools/schema/detect',
                method: 'POST',
                data: { url: postUrl },
            });

            if (response.success && response.data.suggested_type) {
                setSelectedType(response.data.suggested_type);
                if (response.data.prefilled_data) {
                    setFormData(response.data.prefilled_data);
                }
            }
        } catch (error) {
            console.error('Failed to detect:', error);
        } finally {
            setDetecting(false);
        }
    };

    const handleGenerateSchema = async () => {
        if (!selectedType) return;

        setGenerating(true);
        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/tools/schema/generate',
                method: 'POST',
                data: {
                    type: selectedType,
                    data: formData,
                },
            });

            if (response.success) {
                setGeneratedSchema(response.data.schema);
            }
        } catch (error) {
            console.error('Failed to generate:', error);
        } finally {
            setGenerating(false);
        }
    };

    const handleValidate = async () => {
        if (!generatedSchema) return;

        setValidating(true);
        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/tools/schema/validate',
                method: 'POST',
                data: { schema: generatedSchema },
            });

            if (response.success) {
                setValidation(response.data);
            }
        } catch (error) {
            console.error('Failed to validate:', error);
        } finally {
            setValidating(false);
        }
    };

    const handleCopySchema = () => {
        const schemaScript = `<script type="application/ld+json">\n${JSON.stringify(generatedSchema, null, 2)}\n</script>`;
        navigator.clipboard.writeText(schemaScript);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const renderField = (field) => {
        const value = formData[field.key] || '';

        switch (field.type) {
            case 'textarea':
                return (
                    <textarea
                        value={value}
                        onChange={(e) => handleFieldChange(field.key, e.target.value)}
                        placeholder={field.placeholder || ''}
                        rows={3}
                    />
                );
            case 'select':
                return (
                    <select
                        value={value}
                        onChange={(e) => handleFieldChange(field.key, e.target.value)}
                    >
                        <option value="">Select...</option>
                        {field.options.map(opt => (
                            <option key={opt} value={opt}>{opt}</option>
                        ))}
                    </select>
                );
            case 'checkbox':
                return (
                    <label className="checkbox-label">
                        <input
                            type="checkbox"
                            checked={!!value}
                            onChange={(e) => handleFieldChange(field.key, e.target.checked)}
                        />
                        Enable
                    </label>
                );
            case 'faq-list':
                return <FAQListField value={value || []} onChange={(v) => handleFieldChange(field.key, v)} />;
            case 'steps-list':
                return <StepsListField value={value || []} onChange={(v) => handleFieldChange(field.key, v)} />;
            case 'list':
                return <SimpleListField value={value || []} onChange={(v) => handleFieldChange(field.key, v)} />;
            case 'breadcrumb-list':
                return <BreadcrumbListField value={value || []} onChange={(v) => handleFieldChange(field.key, v)} />;
            default:
                return (
                    <input
                        type={field.type}
                        value={value}
                        onChange={(e) => handleFieldChange(field.key, e.target.value)}
                        placeholder={field.placeholder || ''}
                    />
                );
        }
    };

    return (
        <div className="page schema-builder-page">
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
                        <span>Schema Builder</span>
                    </div>
                    <h1>Visual Schema Builder</h1>
                    <p>Create structured data markup for rich search results.</p>
                </div>
            </div>

            <div className="schema-builder-layout">
                <div className="schema-builder-main">
                    {!selectedType ? (
                        <>
                            <div className="schema-detect">
                                <h3>Auto-Detect from URL</h3>
                                <div className="detect-input">
                                    <input
                                        type="url"
                                        value={postUrl}
                                        onChange={(e) => setPostUrl(e.target.value)}
                                        placeholder="Enter a page URL to auto-detect schema type..."
                                    />
                                    <button
                                        type="button"
                                        className="button button--secondary"
                                        onClick={handleDetectSchema}
                                        disabled={detecting || !postUrl}
                                    >
                                        {detecting ? (
                                            <span className="spinner-small"></span>
                                        ) : (
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
                                                <circle cx="11" cy="11" r="8"/>
                                                <path d="M21 21l-4.35-4.35"/>
                                            </svg>
                                        )}
                                        Detect
                                    </button>
                                </div>
                            </div>

                            <div className="schema-types">
                                <h3>Or Choose Schema Type</h3>
                                <div className="schema-types-grid">
                                    {schemaTypes.map(type => (
                                        <button
                                            key={type.id}
                                            type="button"
                                            className="schema-type-card"
                                            onClick={() => handleTypeSelect(type.id)}
                                        >
                                            <span className="schema-type-icon">{type.icon}</span>
                                            <span className="schema-type-name">{type.name}</span>
                                            <span className="schema-type-desc">{type.description}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </>
                    ) : (
                        <>
                            <div className="schema-form-header">
                                <button
                                    type="button"
                                    className="back-button"
                                    onClick={() => {
                                        setSelectedType(null);
                                        setFormData({});
                                        setGeneratedSchema(null);
                                    }}
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
                                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                                    </svg>
                                    Back
                                </button>
                                <h3>
                                    {schemaTypes.find(t => t.id === selectedType)?.icon}{' '}
                                    {schemaTypes.find(t => t.id === selectedType)?.name} Schema
                                </h3>
                            </div>

                            <div className="schema-form">
                                {schemaFields[selectedType]?.map(field => (
                                    <div key={field.key} className="form-field">
                                        <label>
                                            {field.label}
                                            {field.required && <span className="required">*</span>}
                                        </label>
                                        {renderField(field)}
                                    </div>
                                ))}

                                <div className="form-actions">
                                    <button
                                        type="button"
                                        className="button button--primary"
                                        onClick={handleGenerateSchema}
                                        disabled={generating}
                                    >
                                        {generating ? (
                                            <>
                                                <span className="spinner"></span>
                                                Generating...
                                            </>
                                        ) : (
                                            <>
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
                                                    <path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/>
                                                    <rect x="8" y="2" width="8" height="4" rx="1"/>
                                                </svg>
                                                Generate Schema
                                            </>
                                        )}
                                    </button>
                                </div>
                            </div>
                        </>
                    )}
                </div>

                {generatedSchema && (
                    <div className="schema-builder-preview">
                        <div className="preview-header">
                            <h3>Generated Schema</h3>
                            <div className="preview-actions">
                                <button
                                    type="button"
                                    className="button button--small"
                                    onClick={handleValidate}
                                    disabled={validating}
                                >
                                    {validating ? 'Validating...' : 'Validate'}
                                </button>
                                <button
                                    type="button"
                                    className="button button--small button--primary"
                                    onClick={handleCopySchema}
                                >
                                    {copied ? 'Copied!' : 'Copy'}
                                </button>
                            </div>
                        </div>

                        {validation && (
                            <div className={`validation-result ${validation.valid ? 'valid' : 'invalid'}`}>
                                {validation.valid ? (
                                    <>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
                                            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                            <polyline points="22 4 12 14.01 9 11.01"/>
                                        </svg>
                                        Valid schema markup
                                    </>
                                ) : (
                                    <>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
                                            <circle cx="12" cy="12" r="10"/>
                                            <line x1="12" y1="8" x2="12" y2="12"/>
                                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                                        </svg>
                                        {validation.errors?.length || 0} issues found
                                    </>
                                )}
                            </div>
                        )}

                        {validation?.errors?.length > 0 && (
                            <ul className="validation-errors">
                                {validation.errors.map((err, idx) => (
                                    <li key={idx}>{err}</li>
                                ))}
                            </ul>
                        )}

                        <pre className="schema-code">
                            <code>{JSON.stringify(generatedSchema, null, 2)}</code>
                        </pre>

                        <div className="preview-footer">
                            <a
                                href={`https://search.google.com/test/rich-results?url=${encodeURIComponent('data:application/ld+json,' + JSON.stringify(generatedSchema))}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="button button--secondary"
                            >
                                Test in Google
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="14" height="14">
                                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                                    <polyline points="15 3 21 3 21 9"/>
                                    <line x1="10" y1="14" x2="21" y2="3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

// Sub-components for complex field types
const FAQListField = ({ value, onChange }) => {
    const addItem = () => {
        onChange([...value, { question: '', answer: '' }]);
    };

    const updateItem = (index, field, val) => {
        const newValue = [...value];
        newValue[index] = { ...newValue[index], [field]: val };
        onChange(newValue);
    };

    const removeItem = (index) => {
        onChange(value.filter((_, i) => i !== index));
    };

    return (
        <div className="list-field">
            {value.map((item, idx) => (
                <div key={idx} className="list-item faq-item">
                    <input
                        type="text"
                        value={item.question}
                        onChange={(e) => updateItem(idx, 'question', e.target.value)}
                        placeholder="Question"
                    />
                    <textarea
                        value={item.answer}
                        onChange={(e) => updateItem(idx, 'answer', e.target.value)}
                        placeholder="Answer"
                        rows={2}
                    />
                    <button type="button" className="remove-item" onClick={() => removeItem(idx)}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="14" height="14">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            ))}
            <button type="button" className="add-item" onClick={addItem}>
                + Add FAQ
            </button>
        </div>
    );
};

const StepsListField = ({ value, onChange }) => {
    const addItem = () => {
        onChange([...value, { name: '', text: '' }]);
    };

    const updateItem = (index, field, val) => {
        const newValue = [...value];
        newValue[index] = { ...newValue[index], [field]: val };
        onChange(newValue);
    };

    const removeItem = (index) => {
        onChange(value.filter((_, i) => i !== index));
    };

    return (
        <div className="list-field">
            {value.map((item, idx) => (
                <div key={idx} className="list-item step-item">
                    <span className="step-number">{idx + 1}</span>
                    <div className="step-fields">
                        <input
                            type="text"
                            value={item.name}
                            onChange={(e) => updateItem(idx, 'name', e.target.value)}
                            placeholder="Step title"
                        />
                        <textarea
                            value={item.text}
                            onChange={(e) => updateItem(idx, 'text', e.target.value)}
                            placeholder="Step description"
                            rows={2}
                        />
                    </div>
                    <button type="button" className="remove-item" onClick={() => removeItem(idx)}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="14" height="14">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            ))}
            <button type="button" className="add-item" onClick={addItem}>
                + Add Step
            </button>
        </div>
    );
};

const SimpleListField = ({ value, onChange }) => {
    const addItem = () => {
        onChange([...value, '']);
    };

    const updateItem = (index, val) => {
        const newValue = [...value];
        newValue[index] = val;
        onChange(newValue);
    };

    const removeItem = (index) => {
        onChange(value.filter((_, i) => i !== index));
    };

    return (
        <div className="list-field">
            {value.map((item, idx) => (
                <div key={idx} className="list-item simple-item">
                    <input
                        type="text"
                        value={item}
                        onChange={(e) => updateItem(idx, e.target.value)}
                        placeholder={`Item ${idx + 1}`}
                    />
                    <button type="button" className="remove-item" onClick={() => removeItem(idx)}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="14" height="14">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            ))}
            <button type="button" className="add-item" onClick={addItem}>
                + Add Item
            </button>
        </div>
    );
};

const BreadcrumbListField = ({ value, onChange }) => {
    const addItem = () => {
        onChange([...value, { name: '', url: '' }]);
    };

    const updateItem = (index, field, val) => {
        const newValue = [...value];
        newValue[index] = { ...newValue[index], [field]: val };
        onChange(newValue);
    };

    const removeItem = (index) => {
        onChange(value.filter((_, i) => i !== index));
    };

    return (
        <div className="list-field">
            {value.map((item, idx) => (
                <div key={idx} className="list-item breadcrumb-item">
                    <span className="breadcrumb-position">{idx + 1}</span>
                    <input
                        type="text"
                        value={item.name}
                        onChange={(e) => updateItem(idx, 'name', e.target.value)}
                        placeholder="Page name"
                    />
                    <input
                        type="url"
                        value={item.url}
                        onChange={(e) => updateItem(idx, 'url', e.target.value)}
                        placeholder="URL"
                    />
                    <button type="button" className="remove-item" onClick={() => removeItem(idx)}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="14" height="14">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
            ))}
            <button type="button" className="add-item" onClick={addItem}>
                + Add Breadcrumb
            </button>
        </div>
    );
};

export default SchemaBuilder;
