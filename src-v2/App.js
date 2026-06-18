import { lazy, Suspense, useCallback, useEffect, useState, useTransition } from 'react';
import apiFetch from '@wordpress/api-fetch';
import Header from './components/Header';
import './index.css';

// Eager-load the primary nav views so top-level navigation feels instant.
import Dashboard from './pages/Dashboard';
import Tools from './pages/Tools';
import Settings from './pages/Settings';
import More from './pages/More';

// Lazy load secondary / heavy page components
const SearchAppearance = lazy(() => import('./pages/SearchAppearance'));
const Sitemap = lazy(() => import('./pages/Sitemap'));
const Redirects = lazy(() => import('./pages/Redirects'));
const Log404 = lazy(() => import('./pages/Log404'));
const InternalLinking = lazy(() => import('./pages/InternalLinking'));
const Audit = lazy(() => import('./pages/Audit'));
const AiAssistant = lazy(() => import('./pages/AiAssistant'));
const Assistants = lazy(() => import('./pages/Assistants'));
const Setup = lazy(() => import('./pages/Setup'));
const BulkEditor = lazy(() => import('./pages/BulkEditor'));
const ContentGaps = lazy(() => import('./pages/ContentGaps'));
const SchemaBuilder = lazy(() => import('./pages/SchemaBuilder'));
const LinkHealth = lazy(() => import('./pages/LinkHealth'));
const LocalSeo = lazy(() => import('./pages/LocalSeo'));
const RobotsTxt = lazy(() => import('./pages/RobotsTxt'));
const ImageSeo = lazy(() => import('./pages/ImageSeo'));
const InstantIndexing = lazy(() => import('./pages/InstantIndexing'));
const SchemaValidator = lazy(() => import('./pages/SchemaValidator'));
const HtaccessEditor = lazy(() => import('./pages/HtaccessEditor'));
const MobileFriendly = lazy(() => import('./pages/MobileFriendly'));

// Component prefetch registry: maps view id to its lazy import factory.
const prefetchRegistry = {
    'search-appearance': () => import('./pages/SearchAppearance'),
    sitemap: () => import('./pages/Sitemap'),
    redirects: () => import('./pages/Redirects'),
    '404-log': () => import('./pages/Log404'),
    'internal-linking': () => import('./pages/InternalLinking'),
    audit: () => import('./pages/Audit'),
    'ai-assistant': () => import('./pages/AiAssistant'),
    assistants: () => import('./pages/Assistants'),
    setup: () => import('./pages/Setup'),
    'bulk-editor': () => import('./pages/BulkEditor'),
    'content-gaps': () => import('./pages/ContentGaps'),
    'schema-builder': () => import('./pages/SchemaBuilder'),
    'link-health': () => import('./pages/LinkHealth'),
    'local-seo': () => import('./pages/LocalSeo'),
    'robots-txt': () => import('./pages/RobotsTxt'),
    'image-seo': () => import('./pages/ImageSeo'),
    'instant-indexing': () => import('./pages/InstantIndexing'),
    'schema-validator': () => import('./pages/SchemaValidator'),
    'htaccess-editor': () => import('./pages/HtaccessEditor'),
    'mobile-friendly': () => import('./pages/MobileFriendly'),
};

// Loading spinner for lazy-loaded components
const PageLoader = () => (
    <div className="page-loader">
        <div className="page-loader__spinner" />
    </div>
);

const viewToPage = {
    dashboard: 'saman-seo-dashboard',
    'search-appearance': 'saman-seo-search-appearance',
    sitemap: 'saman-seo-sitemap',
    tools: 'saman-seo-tools',
    redirects: 'saman-seo-redirects',
    '404-log': 'saman-seo-404-log',
    'internal-linking': 'saman-seo-internal-linking',
    audit: 'saman-seo-audit',
    'ai-assistant': 'saman-seo-ai-assistant',
    assistants: 'saman-seo-assistants',
    settings: 'saman-seo-settings',
    more: 'saman-seo-more',
    'bulk-editor': 'saman-seo-bulk-editor',
    'content-gaps': 'saman-seo-content-gaps',
    'schema-builder': 'saman-seo-schema-builder',
    'link-health': 'saman-seo-link-health',
    'local-seo': 'saman-seo-local-seo',
    'robots-txt': 'saman-seo-robots-txt',
    'image-seo': 'saman-seo-image-seo',
    'instant-indexing': 'saman-seo-instant-indexing',
    'schema-validator': 'saman-seo-schema-validator',
    'htaccess-editor': 'saman-seo-htaccess-editor',
    'mobile-friendly': 'saman-seo-mobile-friendly',
};

const pageToView = Object.entries(viewToPage).reduce((acc, [view, page]) => {
    acc[page] = view;
    return acc;
}, {});

const App = ({ initialView = 'dashboard' }) => {
    const [currentView, setCurrentView] = useState(initialView);
    const [showSetup, setShowSetup] = useState(false);
    const [setupChecked, setSetupChecked] = useState(false);
    const [, startTransition] = useTransition();

    // Check setup status on mount
    useEffect(() => {
        const checkSetupStatus = async () => {
            try {
                const response = await apiFetch({ path: '/saman-seo/v1/setup/status' });
                if (response.success && response.data.show_wizard) {
                    setShowSetup(true);
                }
            } catch (err) {
                // Ignore errors, just show the app
            }
            setSetupChecked(true);
        };

        checkSetupStatus();
    }, []);

    const handleSetupComplete = () => {
        setShowSetup(false);
        setCurrentView('dashboard');
    };

    const handleSetupSkip = () => {
        setShowSetup(false);
    };

    const updateAdminMenuHighlight = useCallback((view) => {
        if (typeof document === 'undefined') {
            return;
        }

        const menu = document.getElementById('toplevel_page_saman-seo');
        if (!menu) {
            return;
        }

        const submenuLinks = menu.querySelectorAll('.wp-submenu a[href*="page=saman-seo"]');
        submenuLinks.forEach((link) => {
            link.removeAttribute('aria-current');
            const listItem = link.closest('li');
            if (listItem) {
                listItem.classList.remove('current');
            }
        });

        const page = viewToPage[view] || viewToPage.dashboard;
        const activeLink = menu.querySelector(`.wp-submenu a[href*="page=${page}"]`);
        if (activeLink) {
            activeLink.setAttribute('aria-current', 'page');
            const listItem = activeLink.closest('li');
            if (listItem) {
                listItem.classList.add('current');
            }
        }

        menu.classList.remove('wp-not-current-submenu');
        menu.classList.add('current', 'wp-has-current-submenu');
    }, []);

    const handleNavigate = useCallback(
        (view) => {
            if (view === currentView) {
                return;
            }

            // Use a transition so React can keep the UI responsive while
            // lazy chunks load. The old view stays visible briefly instead of
            // snapping to a blank spinner.
            startTransition(() => {
                setCurrentView(view);
            });

            if (typeof window === 'undefined') {
                return;
            }
            const page = viewToPage[view] || viewToPage.dashboard;
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            url.searchParams.delete('tab');
            window.history.pushState({}, '', url.toString());
            updateAdminMenuHighlight(view);
        },
        [currentView, updateAdminMenuHighlight]
    );

    // Prefetch a view's chunk on hover so navigation feels instant.
    const handlePrefetch = useCallback((view) => {
        const importer = prefetchRegistry[view];
        if (importer && typeof importer === 'function') {
            try {
                importer();
            } catch (err) {
                // Prefetch is best-effort; ignore failures.
            }
        }
    }, []);

    useEffect(() => {
        const handlePopState = () => {
            const url = new URL(window.location.href);
            const page = url.searchParams.get('page');
            if (page && pageToView[page]) {
                setCurrentView(pageToView[page]);
            }
        };

        window.addEventListener('popstate', handlePopState);
        return () => window.removeEventListener('popstate', handlePopState);
    }, []);

    useEffect(() => {
        updateAdminMenuHighlight(currentView);
    }, [currentView, updateAdminMenuHighlight]);

    useEffect(() => {
        const handleMenuClick = (event) => {
            const link = event.target.closest('a');
            if (!link || typeof window === 'undefined') {
                return;
            }

            const menu = document.getElementById('toplevel_page_saman-seo');
            if (!menu || !menu.contains(link)) {
                return;
            }

            const href = link.getAttribute('href');
            if (!href || !href.includes('page=saman-seo')) {
                return;
            }

            const url = new URL(href, window.location.origin);
            const page = url.searchParams.get('page');
            if (!page || !pageToView[page]) {
                return;
            }

            event.preventDefault();
            handleNavigate(pageToView[page]);
        };

        document.addEventListener('click', handleMenuClick);
        return () => document.removeEventListener('click', handleMenuClick);
    }, [handleNavigate]);

    const renderView = () => {
        switch (currentView) {
            case 'search-appearance':
                return <SearchAppearance />;
            case 'sitemap':
                return <Sitemap />;
            case 'tools':
                return <Tools onNavigate={handleNavigate} />;
            case 'redirects':
                return <Redirects />;
            case '404-log':
                return <Log404 onNavigate={handleNavigate} />;
            case 'internal-linking':
                return <InternalLinking />;
            case 'audit':
                return <Audit />;
            case 'ai-assistant':
                return <AiAssistant />;
            case 'assistants':
                return <Assistants />;
            case 'settings':
                return <Settings />;
            case 'more':
                return <More />;
            case 'bulk-editor':
                return <BulkEditor onNavigate={handleNavigate} />;
            case 'content-gaps':
                return <ContentGaps onNavigate={handleNavigate} />;
            case 'schema-builder':
                return <SchemaBuilder onNavigate={handleNavigate} />;
            case 'link-health':
                return <LinkHealth onNavigate={handleNavigate} />;
            case 'local-seo':
                return <LocalSeo />;
            case 'robots-txt':
                return <RobotsTxt />;
            case 'image-seo':
                return <ImageSeo />;
            case 'instant-indexing':
                return <InstantIndexing onNavigate={handleNavigate} />;
            case 'schema-validator':
                return <SchemaValidator onNavigate={handleNavigate} />;
            case 'htaccess-editor':
                return <HtaccessEditor onNavigate={handleNavigate} />;
            case 'mobile-friendly':
                return <MobileFriendly onNavigate={handleNavigate} />;
            default:
                return <Dashboard onNavigate={handleNavigate} />;
        }
    };

    // Show loading while checking setup status
    if (!setupChecked) {
        return (
            <div className="saman-seo-admin">
                <div className="saman-seo-shell">
                    <div className="content-area">
                        <div className="loading-state">Loading...</div>
                    </div>
                </div>
            </div>
        );
    }

    // Show setup wizard if needed
    if (showSetup) {
        return (
            <div className="saman-seo-admin">
                <Suspense fallback={<PageLoader />}>
                    <Setup onComplete={handleSetupComplete} onSkip={handleSetupSkip} />
                </Suspense>
            </div>
        );
    }

    return (
        <div className="saman-seo-admin">
            <div className="saman-seo-shell">
                <Header currentView={currentView} onNavigate={handleNavigate} onPrefetch={handlePrefetch} />
                <div className="content-area">
                    <Suspense fallback={<PageLoader />}>
                        {renderView()}
                    </Suspense>
                </div>
            </div>
        </div>
    );
};

export default App;
