/**
 * General SEO Assistant configuration.
 */
const GeneralSEO = {
    id: 'general-seo',
    name: 'SEO Assistant',
    description: 'Your helpful SEO buddy for all things search optimization.',
    icon: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="M21 21l-4.35-4.35"/>
            <path d="M11 8v6M8 11h6"/>
        </svg>
    ),
    color: '#2271b1',
    initialMessage: "Hey! I'm your SEO assistant. Ask me about meta tags, keywords, content optimization, or anything SEO-related.",
    suggestedPrompts: [
        'How do I write a good meta description?',
        'What makes a title tag effective?',
        'Help me find keywords for my blog post',
        'What are internal links and why do they matter?',
    ],
};

export default GeneralSEO;
