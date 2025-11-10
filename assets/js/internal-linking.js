(function (window, document) {
	const config = window.WPSEOPilotLinks || {};
	const ajaxUrl = config.ajax || '';
	const nonce = config.nonce || '';

	const parseKeywords = (value) =>
		(value || '')
			.split(/\r?\n|,/)
			.map((item) => item.trim())
			.filter(Boolean);

	const renderTags = (wrapper) => {
		const store = wrapper.querySelector('[data-tag-input-store]');
		const list = wrapper.querySelector('[data-tag-list]');
		if (!store || !list) {
			return;
		}

		const tags = parseKeywords(store.value);
		list.innerHTML = '';

		tags.forEach((tag) => {
			const chip = document.createElement('span');
			chip.className = 'wpseopilot-tag';
			chip.textContent = tag;

			const remove = document.createElement('button');
			remove.type = 'button';
			remove.className = 'wpseopilot-tag__remove';
		const removeLabel = (config.labels && config.labels.remove) || 'Remove';
			remove.setAttribute('aria-label', removeLabel);
			remove.dataset.removeTag = tag;
			remove.textContent = '×';

			chip.appendChild(remove);
			list.appendChild(chip);
		});
	};

	const initTagInputs = () => {
		document.querySelectorAll('[data-tag-input]').forEach((wrapper) => {
			const store = wrapper.querySelector('[data-tag-input-store]');
			const field = wrapper.querySelector('[data-tag-input-field]');
			const list = wrapper.querySelector('[data-tag-list]');

			if (!store || !field || !list) {
				return;
			}

			const updateStore = (tags) => {
				store.value = tags.join('\n');
				renderTags(wrapper);
			};

			renderTags(wrapper);

			field.addEventListener('keydown', (event) => {
				if (event.key !== 'Enter' && event.key !== ',') {
					return;
				}

				event.preventDefault();
				const value = field.value.trim();
				if (!value) {
					return;
				}

				const existing = parseKeywords(store.value);
				if (!existing.includes(value)) {
					existing.push(value);
					updateStore(existing);
				}

				field.value = '';
			});

			list.addEventListener('click', (event) => {
				const button = event.target.closest('[data-remove-tag]');
				if (!button) {
					return;
				}

				event.preventDefault();
				const tag = button.dataset.removeTag;
				const existing = parseKeywords(store.value).filter((item) => item !== tag);
				updateStore(existing);
			});
		});
	};

	const initCategorySelect = () => {
		const select = document.querySelector('[data-category-select]');
		const target = document.querySelector('[data-new-category]');

		if (!select || !target) {
			return;
		}

		const toggle = () => {
			target.hidden = select.value !== '__new__';
		};

		select.addEventListener('change', toggle);
		toggle();
	};

	const fetchSuggestions = (term, onSuccess) => {
		if (!ajaxUrl || !term) {
			return;
		}

		const params = new URLSearchParams({
			action: 'wpseopilot_link_destination_search',
			nonce,
			term,
		});

		fetch(`${ajaxUrl}?${params.toString()}`)
			.then((response) => response.json())
			.then((payload) => {
				if (!payload || !payload.success) {
					return;
				}

				onSuccess(payload.data || []);
			})
			.catch(() => {});
	};

	const bindLookupField = (input, hidden, list) => {
		if (!input || !hidden || !list) {
			return;
		}

		let timer = null;

		const showSuggestions = (items) => {
			if (!items.length) {
				list.hidden = true;
				list.innerHTML = '';
				return;
			}

			list.innerHTML = '';
			items.forEach((item) => {
				const button = document.createElement('button');
				button.type = 'button';
				button.textContent = `${item.title} (${item.type})`;
				button.dataset.id = item.id;
				list.appendChild(button);
			});
			list.hidden = false;
		};

		input.addEventListener('input', () => {
			hidden.value = '';
			if (timer) {
				clearTimeout(timer);
			}

			const value = input.value.trim();
			if (!value) {
				list.hidden = true;
				return;
			}

			timer = window.setTimeout(() => fetchSuggestions(value, showSuggestions), 220);
		});

		list.addEventListener('click', (event) => {
			const button = event.target.closest('button[data-id]');
			if (!button) {
				return;
			}

			event.preventDefault();
			hidden.value = button.dataset.id;
			input.value = button.textContent;
			list.hidden = true;
		});
	};

	const initDestinationLookups = () => {
		document.querySelectorAll('[data-destination-input]').forEach((input) => {
			const wrapper = input.closest('[data-destination-field]');
			const hidden = wrapper ? wrapper.querySelector('[data-destination-value]') : null;
			const list = wrapper ? wrapper.querySelector('[data-destination-suggestions]') : null;
			bindLookupField(input, hidden, list);
		});

		document.querySelectorAll('[data-destination-toggle]').forEach((radio) => {
			radio.addEventListener('change', () => {
				const value = radio.value;
				document.querySelectorAll('[data-destination-field]').forEach((field) => {
					field.hidden = field.dataset.destinationField !== value;
				});
			});
		});
	};

	const initPreviewLookup = () => {
		document.querySelectorAll('[data-preview-input]').forEach((input) => {
			const wrapper = input.closest('[data-preview-target]');
			const hidden = wrapper ? wrapper.querySelector('[data-preview-post]') : null;
			const list = wrapper ? wrapper.querySelector('[data-preview-suggestions]') : null;
			bindLookupField(input, hidden, list);
		});
	};

	const initBulkActions = () => {
		document.querySelectorAll('[data-bulk-action]').forEach((select) => {
			const form = select.closest('[data-bulk-form]');
			const category = form ? form.querySelector('[data-bulk-category]') : null;
			if (!category) {
				return;
			}

			const toggle = () => {
				category.hidden = select.value !== 'change_category';
			};

			select.addEventListener('change', toggle);
			toggle();
		});
	};

	const initSelectAll = () => {
		document.querySelectorAll('[data-select-all]').forEach((checkbox) => {
			const table = checkbox.closest('table');
			if (!table) {
				return;
			}

			checkbox.addEventListener('change', () => {
				const checked = checkbox.checked;
				table.querySelectorAll('tbody input[type="checkbox"]').forEach((cb) => {
					cb.checked = checked;
				});
			});
		});
	};

	const initHeadingControls = () => {
		document.querySelectorAll('[data-heading-toggle]').forEach((radio) => {
			const fieldset = radio.closest('fieldset');
			const levels = fieldset ? fieldset.querySelector('[data-heading-levels]') : null;
			if (!levels) {
				return;
			}

			const toggle = () => {
				levels.hidden = radio.value !== 'selected' || !radio.checked;
			};

			radio.addEventListener('change', toggle);
			toggle();
		});

		const settingsRadios = document.querySelectorAll('input[name="settings[default_heading_behavior]"]');
		const settingsLevels = document.querySelector('[data-settings-heading]');
		if (settingsRadios.length && settingsLevels) {
			const toggle = () => {
				const current = Array.from(settingsRadios).find((radio) => radio.checked);
				settingsLevels.hidden = !current || current.value !== 'selected';
			};

			settingsRadios.forEach((radio) => radio.addEventListener('change', toggle));
			toggle();
		}
	};

	const setStatus = (element, message, state) => {
		if (!element) {
			return;
		}

		element.textContent = message || '';
		element.classList.remove('is-error', 'is-success', 'is-loading');
		if (state) {
			element.classList.add(state);
		}
	};

	const initPreview = () => {
		const form = document.querySelector('.wpseopilot-links__rule-form');
		if (!form) {
			return;
		}

		const button = form.querySelector('[data-preview-run]');
		const statusEl = form.querySelector('[data-preview-status]');
		const output = form.querySelector('[data-preview-result]');
		const container = form.querySelector('[data-preview-output]');
		const postField = form.querySelector('[data-preview-post]');
		const urlField = form.querySelector('[data-preview-url]');

		if (!button || !statusEl || !output || !container) {
			return;
		}

		button.addEventListener('click', () => {
			const postId = postField ? postField.value : '';
			const previewUrl = urlField ? urlField.value.trim() : '';

			if (!postId && !previewUrl) {
				const msg = (config.labels && config.labels.previewSelect) || 'Select a post or enter a URL to preview.';
				setStatus(statusEl, msg, 'is-error');
				container.hidden = false;
				return;
			}

			const formData = new FormData(form);
			formData.delete('action');
			formData.append('action', 'wpseopilot_link_preview');
			formData.append('nonce', nonce);
			formData.append('preview[post]', postId);
			formData.append('preview[url]', previewUrl);

			button.disabled = true;
			setStatus(statusEl, (config.labels && config.labels.previewRunning) || 'Generating preview…', 'is-loading');
			container.hidden = false;

			fetch(ajaxUrl, {
				method: 'POST',
				body: formData,
			})
				.then((response) => response.json())
				.then((payload) => {
					if (!payload || !payload.success) {
						const errorMessage = (payload && payload.data) || (config.labels && config.labels.previewError) || 'Unable to run preview.';
						throw errorMessage;
					}

					const data = payload.data || {};
					const replacements = data.replacements || [];
					const count = replacements.reduce((total, item) => total + (item.count || 0), 0);
					const successLabel = (config.labels && config.labels.previewSuccess) || 'Preview complete: %d replacement(s).';
					const emptyLabel = (config.labels && config.labels.previewEmpty) || 'No replacements found.';
					const message = count ? successLabel.replace('%d', count) : emptyLabel;

					setStatus(statusEl, message, count ? 'is-success' : '');
					output.textContent = data.content || '';
				})
				.catch((error) => {
					const defaultMessage = (config.labels && config.labels.previewError) || 'Unable to run preview.';
					const message = typeof error === 'string' ? error : defaultMessage;
					setStatus(statusEl, message, 'is-error');
				})
				.finally(() => {
					button.disabled = false;
				});
		});
	};

	const initForms = () => {
		initTagInputs();
		initCategorySelect();
		initDestinationLookups();
		initPreviewLookup();
		initBulkActions();
		initSelectAll();
		initHeadingControls();
		initPreview();
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initForms);
	} else {
		initForms();
	}
})(window, document);
