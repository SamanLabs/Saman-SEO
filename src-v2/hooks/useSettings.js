/**
 * useSettings Hook - Manages plugin settings state
 *
 * @package WPSEOPilot
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Hook for fetching and updating plugin settings.
 */
export function useSettings() {
    const [settings, setSettings] = useState({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);

    /**
     * Fetch all settings from the REST API.
     */
    const fetchSettings = useCallback(async () => {
        try {
            setLoading(true);
            const response = await apiFetch({
                path: '/saman-seo/v1/settings',
            });
            setSettings(response.data || {});
            setError(null);
        } catch (err) {
            console.error('Failed to fetch settings:', err);
            setError(err.message || 'Failed to fetch settings');
        } finally {
            setLoading(false);
        }
    }, []);

    /**
     * Save multiple settings at once.
     *
     * @param {Object} newSettings - Object with setting key-value pairs.
     * @returns {boolean} - True if save was successful.
     */
    const saveSettings = useCallback(async (newSettings) => {
        try {
            setSaving(true);
            await apiFetch({
                path: '/saman-seo/v1/settings',
                method: 'POST',
                data: newSettings,
            });
            setSettings(prev => ({ ...prev, ...newSettings }));
            setError(null);
            return true;
        } catch (err) {
            console.error('Failed to save settings:', err);
            setError(err.message || 'Failed to save settings');
            return false;
        } finally {
            setSaving(false);
        }
    }, []);

    /**
     * Save a single setting.
     *
     * @param {string} key - Setting key.
     * @param {any} value - Setting value.
     * @returns {boolean} - True if save was successful.
     */
    const saveSetting = useCallback(async (key, value) => {
        try {
            setSaving(true);
            await apiFetch({
                path: `/saman-seo/v1/settings/${key}`,
                method: 'PUT',
                data: { value },
            });
            setSettings(prev => ({ ...prev, [key]: value }));
            setError(null);
            return true;
        } catch (err) {
            console.error(`Failed to save setting ${key}:`, err);
            setError(err.message || 'Failed to save setting');
            return false;
        } finally {
            setSaving(false);
        }
    }, []);

    /**
     * Refresh settings from server.
     */
    const refresh = useCallback(() => {
        fetchSettings();
    }, [fetchSettings]);

    // Fetch settings on mount
    useEffect(() => {
        fetchSettings();
    }, [fetchSettings]);

    return {
        settings,
        loading,
        saving,
        error,
        fetchSettings,
        saveSettings,
        saveSetting,
        refresh,
    };
}
