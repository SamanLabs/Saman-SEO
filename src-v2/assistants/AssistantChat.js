import { useState, useRef, useEffect } from '@wordpress/element';
import { useAssistant } from './AssistantProvider';
import AssistantMessage from './AssistantMessage';
import AssistantTyping from './AssistantTyping';

/**
 * Main chat interface component.
 */
const AssistantChat = ({ suggestedPrompts = [] }) => {
    const { messages, isLoading, error, sendMessage, executeAction } = useAssistant();
    const [input, setInput] = useState('');
    const messagesEndRef = useRef(null);
    const inputRef = useRef(null);

    // Auto-scroll to bottom when new messages arrive
    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages, isLoading]);

    // Focus input on mount
    useEffect(() => {
        inputRef.current?.focus();
    }, []);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (input.trim() && !isLoading) {
            sendMessage(input);
            setInput('');
        }
    };

    const handleSuggestedPrompt = (prompt) => {
        if (!isLoading) {
            sendMessage(prompt);
        }
    };

    const handleAction = (actionId) => {
        if (!isLoading) {
            executeAction(actionId);
        }
    };

    const handleKeyDown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSubmit(e);
        }
    };

    const showSuggestions = messages.length <= 1 && suggestedPrompts.length > 0;

    return (
        <div className="assistant-chat">
            <div className="assistant-chat__messages">
                {messages.map((message) => (
                    <AssistantMessage
                        key={message.id}
                        message={message.content}
                        isUser={message.role === 'user'}
                        actions={message.actions}
                        onAction={handleAction}
                    />
                ))}

                {isLoading && <AssistantTyping />}

                {error && (
                    <div className="assistant-chat__error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4m0 4h.01"/>
                        </svg>
                        <span>{error}</span>
                    </div>
                )}

                <div ref={messagesEndRef} />
            </div>

            {showSuggestions && (
                <div className="assistant-chat__suggestions">
                    <p className="assistant-chat__suggestions-label">Try asking:</p>
                    <div className="assistant-chat__suggestions-list">
                        {suggestedPrompts.map((prompt, index) => (
                            <button
                                key={index}
                                type="button"
                                className="assistant-chat__suggestion"
                                onClick={() => handleSuggestedPrompt(prompt)}
                                disabled={isLoading}
                            >
                                {prompt}
                            </button>
                        ))}
                    </div>
                </div>
            )}

            <form className="assistant-chat__input-form" onSubmit={handleSubmit}>
                <div className="assistant-chat__input-wrapper">
                    <textarea
                        ref={inputRef}
                        className="assistant-chat__input"
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        onKeyDown={handleKeyDown}
                        placeholder="Type your message..."
                        disabled={isLoading}
                        rows={1}
                    />
                    <button
                        type="submit"
                        className="assistant-chat__send-btn"
                        disabled={!input.trim() || isLoading}
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    );
};

export default AssistantChat;
