/**
 * AI SEO Assistant — Gutenberg Document Settings Panel
 *
 * Adds a Document-level sidebar panel for generating SEO meta
 * and writing it directly to Yoast SEO / Rank Math / AIOSEO.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Button, TextControl, TextareaControl, Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { callWpApi } from '../utils/callWpApi';
import PostPromptGenerator from '../utils/PromptGenerator';

const AISeoPanel = () => {
    const [loading, setLoading]     = useState(false);
    const [notice, setNotice]       = useState({ text: '', status: '' });
    const [seoTitle, setSeoTitle]   = useState('');
    const [seoDesc, setSeoDesc]     = useState('');
    const [keywords, setKeywords]   = useState('');

    const postTitle   = useSelect(select => select('core/editor').getEditedPostAttribute('title') || '');
    const postId      = useSelect(select => select('core/editor').getCurrentPostId());
    const postContent = useSelect(select => {
        const content = select('core/editor').getEditedPostAttribute('content') || '';
        // Strip HTML and truncate
        const tmp = document.createElement('div');
        tmp.innerHTML = content;
        return tmp.textContent?.substring(0, 500) || '';
    });

    const seoPlugins = window.wacdmgAdmin?.seoPlugins || [];

    const handleGenerate = async () => {
        if (!postTitle) {
            setNotice({ text: __('Please add a post title first.', 'wacdmg-ai-content-assistant'), status: 'error' });
            return;
        }

        setLoading(true);
        setNotice({ text: '', status: '' });

        const generator = new PostPromptGenerator({ tone: 'professional', language: 'English' });

        try {
            const response = await callWpApi('/generate-seo-meta', 'POST', {
                title_prompt: generator.seoMetaTitle(postTitle, keywords),
                desc_prompt:  generator.seoMetaDescription(postTitle, postContent, keywords),
                kw_prompt:    generator.seoFocusKeywords(postTitle, postContent),
                post_id:      postId,
            });

            if (response.success) {
                const data = response.data;
                if (data.seo_title)        setSeoTitle(data.seo_title);
                if (data.meta_description) setSeoDesc(data.meta_description);
                if (data.focus_keywords)   setKeywords(data.focus_keywords);

                const plugin = seoPlugins.length > 0 ? seoPlugins[0] : 'generic';
                setNotice({
                    text: `${__('SEO meta generated and saved to', 'wacdmg-ai-content-assistant')} ${plugin.charAt(0).toUpperCase() + plugin.slice(1)}.`,
                    status: 'success',
                });
            } else {
                setNotice({
                    text: response.data?.message || __('Failed to generate SEO meta.', 'wacdmg-ai-content-assistant'),
                    status: 'error',
                });
            }
        } catch (error) {
            setNotice({ text: error.message || __('Network error. Check your AI settings.', 'wacdmg-ai-content-assistant'), status: 'error' });
        }

        setLoading(false);
    };

    return (
        <PluginDocumentSettingPanel
            name="wacdmg-seo-panel"
            title={__('AI SEO Assistant', 'wacdmg-ai-content-assistant')}
            className="wacdmg-seo-panel"
        >
            {seoPlugins.length > 0 && (
                <p style={{ fontSize: '11px', color: '#16a34a', margin: '0 0 10px', fontWeight: 600 }}>
                    Active: {seoPlugins.map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(', ')} detected
                </p>
            )}

            <TextControl
                label={__('Focus Keywords (optional)', 'wacdmg-ai-content-assistant')}
                help={__('Comma-separated keywords to guide SEO meta generation.', 'wacdmg-ai-content-assistant')}
                value={keywords}
                onChange={setKeywords}
                placeholder="e.g., wireless headphones, noise cancelling"
            />

            <Button
                variant="primary"
                onClick={handleGenerate}
                disabled={loading || !postTitle}
                style={{ marginBottom: '12px', width: '100%', justifyContent: 'center' }}
            >
                {loading ? <><Spinner /> {__('Generating...', 'wacdmg-ai-content-assistant')}</> : __('Generate SEO Meta', 'wacdmg-ai-content-assistant')}
            </Button>

            {notice.text && (
                <Notice status={notice.status === 'success' ? 'success' : 'error'} isDismissible={false}>
                    {notice.text}
                </Notice>
            )}

            {(seoTitle || seoDesc) && (
                <div style={{ marginTop: '12px' }}>
                    {seoTitle && (
                        <div style={{ marginBottom: '10px' }}>
                            <TextControl
                                label={<>
                                    {__('SEO Title', 'wacdmg-ai-content-assistant')}
                                    <span style={{ fontWeight: 400, color: seoTitle.length > 60 ? '#dc2626' : '#16a34a', marginLeft: '6px' }}>
                                        {seoTitle.length}/60
                                    </span>
                                </>}
                                value={seoTitle}
                                onChange={setSeoTitle}
                            />
                        </div>
                    )}
                    {seoDesc && (
                        <TextareaControl
                            label={<>
                                {__('Meta Description', 'wacdmg-ai-content-assistant')}
                                <span style={{ fontWeight: 400, color: seoDesc.length > 160 ? '#dc2626' : seoDesc.length < 120 ? '#d97706' : '#16a34a', marginLeft: '6px' }}>
                                    {seoDesc.length}/160
                                </span>
                            </>}
                            value={seoDesc}
                            onChange={setSeoDesc}
                            rows={3}
                        />
                    )}
                </div>
            )}
        </PluginDocumentSettingPanel>
    );
};

registerPlugin('wacdmg-seo-panel', { render: AISeoPanel });
