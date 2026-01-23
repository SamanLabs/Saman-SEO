/**
 * Saman SEO Admin JavaScript
 *
 * Handles media picker, form interactions, and other admin functionality.
 *
 * @package Saman\SEO
 */

(function ($) {
	'use strict';

	/**
	 * Media Picker Module
	 * Handles WordPress Media Library integration for image selection
	 */
	const MediaPicker = {
		/**
		 * Initialize media picker functionality
		 */
		init: function () {
			// Bind to both button classes used in templates
			$(document).on('click', '.saman-seo-media-upload-btn, .saman-seo-media-trigger', this.openMediaPicker);
			$(document).on('click', '.saman-seo-media-remove-btn', this.removeImage);
		},

		/**
		 * Open WordPress Media Library picker
		 *
		 * @param {Event} e Click event
		 */
		openMediaPicker: function (e) {
			e.preventDefault();

			const $button = $(this);
			const $container = $button.closest('.saman-seo-media-upload, .saman-seo-media-field');
			const $input = $container.find('input[type="url"], input[type="text"]').first();
			const $preview = $container.find('.saman-seo-media-preview');

			// Create media frame if it doesn't exist
			let mediaFrame = $button.data('mediaFrame');

			if (!mediaFrame) {
				mediaFrame = wp.media({
					title: window.SamanSEOAdmin?.mediaTitle || 'Select Image',
					button: {
						text: window.SamanSEOAdmin?.mediaButton || 'Use this image'
					},
					multiple: false,
					library: {
						type: 'image'
					}
				});

				// When an image is selected
				mediaFrame.on('select', function () {
					const attachment = mediaFrame.state().get('selection').first().toJSON();
					const imageUrl = attachment.url;
					const imageId = attachment.id;

					// Set the input value
					$input.val(imageUrl).trigger('change');

					// Update preview if exists
					if ($preview.length) {
						MediaPicker.updatePreview($preview, imageUrl, imageId);
					} else {
						// Create preview if it doesn't exist
						MediaPicker.createPreview($container, imageUrl, imageId);
					}

					// Show remove button
					$container.find('.saman-seo-media-remove-btn').show();
				});

				$button.data('mediaFrame', mediaFrame);
			}

			// Open the media frame
			mediaFrame.open();

			// If there's already an image, select it
			if ($input.val()) {
				const selection = mediaFrame.state().get('selection');
				// Try to get attachment by URL - this may not always work
				// but provides a better UX when editing existing images
			}
		},

		/**
		 * Remove selected image
		 *
		 * @param {Event} e Click event
		 */
		removeImage: function (e) {
			e.preventDefault();

			const $button = $(this);
			const $container = $button.closest('.saman-seo-media-upload, .saman-seo-media-field');
			const $input = $container.find('input[type="url"], input[type="text"]').first();
			const $preview = $container.find('.saman-seo-media-preview');

			// Clear the input
			$input.val('').trigger('change');

			// Remove preview
			$preview.empty().hide();

			// Hide remove button
			$button.hide();
		},

		/**
		 * Update existing preview
		 *
		 * @param {jQuery} $preview Preview element
		 * @param {string} imageUrl Image URL
		 * @param {number} imageId Attachment ID
		 */
		updatePreview: function ($preview, imageUrl, imageId) {
			$preview.html('<img src="' + imageUrl + '" alt="" />').show();
		},

		/**
		 * Create preview element
		 *
		 * @param {jQuery} $container Container element
		 * @param {string} imageUrl Image URL
		 * @param {number} imageId Attachment ID
		 */
		createPreview: function ($container, imageUrl, imageId) {
			const $input = $container.find('input[type="url"], input[type="text"]').first();
			const $preview = $('<div class="saman-seo-media-preview"><img src="' + imageUrl + '" alt="" /></div>');
			const $removeBtn = $('<button type="button" class="button saman-seo-media-remove-btn">Remove</button>');

			// Insert after input
			if (!$container.find('.saman-seo-media-preview').length) {
				$input.after($preview);
			}

			// Add remove button if it doesn't exist
			if (!$container.find('.saman-seo-media-remove-btn').length) {
				$container.find('.saman-seo-media-upload-btn, .saman-seo-media-trigger').after($removeBtn);
			}
		}
	};

	/**
	 * Color Picker Sync
	 * Syncs color picker with text input display
	 */
	const ColorPickerSync = {
		init: function () {
			$(document).on('input change', '.saman-seo-color-picker', this.syncColor);
		},

		syncColor: function () {
			const $picker = $(this);
			const $textInput = $picker.siblings('.saman-seo-color-text');
			$textInput.val($picker.val());
		}
	};

	/**
	 * Social Card Preview
	 * Handles live preview updates for social card customization
	 */
	const SocialCardPreview = {
		init: function () {
			$('#saman-seo-refresh-preview').on('click', this.refreshPreview);

			// Auto-refresh on input changes with debounce
			let debounceTimer;
			$(document).on('change', '[name^="SAMAN_SEO_social_card_design"]', function () {
				clearTimeout(debounceTimer);
				debounceTimer = setTimeout(SocialCardPreview.refreshPreview, 500);
			});
		},

		refreshPreview: function () {
			const $preview = $('#saman-seo-social-card-preview-img');
			const $loading = $('.saman-seo-social-card-preview__loading');
			const title = $('#saman-seo-preview-title').val() || 'Sample Post Title';

			// Show loading
			$loading.show();

			// Build preview URL with current settings
			let previewUrl = window.location.origin + '/?SAMAN_SEO_social_card=1';
			previewUrl += '&title=' + encodeURIComponent(title);
			previewUrl += '&_=' + Date.now(); // Cache bust

			// Load new preview
			$preview.on('load', function () {
				$loading.hide();
			}).on('error', function () {
				$loading.hide();
			}).attr('src', previewUrl);
		}
	};

	/**
	 * Form Validation
	 * Handles form validation and save state
	 */
	const FormHandler = {
		init: function () {
			// Mark form as dirty when changes are made
			$(document).on('change input', '.saman-seo-settings__form input, .saman-seo-settings__form textarea, .saman-seo-settings__form select', function () {
				$(this).closest('form').data('dirty', true);
			});

			// Warn before leaving with unsaved changes
			$(window).on('beforeunload', function () {
				if ($('.saman-seo-settings__form').data('dirty')) {
					return 'You have unsaved changes. Are you sure you want to leave?';
				}
			});

			// Clear dirty flag on submit
			$(document).on('submit', '.saman-seo-settings__form', function () {
				$(this).data('dirty', false);
			});
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function () {
		MediaPicker.init();
		ColorPickerSync.init();
		SocialCardPreview.init();
		FormHandler.init();

		// Initialize existing image previews
		$('.saman-seo-media-upload input[type="url"], .saman-seo-media-field input[type="url"]').each(function () {
			const $input = $(this);
			const $container = $input.closest('.saman-seo-media-upload, .saman-seo-media-field');
			const value = $input.val();

			if (value) {
				// Create preview for existing values
				if (!$container.find('.saman-seo-media-preview').length) {
					const $preview = $('<div class="saman-seo-media-preview"><img src="' + value + '" alt="" /></div>');
					$input.after($preview);
				}

				// Add remove button if not exists
				if (!$container.find('.saman-seo-media-remove-btn').length) {
					const $removeBtn = $('<button type="button" class="button saman-seo-media-remove-btn">Remove</button>');
					$container.find('.saman-seo-media-upload-btn, .saman-seo-media-trigger').after($removeBtn);
				}
			}
		});
	});

})(jQuery);
