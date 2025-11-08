(function (wp, config) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar } = wp.editPost;
	const { TextControl, CheckboxControl, PanelBody } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { createElement: el, Fragment } = wp.element;

	config = config || {};

	const defaults = {
		title: '',
		description: '',
		canonical: '',
		noindex: '',
		nofollow: '',
		og_image: '',
	};

	const SeoFields = () => {
		const meta = useSelect((select) => {
			const data =
				select('core/editor').getEditedPostAttribute('meta') || {};
			return { ...defaults, ...(data._wpseopilot_meta || {}) };
		}, []);

		const postTitle = useSelect(
			(select) => select('core/editor').getEditedPostAttribute('title'),
			[]
		);
		const excerpt = useSelect(
			(select) => select('core/editor').getEditedPostAttribute('excerpt'),
			[]
		);

		const permalink = useSelect(
			(select) => select('core/editor').getPermalink(),
			[]
		);

		const { editPost } = useDispatch('core/editor');

		const update = (prop, value) => {
			const next = { ...meta, [prop]: value };
			editPost({ meta: { _wpseopilot_meta: next } });
		};

		const snippetTitle = meta.title || postTitle;
		const snippetDesc = meta.description || excerpt || config.defaultDescription || '';

		return el(
			Fragment,
			null,
			el(TextControl, {
				label: 'Meta title',
				value: meta.title,
				maxLength: 160,
				onChange: (value) => update('title', value),
			}),
			el(TextControl, {
				label: 'Meta description',
				value: meta.description,
				maxLength: 320,
				onChange: (value) => update('description', value),
			}),
			el(TextControl, {
				label: 'Canonical URL',
				value: meta.canonical,
				onChange: (value) => update('canonical', value),
			}),
			el(TextControl, {
				label: 'Social image URL',
				value: meta.og_image,
				onChange: (value) => update('og_image', value),
			}),
			el(CheckboxControl, {
				label: 'Noindex',
				checked: meta.noindex === '1',
				onChange: (value) => update('noindex', value ? '1' : ''),
			}),
			el(CheckboxControl, {
				label: 'Nofollow',
				checked: meta.nofollow === '1',
				onChange: (value) => update('nofollow', value ? '1' : ''),
			}),
			el(
				'div',
				{ className: 'wpseopilot-snippet' },
				el('div', { className: 'wpseopilot-snippet__title' }, snippetTitle),
				el('div', { className: 'wpseopilot-snippet__url' }, permalink || ''),
				el('div', { className: 'wpseopilot-snippet__desc' }, snippetDesc || '')
			)
		);
	};

	registerPlugin('wpseopilot-sidebar', {
		render: () =>
			el(
				PluginSidebar,
				{
					name: 'wpseopilot-sidebar',
					title: 'WP SEO Pilot',
				},
				el(
					PanelBody,
					{ className: 'wpseopilot-panel', initialOpen: true },
					el(SeoFields)
				)
			),
		icon: 'airplane',
	});
})(window.wp, window.WPSEOPilotEditor);
