/**
 * Single message component for assistant chat.
 */
const AssistantMessage = ({ message, isUser, actions, onAction }) => {
    // Simple markdown-like formatting (bold, lists)
    const formatMessage = (text) => {
        if (!text) return '';

        // Split into lines
        const lines = text.split('\n');
        const formatted = [];
        let inList = false;

        lines.forEach((line, index) => {
            // Bold text: **text** or __text__
            let processedLine = line.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            processedLine = processedLine.replace(/__(.*?)__/g, '<strong>$1</strong>');

            // Check for list items
            const listMatch = line.match(/^[-*]\s+(.*)$/);
            if (listMatch) {
                if (!inList) {
                    formatted.push('<ul class="assistant-message__list">');
                    inList = true;
                }
                formatted.push(`<li>${processedLine.replace(/^[-*]\s+/, '')}</li>`);
            } else {
                if (inList) {
                    formatted.push('</ul>');
                    inList = false;
                }
                if (processedLine.trim()) {
                    formatted.push(`<p>${processedLine}</p>`);
                } else if (index < lines.length - 1) {
                    // Keep empty lines as breaks
                    formatted.push('<br/>');
                }
            }
        });

        if (inList) {
            formatted.push('</ul>');
        }

        return formatted.join('');
    };

    return (
        <div className={`assistant-message ${isUser ? 'assistant-message--user' : 'assistant-message--assistant'}`}>
            <div className="assistant-message__content">
                {isUser ? (
                    <p>{message}</p>
                ) : (
                    <div
                        className="assistant-message__text"
                        dangerouslySetInnerHTML={{ __html: formatMessage(message) }}
                    />
                )}
            </div>

            {!isUser && actions && actions.length > 0 && (
                <div className="assistant-message__actions">
                    {actions.map((action) => (
                        <button
                            key={action.id}
                            type="button"
                            className="assistant-message__action-btn"
                            onClick={() => onAction && onAction(action.id)}
                        >
                            {action.label}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
};

export default AssistantMessage;
