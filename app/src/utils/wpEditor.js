function dispatchFieldEvents(element) {
    if (!element) {
        return;
    }
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
    if (typeof window.jQuery === 'function') {
        window.jQuery(element).trigger('input').trigger('change');
    }
}

function getTinyMCEEditor(editorId) {
    const tinymce = window.tinymce || window.tinyMCE;
    if (!tinymce) {
        return null;
    }

    if (typeof tinymce.get === 'function') {
        const editor = tinymce.get(editorId);
        if (editor && !editor.removed) {
            return editor;
        }
    }

    const editors = tinymce.editors;
    if (!editors || !editors.length) {
        return null;
    }

    for (let i = 0; i < editors.length; i++) {
        const editor = editors[i];
        if (!editor || editor.removed) {
            continue;
        }
        if (editor.id === editorId) {
            return editor;
        }
        if (editor.targetElm && editor.targetElm.id === editorId) {
            return editor;
        }
    }

    return null;
}

function markEditorDirty(editor) {
    if (!editor) {
        return;
    }
    editor.isNotDirty = false;
    if (typeof editor.setDirty === 'function') {
        editor.setDirty(true);
    }
    if (editor.undoManager && typeof editor.undoManager.add === 'function') {
        editor.undoManager.add();
    }
}

function isBlockEditor() {
    return document.body.classList.contains('block-editor-page');
}

function writeBlockEditorField(editorId, value) {
    if (!isBlockEditor() || !window.wp?.data?.dispatch) {
        return false;
    }
    try {
        const dispatch = window.wp.data.dispatch('core/editor');
        if (!dispatch || typeof dispatch.editPost !== 'function') {
            return false;
        }
        if (editorId === 'content') {
            const blockEditor = window.wp.data.dispatch('core/block-editor');
            if (blockEditor && typeof blockEditor.resetBlocks === 'function' && window.wp?.blocks?.rawHandler) {
                const blocks = window.wp.blocks.rawHandler({ HTML: value });
                blockEditor.resetBlocks(blocks.length ? blocks : window.wp.blocks.rawHandler({ HTML: `<p>${value}</p>` }));
            } else {
                dispatch.editPost({ content: value });
            }
            return true;
        }
        if (editorId === 'excerpt') {
            dispatch.editPost({ excerpt: value });
            return true;
        }
    } catch (e) {
        return false;
    }
    return false;
}

/**
 * Read the live value of a wp_editor field (TinyMCE visual or textarea).
 */
export function readWpEditorContent(editorId, format = 'html') {
    const editor = getTinyMCEEditor(editorId);
    if (editor) {
        try {
            if (format === 'text') {
                return editor.getContent({ format: 'text' }) || '';
            }
            return editor.getContent({ format: 'html' }) || editor.getContent() || '';
        } catch (e) {
            // Fall through to the textarea.
        }
    }

    const textarea = document.getElementById(editorId);
    return textarea ? textarea.value : '';
}

/**
 * Write HTML/text into a wp_editor field so TinyMCE, the textarea WordPress
 * posts on save, and Gutenberg (when present) stay in sync.
 */
export function writeWpEditorContent(editorId, content) {
    const value = content == null ? '' : String(content);
    const textarea = document.getElementById(editorId);
    const editor = getTinyMCEEditor(editorId);
    let written = false;

    if (editor) {
        try {
            editor.setContent(value, { format: 'html' });
            if (typeof editor.fire === 'function') {
                editor.fire('SetContent');
                editor.fire('change');
                editor.fire('input');
            }
            if (typeof editor.nodeChanged === 'function') {
                editor.nodeChanged();
            }
            if (typeof editor.save === 'function') {
                editor.save();
            }
            markEditorDirty(editor);
            written = true;
        } catch (e) {
            // Fall through to the textarea.
        }
    }

    if (textarea) {
        textarea.value = value;
        dispatchFieldEvents(textarea);
        written = true;
    }

    if (writeBlockEditorField(editorId, value)) {
        written = true;
    }

    return written;
}

/**
 * Write the product/post title so Classic Editor, WooCommerce, and Gutenberg see it.
 */
export function writePostTitle(content) {
    const value = content == null ? '' : String(content).replace(/<[^>]+>/g, '').trim();
    const input = document.getElementById('title') || document.querySelector('input[name="post_title"]');

    if (input) {
        input.value = value;
        dispatchFieldEvents(input);
        const placeholder = document.getElementById('title-prompt-text');
        if (placeholder) {
            placeholder.style.display = value ? 'none' : '';
        }
    }

    if (isBlockEditor() && window.wp?.data?.dispatch) {
        try {
            window.wp.data.dispatch('core/editor').editPost({ title: value });
        } catch (e) {
            // Ignore missing editor store.
        }
    }

    return !!input;
}

/**
 * Append tags using WooCommerce/WordPress tag UI without replacing existing tags.
 */
export function writeProductTags(tags) {
    return writeTaxonomyTags('product_tag', tags);
}

/**
 * Append tags for any tag-style taxonomy.
 */
export function writeTaxonomyTags(taxonomy, tags) {
    const tax = taxonomy || 'product_tag';
    const list = (Array.isArray(tags) ? tags : [])
        .map((tag) => String(tag).trim())
        .filter(Boolean);

    if (!list.length) {
        return false;
    }

    const $ = typeof window.jQuery === 'function' ? window.jQuery : null;
    const metabox = document.getElementById(tax) || document.getElementById('tagsdiv-' + tax);
    const input = document.getElementById('new-tag-' + tax);

    if ($ && window.tagBox && typeof window.tagBox.flushTags === 'function' && metabox && input) {
        $(input).val(list.join(','));
        window.tagBox.flushTags($(metabox));
    } else {
        const addBtn = document.querySelector('#' + tax + ' .tagadd, .tagsdiv#' + tax + ' .tagadd, #tagsdiv-' + tax + ' .tagadd');
        if (input && addBtn) {
            if ($) {
                $(input).val(list.join(','));
                $(addBtn).trigger('click');
            } else {
                input.value = list.join(',');
                addBtn.click();
            }
        }
    }

    const taxInput = document.getElementById('tax-input-' + tax);
    if (taxInput) {
        const existing = taxInput.value.split(',').map((item) => item.trim()).filter(Boolean);
        const merged = [];
        existing.concat(list).forEach((tag) => {
            if (merged.indexOf(tag) === -1) {
                merged.push(tag);
            }
        });
        taxInput.value = merged.join(',');
        dispatchFieldEvents(taxInput);
        return true;
    }

    return !!(input && metabox);
}

/**
 * Check generated category names in the WooCommerce category checklist.
 */
export function writeProductCategories(termIds) {
    return writeTaxonomyChecklist('product_cat', termIds);
}

/**
 * Check generated terms in a hierarchical taxonomy checklist.
 */
export function writeTaxonomyChecklist(taxonomy, termIds) {
    const tax = taxonomy || 'product_cat';
    const ids = (Array.isArray(termIds) ? termIds : [])
        .map((id) => parseInt(id, 10))
        .filter((id) => id > 0);

    if (!ids.length) {
        return false;
    }

    let written = false;
    ids.forEach((id) => {
        const box = document.getElementById('in-' + tax + '-' + id);
        if (box) {
            box.checked = true;
            dispatchFieldEvents(box);
            written = true;
        }
    });

    if (isBlockEditor() && window.wp?.data?.dispatch) {
        try {
            window.wp.data.dispatch('core/editor').editPost({ [tax]: ids });
            written = true;
        } catch (e) {
            // Classic screens may not have this store.
        }
    }

    return written;
}

/**
 * Append custom product attributes into the WooCommerce attributes form when possible.
 */
export function writeProductAttributes(attributes) {
    const list = (Array.isArray(attributes) ? attributes : []).filter((row) => row && row.name && row.value);
    if (!list.length) {
        return false;
    }

    const $ = typeof window.jQuery === 'function' ? window.jQuery : null;
    const wrap = document.querySelector('#product_attributes .product_attributes') || document.getElementById('product_attributes');
    if (!$ || !wrap) {
        return false;
    }

    list.forEach((row) => {
        const index = wrap.querySelectorAll('.woocommerce_attribute').length;
        const safeName = String(row.name).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '');
        const safeValue = String(row.value).replace(/&/g, '&amp;').replace(/</g, '&lt;');
        const html = '<div class="woocommerce_attribute wc-metabox closed">'
            + '<h3><input type="hidden" name="attribute_names[' + index + ']" value="' + safeName + '">'
            + '<input type="hidden" name="attribute_position[' + index + ']" value="' + index + '">'
            + '<input type="hidden" name="attribute_visibility[' + index + ']" value="1">'
            + '<input type="hidden" name="attribute_variation[' + index + ']" value="0">'
            + '<input type="hidden" name="attribute_taxonomy[' + index + ']" value="">'
            + '<strong>' + safeName + '</strong></h3>'
            + '<div class="woocommerce_attribute_data wc-metabox-content" style="display:block">'
            + '<table class="widefat"><tr><td><textarea name="attribute_values[' + index + ']">' + safeValue + '</textarea></td></tr></table>'
            + '</div></div>';
        $(wrap).append(html);
    });

    return true;
}
