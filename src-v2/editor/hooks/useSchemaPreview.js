/**
 * Hook for fetching schema preview with debounce.
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Fetch JSON-LD preview from REST API with debounce.
 *
 * @param {number} postId      - The post ID to preview.
 * @param {string} schemaType  - The schema type to preview.
 * @param {Array}  dependencies - Additional dependencies to trigger refetch.
 * @returns {{ preview: object|null, loading: boolean, error: string|null }}
 */
const useSchemaPreview = (postId, schemaType, dependencies = []) => {
    const [preview, setPreview] = useState(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const timeoutRef = useRef(null);
    const abortControllerRef = useRef(null);

    useEffect(() => {
        // Don't fetch if no post ID (unsaved post)
        if (!postId) {
            setPreview(null);
            return;
        }

        // Clear previous timeout
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // Abort previous request
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
        }

        // Debounce: wait 500ms after last change
        timeoutRef.current = setTimeout(() => {
            setLoading(true);
            setError(null);

            abortControllerRef.current = new AbortController();

            apiFetch({
                path: `/saman-seo/v1/schema/preview/${postId}`,
                method: 'POST',
                data: { schema_type: schemaType || '' },
                signal: abortControllerRef.current.signal,
            })
                .then((response) => {
                    if (response.success) {
                        setPreview(response.data.json_ld);
                    } else {
                        setError(response.message || 'Failed to load preview');
                    }
                })
                .catch((err) => {
                    // Ignore abort errors
                    if (err.name !== 'AbortError') {
                        setError(err.message || 'Failed to load preview');
                    }
                })
                .finally(() => setLoading(false));
        }, 500);

        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
            if (abortControllerRef.current) {
                abortControllerRef.current.abort();
            }
        };
    }, [postId, schemaType, ...dependencies]);

    return { preview, loading, error };
};

export default useSchemaPreview;
