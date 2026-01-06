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

				// Track AI generation success
				if (typeof _paq !== 'undefined') {
					const fieldName = field.charAt(0).toUpperCase() + field.slice(1);
					_paq.push(['trackEvent', 'AI Assistant', 'Generate', fieldName]);
				}
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
			'wpseopilot-tab-social-cards',
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

				// Update URL hash
				const shortName = targetId.replace('wpseopilot-tab-', '');
				if (window.location.hash.substring(1) !== shortName) {
					window.location.hash = shortName;
				}
			};

			$tabs.on('click', function (event) {
				event.preventDefault();
				const tabId = $(this).data('wpseopilot-tab');
				activate(tabId);

				// Track tab switch with Matomo
				if (typeof _paq !== 'undefined') {
					const tabName = tabId.replace('wpseopilot-tab-', '').replace(/-/g, ' ');
					_paq.push(['trackEvent', 'Tabs', 'Switch', tabName]);
				}
			});

			$container.addClass('wpseopilot-tabs--ready');

			// Check for URL hash to determine initial tab
			let initial = $tabs.first().data('wpseopilot-tab');

			if (window.location.hash) {
				// Only use the first part of the hash for the main tab
				const hash = window.location.hash.substring(1).split('/')[0];
				if (hash) {
					const tabId = 'wpseopilot-tab-' + hash;
					// Validate selector safety to handle edge cases
					try {
						if ($panels.filter('#' + tabId).length) {
							initial = tabId;
						}
					} catch (e) {
						// Invalid selector from hash, ignore
					}
				}
			}

			if (!initial) {
				initial = $tabs.filter('.nav-tab-active').data('wpseopilot-tab') ||
					$tabs.first().data('wpseopilot-tab');
			}

			activate(initial);

			// Handle hash changes for navigation
			$(window).on('hashchange', function () {
				if (window.location.hash) {
					const hash = window.location.hash.substring(1).split('/')[0];
					if (hash) {
						const tabId = 'wpseopilot-tab-' + hash;
						try {
							if ($panels.filter('#' + tabId).length) {
								activate(tabId);
							}
						} catch (e) {
							// Invalid selector from hash, ignore
						}
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

			// Find associated input fields by looking in the closest container (accordion or card)
			// This fixes the issue where inputs from other text types were being selected
			let $container = $preview.closest('.wpseopilot-accordion__body');
			if (!$container.length) {
				$container = $preview.closest('.wpseopilot-card-body');
			}
			// Fallback to form if no container found (e.g. global settings)
			if (!$container.length) {
				$container = $preview.closest('form');
			}

			const $titleField = $container.find('[data-preview-field="title"]');
			const $descField = $container.find('[data-preview-field="description"]');

			// Preview Source Logic
			const $sourceToggle = $preview.find('.wpseopilot-preview-source-toggle');
			const $sourcePanel = $preview.find('.wpseopilot-preview-source-panel');
			const $sourceInput = $preview.find('.wpseopilot-preview-object-id-input');
			const $sourceApply = $preview.find('.wpseopilot-preview-apply-id');
			const $sourceStatus = $preview.find('.wpseopilot-preview-source-status');

			$sourceToggle.on('click', function (e) {
				e.preventDefault();
				$sourcePanel.slideToggle(200);
			});

			$sourceApply.on('click', function (e) {
				e.preventDefault();
				const id = $sourceInput.val();
				if (!id) return;

				$sourceStatus.text('Loading...');
				// Trigger the fetch manually
				// We need current title/desc values
				const titleVal = $titleField.length ? $titleField.val() : $preview.find('[data-preview-title]').text();
				const descVal = $descField.length ? $descField.val() : $preview.find('[data-preview-description]').text();
				const context = $titleField.data('context') || 'global';

				fetchRenderedPreview(titleVal, context, $titlePreview, $titleCounter, 60);
				// We should also trigger description fetch but fetches are debounced/independent.
				// Let's just trigger title fetch which usually covers it, or split logic.
				// Better: call both if fields exist.
				if ($descField.length) {
					fetchRenderedPreview(descVal, context, $descPreview, $descCounter, 155);
				}

				setTimeout(() => $sourceStatus.text('Applied'), 1000);
			});

			// Fetch rendered preview from server
			const fetchRenderedPreview = (template, context, previewEl, counterEl, maxChars) => {
				if (!template) {
					previewEl.text(previewEl.data('default') || '');
					if (counterEl.length) {
						counterEl.text(0);
					}
					return;
				}

				const objectId = $sourceInput.val();

				// Use the AJAX endpoint to render the template with variables replaced
				$.post(settings.ai.ajax, {
					action: 'wpseopilot_render_preview',
					nonce: settings.ai.nonce,
					template: template,
					context: context || 'global',
					object_id: objectId // Pass the explicit ID if set
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

	// Radio Card Interaction
	$(document).on('change', '.wpseopilot-radio-card input[type="radio"]', function () {
		const $input = $(this);
		const $group = $input.closest('.wpseopilot-radio-card-grid');
		$group.find('.wpseopilot-radio-card').removeClass('is-selected');
		$input.closest('.wpseopilot-radio-card').addClass('is-selected');
	});

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
			// Only init if not already ready
			if ($container.hasClass('wpseopilot-accordion-tabs--ready')) return;

			const $tabs = $container.find('[data-accordion-tab]');
			const $panels = $container.find('.wpseopilot-accordion-tab-panel');

			if (!$tabs.length || !$panels.length) return;

			const activate = (targetId) => {
				$tabs.removeClass('is-active').attr('aria-selected', 'false');
				$panels.removeClass('is-active').attr('hidden', '');

				$tabs.filter('[data-accordion-tab="' + targetId + '"]')
					.addClass('is-active').attr('aria-selected', 'true');
				// Ensure panel is visible
				const $panel = $('#' + targetId);
				$panel.addClass('is-active').removeAttr('hidden');
			};

			$tabs.on('click', function (e) {
				e.preventDefault();
				console.log('Accordion tab clicked', $(this).data('accordion-tab'));
				activate($(this).data('accordion-tab'));
			});

			$container.addClass('wpseopilot-accordion-tabs--ready');

			// Init first tab if none active
			if (!$tabs.filter('.is-active').length) {
				activate($tabs.first().data('accordion-tab'));
			}
		});
	};

	// Deep linking handler for nested tabs
	const initDeepLinking = () => {
		// helper to update hash
		const updateHash = () => {
			const activeMain = $('.nav-tab-active').attr('href').substring(1);
			let hash = activeMain;

			// If in content types, check for open accordion and active subtab
			if (activeMain === 'content-types') {
				const $openAccordion = $('.wpseopilot-accordion[open]');
				if ($openAccordion.length) {
					const slug = $openAccordion.data('accordion-slug');
					hash += '/' + slug;

					const $activeSubTab = $openAccordion.find('.wpseopilot-accordion-tab.is-active');
					if ($activeSubTab.length) {
						// Extract 'title-description' from 'wpseopilot-accordion-post-title-description'
						const fullId = $activeSubTab.data('accordion-tab');
						// format is wpseopilot-accordion-{slug}-{tab}
						// simplified: we store the simple key in data attribute usually, but here ID is complex.
						// Let's rely on index or simple mapping.
						// Actually, let's just grab the last part.
						// Convention: wpseopilot-accordion-{slug}-{tabKey}
						const prefix = `wpseopilot-accordion-${slug}-`;
						const tabKey = fullId.replace(prefix, '');
						hash += '/' + tabKey;
					}
				}
			}

			// Don't scroll when updating hash
			const scrollV = document.body.scrollTop;
			const scrollH = document.body.scrollLeft;
			window.location.hash = hash;
			document.body.scrollTop = scrollV;
			document.body.scrollLeft = scrollH;
		};

		const parseHash = () => {
			if (!window.location.hash) return null;
			const parts = window.location.hash.substring(1).split('/');
			return {
				mainTab: parts[0] || 'global',
				accordionSlug: parts[1] || null,
				subTab: parts[2] || null
			};
		};

		const restoreState = () => {
			const parsed = parseHash();
			if (!parsed) return;

			// 1. Activate main tab
			const $mainTab = $(`a[href="#${parsed.mainTab}"]`);
			if ($mainTab.length) {
				$('.nav-tab').removeClass('nav-tab-active');
				$('.wpseopilot-tab-panel').removeClass('is-active');

				$mainTab.addClass('nav-tab-active');
				$(`#wpseopilot-tab-${parsed.mainTab}`).addClass('is-active');
			}

			// 2. Expand accordion if slug provided and we are on content-types
			if (parsed.mainTab === 'content-types' && parsed.accordionSlug) {
				const $accordion = $(`details[data-accordion-slug="${parsed.accordionSlug}"]`);
				if ($accordion.length) {
					$accordion.prop('open', true);

					// Force init tabs inside
					const initAccordionInside = () => {
						const $container = $accordion.find('.wpseopilot-accordion-tabs');

						// Manually trigger tab switch logic from initAccordionTabs
						// We need to define activate function locally or reuse generic click trigger
						const $tabs = $container.find('[data-accordion-tab]');
						const $panels = $container.find('.wpseopilot-accordion-tab-panel');

						if (parsed.subTab) {
							const targetId = `wpseopilot-accordion-${parsed.accordionSlug}-${parsed.subTab}`;

							$tabs.removeClass('is-active').attr('aria-selected', 'false');
							$panels.removeClass('is-active').attr('hidden', '');

							$tabs.filter(`[data-accordion-tab="${targetId}"]`)
								.addClass('is-active').attr('aria-selected', 'true');
							$panels.filter(`#${targetId}`).addClass('is-active').removeAttr('hidden');

							// Scroll to accordion
							setTimeout(() => {
								$accordion[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
							}, 200);
						}
					};
					// Wait for DOM paint/expand
					setTimeout(initAccordionInside, 50);
				}
			}
		};

		// Event Listeners for State Change
		$('.nav-tab').on('click', function () {
			// Small delay to let default handler run (if any) or just run update immediately
			// We attach to click, but the actual tab switch might happen via hashed hrefs in some implementations.
			// Here we assume our custom tab handler toggles classes.
			// Ideally we hook into the tab activation logic.
			setTimeout(updateHash, 50);
		});

		$(document).on('click', '.wpseopilot-accordion-tab', function () {
			setTimeout(updateHash, 50);
		});

		$(document).on('toggle', '.wpseopilot-accordion', function () {
			if (this.open) {
				// Close other accordions? (Optional, user didn't ask but good for deep link clarity)
				// For now let's just update hash based on this one.
				updateHash();
			} else {
				// If closing, maybe revert to just main tab hash?
				// Delay to check if another one opened?
				// If all closed, revert to #content-types
				setTimeout(() => {
					if (!$('.wpseopilot-accordion[open]').length) {
						updateHash();
					}
				}, 50);
			}
		});

		// Run restore on load
		restoreState();

		// Handle browser back/forward
		$(window).on('hashchange', restoreState);
	};

	// Lazy initialization on accordion open
	const initAccordionOpenHandlers = () => {
		// Event delegation setup for 'toggle' which doesn't bubble, so we catch it at document level with capture=true
		// However jQuery doesn't easily support capture phase in 'on'. 
		// We can try to bind to all details elements present now, and maybe use a MutationObserver if they are dynamic.
		// Since this is admin page, they are likely static.

		const details = document.querySelectorAll('details.wpseopilot-accordion');
		details.forEach((el) => {
			el.addEventListener('toggle', (e) => {
				if (el.open) {
					// We need to use jQuery to match the initAccordionTabs logic expectations
					// or just call it globally since it checks class guards
					initAccordionTabs();

					// Also refresh the google preview sizing if needed? 
					// The preview might need a refresh of text if it was hidden
					// but standard flow updates it on input.
				}
			});
		});

		// Fallback: Check on click of summary
		$(document).on('click', 'summary', function () {
			setTimeout(() => {
				initAccordionTabs();
			}, 50);
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

	// Copy variable handler
	$(document).on('click', '.wpseopilot-copy-var', function (e) {
		e.preventDefault();
		const $btn = $(this);
		const variable = $btn.data('var');
		const text = '{{' + variable + '}}'; // Include brackets for easy usage

		navigator.clipboard.writeText(text).then(function () {
			const $icon = $btn.find('.dashicons');
			const original = $icon.attr('class');

			$icon.removeClass('dashicons-docs').addClass('dashicons-yes');
			$btn.addClass('button-primary');

			setTimeout(function () {
				$icon.attr('class', original);
				$btn.removeClass('button-primary');
			}, 1500);
		}, function (err) {
			console.error('Async: Could not copy text: ', err);
		});
	});

	/**
	 * Initialize social card preview functionality.
	 */
	const initSocialCardPreview = () => {
		const $preview = $('#wpseopilot-social-card-preview-img');
		const $titleInput = $('#wpseopilot-preview-title');
		const $refreshBtn = $('#wpseopilot-refresh-preview');

		if (!$preview.length) {
			return;
		}

		// Debounce helper
		const debounce = (func, wait) => {
			let timeout;
			return function (...args) {
				clearTimeout(timeout);
				timeout = setTimeout(() => func.apply(this, args), wait);
			};
		};

		// Update preview image
		const updatePreview = () => {
			const title = $titleInput.val() || 'Sample Post Title';
			const baseUrl = window.location.protocol + '//' + window.location.host;
			const url = baseUrl + '/?wpseopilot_social_card=1&title=' + encodeURIComponent(title);

			$preview.closest('.wpseopilot-social-card-preview__frame').addClass('is-loading');
			$preview.attr('src', url + '&t=' + Date.now()).on('load', function() {
				$(this).closest('.wpseopilot-social-card-preview__frame').removeClass('is-loading');
			});
		};

		// Auto-update on design changes
		$('.wpseopilot-color-picker, input[name^="wpseopilot_social_card_design"]').on('change',
			debounce(updatePreview, 1000)
		);

		// Manual refresh
		$refreshBtn.on('click', function(e) {
			e.preventDefault();
			updatePreview();
		});

		// Title input
		$titleInput.on('keyup', debounce(updatePreview, 500));

		// Color picker sync to text field
		$('.wpseopilot-color-picker').on('input change', function() {
			$(this).next('.wpseopilot-color-text').val($(this).val());
		});

		// Initial load
		updatePreview();
	};

	/**
	 * Initialize media upload for social card logo.
	 */
	const initSocialCardMedia = () => {
		$('.wpseopilot-media-upload-btn').on('click', function(e) {
			e.preventDefault();

			const $btn = $(this);
			const targetSelector = $btn.data('target');
			const $target = $(targetSelector);

			if (!$target.length) {
				return;
			}

			const frame = wp.media({
				title: 'Select Logo',
				button: { text: 'Use this image' },
				multiple: false
			});

			frame.on('select', function() {
				const attachment = frame.state().get('selection').first().toJSON();
				$target.val(attachment.url);
				$target.trigger('change');
			});

			frame.open();
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
		initSocialCardPreview();
		initSocialCardMedia();
	});
})(jQuery, window.WPSEOPilotAdmin);
