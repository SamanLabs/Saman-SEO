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
	 * Local SEO Module
	 * Handles opening hours presets, live preview, and entity card selection
	 */
	const LocalSEO = {
		debounceTimer: null,

		/**
		 * Initialize Local SEO functionality
		 */
		init: function () {
			// Only initialize if we're on the Local SEO page
			if (!$('.saman-seo-local-seo-page').length) {
				return;
			}

			this.initEntityCards();
			this.initHoursPresets();
			this.initHoursCopy();
			this.initHoursToggle();
			this.initLivePreview();
			this.updateClosedDaysSummary();
			this.initGoogleMaps();
		},

		/**
		 * Initialize entity type card selection
		 */
		initEntityCards: function () {
			$('[data-component="saman-seo-entity-cards"] input[type="radio"]').on('change', function () {
				const $cards = $(this).closest('[data-component="saman-seo-entity-cards"]');
				$cards.find('.saman-seo-entity-card').removeClass('is-selected');
				$(this).closest('.saman-seo-entity-card').addClass('is-selected');
			});
		},

		/**
		 * Initialize opening hours presets
		 */
		initHoursPresets: function () {
			const self = this;

			$('[data-component="saman-seo-hours-presets"] [data-preset]').on('click', function () {
				const preset = $(this).data('preset');
				const $grid = $('[data-component="saman-seo-hours-grid"]');

				// Remove active state from all preset buttons
				$('[data-component="saman-seo-hours-presets"] [data-preset]').removeClass('is-active');
				$(this).addClass('is-active');

				const presets = {
					'9-5-mon-fri': {
						weekdays: { enabled: true, open: '09:00', close: '17:00' },
						weekends: { enabled: false, open: '09:00', close: '17:00' }
					},
					'9-6-mon-sat': {
						weekdays: { enabled: true, open: '09:00', close: '18:00' },
						saturday: { enabled: true, open: '09:00', close: '18:00' },
						sunday: { enabled: false, open: '09:00', close: '18:00' }
					},
					'24-7': {
						all: { enabled: true, open: '00:00', close: '23:59' }
					},
					'clear': {
						all: { enabled: false, open: '09:00', close: '17:00' }
					}
				};

				const config = presets[preset];
				if (!config) return;

				$grid.find('.saman-seo-hours__row').each(function () {
					const $row = $(this);
					const day = $row.data('day');
					const isWeekday = $row.data('weekday') === true || $row.data('weekday') === 'true';
					const isWeekend = day === 'saturday' || day === 'sunday';

					let settings;

					if (config.all) {
						settings = config.all;
					} else if (day === 'saturday' && config.saturday) {
						settings = config.saturday;
					} else if (day === 'sunday' && config.sunday) {
						settings = config.sunday;
					} else if (isWeekday && config.weekdays) {
						settings = config.weekdays;
					} else if (isWeekend && config.weekends) {
						settings = config.weekends;
					}

					if (settings) {
						$row.find('[data-day-toggle]').prop('checked', settings.enabled).trigger('change');
						$row.find('[data-time="open"]').val(settings.open);
						$row.find('[data-time="close"]').val(settings.close);
					}
				});

				self.updateClosedDaysSummary();
			});
		},

		/**
		 * Initialize copy hours to weekdays functionality
		 */
		initHoursCopy: function () {
			const self = this;

			$('[data-copy-hours]').on('click', function () {
				const $sourceRow = $(this).closest('.saman-seo-hours__row');
				const openTime = $sourceRow.find('[data-time="open"]').val();
				const closeTime = $sourceRow.find('[data-time="close"]').val();
				const enabled = $sourceRow.find('[data-day-toggle]').prop('checked');

				// Apply to all weekdays
				$('.saman-seo-hours__row[data-weekday="true"]').each(function () {
					$(this).find('[data-day-toggle]').prop('checked', enabled).trigger('change');
					$(this).find('[data-time="open"]').val(openTime);
					$(this).find('[data-time="close"]').val(closeTime);
				});

				self.updateClosedDaysSummary();
			});
		},

		/**
		 * Initialize hours toggle functionality
		 */
		initHoursToggle: function () {
			const self = this;

			$('[data-day-toggle]').on('change', function () {
				const $row = $(this).closest('.saman-seo-hours__row');
				if ($(this).prop('checked')) {
					$row.removeClass('is-disabled');
				} else {
					$row.addClass('is-disabled');
				}
				self.updateClosedDaysSummary();
			});
		},

		/**
		 * Update closed days summary
		 */
		updateClosedDaysSummary: function () {
			const closedDays = [];
			const dayNames = {
				'monday': 'Monday',
				'tuesday': 'Tuesday',
				'wednesday': 'Wednesday',
				'thursday': 'Thursday',
				'friday': 'Friday',
				'saturday': 'Saturday',
				'sunday': 'Sunday'
			};

			$('.saman-seo-hours__row').each(function () {
				const day = $(this).data('day');
				const enabled = $(this).find('[data-day-toggle]').prop('checked');
				if (!enabled) {
					closedDays.push(dayNames[day]);
				}
			});

			const $summary = $('#saman-seo-hours-summary');
			const $closedDays = $('#saman-seo-closed-days');

			if (closedDays.length > 0) {
				$closedDays.text(closedDays.join(', '));
				$summary.show();
			} else {
				$summary.hide();
			}
		},

		/**
		 * Initialize live preview updates
		 */
		initLivePreview: function () {
			const self = this;

			// Debounced update for all preview fields
			$('[data-preview-field]').on('input change', function () {
				clearTimeout(self.debounceTimer);
				self.debounceTimer = setTimeout(function () {
					self.updatePreview();
				}, 300);
			});

			// Immediate update for media picker changes
			$(document).on('change', '[data-preview-field="logo"], [data-preview-field="cover"]', function () {
				self.updatePreview();
			});
		},

		/**
		 * Update the knowledge panel preview
		 */
		updatePreview: function () {
			const $panel = $('.saman-seo-knowledge-panel');
			if (!$panel.length) return;

			// Update business name
			const name = $('[data-preview-field="name"]').val() || $('[data-preview-field="name"]').attr('placeholder') || '';
			const $nameEl = $panel.find('[data-preview-name]');
			if (name) {
				$nameEl.text(name).removeClass('is-empty');
			} else {
				$nameEl.text('Business Name').addClass('is-empty');
			}

			// Update business type
			const typeVal = $('[data-preview-field="type"]').val() || 'LocalBusiness';
			const typeLabel = typeVal.replace(/([A-Z])/g, ' $1').replace(/^ /, '').replace('Local Business', 'Local Business');
			$panel.find('[data-preview-type]').text(typeLabel);

			// Update description
			const desc = $('[data-preview-field="description"]').val() || '';
			const $descEl = $panel.find('[data-preview-description]');
			if (desc) {
				$descEl.text(desc).removeClass('is-empty');
			} else {
				$descEl.text('Add a description to tell customers about your business.').addClass('is-empty');
			}

			// Update logo
			const logo = $('[data-preview-field="logo"]').val() || $('[data-preview-field="logo"]').attr('placeholder') || '';
			const $logoEl = $panel.find('[data-preview-logo]');
			if (logo) {
				$logoEl.css('background-image', 'url(' + logo + ')').addClass('has-image').html('');
			} else {
				$logoEl.css('background-image', '').removeClass('has-image').html('<span class="dashicons dashicons-building"></span>');
			}

			// Update cover image
			const cover = $('[data-preview-field="cover"]').val() || '';
			const $coverEl = $panel.find('[data-preview-cover]');
			if (cover) {
				$coverEl.css('background-image', 'url(' + cover + ')').addClass('has-image').html('');
			} else {
				$coverEl.css('background-image', '').removeClass('has-image').html('<span class="dashicons dashicons-format-image"></span>');
			}

			// Update address
			const street = $('[data-preview-field="street"]').val() || '';
			const city = $('[data-preview-field="city"]').val() || '';
			const state = $('[data-preview-field="state"]').val() || '';
			const zip = $('[data-preview-field="zip"]').val() || '';
			const addressParts = [street, city, state].filter(function (p) { return p; });
			let address = addressParts.join(', ');
			if (zip) address += ' ' + zip;

			const $addressEl = $panel.find('[data-preview-address]');
			if (address) {
				$addressEl.removeClass('is-empty').find('span:not(.dashicons)').text(address);
			} else {
				$addressEl.addClass('is-empty').find('span:not(.dashicons)').text('No address set');
			}

			// Update phone
			const phone = $('[data-preview-field="phone"]').val() || '';
			const $phoneEl = $panel.find('[data-preview-phone]');
			if (phone) {
				$phoneEl.removeClass('is-empty').find('span:not(.dashicons)').text(phone);
			} else {
				$phoneEl.addClass('is-empty').find('span:not(.dashicons)').text('No phone set');
			}

			// Update price range
			const price = $('[data-preview-field="price"]').val() || '';
			const $priceEl = $panel.find('[data-preview-price]');
			if (price) {
				$priceEl.show().find('.saman-seo-knowledge-panel__price').text(price);
			} else {
				$priceEl.hide();
			}
		},

		/**
		 * Initialize Google Maps integration
		 */
		initGoogleMaps: function () {
			const $mapContainer = $('#saman-seo-google-map');
			if (!$mapContainer.length || !$mapContainer.hasClass('is-interactive')) {
				return;
			}

			const apiKey = $mapContainer.data('api-key');
			if (!apiKey) return;

			// Load Google Maps script if not already loaded
			if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
				const script = document.createElement('script');
				script.src = 'https://maps.googleapis.com/maps/api/js?key=' + apiKey + '&callback=samanSeoInitMap';
				script.async = true;
				script.defer = true;
				document.head.appendChild(script);

				// Define the callback
				window.samanSeoInitMap = function () {
					LocalSEO.setupMap();
				};
			} else {
				this.setupMap();
			}
		},

		/**
		 * Setup Google Map with marker
		 */
		setupMap: function () {
			const $mapContainer = $('#saman-seo-google-map');
			const $latInput = $('[data-coord="lat"]');
			const $lngInput = $('[data-coord="lng"]');

			// Get initial coordinates or use defaults
			let lat = parseFloat($latInput.val()) || 25.761681;
			let lng = parseFloat($lngInput.val()) || -80.191788;

			const map = new google.maps.Map($mapContainer[0], {
				center: { lat: lat, lng: lng },
				zoom: 15,
				mapTypeControl: false,
				streetViewControl: false
			});

			const marker = new google.maps.Marker({
				position: { lat: lat, lng: lng },
				map: map,
				draggable: true,
				title: 'Drag to set location'
			});

			// Update coordinates on marker drag
			marker.addListener('dragend', function () {
				const position = marker.getPosition();
				$latInput.val(position.lat().toFixed(6));
				$lngInput.val(position.lng().toFixed(6));
			});

			// Update marker on map click
			map.addListener('click', function (e) {
				marker.setPosition(e.latLng);
				$latInput.val(e.latLng.lat().toFixed(6));
				$lngInput.val(e.latLng.lng().toFixed(6));
			});

			// Update map when coordinates are manually entered
			$latInput.add($lngInput).on('change', function () {
				const newLat = parseFloat($latInput.val());
				const newLng = parseFloat($lngInput.val());
				if (!isNaN(newLat) && !isNaN(newLng)) {
					const newPos = { lat: newLat, lng: newLng };
					marker.setPosition(newPos);
					map.setCenter(newPos);
				}
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
		LocalSEO.init();

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
