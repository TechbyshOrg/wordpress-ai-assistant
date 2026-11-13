import DOMPurify from 'dompurify';

/**
 * Sanitize AI-generated HTML before it is previewed or written into an editor.
 *
 * Model output can be steered by the content it was given (prompt injection), so it is
 * treated as untrusted: scripts, event handlers, javascript: URLs, embeds, and forms are
 * removed. Formatting tags used in content (headings, lists, links, tables, emphasis) are kept.
 *
 * Use only for HTML destinations. Plain-text values (titles, tags) must not be passed
 * through here, because serializing HTML escapes characters such as "&".
 *
 * Keep this file identical in app/src/utils and block-app/src/utils.
 *
 * @param {string} html Untrusted HTML.
 * @return {string} Safe HTML.
 */
export function sanitizeAiHtml(html) {
    if (html == null || html === '') {
        return '';
    }
    return DOMPurify.sanitize(String(html), {
        USE_PROFILES: { html: true },
        FORBID_TAGS: ['style', 'form', 'input', 'button', 'textarea', 'select', 'option', 'iframe', 'frame', 'object', 'embed', 'base', 'link', 'meta'],
        FORBID_ATTR: ['style', 'srcdoc', 'formaction'],
    });
}

/**
 * Extract plain text from HTML without executing anything.
 *
 * DOMParser documents are inert: scripts do not run and images do not load,
 * unlike assigning to innerHTML on an element.
 *
 * @param {string} html HTML string.
 * @return {string} Text content.
 */
export function htmlToText(html) {
    if (html == null || html === '') {
        return '';
    }
    const doc = new DOMParser().parseFromString(String(html), 'text/html');
    return doc.body ? doc.body.textContent || '' : '';
}
