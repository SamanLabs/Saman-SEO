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

	$(document).ready(function () {
		['wpseopilot_title', 'wpseopilot_description'].forEach(counter);
		updatePreview();
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
				aiConfig.strings?.disabled || ''
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
			setAiStatus(statusEl, aiConfig.strings?.error || '', 'error');
			return;
		}

		button.prop('disabled', true);
		setAiStatus(
			statusEl,
			aiConfig.strings?.running || 'Generating…',
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
						aiConfig.strings?.error ||
						'';
					setAiStatus(statusEl, message, 'error');
					return;
				}

				const value = response.data.value || '';
				target.val(value).trigger('input');
				setAiStatus(
					statusEl,
					aiConfig.strings?.success || '',
					'success'
				);
			}
		)
			.fail((xhr) => {
				const message =
					xhr?.responseJSON?.data ||
					xhr?.statusText ||
					aiConfig.strings?.error ||
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
})(jQuery, window.WPSEOPilotAdmin);
