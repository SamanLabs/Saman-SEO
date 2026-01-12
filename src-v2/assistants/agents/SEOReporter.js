/**
 * SEO Reporter Assistant configuration.
 */
const SEOReporter = {
    id: 'seo-reporter',
    name: 'SEO Reporter',
    description: 'Your weekly SEO buddy that gives you the rundown on your site.',
    icon: (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
            <rect x="9" y="3" width="6" height="4" rx="1"/>
            <path d="M9 12h6M9 16h6"/>
        </svg>
    ),
    color: '#00a32a',
    initialMessage: "Hey! I can give you a quick rundown of your site's SEO health. Want me to take a look?",
    suggestedPrompts: [
        'Give me a quick SEO report',
        'What SEO issues should I fix first?',
        'Check my meta titles and descriptions',
        'Find posts missing SEO data',
    ],
};

export default SEOReporter;
