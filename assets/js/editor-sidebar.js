(function (wp, config) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar } = wp.editPost;
	const { TextControl, CheckboxControl, PanelBody, Button } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { createElement: el, Fragment, useState } = wp.element;
	const __ = (wp.i18n && wp.i18n.__) || ((str) => str);

	config = config || {};

	const defaults = {
		title: '',
		description: '',
		canonical: '',
		noindex: '',
		nofollow: '',
		og_image: '',
	};

	const stripMarkup = (value = '') =>
		value
			.replace(/<!--[\s\S]*?-->/g, ' ')
			.replace(/<\/?[^>]+(>|$)/g, ' ')
			.replace(/\s+/g, ' ')
			.trim();

	const aiConfig = config.ai || {};

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

		const content = useSelect(
			(select) => select('core/editor').getEditedPostAttribute('content'),
			[]
		);

		const postType = useSelect(
			(select) => select('core/editor').getCurrentPostType(),
			[]
		);

		const postId = useSelect(
			(select) => select('core/editor').getCurrentPostId(),
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

		const typeDescription =
			(config.postTypeDescriptions && postType && config.postTypeDescriptions[postType]) || '';

		const excerptText = stripMarkup(excerpt || '');
		const contentSnippet = stripMarkup(content || '');

		const snippetTitle = meta.title || postTitle;
		const snippetDescSource =
			meta.description ||
			excerptText ||
			typeDescription ||
			contentSnippet ||
			config.defaultDescription ||
			'';
		const snippetDesc = snippetDescSource.slice(0, 320);

		const [aiState, setAiState] = useState({
			message: '',
			variant: '',
			loadingField: '',
		});

		const setStatus = (message, variant = '', loadingField = '') => {
			setAiState({ message, variant, loadingField });
		};

		const requestAi = (field) => {
			if (!aiConfig.enabled) {
				setStatus(aiConfig.strings?.disabled || '', 'error');
				return;
			}

			if (!postId) {
				setStatus(aiConfig.strings?.error || '', 'error');
				return;
			}

			setStatus(aiConfig.strings?.running || 'Generating…', 'loading', field);

			const payload = new window.URLSearchParams();
			payload.append('action', 'wpseopilot_generate_ai');
			payload.append('nonce', aiConfig.nonce);
			payload.append('postId', postId);
			payload.append('field', field);

			window
				.fetch(aiConfig.ajax, {
					method: 'POST',
					headers: {
						'Content-Type':
							'application/x-www-form-urlencoded; charset=UTF-8',
					},
					body: payload.toString(),
				})
				.then((res) => res.json())
				.then((response) => {
					if (!response || !response.success) {
						throw new Error(
							response?.data ||
								aiConfig.strings?.error ||
								'Unable to generate suggestion.'
						);
					}

					const value = response.data.value || '';
					update(field, value);
					setStatus(aiConfig.strings?.success || '', 'success');
				})
				.catch((err) => {
					setStatus(err.message || aiConfig.strings?.error || '', 'error');
				});
		};

		const aiControls =
			aiConfig.enabled &&
			el(
				'div',
				{ className: 'wpseopilot-ai-panel' },
				el(
					Button,
					{
						isSecondary: true,
						isBusy: aiState.loadingField === 'title' && aiState.variant === 'loading',
						onClick: () => requestAi('title'),
					},
					__('AI title', 'wp-seo-pilot')
				),
				el(
					Button,
					{
						isSecondary: true,
						isBusy:
							aiState.loadingField === 'description' &&
							aiState.variant === 'loading',
						onClick: () => requestAi('description'),
					},
					__('AI description', 'wp-seo-pilot')
				),
				aiState.message
					? el(
							'p',
							{
								className: `wpseopilot-ai-panel__status ${
									aiState.variant ? 'is-' + aiState.variant : ''
								}`,
							},
							aiState.message
					  )
					: null
			);

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
			aiControls,
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
