(function (wp, config) {
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar } = wp.editPost;
	const { TextControl, CheckboxControl, PanelBody, Button } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { createElement: el, Fragment, useState } = wp.element;
	const __ = (wp.i18n && wp.i18n.__) || ((str) => str);

	config = config || {};
	console.log('WPSEOPilotEditor Config:', config);

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
							className: `wpseopilot-ai-panel__status ${aiState.variant ? 'is-' + aiState.variant : ''
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

	const SlugMonitor = () => {
		const { createNotice, removeNotice } = useDispatch('core/notices');
		const { isSavingPost, isCurrentPostPublished, getCurrentPostAttribute, getPermalink } = useSelect(
			(select) => select('core/editor'),
			[]
		);

		// Debug logs
		// console.log('SlugMonitor Render', { isSavingPost, isCurrentPostPublished });

		// Initial slug state from when the editor loaded.
		const [initialSlug, setInitialSlug] = useState(
			getCurrentPostAttribute('slug')
		);
		const [wasSaving, setWasSaving] = useState(false);

		// Check for transient data passed from PHP (e.g. page reload after slug change).
		const [processedTransient, setProcessedTransient] = useState(false);

		const handleCreateRedirect = (oldUrl, newUrl, nonce, noticeId) => {
			console.log('SlugMonitor: Creating redirect for', oldUrl, 'to', newUrl);
			const ajaxUrl = aiConfig.ajax || window.ajaxurl;

			const formData = new window.FormData();
			formData.append('action', 'wpseopilot_create_automatic_redirect');
			formData.append('nonce', nonce);
			formData.append('source', oldUrl);
			formData.append('target', newUrl);

			removeNotice(noticeId);
			createNotice('info', __('Creating redirect…', 'wp-seo-pilot'), { id: 'wpseopilot-redirect-creating', isDismissible: false });

			window.fetch(ajaxUrl, {
				method: 'POST',
				body: formData,
			})
				.then(res => res.json())
				.then(response => {
					removeNotice('wpseopilot-redirect-creating');
					if (response.success) {
						console.log('SlugMonitor: Redirect created success');
						createNotice('success', response.data, {
							id: 'wpseopilot-slug-success',
							isDismissible: true,
							type: 'snackbar'
						});
					} else {
						console.warn('SlugMonitor: Redirect created error', response);
						createNotice('error', response.data || 'Error', { isDismissible: true });
					}
				})
				.catch((err) => {
					console.error('SlugMonitor: Redirect created fail', err);
					removeNotice('wpseopilot-redirect-creating');
					createNotice('error', __('Request failed', 'wp-seo-pilot'), { isDismissible: true });
				});
		};

		if (!processedTransient) {
			console.log('SlugMonitor: check config.slugChange', config.slugChange);
			if (config.slugChange) {
				setProcessedTransient(true);
				const { old_url, new_url, nonce } = config.slugChange;

				console.log('SlugMonitor: Showing transient notice');
				createNotice(
					'info',
					__('We noticed the post slug changed. Would you like to create a redirect?', 'wp-seo-pilot'),
					{
						id: 'wpseopilot-slug-notice',
						isDismissible: true,
						speak: true,
						actions: [
							{
								label: __('Create Redirect', 'wp-seo-pilot'),
								onClick: () => handleCreateRedirect(old_url, new_url, nonce, 'wpseopilot-slug-notice')
							}
						]
					}
				);
			} else {
				setProcessedTransient(true);
			}
		}

		// Live monitoring logic.
		if (wasSaving && !isSavingPost) {
			// Save just finished.
			setWasSaving(false);

			if (isCurrentPostPublished) {
				const currentSlug = getCurrentPostAttribute('slug');
				// console.log('SlugMonitor: Save finished. Slug:', currentSlug, 'Initial:', initialSlug);

				if (currentSlug && initialSlug && currentSlug !== initialSlug) {
					const currentPermalink = getPermalink(); // This should be the NEW URL.
					console.log('SlugMonitor: Slug changed detected. Permalink:', currentPermalink);

					if (currentPermalink) {
						const oldUrl = currentPermalink.replace(currentSlug, initialSlug);
						const nonce = config.redirectNonce;

						if (nonce) {
							console.log('SlugMonitor: Triggering live notice');
							createNotice(
								'info',
								__('We noticed the post slug changed. Would you like to create a redirect?', 'wp-seo-pilot'),
								{
									id: 'wpseopilot-slug-notice-live',
									isDismissible: true,
									speak: true,
									actions: [
										{
											label: __('Create Redirect', 'wp-seo-pilot'),
											onClick: () => handleCreateRedirect(oldUrl, currentPermalink, nonce, 'wpseopilot-slug-notice-live')
										}
									]
								}
							);
						} else {
							console.warn('SlugMonitor: No nonce for live redirect');
						}
					}
					setInitialSlug(currentSlug);
				}
			}
		}

		if (!wasSaving && isSavingPost) {
			setWasSaving(true);
		}

		return null;
	};

	registerPlugin('wpseopilot-sidebar', {
		render: () =>
			el(
				Fragment,
				null,
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
				el(SlugMonitor)
			),
		icon: 'airplane',
	});
})(window.wp, window.WPSEOPilotEditor);
