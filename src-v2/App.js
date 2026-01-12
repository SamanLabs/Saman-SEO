import { useCallback, useEffect, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import Header from './components/Header';
import Dashboard from './pages/Dashboard';
import SearchAppearance from './pages/SearchAppearance';
import Sitemap from './pages/Sitemap';
import Tools from './pages/Tools';
import Redirects from './pages/Redirects';
import Log404 from './pages/Log404';
import InternalLinking from './pages/InternalLinking';
import Audit from './pages/Audit';
import AiAssistant from './pages/AiAssistant';
import Assistants from './pages/Assistants';
import Settings from './pages/Settings';
import More from './pages/More';
import Setup from './pages/Setup';
import BulkEditor from './pages/BulkEditor';
import ContentGaps from './pages/ContentGaps';
import SchemaBuilder from './pages/SchemaBuilder';
import './index.css';

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
                <Setup onComplete={handleSetupComplete} onSkip={handleSetupSkip} />
            </div>
        );
    }

    return (
        <div className="wp-seo-pilot-admin">
            <div className="wp-seo-pilot-shell">
                <Header currentView={currentView} onNavigate={handleNavigate} />
                <div className="content-area">
                    {renderView()}
                </div>
            </div>
        </div>
    );
};

export default App;
