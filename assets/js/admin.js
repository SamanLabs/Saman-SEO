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

	$(document).ready(function () {
		['wpseopilot_title', 'wpseopilot_description'].forEach(counter);
		updatePreview();
		initTabs();
		initSchemaControls();
	});
})(jQuery, window.WPSEOPilotAdmin);
