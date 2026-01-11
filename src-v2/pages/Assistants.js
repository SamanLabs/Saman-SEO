import { useState, useEffect } from '@wordpress/element';
import { AssistantProvider, AssistantChat, assistants, getAssistantById } from '../assistants';

/**
 * Assistants page - Hub for AI assistants.
 */
const Assistants = ({ initialAssistant = null }) => {
    const [selectedAssistant, setSelectedAssistant] = useState(null);

    // Set initial assistant if provided
    useEffect(() => {
        if (initialAssistant) {
            const assistant = getAssistantById(initialAssistant);
            if (assistant) {
                setSelectedAssistant(assistant);
            }
        }
    }, [initialAssistant]);

    const handleSelectAssistant = (assistant) => {
        setSelectedAssistant(assistant);
    };

    const handleBack = () => {
        setSelectedAssistant(null);
    };

    // Show assistant selector if none selected
    if (!selectedAssistant) {
        return (
            <div className="page">
                <div className="page-header">
                    <div>
                        <h1>AI Assistants</h1>
                        <p>Choose an assistant to help with your SEO tasks.</p>
                    </div>
                </div>

                <div className="assistants-grid">
                    {assistants.map((assistant) => (
                        <button
                            key={assistant.id}
                            type="button"
                            className="assistant-card"
                            onClick={() => handleSelectAssistant(assistant)}
                        >
                            <div
                                className="assistant-card__icon"
                                style={{ backgroundColor: `${assistant.color}15`, color: assistant.color }}
                            >
                                {assistant.icon}
                            </div>
                            <div className="assistant-card__content">
                                <h3 className="assistant-card__name">{assistant.name}</h3>
                                <p className="assistant-card__desc">{assistant.description}</p>
                            </div>
                            <div className="assistant-card__arrow" style={{ color: assistant.color }}>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M9 18l6-6-6-6"/>
                                </svg>
                            </div>
                        </button>
                    ))}
                </div>
            </div>
        );
    }

    // Show chat interface for selected assistant
    return (
        <div className="page assistants-page">
            <div className="page-header page-header--with-back">
                <button type="button" className="back-button" onClick={handleBack}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    <span>All Assistants</span>
                </button>
                <div className="page-header__info">
                    <div
                        className="page-header__icon"
                        style={{ backgroundColor: `${selectedAssistant.color}15`, color: selectedAssistant.color }}
                    >
                        {selectedAssistant.icon}
                    </div>
                    <div>
                        <h1>{selectedAssistant.name}</h1>
                        <p>{selectedAssistant.description}</p>
                    </div>
                </div>
            </div>

            <div className="assistants-chat-container">
                <AssistantProvider
                    key={selectedAssistant.id}
                    assistantId={selectedAssistant.id}
                    initialMessage={selectedAssistant.initialMessage}
                >
                    <AssistantChat suggestedPrompts={selectedAssistant.suggestedPrompts} />
                </AssistantProvider>
            </div>
        </div>
    );
};

export default Assistants;
