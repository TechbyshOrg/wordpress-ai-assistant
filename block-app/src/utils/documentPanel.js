/**
 * Resolve PluginDocumentSettingPanel for post editor and Site Editor.
 */
export function getPluginDocumentSettingPanel() {
    if (typeof window !== 'undefined' && window.wp) {
        if (window.wp.editor && window.wp.editor.PluginDocumentSettingPanel) {
            return window.wp.editor.PluginDocumentSettingPanel;
        }
        if (window.wp.editPost && window.wp.editPost.PluginDocumentSettingPanel) {
            return window.wp.editPost.PluginDocumentSettingPanel;
        }
    }
    return null;
}
