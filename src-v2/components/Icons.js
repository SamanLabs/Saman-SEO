/**
 * Shared SVG icon library.
 *
 * All icons are 24x24 stroked SVGs that inherit color via `currentColor`,
 * so they can be sized and colored from CSS like the inline icons used
 * across Tools.js and SchemaValidator.js.
 */

const createIcon = ( paths, svgProps ) =>
	function Icon( props ) {
		return (
			<svg
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				strokeWidth="2"
				strokeLinecap="round"
				strokeLinejoin="round"
				aria-hidden="true"
				{ ...svgProps }
				{ ...props }
			>
				{ paths }
			</svg>
		);
	};

export const IconFileText = createIcon(
	<>
		<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
		<path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" />
	</>
);

export const IconEdit = createIcon(
	<>
		<path d="M12 20h9" />
		<path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" />
	</>
);

export const IconShoppingBag = createIcon(
	<>
		<path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" />
		<path d="M3 6h18" />
		<path d="M16 10a4 4 0 01-8 0" />
	</>
);

export const IconShoppingCart = createIcon(
	<>
		<circle cx="9" cy="21" r="1" />
		<circle cx="20" cy="21" r="1" />
		<path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" />
	</>
);

export const IconMapPin = createIcon(
	<>
		<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
		<circle cx="12" cy="10" r="3" />
	</>
);

export const IconHelpCircle = createIcon(
	<>
		<circle cx="12" cy="12" r="10" />
		<path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3" />
		<path d="M12 17h.01" />
	</>
);

export const IconClipboardList = createIcon(
	<>
		<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2" />
		<rect x="8" y="2" width="8" height="4" rx="1" />
		<path d="M12 11h4M8 11h.01M12 16h4M8 16h.01" />
	</>
);

export const IconChecklist = createIcon(
	<>
		<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
		<rect x="9" y="3" width="6" height="4" rx="1" />
		<path d="M9 12l2 2 4-4" />
	</>
);

export const IconChefHat = createIcon(
	<>
		<path d="M17 21a1 1 0 001-1v-5.35c0-.457.316-.844.727-1.041a4 4 0 00-2.134-7.589 5 5 0 00-9.186 0 4 4 0 00-2.134 7.588c.411.198.727.585.727 1.041V20a1 1 0 001 1z" />
		<path d="M6 17h12" />
	</>
);

export const IconUtensils = createIcon(
	<>
		<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2" />
		<path d="M7 2v20" />
		<path d="M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7" />
	</>
);

export const IconCalendar = createIcon(
	<>
		<rect x="3" y="4" width="18" height="18" rx="2" />
		<path d="M16 2v4M8 2v4M3 10h18" />
	</>
);

export const IconUser = createIcon(
	<>
		<path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
		<circle cx="12" cy="7" r="4" />
	</>
);

export const IconBuilding = createIcon(
	<path d="M3 21h18M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1" />
);

export const IconGlobe = createIcon(
	<>
		<circle cx="12" cy="12" r="10" />
		<path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z" />
	</>
);

export const IconChevronsRight = createIcon(
	<path d="M6 17l5-5-5-5M13 17l5-5-5-5" />
);

export const IconVideo = createIcon(
	<>
		<rect x="2" y="4" width="20" height="16" rx="2" />
		<path d="M10 9l5 3-5 3V9z" />
	</>
);

export const IconFilm = createIcon(
	<>
		<rect x="2" y="2" width="20" height="20" rx="2.18" />
		<path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5" />
	</>
);

export const IconGraduationCap = createIcon(
	<>
		<path d="M22 10L12 5 2 10l10 5 10-5z" />
		<path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5" />
		<path d="M22 10v6" />
	</>
);

export const IconMonitor = createIcon(
	<>
		<rect x="2" y="3" width="20" height="14" rx="2" />
		<path d="M8 21h8M12 17v4" />
	</>
);

export const IconBookOpen = createIcon(
	<>
		<path d="M2 3h6a4 4 0 014 4v14a3 3 0 00-3-3H2z" />
		<path d="M22 3h-6a4 4 0 00-4 4v14a3 3 0 013-3h7z" />
	</>
);

export const IconMusic = createIcon(
	<>
		<path d="M9 18V5l12-2v13" />
		<circle cx="6" cy="18" r="3" />
		<circle cx="18" cy="16" r="3" />
	</>
);

export const IconWrench = createIcon(
	<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" />
);

export const IconBriefcase = createIcon(
	<>
		<rect x="2" y="7" width="20" height="14" rx="2" />
		<path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16" />
	</>
);

export const IconImage = createIcon(
	<>
		<rect x="3" y="3" width="18" height="18" rx="2" />
		<circle cx="8.5" cy="8.5" r="1.5" />
		<path d="M21 15l-5-5L5 21" />
	</>
);

export const IconLink = createIcon(
	<>
		<path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" />
		<path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71" />
	</>
);

export const IconBarChart = createIcon(
	<path d="M12 20V10M18 20V4M6 20v-4" />
);

export const IconActivity = createIcon(
	<path d="M22 12h-4l-3 9L9 3l-3 9H2" />
);

export const IconAlertCircle = createIcon(
	<>
		<circle cx="12" cy="12" r="10" />
		<path d="M12 8v4m0 4h.01" />
	</>
);

export const IconCornerUpLeft = createIcon(
	<>
		<path d="M9 14L4 9l5-5" />
		<path d="M20 20v-7a4 4 0 00-4-4H4" />
	</>
);

export const IconSitemap = createIcon(
	<>
		<path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z" />
		<path d="M8 2v16M16 6v16" />
	</>
);

export const IconBot = createIcon(
	<>
		<path d="M12 8V4H8" />
		<rect x="4" y="8" width="16" height="12" rx="2" />
		<path d="M2 14h2M20 14h2M15 13v2M9 13v2" />
	</>
);

export const IconSparkles = createIcon(
	<>
		<path d="M9.937 15.5A2 2 0 008.5 14.063l-6.135-1.582a.5.5 0 010-.962L8.5 9.936A2 2 0 009.937 8.5l1.582-6.135a.5.5 0 01.963 0L14.063 8.5A2 2 0 0015.5 9.937l6.135 1.581a.5.5 0 010 .964L15.5 14.063a2 2 0 00-1.437 1.437l-1.582 6.135a.5.5 0 01-.963 0z" />
		<path d="M20 3v4M22 5h-4M4 17v2M5 18H3" />
	</>
);

export const IconZap = createIcon(
	<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
);

export const IconSearch = createIcon(
	<>
		<circle cx="11" cy="11" r="8" />
		<path d="M21 21l-4.35-4.35" />
	</>
);

export const IconTrendingUp = createIcon(
	<>
		<path d="M23 6l-9.5 9.5-5-5L1 18" />
		<path d="M17 6h6v6" />
	</>
);

export const IconDollarSign = createIcon(
	<>
		<path d="M12 1v22" />
		<path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
	</>
);

export const IconStar = createIcon(
	<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
);

export const IconHeart = createIcon(
	<path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z" />
);

export const IconPalette = createIcon(
	<>
		<circle cx="13.5" cy="6.5" r=".5" />
		<circle cx="17.5" cy="10.5" r=".5" />
		<circle cx="8.5" cy="7.5" r=".5" />
		<circle cx="6.5" cy="12.5" r=".5" />
		<path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 011.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z" />
	</>
);

export const IconRocket = createIcon(
	<>
		<path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 00-2.91-.09z" />
		<path d="M12 15l-3-3a22 22 0 012-3.95A12.88 12.88 0 0122 2c0 2.72-.78 7.5-6 11a22.35 22.35 0 01-4 2z" />
		<path d="M9 12H4s.55-3.03 2-4c1.62-1.08 5 0 5 0" />
		<path d="M12 15v5s3.03-.55 4-2c1.08-1.62 0-5 0-5" />
	</>
);

export const IconMessageCircle = createIcon(
	<path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z" />
);

export const IconTarget = createIcon(
	<>
		<circle cx="12" cy="12" r="10" />
		<circle cx="12" cy="12" r="6" />
		<circle cx="12" cy="12" r="2" />
	</>
);

export const IconLightbulb = createIcon(
	<>
		<path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 006 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5" />
		<path d="M9 18h6" />
		<path d="M10 22h4" />
	</>
);

export const IconFacebook = createIcon(
	<path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
);

export const IconXBrand = createIcon(
	<path
		d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
		fill="currentColor"
		stroke="none"
	/>,
	{ fill: 'currentColor' }
);

export const IconInstagram = createIcon(
	<>
		<rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
		<path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" />
		<path d="M17.5 6.5h.01" />
	</>
);

export const IconLinkedin = createIcon(
	<>
		<path d="M16 8a6 6 0 016 6v7h-4v-7a2 2 0 00-2-2 2 2 0 00-2 2v7h-4v-7a6 6 0 016-6z" />
		<rect x="2" y="9" width="4" height="12" />
		<circle cx="4" cy="4" r="2" />
	</>
);

export const IconYoutube = createIcon(
	<>
		<path d="M22.54 6.42a2.78 2.78 0 00-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 00-1.94 2A29 29 0 001 11.75a29 29 0 00.46 5.33A2.78 2.78 0 003.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 001.94-1.92 29 29 0 00.46-5.33 29 29 0 00-.46-5.33z" />
		<path d="M9.75 15.02l5.75-3.27-5.75-3.27v6.54z" />
	</>
);

/**
 * Named registry used by pickers and data-driven views.
 */
export const icons = {
	fileText: IconFileText,
	edit: IconEdit,
	shoppingBag: IconShoppingBag,
	shoppingCart: IconShoppingCart,
	mapPin: IconMapPin,
	helpCircle: IconHelpCircle,
	clipboardList: IconClipboardList,
	checklist: IconChecklist,
	chefHat: IconChefHat,
	utensils: IconUtensils,
	calendar: IconCalendar,
	user: IconUser,
	building: IconBuilding,
	globe: IconGlobe,
	chevronsRight: IconChevronsRight,
	video: IconVideo,
	film: IconFilm,
	graduationCap: IconGraduationCap,
	monitor: IconMonitor,
	bookOpen: IconBookOpen,
	music: IconMusic,
	wrench: IconWrench,
	briefcase: IconBriefcase,
	image: IconImage,
	link: IconLink,
	barChart: IconBarChart,
	activity: IconActivity,
	alertCircle: IconAlertCircle,
	cornerUpLeft: IconCornerUpLeft,
	sitemap: IconSitemap,
	bot: IconBot,
	sparkles: IconSparkles,
	zap: IconZap,
	search: IconSearch,
	trendingUp: IconTrendingUp,
	dollarSign: IconDollarSign,
	star: IconStar,
	heart: IconHeart,
	palette: IconPalette,
	rocket: IconRocket,
	messageCircle: IconMessageCircle,
	target: IconTarget,
	lightbulb: IconLightbulb,
};

export const getIcon = ( name ) => icons[ name ] || null;

/**
 * Assistant avatar icon keys (stored on assistant records).
 */
export const assistantIconKeys = [
	'bot',
	'messageCircle',
	'barChart',
	'target',
	'sparkles',
	'search',
	'fileText',
	'lightbulb',
	'rocket',
	'zap',
];

// Emojis saved before the SVG picker existed; mapped to their new keys.
const legacyAssistantIcons = {
	'🤖': 'bot',
	'💬': 'messageCircle',
	'📊': 'barChart',
	'🎯': 'target',
	'✨': 'sparkles',
	'🔍': 'search',
	'📝': 'fileText',
	'💡': 'lightbulb',
	'🚀': 'rocket',
	'⚡': 'zap',
};

export const resolveAssistantIcon = ( value ) => {
	const key =
		legacyAssistantIcons[ value ] ||
		( assistantIconKeys.includes( value ) ? value : 'bot' );
	return icons[ key ];
};
