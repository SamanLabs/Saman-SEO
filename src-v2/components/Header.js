const navItems = [
    { id: 'dashboard', label: 'Dashboard' },
    { id: 'search-appearance', label: 'Search Appearance' },
    { id: 'sitemap', label: 'Sitemap' },
    { id: 'tools', label: 'Tools' },
    { id: 'settings', label: 'Settings' },
    { id: 'more', label: 'More' },
];

const Header = ({ currentView, onNavigate }) => {
    return (
        <header className="top-bar">
            <div className="brand">
                <span className="brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" role="img" focusable="false" preserveAspectRatio="xMidYMid meet">
                        <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                    </svg>
                </span>
                <span className="brand-name">Saman SEO</span>
            </div>
            <nav className="main-nav" aria-label="Primary">
                {navItems.map((item) => (
                    <button
                        key={item.id}
                        type="button"
                        className={`nav-tab ${currentView === item.id ? 'is-active' : ''}`}
                        aria-current={currentView === item.id ? 'page' : undefined}
                        onClick={() => onNavigate(item.id)}
                    >
                        {item.label}
                    </button>
                ))}
            </nav>
            <div className="nav-actions">
                <a
                    className="icon-button"
                    href="https://github.com/Saman/Saman-SEO"
                    target="_blank"
                    rel="noreferrer"
                    aria-label="Open GitHub repository"
                >
                    <svg viewBox="0 0 24 24" role="img" focusable="false">
                        <path d="M12 2C6.5 2 2 6.6 2 12.3c0 4.6 2.9 8.5 6.9 9.9.5.1.7-.2.7-.5v-1.9c-2.8.6-3.3-1.2-3.3-1.2-.5-1.2-1.2-1.5-1.2-1.5-1-.7.1-.7.1-.7 1.1.1 1.7 1.2 1.7 1.2 1 .1.7 1.7 2.6 1.2.1-.8.4-1.2.7-1.5-2.2-.2-4.5-1.2-4.5-5.2 0-1.1.4-2 1-2.7-.1-.2-.4-1.3.1-2.7 0 0 .8-.2 2.7 1a9.2 9.2 0 0 1 4.9 0c1.9-1.2 2.7-1 2.7-1 .5 1.4.2 2.5.1 2.7.6.7 1 1.6 1 2.7 0 4-2.3 5-4.5 5.2.4.3.8 1 .8 2.1v3c0 .3.2.6.7.5 4-1.4 6.9-5.3 6.9-9.9C22 6.6 17.5 2 12 2z" />
                    </svg>
                </a>
            </div>
        </header>
    );
};

export default Header;
