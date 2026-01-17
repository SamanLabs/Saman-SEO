import { createContext, useContext, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Assistant Context for managing chat state.
 */
const AssistantContext = createContext(null);

/**
 * Hook to use assistant context.
 */
export const useAssistant = () => {
    const context = useContext(AssistantContext);
    if (!context) {
        throw new Error('useAssistant must be used within an AssistantProvider');
    }
    return context;
};

/**
 * Assistant Provider component.
 */
export const AssistantProvider = ({ children, assistantId, initialMessage = '' }) => {
    const [messages, setMessages] = useState(() => {
        if (initialMessage) {
            return [{ id: 'initial', role: 'assistant', content: initialMessage, actions: [] }];
        }
        return [];
    });
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState(null);

    /**
     * Send a message to the assistant.
     */
    const sendMessage = useCallback(async (content, context = {}) => {
        if (!content.trim() || isLoading) return;

        // Add user message
        const userMessage = {
            id: `user-${Date.now()}`,
            role: 'user',
            content: content.trim(),
        };
        setMessages((prev) => [...prev, userMessage]);
        setIsLoading(true);
        setError(null);

        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/assistants/chat',
                method: 'POST',
                data: {
                    assistant: assistantId,
                    message: content.trim(),
                    context,
                },
            });

            if (response.success) {
                const assistantMessage = {
                    id: `assistant-${Date.now()}`,
                    role: 'assistant',
                    content: response.data.message,
                    actions: response.data.actions || [],
                    data: response.data.data,
                };
                setMessages((prev) => [...prev, assistantMessage]);
            } else {
                setError(response.message || 'Failed to get response');
            }
        } catch (err) {
            setError(err.message || 'An error occurred');
        } finally {
            setIsLoading(false);
        }
    }, [assistantId, isLoading]);

    /**
     * Execute an action.
     */
    const executeAction = useCallback(async (actionId, context = {}) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/assistants/action',
                method: 'POST',
                data: {
                    assistant: assistantId,
                    action: actionId,
                    context,
                },
            });

            if (response.success) {
                const assistantMessage = {
                    id: `assistant-${Date.now()}`,
                    role: 'assistant',
                    content: response.data.message,
                    actions: response.data.actions || [],
                    data: response.data.data,
                };
                setMessages((prev) => [...prev, assistantMessage]);
            } else {
                setError(response.message || 'Failed to execute action');
            }
        } catch (err) {
            setError(err.message || 'An error occurred');
        } finally {
            setIsLoading(false);
        }
    }, [assistantId]);

    /**
     * Clear chat history.
     */
    const clearChat = useCallback(() => {
        setMessages(initialMessage ? [{ id: 'initial', role: 'assistant', content: initialMessage, actions: [] }] : []);
        setError(null);
    }, [initialMessage]);

    const value = {
        messages,
        isLoading,
        error,
        sendMessage,
        executeAction,
        clearChat,
        assistantId,
    };

    return (
        <AssistantContext.Provider value={value}>
            {children}
        </AssistantContext.Provider>
    );
};

export default AssistantProvider;
