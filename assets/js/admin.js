(function ($, settings) {
	settings = settings || {
		mediaTitle: 'Select image',
		mediaButton: 'Use image',
	};
	const aiConfig = settings.ai || {};

	const counter = (target) => {
		const el = $('#' + target);
		const length = el.val() ? el.val().length : 0;
		$('[data-target="' + target + '"]').text(length + ' chars');
	};

	const updatePreview = () => {
		const title = $('#wpseopilot_title').val() || $('#title').val();
		const desc =
			$('#wpseopilot_description').val() ||
			($('#excerpt').length ? $('#excerpt').val() : '');

		$('[data-preview="title"]').text(title);
		$('[data-preview="description"]').text(desc);
	};

	$(document).on('input', '#wpseopilot_title, #wpseopilot_description', function () {
		counter(this.id);
		updatePreview();
	});

	$('.wpseopilot-media-trigger').on('click', function (e) {
		e.preventDefault();
		const frame = wp.media({
			title: settings.mediaTitle,
			button: { text: settings.mediaButton },
			multiple: false,
		});

		frame.on('select', function () {
			const attachment = frame.state().get('selection').first().toJSON();
			$('#wpseopilot_default_og_image, #wpseopilot_og_image').val(attachment.url);
			updatePreview();
		});

		frame.open();
	});

	const setAiStatus = (statusEl, message, variant) => {
		if (!statusEl || !statusEl.length) {
			return;
		}

		statusEl
			.text(message || '')
			.removeClass('is-error is-loading is-success')
			.addClass(variant ? 'is-' + variant : '');
	};

	const requestAi = (button) => {
		if (!aiConfig.enabled) {
			setAiStatus(
				button.closest('.wpseopilot-ai-inline').find('[data-ai-status]'),
				(aiConfig.strings && aiConfig.strings.disabled) || ''
			);
			return;
		}

		const field = button.data('field');
		const postId = button.data('post');
		const targetSelector = button.data('target');
		const target = $(targetSelector);
		const statusEl = button
			.closest('.wpseopilot-ai-inline')
			.find('[data-ai-status]');

		if (!field || !postId || !target.length) {
			setAiStatus(statusEl, (aiConfig.strings && aiConfig.strings.error) || '', 'error');
			return;
		}

		button.prop('disabled', true);
		setAiStatus(
			statusEl,
			(aiConfig.strings && aiConfig.strings.running) || 'Generating…',
			'loading'
		);

		$.post(
			aiConfig.ajax,
			{
				action: 'wpseopilot_generate_ai',
				nonce: aiConfig.nonce,
				postId,
				field,
			},
			(response) => {
				if (!response || !response.success) {
					const message =
						(response && response.data) ||
						(aiConfig.strings && aiConfig.strings.error) ||
						'';
					setAiStatus(statusEl, message, 'error');
					return;
				}

				const value = response.data.value || '';
				target.val(value).trigger('input');
				setAiStatus(
					statusEl,
					(aiConfig.strings && aiConfig.strings.success) || '',
					'success'
				);
			}
		)
			.fail((xhr) => {
				const message =
					(xhr && xhr.responseJSON && xhr.responseJSON.data) ||
					(xhr && xhr.statusText) ||
					(aiConfig.strings && aiConfig.strings.error) ||
					'';
				setAiStatus(statusEl, message, 'error');
			})
			.always(() => {
				button.prop('disabled', false);
			});
	};

	$(document).on('click', '.wpseopilot-ai-button', function (e) {
		e.preventDefault();
		requestAi($(this));
	});

	const initTabs = () => {
		$('.wpseopilot-tabs').each(function () {
			const $container = $(this);
			const $tabs = $container.find('[data-wpseopilot-tab]');
			const $panels = $container.find('.wpseopilot-tab-panel');
			const standalonePanels = [
				'wpseopilot-tab-export',
				'wpseopilot-tab-knowledge',
				'wpseopilot-tab-social',
			];

			if (!$tabs.length || !$panels.length) {
				return;
			}

			const activate = (targetId) => {
				if (!targetId) {
					return;
				}

				const $targetTab = $tabs.filter(function () {
					return $(this).data('wpseopilot-tab') === targetId;
				});
				const $targetPanel = $panels.filter('#' + targetId);

				if (!$targetTab.length || !$targetPanel.length) {
					return;
				}

				$tabs.removeClass('nav-tab-active').attr('aria-selected', 'false');
				$targetTab.addClass('nav-tab-active').attr('aria-selected', 'true');

				$panels.removeClass('is-active').attr('hidden', 'hidden');
				$targetPanel.addClass('is-active').removeAttr('hidden');

				const noActions = standalonePanels.includes(targetId);
				$container.toggleClass('wpseopilot-tabs--no-actions', noActions);
			};

			$tabs.on('click', function (event) {
				event.preventDefault();
				activate($(this).data('wpseopilot-tab'));
			});

			$container.addClass('wpseopilot-tabs--ready');

			// Check for URL hash to determine initial tab
			let initial = $tabs.first().data('wpseopilot-tab');

			if (window.location.hash) {
				const hash = window.location.hash.substring(1); // Remove the #
				const tabId = 'wpseopilot-tab-' + hash;
				if ($panels.filter('#' + tabId).length) {
					initial = tabId;
				}
			}

			if (!initial) {
				initial = $tabs.filter('.nav-tab-active').data('wpseopilot-tab') ||
					$tabs.first().data('wpseopilot-tab');
			}

			activate(initial);

			// Handle hash changes for navigation
			$(window).on('hashchange', function() {
				if (window.location.hash) {
					const hash = window.location.hash.substring(1);
					const tabId = 'wpseopilot-tab-' + hash;
					if ($panels.filter('#' + tabId).length) {
						activate(tabId);
					}
				}
			});
		});
	};

	const initSchemaControls = () => {
		const controls = document.querySelectorAll('[data-schema-control]');
		if (!controls.length) {
			return;
		}

		const normalize = (value) =>
			(typeof value === 'string' ? value : '').trim().toLowerCase();

		controls.forEach((control) => {
			const select = control.querySelector('[data-schema-select]');
			const input = control.querySelector('[data-schema-input]');

			if (!select || !input) {
				return;
			}

			const findPreset = (value) => {
				const normalized = normalize(value);
				let match = null;

				select.querySelectorAll('option').forEach((option) => {
					const optionValue = option.value;
					if (optionValue === '__custom') {
						return;
					}

					if (normalize(optionValue) === normalized) {
						match = optionValue;
					}
				});

				return match;
			};

			const applyPreset = (value) => {
				const preset = findPreset(value);

				if (preset !== null) {
					select.value = preset;
					input.value = preset;
					input.setAttribute('readonly', 'readonly');
					control.classList.add('is-preset');
					control.classList.remove('is-custom');
					return;
				}

				select.value = '__custom';
				input.removeAttribute('readonly');
				control.classList.add('is-custom');
				control.classList.remove('is-preset');
			};

			applyPreset(input.value);

			select.addEventListener('change', () => {
				if (select.value === '__custom') {
					input.removeAttribute('readonly');
					control.classList.add('is-custom');
					control.classList.remove('is-preset');
					input.focus();
					return;
				}

				input.value = select.value;
				applyPreset(select.value);
			});

			input.addEventListener('input', () => {
				const preset = findPreset(input.value);
				if (preset !== null) {
					applyPreset(preset);
				} else {
					select.value = '__custom';
					input.removeAttribute('readonly');
					control.classList.add('is-custom');
					control.classList.remove('is-preset');
				}
			});
		});
	};

	const initGooglePreview = () => {
		// Find all Google preview components
		$('.wpseopilot-google-preview').each(function () {
			const $preview = $(this);
			const $titlePreview = $preview.find('[data-preview-title]');
			const $descPreview = $preview.find('[data-preview-description]');
			const $titleCounter = $preview.find('.wpseopilot-char-count[data-type="title"]');
			const $descCounter = $preview.find('.wpseopilot-char-count[data-type="description"]');

			// Find associated input fields (within the same form or section)
			const $form = $preview.closest('form');
			const $titleField = $form.find('[data-preview-field="title"]');
			const $descField = $form.find('[data-preview-field="description"]');

			// Fetch rendered preview from server
			const fetchRenderedPreview = (template, context, previewEl, counterEl, maxChars) => {
				if (!template) {
					previewEl.text(previewEl.data('default') || '');
					if (counterEl.length) {
						counterEl.text(0);
					}
					return;
				}

				// Use the AJAX endpoint to render the template with variables replaced
				$.post(settings.ai.ajax, {
					action: 'wpseopilot_render_preview',
					nonce: settings.ai.nonce,
					template: template,
					context: context || 'global'
				}).done(function (response) {
					if (response.success) {
						const rendered = response.data.preview || template;
						previewEl.text(rendered);

						if (counterEl.length) {
							const charCount = rendered.length;
							counterEl.text(charCount);

							// Add warning class if over limit
							const $charSpan = counterEl.closest('.wpseopilot-google-preview__chars');
							if (charCount > maxChars) {
								$charSpan.addClass('over-limit');
							} else {
								$charSpan.removeClass('over-limit');
							}
						}
					} else {
						// Fallback to showing the template as-is
						previewEl.text(template);
						if (counterEl.length) {
							counterEl.text(template.length);
						}
					}
				}).fail(function () {
					// Fallback to showing the template as-is
					previewEl.text(template);
					if (counterEl.length) {
						counterEl.text(template.length);
					}
				});
			};

			// Debounce function
			const debounce = (func, wait) => {
				let timeout;
				return function (...args) {
					clearTimeout(timeout);
					timeout = setTimeout(() => func.apply(this, args), wait);
				};
			};

			// Initialize with current values
			if ($titleField.length) {
				const context = $titleField.data('context') || 'global';
				const debouncedFetch = debounce(() => {
					fetchRenderedPreview($titleField.val(), context, $titlePreview, $titleCounter, 60);
				}, 500);

				fetchRenderedPreview($titleField.val(), context, $titlePreview, $titleCounter, 60);

				$titleField.on('input change', debouncedFetch);
			}

			if ($descField.length) {
				const context = $descField.data('context') || 'global';
				const debouncedFetch = debounce(() => {
					fetchRenderedPreview($descField.val(), context, $descPreview, $descCounter, 155);
				}, 500);

				fetchRenderedPreview($descField.val(), context, $descPreview, $descCounter, 155);

				$descField.on('input change', debouncedFetch);
			}
		});
	};

	$(document).on('click', '.wpseopilot-create-redirect-btn', function (e) {
		e.preventDefault();
		const $btn = $(this);
		const $notice = $btn.closest('.notice');
		const source = $btn.data('source');
		const target = $btn.data('target');
		const nonce = $btn.data('nonce');

		$btn.prop('disabled', true).text('Creating...');

		$.post(
			ajaxurl,
			{
				action: 'wpseopilot_create_automatic_redirect',
				nonce: nonce,
				source: source,
				target: target,
			},
			function (response) {
				if (response.success) {
					$notice
						.removeClass('notice-info')
						.addClass('notice-success')
						.html('<p>' + response.data + '</p>');
					setTimeout(function () {
						$notice.fadeOut();
					}, 3000);
				} else {
					$notice
						.removeClass('notice-info')
						.addClass('notice-error')
						.html('<p>' + (response.data || 'Error creating redirect') + '</p>');
				}
			}
		).fail(function () {
			$notice
				.removeClass('notice-info')
				.addClass('notice-error')
				.html('<p>Request failed.</p>');
		});
	});

	// Initialize nested accordion tabs
	const initAccordionTabs = () => {
		$('.wpseopilot-accordion-tabs').each(function () {
			const $container = $(this);
			const $tabs = $container.find('[data-accordion-tab]');
			const $panels = $container.find('.wpseopilot-accordion-tab-panel');

			if (!$tabs.length || !$panels.length) return;

			const activate = (targetId) => {
				$tabs.removeClass('is-active').attr('aria-selected', 'false');
				$panels.removeClass('is-active').attr('hidden', '');

				$tabs.filter('[data-accordion-tab="' + targetId + '"]')
					.addClass('is-active').attr('aria-selected', 'true');
				$('#' + targetId).addClass('is-active').removeAttr('hidden');
			};

			$tabs.on('click', function (e) {
				e.preventDefault();
				activate($(this).data('accordion-tab'));
			});

			$container.addClass('wpseopilot-accordion-tabs--ready');
			activate($tabs.first().data('accordion-tab'));
		});
	};

	// Deep linking handler for nested tabs
	const initDeepLinking = () => {
		const parseHash = () => {
			if (!window.location.hash) return null;
			const parts = window.location.hash.substring(1).split('/');
			return {
				mainTab: parts[0] || null,
				accordionSlug: parts[1] || null,
				subTab: parts[2] || null
			};
		};

		const activateDeepLink = () => {
			const parsed = parseHash();
			if (!parsed?.mainTab) return;

			// 1. Activate main tab
			const mainTabId = 'wpseopilot-tab-' + parsed.mainTab;
			$('[data-wpseopilot-tab="' + mainTabId + '"]').trigger('click');

			// 2. Expand accordion if slug provided
			if (parsed.accordionSlug) {
				const $accordion = $('[data-accordion-slug="' + parsed.accordionSlug + '"]');
				$accordion.prop('open', true);

				// 3. Activate sub-tab if provided
				if (parsed.subTab) {
					const subTabId = 'wpseopilot-accordion-' + parsed.accordionSlug + '-' + parsed.subTab;
					setTimeout(() => {
						$('[data-accordion-tab="' + subTabId + '"]').trigger('click');
						if ($accordion[0]) {
							$accordion[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
						}
					}, 100);
				}
			}
		};

		activateDeepLink();
		$(window).on('hashchange', activateDeepLink);
	};

	// Lazy initialization on accordion open
	const initAccordionOpenHandlers = () => {
		$(document).on('toggle', '.wpseopilot-accordion[data-accordion-slug]', function () {
			if ($(this).prop('open')) {
				const $tabs = $(this).find('.wpseopilot-accordion-tabs');
				if ($tabs.length && !$tabs.hasClass('wpseopilot-accordion-tabs--ready')) {
					initAccordionTabs();
				}
			}
		});
	};

	// Initialize separator selector
	const initSeparatorSelector = () => {
		const $selector = $('[data-component="separator-selector"]');
		if (!$selector.length) return;

		const $options = $selector.find('.wpseopilot-separator-option');
		const $customInput = $selector.find('#wpseopilot_custom_separator');
		const $hiddenField = $selector.find('#wpseopilot_title_separator');
		const $customContainer = $selector.find('.wpseopilot-separator-custom-input');
		const $customOption = $selector.find('.wpseopilot-separator-custom');

		$options.on('click', function () {
			const $this = $(this);
			const separator = $this.data('separator');

			// Remove active class from all options
			$options.removeClass('is-active');
			$this.addClass('is-active');

			if (separator === 'custom') {
				// Show custom input
				$customContainer.slideDown(200);
				$customInput.focus();

				// Update hidden field with custom value or empty if not set
				const customValue = $customInput.val().trim();
				$hiddenField.val(customValue || '-');
			} else {
				// Hide custom input
				$customContainer.slideUp(200);

				// Update hidden field with selected separator
				$hiddenField.val(separator);

				// Update custom option preview back to question mark
				$customOption.find('.wpseopilot-separator-preview').text('?');
			}
		});

		// Handle custom input changes
		$customInput.on('input', function () {
			const value = $(this).val().trim();
			$hiddenField.val(value || '-');

			// Update the custom option preview
			if (value) {
				$customOption.find('.wpseopilot-separator-preview').text(value);
			}
		});
	};

	$(document).ready(function () {
		['wpseopilot_title', 'wpseopilot_description'].forEach(counter);
		updatePreview();
		initTabs();
		initAccordionOpenHandlers();
		initDeepLinking();
		initSchemaControls();
		initGooglePreview();
		initSeparatorSelector();
	});
})(jQuery, window.WPSEOPilotAdmin);
