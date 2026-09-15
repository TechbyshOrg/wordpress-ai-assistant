(function () {
    'use strict';

    var active = null;

    function cfg() {
        return window.wacdmgFieldAssistant || window.wacdmgAdmin || {};
    }

    function isAbortError(error) {
        return !!error && (error.name === 'AbortError' || error.code === 20);
    }

    function startSignal() {
        if (active && active.inFlight) {
            active.inFlight = false;
            active.controller.abort();
        }
        var controller = new AbortController();
        active = { controller: controller, inFlight: true };
        return controller.signal;
    }

    function settle(signal) {
        if (!active || active.controller.signal !== signal) {
            return false;
        }
        active.inFlight = false;
        active = null;
        return true;
    }

    function apiBase() {
        var c = cfg();
        return (c.apiBaseUrl || '').replace(/\/$/, '');
    }

    function restNonce() {
        return cfg().restNonce || cfg().rest_nonce || '';
    }

    function postTitle() {
        var input = document.getElementById('title') || document.querySelector('input[name="post_title"]');
        if (input && input.value) {
            return input.value.trim();
        }
        return '';
    }

    function dispatchEvents(el) {
        if (!el) {
            return;
        }
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        if (typeof window.jQuery === 'function') {
            window.jQuery(el).trigger('input').trigger('change');
        }
    }

    function writeInput(el, value) {
        if (!el) {
            return false;
        }
        el.value = value == null ? '' : String(value);
        dispatchEvents(el);
        return true;
    }

    function getTinyMCE(editorId) {
        var tinymce = window.tinymce || window.tinyMCE;
        if (!tinymce || typeof tinymce.get !== 'function') {
            return null;
        }
        var editor = tinymce.get(editorId);
        return editor && !editor.removed ? editor : null;
    }

    function writeTinyMCE(editorId, value) {
        var editor = getTinyMCE(editorId);
        var written = false;
        if (editor) {
            try {
                editor.setContent(String(value || ''), { format: 'html' });
                if (typeof editor.save === 'function') {
                    editor.save();
                }
                written = true;
            } catch (e) {
                written = false;
            }
        }
        var textarea = document.getElementById(editorId);
        if (textarea) {
            writeInput(textarea, value);
            written = true;
        }
        return written;
    }

    function readTinyMCE(editorId) {
        var editor = getTinyMCE(editorId);
        if (editor) {
            try {
                return editor.getContent({ format: 'html' }) || '';
            } catch (e) {
                // Fall through.
            }
        }
        var textarea = document.getElementById(editorId);
        return textarea ? textarea.value : '';
    }

    function closestFieldWrap(el) {
        return el.closest('.acf-field, .rwmb-field, .rwmb-input, .form-field, .woocommerce_variation, .yikes_woo_tabs_title_field, .yikes-custom-woo-tab, p, tr, .form-field') || el.parentNode;
    }

    function readAdapter(btn) {
        var adapter = btn.getAttribute('data-adapter') || 'textarea';
        var key = btn.getAttribute('data-field-key') || '';
        var wrap = closestFieldWrap(btn);

        if (adapter === 'acf' && window.acf && typeof window.acf.getField === 'function') {
            var field = window.acf.getField(key) || (wrap ? window.acf.getField(window.jQuery(wrap)) : null);
            if (field && typeof field.val === 'function') {
                return field.val() || '';
            }
        }

        if (adapter === 'tinymce') {
            return readTinyMCE(key);
        }

        var selector = btn.getAttribute('data-target') || '';
        var input = (selector && document.querySelector(selector))
            || (wrap && wrap.querySelector('textarea, input[type="text"], input:not([type])'));
        if (input && input.id && getTinyMCE(input.id)) {
            return readTinyMCE(input.id);
        }
        return input ? input.value : '';
    }

    function writeAdapter(btn, value) {
        var adapter = btn.getAttribute('data-adapter') || 'textarea';
        var key = btn.getAttribute('data-field-key') || '';
        var wrap = closestFieldWrap(btn);

        if (adapter === 'acf' && window.acf && typeof window.acf.getField === 'function') {
            var field = window.acf.getField(key);
            if (!field && wrap && typeof window.jQuery === 'function') {
                field = window.acf.getField(window.jQuery(wrap));
            }
            if (field) {
                if (field.type === 'wysiwyg') {
                    var ta = field.$el ? field.$el.find('textarea').get(0) : null;
                    if (ta && ta.id) {
                        writeTinyMCE(ta.id, value);
                    }
                }
                if (typeof field.val === 'function') {
                    field.val(value);
                    if (field.$el && field.$el.trigger) {
                        field.$el.trigger('change');
                    }
                    return true;
                }
            }
        }

        if (adapter === 'elementor') {
            return writeElementor(value);
        }

        if (adapter === 'tinymce') {
            return writeTinyMCE(key, value);
        }

        var selector = btn.getAttribute('data-target') || '';
        var input = (selector && document.querySelector(selector))
            || (wrap && wrap.querySelector('textarea, input[type="text"], input:not([type])'));
        if (input && input.id && getTinyMCE(input.id)) {
            return writeTinyMCE(input.id, value);
        }
        return writeInput(input, value);
    }

    function getElementorControl() {
        var panel = document.getElementById('elementor-controls');
        if (!panel) {
            return null;
        }
        var active = panel.querySelector('.elementor-control-type-text:not(.elementor-hidden-control) input, .elementor-control-type-textarea:not(.elementor-hidden-control) textarea, .elementor-control-type-wysiwyg:not(.elementor-hidden-control) textarea, .elementor-control-type-title input');
        return active;
    }

    function writeElementor(value) {
        var input = getElementorControl();
        if (!input) {
            return false;
        }
        writeInput(input, value);
        var setting = input.getAttribute('data-setting');
        try {
            if (window.$e && setting && window.elementor && typeof window.elementor.getContainer === 'function') {
                // Native events above are the supported fallback; $e is best-effort.
            }
        } catch (e) {
            // Ignore Elementor internals.
        }
        return true;
    }

    function promptFor(action, label, current) {
        var title = postTitle();
        var language = 'English';
        var tone = 'informative';
        if (action === 'improve') {
            return 'Improve this "' + label + '" field value.' + (title ? ' Context: "' + title + '".' : '') + '\n\n"' + current + '"\n\nWrite in ' + language + '. Tone: ' + tone + '.\nKeep facts. Return only the improved value.';
        }
        if (action === 'translate') {
            return 'Translate the following ' + label + ' into ' + language + '.\nPreserve facts, numbers, HTML tags, and shortcodes.\nReturn only the translated ' + label + '.\n\n"""' + current + '"""';
        }
        return 'Write content for the "' + label + '" field.' + (title ? ' The content is for: "' + title + '".' : '') + '\nWrite in ' + language + '. Tone: ' + tone + '.\nReturn only the field value. No labels or explanation.';
    }

    function variationPrompt(current) {
        var title = postTitle();
        var wrap = document.querySelector('.woocommerce_variation.open') || document.querySelector('.woocommerce_variation');
        var attrs = '';
        if (wrap) {
            var h3 = wrap.querySelector('h3');
            attrs = h3 ? h3.textContent.replace(/\s+/g, ' ').trim() : '';
        }
        return 'Write a short WooCommerce variation description for "' + title + '".' + (attrs ? ' Variation attributes: ' + attrs + '.' : '') + (current ? '\nCurrent description: "' + current.substring(0, 300) + '"' : '') + '\nWrite in English. Tone: informative.\n2-4 sentences. Return only the description as plain text.';
    }

    function setStatus(btn, text, isError) {
        var row = btn.closest('.wacdmg-field-ai-row') || btn.parentNode;
        var status = row.querySelector('.wacdmg-field-ai-status');
        if (!status) {
            status = document.createElement('span');
            status.className = 'wacdmg-field-ai-status';
            row.appendChild(status);
        }
        status.textContent = text || '';
        status.style.color = isError ? '#b32d2e' : '#1e8c3e';
    }

    function generate(btn) {
        var action = btn.getAttribute('data-action') || 'generate';
        var label = btn.getAttribute('data-field-label') || 'content';
        var current = readAdapter(btn);
        if ((action === 'improve' || action === 'translate') && !String(current).trim()) {
            setStatus(btn, 'Add content first.', true);
            return;
        }

        var prompt = btn.getAttribute('data-adapter') === 'variation'
            ? variationPrompt(current)
            : promptFor(action, label, current);

        var signal = startSignal();
        var buttons = (btn.closest('.wacdmg-field-ai-row') || document).querySelectorAll('.wacdmg-field-ai');
        buttons.forEach(function (b) { b.disabled = true; });
        setStatus(btn, 'Generating...', false);

        fetch(apiBase() + '/generate-description', {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': restNonce(),
            },
            body: JSON.stringify({ prompt: prompt }),
            signal: signal,
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                var text = result.data && result.data.data && (result.data.data.description || result.data.data.content);
                var err = result.data && result.data.data && result.data.data.message;
                if (!result.ok || !text) {
                    setStatus(btn, err || 'Generation failed.', true);
                    return;
                }
                if (!writeAdapter(btn, text)) {
                    setStatus(btn, 'Could not write to the field.', true);
                    return;
                }
                setStatus(btn, 'Inserted. Save to keep it.', false);
            })
            .catch(function (error) {
                if (!isAbortError(error)) {
                    setStatus(btn, 'Network error. Check AI settings.', true);
                }
            })
            .finally(function () {
                if (settle(signal)) {
                    buttons.forEach(function (b) { b.disabled = false; });
                }
            });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.wacdmg-field-ai');
        if (!btn) {
            return;
        }
        e.preventDefault();
        generate(btn);
    });

    function injectVariationButtons(root) {
        var scope = root || document;
        var areas = scope.querySelectorAll('textarea[name^="variable_description"]');
        areas.forEach(function (ta) {
            if (ta.parentNode.querySelector('.wacdmg-field-ai-row')) {
                return;
            }
            var row = document.createElement('p');
            row.className = 'wacdmg-field-ai-row';
            row.innerHTML = '<button type="button" class="button wacdmg-field-ai" data-action="generate" data-adapter="variation" data-field-label="variation description">Generate with AI</button> '
                + '<button type="button" class="button wacdmg-field-ai" data-action="improve" data-adapter="variation" data-field-label="variation description">Improve</button> '
                + '<span class="wacdmg-field-ai-status"></span>';
            ta.parentNode.insertBefore(row, ta.nextSibling);
        });
    }

    function injectYikesButtons(root) {
        var scope = root || document;
        var titles = scope.querySelectorAll('input.yikes_woo_tabs_title, input[name*="yikes_woo_products_tabs"][name*="[title]"]');
        titles.forEach(function (input) {
            if (input.parentNode.querySelector('.wacdmg-field-ai-row')) {
                return;
            }
            var row = document.createElement('p');
            row.className = 'wacdmg-field-ai-row';
            row.innerHTML = '<button type="button" class="button wacdmg-field-ai" data-action="generate" data-adapter="textarea" data-field-label="product tab title">Generate with AI</button> '
                + '<span class="wacdmg-field-ai-status"></span>';
            input.parentNode.appendChild(row);
        });
        var contents = scope.querySelectorAll('textarea.yikes_woo_tabs_content, textarea[name*="yikes_woo_products_tabs"][name*="[content]"]');
        contents.forEach(function (ta) {
            if (ta.parentNode.querySelector('.wacdmg-field-ai-row')) {
                return;
            }
            var row = document.createElement('p');
            row.className = 'wacdmg-field-ai-row';
            row.innerHTML = '<button type="button" class="button wacdmg-field-ai" data-action="generate" data-adapter="tinymce" data-field-key="' + (ta.id || '') + '" data-field-label="product tab content">Generate with AI</button> '
                + '<button type="button" class="button wacdmg-field-ai" data-action="improve" data-adapter="tinymce" data-field-key="' + (ta.id || '') + '" data-field-label="product tab content">Improve</button> '
                + '<span class="wacdmg-field-ai-status"></span>';
            ta.parentNode.appendChild(row);
        });
    }

    function mountElementorPanel() {
        if (!document.body.classList.contains('elementor-editor-active') && !document.getElementById('elementor-panel')) {
            return;
        }
        if (document.getElementById('wacdmg-elementor-ai')) {
            return;
        }
        var panel = document.getElementById('elementor-panel-content-wrapper') || document.getElementById('elementor-panel');
        if (!panel) {
            return;
        }
        var box = document.createElement('div');
        box.id = 'wacdmg-elementor-ai';
        box.className = 'wacdmg-field-ai-row';
        box.style.cssText = 'padding:10px 15px;border-bottom:1px solid #d5d8dc;';
        box.innerHTML = '<strong style="display:block;margin-bottom:6px;">AI Content Assistant</strong>'
            + '<button type="button" class="elementor-button elementor-button-success wacdmg-field-ai" data-action="generate" data-adapter="elementor" data-field-label="selected control">Generate</button> '
            + '<button type="button" class="elementor-button wacdmg-field-ai" data-action="improve" data-adapter="elementor" data-field-label="selected control">Improve</button> '
            + '<button type="button" class="elementor-button wacdmg-field-ai" data-action="translate" data-adapter="elementor" data-field-label="selected control">Translate</button> '
            + '<span class="wacdmg-field-ai-status"></span>';
        panel.insertBefore(box, panel.firstChild);
    }

    function observeWoo() {
        var target = document.getElementById('woocommerce-product-data') || document.body;
        if (!target || target.getAttribute('data-wacdmg-observed')) {
            return;
        }
        target.setAttribute('data-wacdmg-observed', '1');
        injectVariationButtons(document);
        injectYikesButtons(document);
        var observer = new MutationObserver(function () {
            injectVariationButtons(document);
            injectYikesButtons(document);
        });
        observer.observe(target, { childList: true, subtree: true });
    }

    document.addEventListener('DOMContentLoaded', function () {
        observeWoo();
        mountElementorPanel();
    });

    if (document.readyState !== 'loading') {
        observeWoo();
        mountElementorPanel();
    }

    if (window.elementor && window.elementor.hooks && typeof window.elementor.hooks.addAction === 'function') {
        window.elementor.hooks.addAction('panel/open_editor/widget', function () {
            mountElementorPanel();
        });
    }
})();
