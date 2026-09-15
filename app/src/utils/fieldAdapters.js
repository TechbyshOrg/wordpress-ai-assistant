import { readWpEditorContent, writeWpEditorContent } from './wpEditor';

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

function writeInput(element, value) {
    if (!element) {
        return false;
    }
    element.value = value == null ? '' : String(value);
    dispatchFieldEvents(element);
    return true;
}

const adapters = {
    textarea: {
        read(target) {
            const el = typeof target === 'string' ? document.getElementById(target) : target;
            return el ? el.value : '';
        },
        write(target, value) {
            const el = typeof target === 'string' ? document.getElementById(target) : target;
            return writeInput(el, value);
        },
    },
    tinymce: {
        read(editorId, format = 'html') {
            return readWpEditorContent(editorId, format);
        },
        write(editorId, value) {
            return writeWpEditorContent(editorId, value);
        },
    },
    acf: {
        read(fieldKey) {
            if (!window.acf || typeof window.acf.getField !== 'function') {
                return '';
            }
            const field = window.acf.getField(fieldKey);
            if (!field || typeof field.val !== 'function') {
                return '';
            }
            return field.val() || '';
        },
        write(fieldKey, value) {
            if (!window.acf || typeof window.acf.getField !== 'function') {
                return false;
            }
            const field = window.acf.getField(fieldKey);
            if (!field) {
                return false;
            }
            if (field.type === 'wysiwyg') {
                const textarea = field.$el ? field.$el.find('textarea').get(0) : null;
                const editorId = textarea ? textarea.id : '';
                if (editorId && writeWpEditorContent(editorId, value)) {
                    if (typeof field.val === 'function') {
                        field.val(value);
                    }
                    return true;
                }
            }
            if (typeof field.val === 'function') {
                field.val(value);
                if (field.$el && field.$el.trigger) {
                    field.$el.trigger('change');
                }
                return true;
            }
            return false;
        },
    },
    metabox: {
        read(selector) {
            const el = document.querySelector(selector);
            return el ? el.value : '';
        },
        write(selector, value) {
            const el = document.querySelector(selector);
            if (!el) {
                return false;
            }
            if (el.id && writeWpEditorContent(el.id, value)) {
                return true;
            }
            return writeInput(el, value);
        },
    },
};

export function readField(adapter, target, format) {
    const impl = adapters[adapter];
    if (!impl || typeof impl.read !== 'function') {
        return '';
    }
    return impl.read(target, format);
}

export function writeField(adapter, target, value) {
    const impl = adapters[adapter];
    if (!impl || typeof impl.write !== 'function') {
        return false;
    }
    return impl.write(target, value);
}

export default adapters;
