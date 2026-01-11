import { useCallback, useEffect, useState } from 'react';
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
import Settings from './pages/Settings';
import More from './pages/More';
import './index.css';

const viewToPage = {
    dashboard: 'wpseopilot-v2-dashboard',
    'search-appearance': 'wpseopilot-v2-search-appearance',
    sitemap: 'wpseopilot-v2-sitemap',
    tools: 'wpseopilot-v2-tools',
    redirects: 'wpseopilot-v2-redirects',
    '404-log': 'wpseopilot-v2-404-log',
    'internal-linking': 'wpseopilot-v2-internal-linking',
    audit: 'wpseopilot-v2-audit',
    'ai-assistant': 'wpseopilot-v2-ai-assistant',
    settings: 'wpseopilot-v2-settings',
    more: 'wpseopilot-v2-more',
};

const pageToView = Object.entries(viewToPage).reduce((acc, [view, page]) => {
    acc[page] = view;
    return acc;
}, {});

const App = ({ initialView = 'dashboard' }) => {
    const [currentView, setCurrentView] = useState(initialView);

    const updateAdminMenuHighlight = useCallback((view) => {
        if (typeof document === 'undefined') {
            return;
        }

        const menu = document.getElementById('toplevel_page_wpseopilot-v2');
        if (!menu) {
            return;
        }

        const submenuLinks = menu.querySelectorAll('.wp-submenu a[href*="page=wpseopilot-v2"]');
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

            const menu = document.getElementById('toplevel_page_wpseopilot-v2');
            if (!menu || !menu.contains(link)) {
                return;
            }

            const href = link.getAttribute('href');
            if (!href || !href.includes('page=wpseopilot-v2')) {
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
            case 'settings':
                return <Settings />;
            case 'more':
                return <More />;
            default:
                return <Dashboard />;
        }
    };

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
