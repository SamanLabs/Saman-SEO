import { lazy, Suspense, useCallback, useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import Header from './components/Header';
import './index.css';

// Lazy load page components for better performance
const Dashboard = lazy(() => import('./pages/Dashboard'));
const SearchAppearance = lazy(() => import('./pages/SearchAppearance'));
const Sitemap = lazy(() => import('./pages/Sitemap'));
const Tools = lazy(() => import('./pages/Tools'));
const Redirects = lazy(() => import('./pages/Redirects'));
const Log404 = lazy(() => import('./pages/Log404'));
const InternalLinking = lazy(() => import('./pages/InternalLinking'));
const Audit = lazy(() => import('./pages/Audit'));
const AiAssistant = lazy(() => import('./pages/AiAssistant'));
const Assistants = lazy(() => import('./pages/Assistants'));
const Settings = lazy(() => import('./pages/Settings'));
const More = lazy(() => import('./pages/More'));
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

// Loading spinner for lazy-loaded components
const PageLoader = () => (
    <div className="page-loader">
        <div className="page-loader__spinner" />
    </div>
);

const viewToPage = {
    dashboard: 'wpseopilot-dashboard',
    'search-appearance': 'wpseopilot-search-appearance',
    sitemap: 'wpseopilot-sitemap',
    tools: 'wpseopilot-tools',
    redirects: 'wpseopilot-redirects',
    '404-log': 'wpseopilot-404-log',
    'internal-linking': 'wpseopilot-internal-linking',
    audit: 'wpseopilot-audit',
    'ai-assistant': 'wpseopilot-ai-assistant',
    assistants: 'wpseopilot-assistants',
    settings: 'wpseopilot-settings',
    more: 'wpseopilot-more',
    'bulk-editor': 'wpseopilot-bulk-editor',
    'content-gaps': 'wpseopilot-content-gaps',
    'schema-builder': 'wpseopilot-schema-builder',
    'link-health': 'wpseopilot-link-health',
    'local-seo': 'wpseopilot-local-seo',
    'robots-txt': 'wpseopilot-robots-txt',
    'image-seo': 'wpseopilot-image-seo',
    'instant-indexing': 'wpseopilot-instant-indexing',
    'schema-validator': 'wpseopilot-schema-validator',
    'htaccess-editor': 'wpseopilot-htaccess-editor',
    'mobile-friendly': 'wpseopilot-mobile-friendly',
};

const pageToView = Object.entries(viewToPage).reduce((acc, [view, page]) => {
    acc[page] = view;
    return acc;
}, {});

const App = ({ initialView = 'dashboard' }) => {
    const [currentView, setCurrentView] = useState(initialView);
    const [showSetup, setShowSetup] = useState(false);
    const [setupChecked, setSetupChecked] = useState(false);

    // Check setup status on mount
    useEffect(() => {
        const checkSetupStatus = async () => {
            try {
                const response = await apiFetch({ path: '/wpseopilot/v2/setup/status' });
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

        const menu = document.getElementById('toplevel_page_wpseopilot');
        if (!menu) {
            return;
        }

        const submenuLinks = menu.querySelectorAll('.wp-submenu a[href*="page=wpseopilot"]');
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

        menu.classList.add('current', 'wp-has-current-submenu');
    }, []);

    const handleNavigate = useCallback(
        (view) => {
            if (view === currentView) {
                return;
            }

            setCurrentView(view);
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

            const menu = document.getElementById('toplevel_page_wpseopilot');
            if (!menu || !menu.contains(link)) {
                return;
            }

            const href = link.getAttribute('href');
            if (!href || !href.includes('page=wpseopilot')) {
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
            <div className="wp-seo-pilot-admin">
                <div className="wp-seo-pilot-shell">
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
            <div className="wp-seo-pilot-admin">
                <Suspense fallback={<PageLoader />}>
                    <Setup onComplete={handleSetupComplete} onSkip={handleSetupSkip} />
                </Suspense>
            </div>
        );
    }

    return (
        <div className="wp-seo-pilot-admin">
            <div className="wp-seo-pilot-shell">
                <Header currentView={currentView} onNavigate={handleNavigate} />
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
