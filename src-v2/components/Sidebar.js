import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const COLLAPSE_KEY = 'saman_seo_sidebar_collapsed';

const icon = ( children ) => (
	<svg
		width="16"
		height="16"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="1.75"
		strokeLinecap="round"
		strokeLinejoin="round"
		aria-hidden="true"
		focusable="false"
	>
		{ children }
	</svg>
);

export const ICONS = {
	dashboard: icon(
		<>
			<rect x="3" y="3" width="7" height="9" rx="1.5" />
			<rect x="14" y="3" width="7" height="5" rx="1.5" />
			<rect x="14" y="12" width="7" height="9" rx="1.5" />
			<rect x="3" y="16" width="7" height="5" rx="1.5" />
		</>
	),
	'search-appearance': icon(
		<>
			<circle cx="11" cy="11" r="7" />
			<path d="M21 21l-4.3-4.3" />
		</>
	),
	sitemap: icon(
		<>
			<rect x="9" y="3" width="6" height="5" rx="1" />
			<rect x="3" y="16" width="6" height="5" rx="1" />
			<rect x="15" y="16" width="6" height="5" rx="1" />
			<path d="M12 8v4M6 16v-2a2 2 0 012-2h8a2 2 0 012 2v2" />
		</>
	),
	audit: icon(
		<>
			<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
			<rect x="9" y="3" width="6" height="4" rx="1" />
			<path d="M9 14l2 2 4-4" />
		</>
	),
	improvements: icon(
		<>
			<path d="M3 17l6-6 4 4 8-8" />
			<path d="M15 7h6v6" />
		</>
	),
	redirects: icon(
		<>
			<path d="M4 7h11a5 5 0 010 10H8" />
			<path d="M11 14l-3 3 3 3" />
		</>
	),
	'404-log': icon(
		<>
			<path d="M12 9v4M12 17h.01" />
			<path d="M10.3 3.9L2.4 17.5A2 2 0 004.1 20.5h15.8a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z" />
		</>
	),
	'link-health': icon(
		<>
			<path d="M10 13a5 5 0 007.1 0l3-3a5 5 0 00-7.1-7.1l-1.2 1.2" />
			<path d="M14 11a5 5 0 00-7.1 0l-3 3a5 5 0 007.1 7.1l1.2-1.2" />
		</>
	),
	'instant-indexing': icon( <path d="M13 2L4 14h7l-1 8 9-12h-7l1-8z" /> ),
	'bulk-editor': icon(
		<>
			<path d="M4 6h16M4 12h10M4 18h7" />
			<path d="M17 15l4 4-4 0 0-4z" />
		</>
	),
	'content-gaps': icon(
		<>
			<circle cx="12" cy="12" r="9" />
			<circle cx="12" cy="12" r="5" />
			<circle cx="12" cy="12" r="1" />
		</>
	),
	'internal-linking': icon(
		<>
			<circle cx="6" cy="6" r="3" />
			<circle cx="18" cy="18" r="3" />
			<path d="M8.5 8.5l7 7M18 6h-5M18 6v5" />
		</>
	),
	'image-seo': icon(
		<>
			<rect x="3" y="4" width="18" height="16" rx="2" />
			<circle cx="9" cy="10" r="1.5" />
			<path d="M21 16l-5-5-8 8" />
		</>
	),
	'local-seo': icon(
		<>
			<path d="M12 21s7-6.1 7-11a7 7 0 10-14 0c0 4.9 7 11 7 11z" />
			<circle cx="12" cy="10" r="2.5" />
		</>
	),
	'schema-builder': icon(
		<>
			<path d="M8 4H7a2 2 0 00-2 2v4l-2 2 2 2v4a2 2 0 002 2h1" />
			<path d="M16 4h1a2 2 0 012 2v4l2 2-2 2v4a2 2 0 01-2 2h-1" />
		</>
	),
	'schema-validator': icon(
		<>
			<path d="M12 3l8 3v6c0 4.5-3.2 8-8 9-4.800-1-8-4.500-8-9V6l8-3z" />
			<path d="M9 12l2 2 4-4" />
		</>
	),
	'robots-txt': icon(
		<>
			<path d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5z" />
			<path d="M14 3v5h5M9 13h6M9 17h4" />
		</>
	),
	'htaccess-editor': icon(
		<>
			<rect x="3" y="4" width="18" height="16" rx="2" />
			<path d="M7 10l3 2-3 2M13 15h4" />
		</>
	),
	'mobile-friendly': icon(
		<>
			<rect x="7" y="2" width="10" height="20" rx="2" />
			<path d="M11 18h2" />
		</>
	),
	'ai-assistant': icon(
		<>
			<path d="M12 3l1.8 4.7L18.500 9.500l-4.700 1.800L12 16l-1.800-4.700L5.500 9.500l4.700-1.800L12 3z" />
			<path d="M19 15v4M17 17h4" />
		</>
	),
	assistants: icon(
		<>
			<circle cx="9" cy="8" r="3.5" />
			<path d="M2.500 20a6.500 6.500 0 0113 0" />
			<path d="M16 4.500a3.500 3.500 0 010 7M18 14.500a6.500 6.500 0 013.500 5.500" />
		</>
	),
	tools: icon(
		<>
			<rect x="3" y="3" width="7" height="7" rx="1.5" />
			<rect x="14" y="3" width="7" height="7" rx="1.5" />
			<rect x="3" y="14" width="7" height="7" rx="1.5" />
			<rect x="14" y="14" width="7" height="7" rx="1.5" />
		</>
	),
	settings: icon(
		<>
			<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0" />
			<circle cx="16" cy="6" r="2" />
			<circle cx="10" cy="12" r="2" />
			<circle cx="18" cy="18" r="2" />
		</>
	),
	more: icon(
		<>
			<path d="M12 3l9 5-9 5-9-5 9-5z" />
			<path d="M3 13l9 5 9-5" />
		</>
	),
};

// Every page of the app, grouped by the job it does.
const NAV_GROUPS = [
	{
		id: 'main',
		items: [
			{ id: 'dashboard', label: __( 'Dashboard', 'saman-seo' ) },
			{
				id: 'search-appearance',
				label: __( 'Search appearance', 'saman-seo' ),
			},
			{ id: 'sitemap', label: __( 'Sitemap', 'saman-seo' ) },
		],
	},
	{
		id: 'monitor',
		label: __( 'Monitor', 'saman-seo' ),
		items: [
			{ id: 'audit', label: __( 'SEO audit', 'saman-seo' ) },
			{ id: 'improvements', label: __( 'Improvements', 'saman-seo' ) },
			{ id: 'redirects', label: __( 'Redirects', 'saman-seo' ) },
			{ id: '404-log', label: __( '404 monitor', 'saman-seo' ) },
			{ id: 'link-health', label: __( 'Link health', 'saman-seo' ) },
			{
				id: 'instant-indexing',
				label: __( 'Instant indexing', 'saman-seo' ),
			},
		],
	},
	{
		id: 'content',
		label: __( 'Content', 'saman-seo' ),
		items: [
			{ id: 'bulk-editor', label: __( 'Bulk editor', 'saman-seo' ) },
			{ id: 'content-gaps', label: __( 'Content gaps', 'saman-seo' ) },
			{
				id: 'internal-linking',
				label: __( 'Internal linking', 'saman-seo' ),
			},
			{ id: 'image-seo', label: __( 'Image SEO', 'saman-seo' ) },
			{ id: 'local-seo', label: __( 'Local SEO', 'saman-seo' ) },
		],
	},
	{
		id: 'technical',
		label: __( 'Schema and technical', 'saman-seo' ),
		items: [
			{
				id: 'schema-builder',
				label: __( 'Schema builder', 'saman-seo' ),
			},
			{
				id: 'schema-validator',
				label: __( 'Schema validator', 'saman-seo' ),
			},
			{ id: 'robots-txt', label: __( 'robots.txt', 'saman-seo' ) },
			{ id: 'htaccess-editor', label: __( '.htaccess', 'saman-seo' ) },
			{
				id: 'mobile-friendly',
				label: __( 'Mobile friendly', 'saman-seo' ),
			},
		],
	},
	{
		id: 'ai',
		label: __( 'AI', 'saman-seo' ),
		items: [
			{ id: 'ai-assistant', label: __( 'AI assistant', 'saman-seo' ) },
			{ id: 'assistants', label: __( 'Assistants', 'saman-seo' ) },
		],
	},
];

const FOOTER_ITEMS = [
	{ id: 'tools', label: __( 'All tools', 'saman-seo' ) },
	{ id: 'settings', label: __( 'Settings', 'saman-seo' ) },
	{ id: 'more', label: __( 'More', 'saman-seo' ) },
];

const readCollapsed = () => {
	try {
		return window.localStorage.getItem( COLLAPSE_KEY ) === '1';
	} catch {
		return false;
	}
};

const Sidebar = ( { currentView, onNavigate, onPrefetch } ) => {
	const [ collapsed, setCollapsed ] = useState( readCollapsed );

	const toggleCollapsed = () => {
		const next = ! collapsed;
		setCollapsed( next );
		try {
			window.localStorage.setItem( COLLAPSE_KEY, next ? '1' : '0' );
		} catch {
			// The preference is a convenience; ignore blocked storage.
		}
	};

	const renderItem = ( item ) => {
		const active = currentView === item.id;
		const prefetch = () => {
			if ( onPrefetch ) {
				onPrefetch( item.id );
			}
		};
		return (
			<li key={ item.id }>
				<button
					type="button"
					className={ `side-nav__item${
						active ? ' is-active' : ''
					}` }
					aria-current={ active ? 'page' : undefined }
					title={ item.label }
					onClick={ () => onNavigate( item.id ) }
					onMouseEnter={ prefetch }
					onFocus={ prefetch }
				>
					<span className="side-nav__icon">{ ICONS[ item.id ] }</span>
					<span className="side-nav__label">{ item.label }</span>
				</button>
			</li>
		);
	};

	return (
		<aside className={ `side-nav${ collapsed ? ' is-collapsed' : '' }` }>
			<div className="side-nav__brand">
				<span className="brand-icon" aria-hidden="true">
					<svg
						viewBox="491 491 2048 2048"
						role="img"
						focusable="false"
						preserveAspectRatio="xMidYMid meet"
					>
						<path d="M2009.2,1575.62c-20.77,97.29-56.47,168.25-111.98,249.43l44.32,45.17c29.65-3.23,51.62,10.65,71.38,30.51l247,248.18c33.25,33.41,45.11,80.99,29.16,125.89-16.43,46.25-59.94,78.94-109.58,81.07-36.57,1.57-68.29-15.42-93.1-40.42l-49.57-49.96-198.34-199.1c-17.61-17.68-26.2-39.72-25.72-64.12l-48.52-44.54c-4.29,2.17-7.75,4.08-11.36,6.68-174.71,125.76-394.51,153.76-595.73,77.03-41.9-15.98-80.46-36.62-117.89-60.92-63.98-41.55-119.43-93.05-165.46-153.92-39.66-52.45-70.22-110.63-92.23-172.79-47.86-135.17-47.62-277.57-3.08-413.39,37.19-113.4,110.51-217.97,202.41-293.57,55.77-45.87,107.56-76.55,174.88-103.29,50.67-20.12,102.36-32.12,156.56-38.67,106.97-12.93,225.92,4.33,324.11,48.58,42.42,19.12,82.29,39.78,119.05,69.06l-59.71,61.31-28.09,29.35-42.37-24.49c-33.48-19.35-69.27-31.7-106.93-42.27-89.28-25.07-184.2-23.74-273.39,1.03-28.97,8.05-54.55,18.2-81.3,31.09-257.28,123.98-366.11,433.13-239.74,690.72,19.64,40.04,44.9,75.12,73.49,108.79,15.75,18.54,32.2,34.45,50.38,50.37,141.85,124.23,340.92,162.64,517.54,93.77,51.48-20.07,99.06-46.68,141.65-81.9,36.95-30.56,69.48-63.27,97.07-102.83,25.12-36.02,45.92-74.51,61.13-116.11,26.8-73.29,36.13-151.35,28.93-229.05-6.93-72.22-27.7-140.49-63.88-204.66l89.83-92.32c22.95,35.88,40.95,72.55,57.2,111.34,12.81,33.54,23.36,65.9,30.61,101.04,20.68,88.49,20.24,179.09,1.28,267.92Z" />
						<path d="M958.58,1578.07c-9.76-28.54-16.85-55.64-17.74-85.19-.19-6.31,2.22-10.1,6.66-14.52l149.64-148.92,152.41-152.13c7.93-7.92,49.65-51.13,55.97-50.64,2.04.16,6.05,2.26,8.05,4.25l166.59,165.17,214.54-218.28,165.52-167.93-97.61-98.1,39.87-6.7,150.59-23.78,131.62-19.78-1.87,11.77-26.41,158.67-24.61,149.71-45.07-44.26-50.09-50.26-250.57,254.99-137.84,140.16-65.36,65.68-19.39-19.26-157.09-156.33c-20.87,18.85-38.51,38.26-57.83,57.76l-204.48,206.48-67.63,68.54c-7.72,7.82-15.16-19.12-17.88-27.08Z" />
						<path d="M1478.64,1861.67c0,7.32-2.73,10.09-8.61,11.13-64.81,11.48-107.08,11.42-171.66-.54-3.65-.68-6.87-3.89-6.87-8.31l.06-475.69c0-7.98,10.04-15.35,16.58-12.66l143.78,144.8c15.11,15.21,26.64,7.34,26.64,27.5l.06,313.78Z" />
						<path d="M1735.84,1707.89c-38.05,54.54-99.32,100.25-158.33,129.87-3.97,1.99-7.09,4.54-11.89,1.92l.43-359.73c0-7.78.53-17.63,4.55-23.32,7.96-11.27,16.97-19.9,26.69-29.73l153.03-154.62c4.23,8.13,4.92,16.39,4.93,25.6l.3,337.95c.02,17.31-4.09,33.32-8.31,49.63-2,7.74-6.81,15.85-11.39,22.41Z" />
						<path d="M1032.27,1708.49c-8.34-12.18-12.95-27.87-7.29-41.4,3.62-8.64,11.57-15.25,17.95-21.98l57.64-60.7,91.89-95.92c.91-.95,4.39-3.67,5.19-2.7s2.27,4.21,2.27,5.91l.03,350.17c-43.86-16.17-87.21-49.17-119.56-78.46-18.32-16.58-33.8-34-48.12-54.92Z" />
					</svg>
				</span>
				<span className="side-nav__brand-name">
					{ __( 'Saman SEO', 'saman-seo' ) }
				</span>
			</div>
			<nav
				className="side-nav__scroll"
				aria-label={ __( 'Saman SEO', 'saman-seo' ) }
			>
				{ NAV_GROUPS.map( ( group ) => (
					<div key={ group.id } className="side-nav__group">
						{ group.label && (
							<h2 className="side-nav__heading">
								{ group.label }
							</h2>
						) }
						<ul>{ group.items.map( renderItem ) }</ul>
					</div>
				) ) }
			</nav>
			<div className="side-nav__footer">
				<ul>{ FOOTER_ITEMS.map( renderItem ) }</ul>
				<button
					type="button"
					className="side-nav__collapse"
					onClick={ toggleCollapsed }
					aria-pressed={ collapsed }
					title={
						collapsed
							? __( 'Expand menu', 'saman-seo' )
							: __( 'Collapse menu', 'saman-seo' )
					}
				>
					<span className="side-nav__icon">
						{ icon(
							<>
								<rect
									x="3"
									y="4"
									width="18"
									height="16"
									rx="2"
								/>
								<path d="M9 4v16M15 10l-2 2 2 2" />
							</>
						) }
					</span>
					<span className="side-nav__label">
						{ __( 'Collapse menu', 'saman-seo' ) }
					</span>
				</button>
			</div>
		</aside>
	);
};

export default Sidebar;
