import GeneralSEO from './GeneralSEO';
import SEOReporter from './SEOReporter';

/**
 * All available assistants.
 */
export const assistants = [GeneralSEO, SEOReporter];

/**
 * Get assistant by ID.
 */
export const getAssistantById = (id) => {
    return assistants.find((a) => a.id === id);
};

export { GeneralSEO, SEOReporter };
