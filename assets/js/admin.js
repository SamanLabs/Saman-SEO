(function ($, settings) {
	settings = settings || {
		mediaTitle: 'Select image',
		mediaButton: 'Use image',
	};

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
})(jQuery, window.WPSEOPilotAdmin);
