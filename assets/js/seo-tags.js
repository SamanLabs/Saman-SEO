/**
 * WP SEO Pilot - Visual Tags & Rich Input
 */
(function ($, settings) {
    const initRichTags = () => {
        // Inject styles for the menu
        const injectStyles = () => {
            if ($('#wpseopilot-menu-styles').length) return;
            const style = `
                .wpseopilot-autocomplete-menu {
                    position: absolute;
                    background: #fff;
                    border: 1px solid #8c8f94;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
                    z-index: 100000;
                    max-height: 300px;
                    overflow-y: auto;
                    border-radius: 4px;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                }
                .wpseopilot-autocomplete-header {
                    padding: 8px 12px;
                    background: #f0f0f1;
                    font-weight: 700;
                    color: #1d2327;
                    font-size: 12px;
                    text-transform: uppercase;
                    border-bottom: 1px solid #c3c4c7;
                    position: sticky;
                    top: 0;
                    z-index: 1;
                }
                .wpseopilot-autocomplete-item {
                    padding: 8px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #f0f0f1;
                    transition: none;
                    color: #2c3338;
                }
                .wpseopilot-autocomplete-item:last-child {
                    border-bottom: none;
                }
                /* Selected / Hover State - High Contrast Light Gray */
                .wpseopilot-autocomplete-item:hover, .wpseopilot-autocomplete-item.is-selected {
                    background: #f0f0f1;
                    color: #1d2327;
                    border-left: 4px solid #2271b1; /* Blue accent bar */
                    padding-left: 8px; /* Offset for border */
                }
                .wpseopilot-autocomplete-item .item-content {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 4px;
                }
                .wpseopilot-autocomplete-item .item-tag {
                    font-weight: 700;
                    font-family: monospace;
                    background: #dcdcde;
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-size: 12px;
                    color: #1d2327;
                    border: 1px solid #c3c4c7;
                }
                /* Tag in selected state - distinct but readable */
                .wpseopilot-autocomplete-item.is-selected .item-tag, .wpseopilot-autocomplete-item:hover .item-tag {
                    background: #fff;
                    border-color: #8c8f94;
                    color: #1d2327;
                }
                .wpseopilot-autocomplete-item .item-preview {
                    font-size: 11px;
                    color: #50575e;
                    max-width: 50%;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    text-align: right;
                }
                .wpseopilot-autocomplete-item.is-selected .item-preview, .wpseopilot-autocomplete-item:hover .item-preview {
                    color: #1d2327;
                    font-weight: 500;
                }
                .wpseopilot-autocomplete-item .item-desc {
                    font-size: 11px;
                    color: #646970;
                    margin-top: 2px;
                }
                .wpseopilot-autocomplete-item.is-selected .item-desc, .wpseopilot-autocomplete-item:hover .item-desc {
                    color: #1d2327;
                }
            `;
            $('<style id="wpseopilot-menu-styles">').text(style).appendTo('head');
        };
        injectStyles();

        console.log('WP SEO Pilot: Initializing Rich Tags...');
        const tagRegex = /\{\{\s*([^}]+)\s*\}\}/g; // Matches {{variable}} or {{ variable }}

        // Variables from server settings
        const variableSettings = settings.variables || {};
        const globalVars = variableSettings.global || [];

        // Debounce utility
        const debounce = (func, wait) => {
            let timeout;
            return function (...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        };

        // Fetch Preview
        const fetchPreview = (template, context, $previewContainer) => {
            if (!$previewContainer.length) return;

            // $previewContainer.text('Updating...'); // Optional: visual feedback immediately? Maybe too jittery.

            $.post(settings.ai.ajax, {
                action: 'wpseopilot_render_preview',
                nonce: settings.ai.nonce,
                template: template,
                context: context
            }).done(function (response) {
                if (response.success) {
                    $previewContainer.text(response.data.preview || '(Empty)');
                } else {
                    $previewContainer.text('Preview Error');
                }
            });
        };

        const getVarsForInput = ($input) => {
            const context = $input.data('context');
            let vars = {};

            // Helper to add group if not strict
            const addGroup = (key) => {
                if (variableSettings[key] && !vars[key]) {
                    vars[key] = variableSettings[key];
                }
            };

            if (context) {
                // 1. Try exact context match (e.g. "post_type:book")
                addGroup(context);

                // 2. Try generic fallback
                let genericKey = 'global';
                if (context.includes('post_type') || context === 'post') genericKey = 'post';
                else if (context.includes('taxonomy')) genericKey = 'taxonomy';
                else if (context.includes('archive')) genericKey = 'archive';

                if (genericKey !== context) {
                    addGroup(genericKey);
                }
            }

            // 3. Always add global
            addGroup('global');

            return vars;
        };

        let activeEditor = null;
        let activeVars = []; // Current filtering list
        const $menu = $('<div class="wpseopilot-autocomplete-menu"></div>').appendTo('body');
        let selectedIndex = 0;

        const hideMenu = () => {
            $menu.removeClass('is-visible').empty();
            activeEditor = null;
            selectedIndex = 0;
            activeVars = [];
        };

        const insertVar = (variable) => {
            console.log('WP SEO Pilot: Inserting variable', variable);
            const editor = activeEditor; // Cache it because hideMenu clears it

            if (!editor) {
                console.error('WP SEO Pilot: No active editor found for insertion');
                return;
            }

            // Restore focus to editor if lost
            editor.focus();

            const sel = window.getSelection();
            if (sel.rangeCount) {
                const range = sel.getRangeAt(0);

                // Ensure range is inside the editor
                if (!editor.contains(range.startContainer)) {
                    console.warn('WP SEO Pilot: Selection is outside editor, appending to end');
                    // Move caret to end
                    range.selectNodeContents(editor);
                    range.collapse(false);
                    sel.removeAllRanges();
                    sel.addRange(range);
                }

                const tagHtml = `<span class="wpseopilot-tag" contenteditable="false">{{${variable}}}</span>&nbsp;`;

                const textNode = range.startContainer;
                if (textNode.nodeType === 3) {
                    const text = textNode.textContent;
                    const offset = range.startOffset;
                    // Look for '{{' or '{' just before cursor
                    let triggerLen = 0;
                    if (offset >= 2 && text.substring(offset - 2, offset) === '{{') {
                        triggerLen = 2;
                    }

                    if (triggerLen > 0) {
                        console.log('WP SEO Pilot: Removing trigger characters');
                        const before = text.substring(0, offset - triggerLen);
                        const after = text.substring(offset);
                        textNode.textContent = before + after;

                        const newRange = document.createRange();
                        newRange.setStart(textNode, offset - triggerLen);
                        newRange.setEnd(textNode, offset - triggerLen);
                        sel.removeAllRanges();
                        sel.addRange(newRange);
                    }
                }

                document.execCommand('insertHTML', false, tagHtml);
            } else {
                console.log('WP SEO Pilot: No range, appending to end');
                $(editor).append(`<span class="wpseopilot-tag" contenteditable="false">{{${variable}}}</span>&nbsp;`);
            }

            hideMenu();
            $(editor).trigger('input'); // Sync
            $(editor).focus();
        };

        const renderMenu = (groups) => {
            $menu.empty();
            let hasItems = false;
            let itemIndex = 0;

            Object.keys(groups).forEach(groupKey => {
                const group = groups[groupKey];
                if (!group || !group.vars || !group.vars.length) return;

                const $header = $('<div class="wpseopilot-autocomplete-header"></div>').text(group.label);
                $menu.append($header);

                group.vars.forEach(v => {
                    hasItems = true;
                    const $item = $('<div class="wpseopilot-autocomplete-item"></div>')
                        .attr('data-value', v.tag)
                        .attr('data-index', itemIndex);

                    const $content = $('<div class="item-content"></div>');
                    $content.append($('<span class="item-tag"></span>').text('{{' + v.tag + '}}'));
                    if (v.preview) {
                        $content.append($('<span class="item-preview"></span>').text(v.preview));
                    }
                    $item.append($content);

                    if (v.desc) {
                        $item.append($('<div class="item-desc"></div>').text(v.desc));
                    }

                    if (itemIndex === selectedIndex) {
                        $item.addClass('is-selected');
                    }

                    $item.on('mousedown', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        insertVar(v.tag);
                    });
                    $menu.append($item);
                    itemIndex++;
                });
            });

            if (!hasItems) {
                hideMenu();
                return;
            }

            if (activeEditor) {
                const offset = $(activeEditor).offset();
                $menu.css({
                    top: offset.top + $(activeEditor).outerHeight(),
                    left: offset.left,
                    width: $(activeEditor).outerWidth()
                }).addClass('is-visible');
            }
        };

        const createRichInput = (input) => {
            const $input = $(input);
            if ($input.data('rich-init')) return;
            $input.data('rich-init', true);
            console.log('WP SEO Pilot: Creating rich input for', input.id || input.name);

            const $container = $('<div class="wpseopilot-rich-container"></div>');
            const $editor = $('<div class="wpseopilot-rich-input" contenteditable="true"></div>');

            if ($input.is('textarea')) {
                $editor.addClass('is-textarea');
            }

            $input.hide().after($container);
            $container.append($editor);

            // Determine relevant variables
            const allowedVars = getVarsForInput($input);
            $editor.data('allowed-vars', allowedVars);

            // Expose editor for external triggers
            $input.data('editor', $editor);

            const syncToEditor = () => {
                let text = $input.val() || '';
                // render pills
                const html = text.replace(tagRegex, '<span class="wpseopilot-tag" contenteditable="false">{{$1}}</span>');
                $editor.html(html);
            };

            const syncToInput = () => {
                let text = $editor[0].innerText;
                // remove Zero Width Spaces if any seem to creep in
                $input.val(text).trigger('change');
            };

            syncToEditor();

            $editor.on('input', function () {
                syncToInput();

                // Trigger Preview
                const $previewContainer = $input.nextAll('.wpseopilot-preview').add($input.parent().nextAll('.wpseopilot-preview')).first();
                if ($previewContainer.length) {
                    debouncedPreview($input.val(), $input.data('context'), $previewContainer);
                }

                const sel = window.getSelection();
                if (!sel.rangeCount) return;
                const range = sel.getRangeAt(0);
                const node = range.startContainer;

                // Only trigger autocomplete if typing
                if (node.nodeType === 3) {
                    const text = node.textContent;
                    const caret = range.startOffset;

                    // Detect '{{'
                    const lastOpen = text.lastIndexOf('{{', caret);
                    if (lastOpen !== -1) {
                        const query = text.substring(lastOpen + 2, caret).trim();

                        // Check if we just typed '}}' to close a tag manually
                        // We check if the text *ends* with '}}' at the caret position
                        // Or simply if text substring from lastOpen includes '}}' at the end
                        const rawSegment = text.substring(lastOpen, caret);
                        if (rawSegment.endsWith('}}')) {
                            // Extract content between {{ and }}
                            // content is rawSegment minus {{ and }}
                            const content = rawSegment.substring(2, rawSegment.length - 2); // preserve whitespace inside if user typed spaces
                            // We probably want to trim for the pill attribute value, but keep visual if complex?
                            // Actually existing pill logic inserts `{{${variable}}}`. 
                            // If user types `{{ post_title }}`, we probably want `{{post_title}}` or `{{ post_title }}`.
                            // The regex used elsewhere is `\{\{\s*([^}]+)\s*\}\}`.
                            // Let's use the content as-is or trimmed.
                            const variable = content; // Keep spaces if user typed them, or trim? 
                            // Let's trim for cleanliness, Twiglet handles spaces fine usually.

                            console.log('WP SEO Pilot: Detected manual tag closure:', variable);

                            // We need to replace the text node's content from lastOpen to caret with the pill
                            const before = text.substring(0, lastOpen);
                            const after = text.substring(caret);

                            // Update text node to contain 'before' part
                            node.textContent = before;

                            // Insert Pill
                            const $pill = $(`<span class="wpseopilot-tag" contenteditable="false">{{${variable}}}</span>`);
                            // Insert Space? Usually good UX to add a space after.
                            const spacer = document.createTextNode('\u00A0'); // nbsp

                            // We need to insert these after 'node'
                            // But 'node' is now just 'before'.
                            // If 'after' exists, we need a text node for it.

                            if (node.nextSibling) {
                                node.parentNode.insertBefore($pill[0], node.nextSibling);
                                node.parentNode.insertBefore(spacer, $pill[0].nextSibling);
                                if (after) {
                                    const afterNode = document.createTextNode(after);
                                    node.parentNode.insertBefore(afterNode, spacer.nextSibling);
                                }
                            } else {
                                $(node.parentNode).append($pill).append(spacer);
                                if (after) {
                                    $(node.parentNode).append(document.createTextNode(after));
                                }
                            }

                            // Set cursor after spacer
                            const newRange = document.createRange();
                            newRange.setStart(spacer, 1);
                            newRange.setEnd(spacer, 1);
                            sel.removeAllRanges();
                            sel.addRange(newRange);

                            hideMenu();
                            syncToInput();
                            return;
                        }

                        if (!query.includes('}}')) {
                            activeEditor = $editor[0];
                            const currentAllowed = $editor.data('allowed-vars') || {};

                            // Filter grouped results
                            const matches = {};
                            let hasMatch = false;

                            Object.keys(currentAllowed).forEach(groupKey => {
                                const group = currentAllowed[groupKey];
                                const filteredVars = group.vars.filter(v => v.tag.includes(query) || v.label.toLowerCase().includes(query.toLowerCase()));

                                if (filteredVars.length > 0) {
                                    matches[groupKey] = {
                                        label: group.label,
                                        vars: filteredVars.sort((a, b) => {
                                            // Prioritize exact match
                                            if (a.tag === query) return -1;
                                            if (b.tag === query) return 1;
                                            return 0;
                                        })
                                    };
                                    hasMatch = true;
                                }
                            });

                            selectedIndex = 0;
                            renderMenu(matches);
                            return;
                        }
                    }
                }
                hideMenu();
            });

            $editor.on('keydown', function (e) {
                // Autocomplete Navigation
                if ($menu.hasClass('is-visible')) {
                    const items = $menu.find('.wpseopilot-autocomplete-item');
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        selectedIndex = (selectedIndex + 1) % items.length;
                        items.removeClass('is-selected');
                        items.eq(selectedIndex).addClass('is-selected');
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                        items.removeClass('is-selected');
                        items.eq(selectedIndex).addClass('is-selected');
                    } else if (e.key === 'Enter' || e.key === 'Tab') {
                        e.preventDefault();
                        const val = items.eq(selectedIndex).data('value');
                        if (val) insertVar(val);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        hideMenu();
                    }
                    return;
                }

                // Handle Backspace for "Unlocking" Tags
                if (e.key === 'Backspace') {
                    const sel = window.getSelection();
                    if (!sel.rangeCount) return;
                    const range = sel.getRangeAt(0);

                    // Check if collapsed (caret)
                    if (range.collapsed) {
                        // Check if we are immediately after a tag
                        // range.startContainer could be a text node, or the editor element itself
                        let prevNode = null;

                        if (range.startContainer.nodeType === 3 && range.startOffset === 0) {
                            // At start of text node, look at previous sibling
                            prevNode = range.startContainer.previousSibling;
                        } else if (range.startContainer.nodeType === 1) {
                            // In element, look at child at offset-1
                            if (range.startOffset > 0) {
                                prevNode = range.startContainer.childNodes[range.startOffset - 1];
                            }
                        }

                        if (prevNode && prevNode.nodeType === 1 && prevNode.classList.contains('wpseopilot-tag')) {
                            e.preventDefault();
                            console.log('WP SEO Pilot: Unlocking tag on backspace');
                            const tagText = prevNode.innerText; // e.g. "{{post_title}}"
                            // Remove last char (the closing brace usually)
                            const newText = tagText.slice(0, -1);

                            // Create text node
                            const textNode = document.createTextNode(newText);

                            // Replace tag with text
                            prevNode.parentNode.replaceChild(textNode, prevNode);

                            // Set cursor to end of new text node
                            const newRange = document.createRange();
                            newRange.setStart(textNode, newText.length);
                            newRange.setEnd(textNode, newText.length);
                            sel.removeAllRanges();
                            sel.addRange(newRange);

                            // Trigger sync
                            syncToInput();
                        }
                    }
                }
            });

            // Click outside to close
            $(document).on('click', function (e) {
                if (!$(e.target).closest('.wpseopilot-autocomplete-menu').length && !$(e.target).closest('.wpseopilot-trigger-vars').length) {
                    hideMenu();
                }
            });
        };

        // Initialize on existing inputs
        $('input[name^="wpseopilot_"], textarea[name^="wpseopilot_"]').each(function () {
            if ($(this).attr('type') !== 'checkbox' && $(this).attr('type') !== 'radio') {
                createRichInput(this);
            }
        });

        // Trigger Button Logic
        $(document).on('click', '.wpseopilot-trigger-vars', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const targetId = $(this).data('target');
            console.log('WP SEO Pilot: Trigger button clicked for', targetId);
            const $input = $('#' + targetId);
            const $editor = $input.data('editor');

            if ($editor) {
                $editor.focus();
                activeEditor = $editor[0];

                const allowedVars = $editor.data('allowed-vars') || {};

                // Calculate position
                const offset = $editor.offset();
                $menu.css({
                    top: offset.top + $editor.outerHeight(),
                    left: offset.left,
                    width: $editor.outerWidth()
                }).addClass('is-visible');

                selectedIndex = 0;
                renderMenu(allowedVars); // Show all allowed vars
            } else {
                console.error('WP SEO Pilot: No editor found for input', targetId);
            }
        });

        // Trigger Preview Button Logic
        $(document).on('click', '.wpseopilot-trigger-preview', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const targetId = $(this).data('target');
            console.log('WP SEO Pilot: Preview button clicked for', targetId);
            const $input = $('#' + targetId);

            const $previewContainer = $input.nextAll('.wpseopilot-preview').add($input.parent().nextAll('.wpseopilot-preview')).first();

            if ($previewContainer.length) {
                $previewContainer.text('Loading preview...');
                // Force preview update immediately (bypass debounce)
                const template = $input.val();
                const context = $input.data('context');

                $.post(settings.ai.ajax, {
                    action: 'wpseopilot_render_preview',
                    nonce: settings.ai.nonce,
                    template: template,
                    context: context
                }).done(function (response) {
                    if (response.success) {
                        $previewContainer.text(response.data.preview || '(Empty)');
                    } else {
                        $previewContainer.text('Preview Error: ' + (response.data || 'Unknown'));
                    }
                }).fail(function () {
                    $previewContainer.text('Preview Request Failed');
                });
            } else {
                console.error('WP SEO Pilot: No preview container found for input', targetId);
            }
        });
    };

    $(document).ready(function () {
        initRichTags();
    });

})(jQuery, window.WPSEOPilotAdmin);
