import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const businessTypes = [
    { value: 'LocalBusiness', label: 'Local Business (Generic)' },
    { value: 'Restaurant', label: 'Restaurant' },
    { value: 'Dentist', label: 'Dentist' },
    { value: 'Physician', label: 'Physician' },
    { value: 'MedicalClinic', label: 'Medical Clinic' },
    { value: 'Attorney', label: 'Attorney' },
    { value: 'RealEstateAgent', label: 'Real Estate Agent' },
    { value: 'Store', label: 'Store' },
    { value: 'AutoDealer', label: 'Auto Dealer' },
    { value: 'HairSalon', label: 'Hair Salon' },
    { value: 'BeautySalon', label: 'Beauty Salon' },
    { value: 'Plumber', label: 'Plumber' },
    { value: 'Electrician', label: 'Electrician' },
    { value: 'AccountingService', label: 'Accounting Service' },
    { value: 'FinancialService', label: 'Financial Service' },
    { value: 'InsuranceAgency', label: 'Insurance Agency' },
    { value: 'Hotel', label: 'Hotel' },
    { value: 'Bakery', label: 'Bakery' },
    { value: 'BarOrPub', label: 'Bar or Pub' },
    { value: 'CafeOrCoffeeShop', label: 'Cafe / Coffee Shop' },
    { value: 'Pharmacy', label: 'Pharmacy' },
    { value: 'SportsClub', label: 'Sports Club' },
    { value: 'HealthClub', label: 'Health Club / Gym' },
];

const days = [
    { key: 'monday', label: 'Monday', short: 'Mon' },
    { key: 'tuesday', label: 'Tuesday', short: 'Tue' },
    { key: 'wednesday', label: 'Wednesday', short: 'Wed' },
    { key: 'thursday', label: 'Thursday', short: 'Thu' },
    { key: 'friday', label: 'Friday', short: 'Fri' },
    { key: 'saturday', label: 'Saturday', short: 'Sat' },
    { key: 'sunday', label: 'Sunday', short: 'Sun' },
];

const defaultHours = days.reduce((acc, day) => {
    acc[day.key] = { enabled: false, open: '09:00', close: '17:00' };
    return acc;
}, {});

const hourPresets = [
    {
        id: '9-5-weekdays',
        label: '9-5 Mon-Fri',
        apply: () => {
            const hours = { ...defaultHours };
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].forEach(day => {
                hours[day] = { enabled: true, open: '09:00', close: '17:00' };
            });
            return hours;
        },
    },
    {
        id: '9-6-mon-sat',
        label: '9-6 Mon-Sat',
        apply: () => {
            const hours = { ...defaultHours };
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'].forEach(day => {
                hours[day] = { enabled: true, open: '09:00', close: '18:00' };
            });
            return hours;
        },
    },
    {
        id: '24-7',
        label: '24/7',
        apply: () => {
            const hours = {};
            days.forEach(day => {
                hours[day.key] = { enabled: true, open: '00:00', close: '23:59' };
            });
            return hours;
        },
    },
    {
        id: 'custom',
        label: 'Custom',
        apply: null,
    },
];

// Knowledge Panel Preview Component
const KnowledgePanelPreview = ({ business, hours, socialProfiles }) => {
    const addressParts = [business.street, business.city, business.state].filter(Boolean);
    const addressString = addressParts.join(', ') + (business.zip ? ' ' + business.zip : '');

    // Calculate if currently open
    const getHoursStatus = () => {
        if (!hours || Object.keys(hours).length === 0) {
            return { isOpen: false, text: 'Hours not set' };
        }

        const now = new Date();
        const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const currentDay = dayNames[now.getDay()];
        const currentTime = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

        const todayHours = hours[currentDay];
        if (!todayHours || !todayHours.enabled) {
            return { isOpen: false, text: 'Closed today' };
        }

        if (currentTime >= todayHours.open && currentTime <= todayHours.close) {
            const closeTime = new Date(`2000-01-01T${todayHours.close}`);
            return {
                isOpen: true,
                text: `Open · Closes ${closeTime.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`,
            };
        }

        const openTime = new Date(`2000-01-01T${todayHours.open}`);
        return {
            isOpen: false,
            text: `Closed · Opens ${openTime.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`,
        };
    };

    const hoursStatus = getHoursStatus();
    const businessTypeLabel = businessTypes.find(t => t.value === business.type)?.label || 'Local Business';

    // Parse social profiles
    const getSocialIcon = (url) => {
        if (!url) return null;
        if (url.includes('facebook.com')) return '📘';
        if (url.includes('twitter.com') || url.includes('x.com')) return '𝕏';
        if (url.includes('instagram.com')) return '📷';
        if (url.includes('linkedin.com')) return '💼';
        if (url.includes('youtube.com')) return '▶️';
        return '🔗';
    };

    const socialLinks = (socialProfiles || []).filter(Boolean).map(url => ({
        url,
        icon: getSocialIcon(url),
    }));

    return (
        <div className="knowledge-panel">
            <div className="knowledge-panel__header">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                <span>Knowledge Panel Preview</span>
            </div>

            {business.image ? (
                <div
                    className="knowledge-panel__cover has-image"
                    style={{ backgroundImage: `url(${business.image})` }}
                />
            ) : (
                <div className="knowledge-panel__cover">
                    <svg viewBox="0 0 24 24" width="32" height="32" fill="currentColor" opacity="0.3">
                        <rect x="3" y="3" width="18" height="18" rx="2" fill="none" stroke="currentColor" strokeWidth="2"/>
                        <circle cx="8.5" cy="8.5" r="1.5"/>
                        <path d="M21 15l-5-5L5 21"/>
                    </svg>
                </div>
            )}

            <div className="knowledge-panel__body">
                <div className="knowledge-panel__identity">
                    {business.logo ? (
                        <div
                            className="knowledge-panel__logo has-image"
                            style={{ backgroundImage: `url(${business.logo})` }}
                        />
                    ) : (
                        <div className="knowledge-panel__logo">
                            <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor" opacity="0.4">
                                <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    )}
                    <div className="knowledge-panel__info">
                        <h3 className={`knowledge-panel__name ${!business.name ? 'is-empty' : ''}`}>
                            {business.name || 'Business Name'}
                        </h3>
                        <p className="knowledge-panel__type">{businessTypeLabel}</p>
                    </div>
                </div>

                {business.description ? (
                    <p className="knowledge-panel__description">{business.description}</p>
                ) : (
                    <p className="knowledge-panel__description is-empty">
                        Add a description to tell customers about your business.
                    </p>
                )}

                <div className="knowledge-panel__details">
                    <div className={`knowledge-panel__detail ${!addressString ? 'is-empty' : ''}`}>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span>{addressString || 'No address set'}</span>
                    </div>

                    <div className={`knowledge-panel__detail ${!business.phone ? 'is-empty' : ''}`}>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                        </svg>
                        <span>{business.phone || 'No phone set'}</span>
                    </div>

                    <div className="knowledge-panel__detail">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span className={`knowledge-panel__hours-status ${hoursStatus.isOpen ? 'is-open' : 'is-closed'}`}>
                            {hoursStatus.text}
                        </span>
                    </div>

                    {business.priceRange && (
                        <div className="knowledge-panel__detail">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2">
                                <line x1="12" y1="1" x2="12" y2="23"/>
                                <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                            </svg>
                            <span className="knowledge-panel__price">{business.priceRange}</span>
                        </div>
                    )}
                </div>

                {socialLinks.length > 0 && (
                    <div className="knowledge-panel__social">
                        {socialLinks.map((social, idx) => (
                            <a key={idx} href={social.url} target="_blank" rel="noopener noreferrer" title={social.url}>
                                {social.icon}
                            </a>
                        ))}
                    </div>
                )}
            </div>

            <div className="knowledge-panel__actions">
                <a
                    href="https://search.google.com/test/rich-results"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="button ghost small"
                >
                    Test with Google
                </a>
            </div>
        </div>
    );
};

const LocalSeo = () => {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [activeTab, setActiveTab] = useState('business');
    const [notice, setNotice] = useState(null);

    // Business info state
    const [business, setBusiness] = useState({
        name: '',
        type: 'LocalBusiness',
        description: '',
        phone: '',
        email: '',
        street: '',
        city: '',
        state: '',
        zip: '',
        country: '',
        latitude: '',
        longitude: '',
        priceRange: '',
        logo: '',
        image: '',
    });

    // Opening hours state
    const [hours, setHours] = useState(defaultHours);
    const [activePreset, setActivePreset] = useState('custom');

    // Social profiles state
    const [socialProfiles, setSocialProfiles] = useState(['', '', '', '', '']);

    const loadSettings = useCallback(async () => {
        try {
            setLoading(true);
            const response = await apiFetch({ path: '/saman-seo/v1/settings' });
            if (response.success) {
                const data = response.data;

                setBusiness({
                    name: data.local_business_name || data.homepage_organization_name || '',
                    type: data.local_business_type || 'LocalBusiness',
                    description: data.local_description || '',
                    phone: data.local_phone || '',
                    email: data.local_email || '',
                    street: data.local_street || '',
                    city: data.local_city || '',
                    state: data.local_state || '',
                    zip: data.local_zip || '',
                    country: data.local_country || '',
                    latitude: data.local_latitude || '',
                    longitude: data.local_longitude || '',
                    priceRange: data.local_price_range || '',
                    logo: data.local_logo || data.homepage_organization_logo || '',
                    image: data.local_image || '',
                });

                // Load hours
                if (data.local_opening_hours) {
                    try {
                        const parsedHours = typeof data.local_opening_hours === 'string'
                            ? JSON.parse(data.local_opening_hours)
                            : data.local_opening_hours;
                        setHours({ ...defaultHours, ...parsedHours });
                    } catch (e) {
                        setHours(defaultHours);
                    }
                }

                // Load social profiles
                if (data.local_social_profiles) {
                    try {
                        const profiles = typeof data.local_social_profiles === 'string'
                            ? JSON.parse(data.local_social_profiles)
                            : data.local_social_profiles;
                        setSocialProfiles(Array.isArray(profiles) ? [...profiles, '', '', '', '', ''].slice(0, 5) : ['', '', '', '', '']);
                    } catch (e) {
                        setSocialProfiles(['', '', '', '', '']);
                    }
                }
            }
        } catch (error) {
            console.error('Failed to load settings:', error);
            setNotice({ type: 'error', message: 'Failed to load settings.' });
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadSettings();
    }, [loadSettings]);

    useEffect(() => {
        if (notice) {
            const timer = setTimeout(() => setNotice(null), 5000);
            return () => clearTimeout(timer);
        }
    }, [notice]);

    const saveSettings = async () => {
        setSaving(true);
        try {
            await apiFetch({
                path: '/saman-seo/v1/settings',
                method: 'POST',
                data: {
                    local_business_name: business.name,
                    local_business_type: business.type,
                    local_description: business.description,
                    local_phone: business.phone,
                    local_email: business.email,
                    local_street: business.street,
                    local_city: business.city,
                    local_state: business.state,
                    local_zip: business.zip,
                    local_country: business.country,
                    local_latitude: business.latitude,
                    local_longitude: business.longitude,
                    local_price_range: business.priceRange,
                    local_logo: business.logo,
                    local_image: business.image,
                    local_opening_hours: JSON.stringify(hours),
                    local_social_profiles: JSON.stringify(socialProfiles.filter(Boolean)),
                    // Sync with Knowledge Graph
                    homepage_organization_name: business.name,
                    homepage_organization_logo: business.logo,
                },
            });
            setNotice({ type: 'success', message: 'Settings saved successfully.' });
        } catch (error) {
            console.error('Failed to save settings:', error);
            setNotice({ type: 'error', message: 'Failed to save settings.' });
        } finally {
            setSaving(false);
        }
    };

    const handleBusinessChange = (field, value) => {
        setBusiness(prev => ({ ...prev, [field]: value }));
    };

    const handleHoursChange = (day, field, value) => {
        setHours(prev => ({
            ...prev,
            [day]: { ...prev[day], [field]: value },
        }));
        setActivePreset('custom');
    };

    const applyPreset = (preset) => {
        if (preset.apply) {
            setHours(preset.apply());
        }
        setActivePreset(preset.id);
    };

    const copyToWeekdays = (sourceDay) => {
        const sourceHours = hours[sourceDay];
        const weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        setHours(prev => {
            const updated = { ...prev };
            weekdays.forEach(day => {
                updated[day] = { ...sourceHours };
            });
            return updated;
        });
        setActivePreset('custom');
    };

    const handleSocialChange = (index, value) => {
        setSocialProfiles(prev => {
            const updated = [...prev];
            updated[index] = value;
            return updated;
        });
    };

    // Media library picker
    const openMediaLibrary = (field) => {
        if (window.wp && window.wp.media) {
            const frame = window.wp.media({
                title: field === 'logo' ? 'Select Logo' : 'Select Cover Image',
                button: { text: 'Use Image' },
                multiple: false,
            });
            frame.on('select', () => {
                const attachment = frame.state().get('selection').first().toJSON();
                handleBusinessChange(field, attachment.url);
            });
            frame.open();
        }
    };

    if (loading) {
        return (
            <div className="page">
                <div className="loading-state">
                    <span className="spinner is-active"></span>
                    <p>Loading Local SEO settings...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Local SEO</h1>
                    <p>Configure your business information for local search results and Google Knowledge Panel.</p>
                </div>
                <button className="button primary" onClick={saveSettings} disabled={saving}>
                    {saving ? 'Saving...' : 'Save Changes'}
                </button>
            </div>

            {notice && (
                <div className={`notice notice-${notice.type}`}>
                    <p>{notice.message}</p>
                    <button type="button" className="notice-dismiss" onClick={() => setNotice(null)}>
                        <span className="screen-reader-text">Dismiss</span>
                    </button>
                </div>
            )}

            <div className="local-seo-layout">
                {/* Main Content */}
                <div className="local-seo-main">
                    {/* Tabs */}
                    <div className="tabs">
                        <button
                            className={`tab ${activeTab === 'business' ? 'active' : ''}`}
                            onClick={() => setActiveTab('business')}
                        >
                            Business Information
                        </button>
                        <button
                            className={`tab ${activeTab === 'hours' ? 'active' : ''}`}
                            onClick={() => setActiveTab('hours')}
                        >
                            Opening Hours
                        </button>
                    </div>

                    {/* Business Information Tab */}
                    {activeTab === 'business' && (
                        <div className="tab-content">
                            {/* Business Identity */}
                            <div className="card">
                                <div className="card-header">
                                    <h2>Business Identity</h2>
                                </div>
                                <div className="card-body">
                                    <div className="form-grid">
                                        <div className="form-field">
                                            <label>Business Name</label>
                                            <input
                                                type="text"
                                                value={business.name}
                                                onChange={(e) => handleBusinessChange('name', e.target.value)}
                                                placeholder="Your Business Name"
                                            />
                                        </div>
                                        <div className="form-field">
                                            <label>Business Type</label>
                                            <select
                                                value={business.type}
                                                onChange={(e) => handleBusinessChange('type', e.target.value)}
                                            >
                                                {businessTypes.map(type => (
                                                    <option key={type.value} value={type.value}>{type.label}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div className="form-field full-width">
                                            <label>Description</label>
                                            <textarea
                                                value={business.description}
                                                onChange={(e) => handleBusinessChange('description', e.target.value)}
                                                placeholder="Brief description of your business"
                                                rows={3}
                                            />
                                        </div>
                                        <div className="form-field">
                                            <label>Logo</label>
                                            <div className="image-picker">
                                                {business.logo ? (
                                                    <div className="image-preview">
                                                        <img src={business.logo} alt="Logo" />
                                                        <button
                                                            className="image-remove"
                                                            onClick={() => handleBusinessChange('logo', '')}
                                                        >
                                                            ×
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <button
                                                        className="button ghost"
                                                        onClick={() => openMediaLibrary('logo')}
                                                    >
                                                        Select Logo
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                        <div className="form-field">
                                            <label>Cover Image</label>
                                            <div className="image-picker">
                                                {business.image ? (
                                                    <div className="image-preview">
                                                        <img src={business.image} alt="Cover" />
                                                        <button
                                                            className="image-remove"
                                                            onClick={() => handleBusinessChange('image', '')}
                                                        >
                                                            ×
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <button
                                                        className="button ghost"
                                                        onClick={() => openMediaLibrary('image')}
                                                    >
                                                        Select Image
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                        <div className="form-field">
                                            <label>Price Range</label>
                                            <select
                                                value={business.priceRange}
                                                onChange={(e) => handleBusinessChange('priceRange', e.target.value)}
                                            >
                                                <option value="">Select...</option>
                                                <option value="$">$ (Budget)</option>
                                                <option value="$$">$$ (Moderate)</option>
                                                <option value="$$$">$$$ (Expensive)</option>
                                                <option value="$$$$">$$$$ (Luxury)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Contact Information */}
                            <div className="card">
                                <div className="card-header">
                                    <h2>Contact Information</h2>
                                </div>
                                <div className="card-body">
                                    <div className="form-grid">
                                        <div className="form-field">
                                            <label>Phone</label>
                                            <input
                                                type="tel"
                                                value={business.phone}
                                                onChange={(e) => handleBusinessChange('phone', e.target.value)}
                                                placeholder="+1 (555) 123-4567"
                                            />
                                        </div>
                                        <div className="form-field">
                                            <label>Email</label>
                                            <input
                                                type="email"
                                                value={business.email}
                                                onChange={(e) => handleBusinessChange('email', e.target.value)}
                                                placeholder="contact@yourbusiness.com"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Address */}
                            <div className="card">
                                <div className="card-header">
                                    <h2>Address</h2>
                                </div>
                                <div className="card-body">
                                    <div className="form-grid">
                                        <div className="form-field full-width">
                                            <label>Street Address</label>
                                            <input
                                                type="text"
                                                value={business.street}
                                                onChange={(e) => handleBusinessChange('street', e.target.value)}
                                                placeholder="123 Main Street"
                                            />
                                        </div>
                                        <div className="form-field">
                                            <label>City</label>
                                            <input
                                                type="text"
                                                value={business.city}
                                                onChange={(e) => handleBusinessChange('city', e.target.value)}
                                                placeholder="New York"
                                            />
                                        </div>
                                        <div className="form-field">
                                            <label>State/Province</label>
                                            <input
                                                type="text"
                                                value={business.state}
                                                onChange={(e) => handleBusinessChange('state', e.target.value)}
                                                placeholder="NY"
                                            />
                                        </div>
                                        <div className="form-field">
                                            <label>ZIP/Postal Code</label>
                                            <input
                                                type="text"
                                                value={business.zip}
                                                onChange={(e) => handleBusinessChange('zip', e.target.value)}
                                                placeholder="10001"
                                            />
                                        </div>
                                        <div className="form-field">
                                            <label>Country</label>
                                            <input
                                                type="text"
                                                value={business.country}
                                                onChange={(e) => handleBusinessChange('country', e.target.value)}
                                                placeholder="US"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Geo Coordinates */}
                            <div className="card">
                                <div className="card-header">
                                    <h2>Geo Coordinates</h2>
                                    <p>Optional but recommended for precise location in search results.</p>
                                </div>
                                <div className="card-body">
                                    <div className="form-grid">
                                        <div className="form-field">
                                            <label>Latitude</label>
                                            <input
                                                type="text"
                                                value={business.latitude}
                                                onChange={(e) => handleBusinessChange('latitude', e.target.value)}
                                                placeholder="40.7128"
                                            />
                                        </div>
                                        <div className="form-field">
                                            <label>Longitude</label>
                                            <input
                                                type="text"
                                                value={business.longitude}
                                                onChange={(e) => handleBusinessChange('longitude', e.target.value)}
                                                placeholder="-74.0060"
                                            />
                                        </div>
                                    </div>
                                    <p className="form-help">
                                        <a
                                            href={`https://www.google.com/maps/search/${encodeURIComponent([business.street, business.city, business.state, business.zip].filter(Boolean).join(', '))}`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Find coordinates on Google Maps →
                                        </a>
                                    </p>
                                </div>
                            </div>

                            {/* Social Profiles */}
                            <div className="card">
                                <div className="card-header">
                                    <h2>Social Profiles</h2>
                                    <p>Add your social media profile URLs for rich search results.</p>
                                </div>
                                <div className="card-body">
                                    <div className="social-profiles">
                                        {socialProfiles.map((url, index) => (
                                            <div key={index} className="form-field">
                                                <input
                                                    type="url"
                                                    value={url}
                                                    onChange={(e) => handleSocialChange(index, e.target.value)}
                                                    placeholder={`Social profile URL ${index + 1}`}
                                                />
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Opening Hours Tab */}
                    {activeTab === 'hours' && (
                        <div className="tab-content">
                            <div className="card">
                                <div className="card-header">
                                    <h2>Opening Hours</h2>
                                    <p>Set your business hours. These appear in search results and Google Knowledge Panel.</p>
                                </div>
                                <div className="card-body">
                                    {/* Presets */}
                                    <div className="hours-presets">
                                        <span className="presets-label">Quick presets:</span>
                                        {hourPresets.map(preset => (
                                            <button
                                                key={preset.id}
                                                className={`button ${activePreset === preset.id ? 'primary' : 'ghost'} small`}
                                                onClick={() => applyPreset(preset)}
                                            >
                                                {preset.label}
                                            </button>
                                        ))}
                                    </div>

                                    {/* Hours Grid */}
                                    <div className="hours-grid">
                                        {days.map(day => (
                                            <div key={day.key} className={`hours-row ${hours[day.key]?.enabled ? 'is-open' : 'is-closed'}`}>
                                                <label className="hours-toggle">
                                                    <input
                                                        type="checkbox"
                                                        checked={hours[day.key]?.enabled || false}
                                                        onChange={(e) => handleHoursChange(day.key, 'enabled', e.target.checked)}
                                                    />
                                                    <span className="toggle-track"></span>
                                                </label>
                                                <span className="hours-day">{day.label}</span>
                                                {hours[day.key]?.enabled ? (
                                                    <>
                                                        <input
                                                            type="time"
                                                            value={hours[day.key]?.open || '09:00'}
                                                            onChange={(e) => handleHoursChange(day.key, 'open', e.target.value)}
                                                            className="hours-time"
                                                        />
                                                        <span className="hours-separator">to</span>
                                                        <input
                                                            type="time"
                                                            value={hours[day.key]?.close || '17:00'}
                                                            onChange={(e) => handleHoursChange(day.key, 'close', e.target.value)}
                                                            className="hours-time"
                                                        />
                                                        {['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].includes(day.key) && (
                                                            <button
                                                                className="button ghost small"
                                                                onClick={() => copyToWeekdays(day.key)}
                                                                title="Copy to all weekdays"
                                                            >
                                                                Copy to weekdays
                                                            </button>
                                                        )}
                                                    </>
                                                ) : (
                                                    <span className="hours-closed">Closed</span>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                {/* Sidebar */}
                <div className="local-seo-sidebar">
                    <KnowledgePanelPreview
                        business={business}
                        hours={hours}
                        socialProfiles={socialProfiles}
                    />
                </div>
            </div>
        </div>
    );
};

export default LocalSeo;
