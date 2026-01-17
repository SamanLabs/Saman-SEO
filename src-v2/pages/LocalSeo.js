import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const defaultLocation = {
    id: '',
    name: '',
    type: 'LocalBusiness',
    street: '',
    city: '',
    state: '',
    zip: '',
    country: '',
    phone: '',
    email: '',
    latitude: '',
    longitude: '',
    isPrimary: false,
    enabled: true,
};

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
];

const LocalSeo = () => {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [locations, setLocations] = useState([]);
    const [enableLocations, setEnableLocations] = useState(false);
    const [editingLocation, setEditingLocation] = useState(null);
    const [showForm, setShowForm] = useState(false);
    const [notice, setNotice] = useState(null);

    // Primary business info (single location mode)
    const [primaryBusiness, setPrimaryBusiness] = useState({
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
    });

    const loadSettings = useCallback(async () => {
        try {
            setLoading(true);
            const response = await apiFetch({ path: '/saman-seo/v1/settings' });
            if (response.success) {
                const data = response.data;

                // Primary business info
                setPrimaryBusiness({
                    name: data.local_business_name || '',
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
                });

                // Multi-location settings
                setEnableLocations(data.local_enable_locations === '1' || data.local_enable_locations === true);
                setLocations(Array.isArray(data.local_locations) ? data.local_locations : []);
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

    const saveSettings = async (settingsToSave) => {
        setSaving(true);
        try {
            await apiFetch({
                path: '/saman-seo/v1/settings',
                method: 'POST',
                data: settingsToSave,
            });
            setNotice({ type: 'success', message: 'Settings saved successfully.' });
        } catch (error) {
            console.error('Failed to save settings:', error);
            setNotice({ type: 'error', message: 'Failed to save settings.' });
        } finally {
            setSaving(false);
        }
    };

    const handlePrimaryChange = (field, value) => {
        setPrimaryBusiness(prev => ({ ...prev, [field]: value }));
    };

    const savePrimaryBusiness = async () => {
        await saveSettings({
            local_business_name: primaryBusiness.name,
            local_business_type: primaryBusiness.type,
            local_description: primaryBusiness.description,
            local_phone: primaryBusiness.phone,
            local_email: primaryBusiness.email,
            local_street: primaryBusiness.street,
            local_city: primaryBusiness.city,
            local_state: primaryBusiness.state,
            local_zip: primaryBusiness.zip,
            local_country: primaryBusiness.country,
            local_latitude: primaryBusiness.latitude,
            local_longitude: primaryBusiness.longitude,
            local_price_range: primaryBusiness.priceRange,
        });
    };

    const toggleMultiLocation = async () => {
        const newValue = !enableLocations;
        setEnableLocations(newValue);
        await saveSettings({ local_enable_locations: newValue ? '1' : '0' });
    };

    const handleEditLocation = (location) => {
        setEditingLocation({ ...location });
        setShowForm(true);
    };

    const handleAddLocation = () => {
        setEditingLocation({
            ...defaultLocation,
            id: `loc_${Date.now()}`,
        });
        setShowForm(true);
    };

    const handleSaveLocation = async () => {
        if (!editingLocation.name || !editingLocation.street || !editingLocation.city) {
            setNotice({ type: 'error', message: 'Name, street, and city are required.' });
            return;
        }

        let updatedLocations;
        const existingIndex = locations.findIndex(l => l.id === editingLocation.id);

        if (existingIndex >= 0) {
            updatedLocations = [...locations];
            updatedLocations[existingIndex] = editingLocation;
        } else {
            updatedLocations = [...locations, editingLocation];
        }

        // If this is set as primary, unset others
        if (editingLocation.isPrimary) {
            updatedLocations = updatedLocations.map(l => ({
                ...l,
                isPrimary: l.id === editingLocation.id,
            }));
        }

        setLocations(updatedLocations);
        await saveSettings({ local_locations: updatedLocations });
        setShowForm(false);
        setEditingLocation(null);
    };

    const handleDeleteLocation = async (locationId) => {
        if (!window.confirm('Are you sure you want to delete this location?')) {
            return;
        }

        const updatedLocations = locations.filter(l => l.id !== locationId);
        setLocations(updatedLocations);
        await saveSettings({ local_locations: updatedLocations });
    };

    const handleToggleLocation = async (locationId) => {
        const updatedLocations = locations.map(l =>
            l.id === locationId ? { ...l, enabled: !l.enabled } : l
        );
        setLocations(updatedLocations);
        await saveSettings({ local_locations: updatedLocations });
    };

    const handleSetPrimary = async (locationId) => {
        const updatedLocations = locations.map(l => ({
            ...l,
            isPrimary: l.id === locationId,
        }));
        setLocations(updatedLocations);
        await saveSettings({ local_locations: updatedLocations });
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
                    <p>Configure your business information for local search results and schema markup.</p>
                </div>
            </div>

            {notice && (
                <div className={`notice notice-${notice.type}`}>
                    <p>{notice.message}</p>
                    <button type="button" className="notice-dismiss" onClick={() => setNotice(null)}>
                        <span className="screen-reader-text">Dismiss</span>
                    </button>
                </div>
            )}

            {/* Primary Business Information */}
            <div className="card">
                <div className="card-header">
                    <h2>Business Information</h2>
                    <p>Primary business details used for LocalBusiness schema on your homepage.</p>
                </div>
                <div className="card-body">
                    <div className="form-grid">
                        <div className="form-field">
                            <label>Business Name</label>
                            <input
                                type="text"
                                value={primaryBusiness.name}
                                onChange={(e) => handlePrimaryChange('name', e.target.value)}
                                placeholder="Your Business Name"
                            />
                        </div>
                        <div className="form-field">
                            <label>Business Type</label>
                            <select
                                value={primaryBusiness.type}
                                onChange={(e) => handlePrimaryChange('type', e.target.value)}
                            >
                                {businessTypes.map(type => (
                                    <option key={type.value} value={type.value}>{type.label}</option>
                                ))}
                            </select>
                        </div>
                        <div className="form-field full-width">
                            <label>Description</label>
                            <textarea
                                value={primaryBusiness.description}
                                onChange={(e) => handlePrimaryChange('description', e.target.value)}
                                placeholder="Brief description of your business"
                                rows={3}
                            />
                        </div>
                        <div className="form-field">
                            <label>Phone</label>
                            <input
                                type="tel"
                                value={primaryBusiness.phone}
                                onChange={(e) => handlePrimaryChange('phone', e.target.value)}
                                placeholder="+1 (555) 123-4567"
                            />
                        </div>
                        <div className="form-field">
                            <label>Email</label>
                            <input
                                type="email"
                                value={primaryBusiness.email}
                                onChange={(e) => handlePrimaryChange('email', e.target.value)}
                                placeholder="contact@yourbusiness.com"
                            />
                        </div>
                        <div className="form-field">
                            <label>Street Address</label>
                            <input
                                type="text"
                                value={primaryBusiness.street}
                                onChange={(e) => handlePrimaryChange('street', e.target.value)}
                                placeholder="123 Main Street"
                            />
                        </div>
                        <div className="form-field">
                            <label>City</label>
                            <input
                                type="text"
                                value={primaryBusiness.city}
                                onChange={(e) => handlePrimaryChange('city', e.target.value)}
                                placeholder="New York"
                            />
                        </div>
                        <div className="form-field">
                            <label>State/Province</label>
                            <input
                                type="text"
                                value={primaryBusiness.state}
                                onChange={(e) => handlePrimaryChange('state', e.target.value)}
                                placeholder="NY"
                            />
                        </div>
                        <div className="form-field">
                            <label>ZIP/Postal Code</label>
                            <input
                                type="text"
                                value={primaryBusiness.zip}
                                onChange={(e) => handlePrimaryChange('zip', e.target.value)}
                                placeholder="10001"
                            />
                        </div>
                        <div className="form-field">
                            <label>Country</label>
                            <input
                                type="text"
                                value={primaryBusiness.country}
                                onChange={(e) => handlePrimaryChange('country', e.target.value)}
                                placeholder="US"
                            />
                        </div>
                        <div className="form-field">
                            <label>Price Range</label>
                            <select
                                value={primaryBusiness.priceRange}
                                onChange={(e) => handlePrimaryChange('priceRange', e.target.value)}
                            >
                                <option value="">Select...</option>
                                <option value="$">$ (Budget)</option>
                                <option value="$$">$$ (Moderate)</option>
                                <option value="$$$">$$$ (Expensive)</option>
                                <option value="$$$$">$$$$ (Luxury)</option>
                            </select>
                        </div>
                        <div className="form-field">
                            <label>Latitude</label>
                            <input
                                type="text"
                                value={primaryBusiness.latitude}
                                onChange={(e) => handlePrimaryChange('latitude', e.target.value)}
                                placeholder="40.7128"
                            />
                        </div>
                        <div className="form-field">
                            <label>Longitude</label>
                            <input
                                type="text"
                                value={primaryBusiness.longitude}
                                onChange={(e) => handlePrimaryChange('longitude', e.target.value)}
                                placeholder="-74.0060"
                            />
                        </div>
                    </div>
                    <div className="card-footer">
                        <button
                            className="button primary"
                            onClick={savePrimaryBusiness}
                            disabled={saving}
                        >
                            {saving ? 'Saving...' : 'Save Business Info'}
                        </button>
                    </div>
                </div>
            </div>

            {/* Multiple Locations */}
            <div className="card">
                <div className="card-header">
                    <div>
                        <h2>Multiple Locations</h2>
                        <p>Manage multiple business locations with individual schema output.</p>
                    </div>
                    <label className="toggle-switch">
                        <input
                            type="checkbox"
                            checked={enableLocations}
                            onChange={toggleMultiLocation}
                        />
                        <span className="toggle-slider"></span>
                    </label>
                </div>

                {enableLocations && (
                    <div className="card-body">
                        <div className="locations-header">
                            <button className="button primary" onClick={handleAddLocation}>
                                + Add Location
                            </button>
                        </div>

                        {locations.length === 0 ? (
                            <div className="empty-state">
                                <p>No locations added yet. Click "Add Location" to create your first location.</p>
                            </div>
                        ) : (
                            <div className="locations-list">
                                {locations.map(location => (
                                    <div
                                        key={location.id}
                                        className={`location-card ${location.enabled ? '' : 'disabled'} ${location.isPrimary ? 'primary' : ''}`}
                                    >
                                        <div className="location-info">
                                            <div className="location-header">
                                                <h3>{location.name}</h3>
                                                {location.isPrimary && (
                                                    <span className="badge primary">Primary</span>
                                                )}
                                                {!location.enabled && (
                                                    <span className="badge">Disabled</span>
                                                )}
                                            </div>
                                            <p className="location-address">
                                                {[location.street, location.city, location.state, location.zip]
                                                    .filter(Boolean)
                                                    .join(', ')}
                                            </p>
                                            {location.phone && (
                                                <p className="location-phone">{location.phone}</p>
                                            )}
                                        </div>
                                        <div className="location-actions">
                                            {!location.isPrimary && (
                                                <button
                                                    className="button ghost small"
                                                    onClick={() => handleSetPrimary(location.id)}
                                                    title="Set as primary"
                                                >
                                                    Set Primary
                                                </button>
                                            )}
                                            <button
                                                className="button ghost small"
                                                onClick={() => handleToggleLocation(location.id)}
                                            >
                                                {location.enabled ? 'Disable' : 'Enable'}
                                            </button>
                                            <button
                                                className="button ghost small"
                                                onClick={() => handleEditLocation(location)}
                                            >
                                                Edit
                                            </button>
                                            <button
                                                className="button ghost small danger"
                                                onClick={() => handleDeleteLocation(location.id)}
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Location Edit Modal */}
            {showForm && editingLocation && (
                <div className="modal-overlay" onClick={() => setShowForm(false)}>
                    <div className="modal" onClick={e => e.stopPropagation()}>
                        <div className="modal-header">
                            <h2>{editingLocation.id && locations.find(l => l.id === editingLocation.id) ? 'Edit Location' : 'Add Location'}</h2>
                            <button className="modal-close" onClick={() => setShowForm(false)}>&times;</button>
                        </div>
                        <div className="modal-body">
                            <div className="form-grid">
                                <div className="form-field">
                                    <label>Location Name *</label>
                                    <input
                                        type="text"
                                        value={editingLocation.name}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, name: e.target.value }))}
                                        placeholder="Downtown Office"
                                    />
                                </div>
                                <div className="form-field">
                                    <label>Business Type</label>
                                    <select
                                        value={editingLocation.type}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, type: e.target.value }))}
                                    >
                                        {businessTypes.map(type => (
                                            <option key={type.value} value={type.value}>{type.label}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="form-field">
                                    <label>Street Address *</label>
                                    <input
                                        type="text"
                                        value={editingLocation.street}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, street: e.target.value }))}
                                        placeholder="456 Oak Avenue"
                                    />
                                </div>
                                <div className="form-field">
                                    <label>City *</label>
                                    <input
                                        type="text"
                                        value={editingLocation.city}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, city: e.target.value }))}
                                        placeholder="New York"
                                    />
                                </div>
                                <div className="form-field">
                                    <label>State/Province</label>
                                    <input
                                        type="text"
                                        value={editingLocation.state}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, state: e.target.value }))}
                                        placeholder="NY"
                                    />
                                </div>
                                <div className="form-field">
                                    <label>ZIP/Postal Code</label>
                                    <input
                                        type="text"
                                        value={editingLocation.zip}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, zip: e.target.value }))}
                                        placeholder="10001"
                                    />
                                </div>
                                <div className="form-field">
                                    <label>Country</label>
                                    <input
                                        type="text"
                                        value={editingLocation.country}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, country: e.target.value }))}
                                        placeholder="US"
                                    />
                                </div>
                                <div className="form-field">
                                    <label>Phone</label>
                                    <input
                                        type="tel"
                                        value={editingLocation.phone}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, phone: e.target.value }))}
                                        placeholder="+1 (555) 123-4567"
                                    />
                                </div>
                                <div className="form-field">
                                    <label>Email</label>
                                    <input
                                        type="email"
                                        value={editingLocation.email}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, email: e.target.value }))}
                                        placeholder="branch@business.com"
                                    />
                                </div>
                                <div className="form-field">
                                    <label>Latitude</label>
                                    <input
                                        type="text"
                                        value={editingLocation.latitude}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, latitude: e.target.value }))}
                                        placeholder="40.7128"
                                    />
                                </div>
                                <div className="form-field">
                                    <label>Longitude</label>
                                    <input
                                        type="text"
                                        value={editingLocation.longitude}
                                        onChange={(e) => setEditingLocation(prev => ({ ...prev, longitude: e.target.value }))}
                                        placeholder="-74.0060"
                                    />
                                </div>
                                <div className="form-field full-width">
                                    <label className="checkbox-label">
                                        <input
                                            type="checkbox"
                                            checked={editingLocation.isPrimary}
                                            onChange={(e) => setEditingLocation(prev => ({ ...prev, isPrimary: e.target.checked }))}
                                        />
                                        Set as primary location
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button className="button ghost" onClick={() => setShowForm(false)}>
                                Cancel
                            </button>
                            <button className="button primary" onClick={handleSaveLocation} disabled={saving}>
                                {saving ? 'Saving...' : 'Save Location'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default LocalSeo;
