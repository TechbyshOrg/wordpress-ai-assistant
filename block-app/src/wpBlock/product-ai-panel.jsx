/**
 * AI Product Assistant — Gutenberg document panel for WooCommerce products.
 */
import { registerPlugin } from '@wordpress/plugins';
import { useState, useRef } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button, Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { callWpApi } from '../utils/callWpApi';
import { getPluginDocumentSettingPanel } from '../utils/documentPanel';
import PostPromptGenerator from '../utils/PromptGenerator';

const PluginDocumentSettingPanel = getPluginDocumentSettingPanel();

const ProductAiPanel = () => {
    const [loading, setLoading] = useState(false);
    const [notice, setNotice] = useState({ text: '', status: '' });
    const abortRef = useRef(null);

    const postType = useSelect(select => select('core/editor').getCurrentPostType());
    const postId = useSelect(select => select('core/editor').getCurrentPostId());
    const title = useSelect(select => select('core/editor').getEditedPostAttribute('title') || '');
    const content = useSelect(select => select('core/editor').getEditedPostAttribute('content') || '');
    const { editPost } = useDispatch('core/editor');

    if (postType !== 'product' || !PluginDocumentSettingPanel) {
        return null;
    }

    const run = async (label, request) => {
        if (!title) {
            setNotice({ text: __('Please add a product title first.', 'wacdmg-ai-content-assistant'), status: 'error' });
            return;
        }
        if (abortRef.current) {
            abortRef.current.abort();
        }
        abortRef.current = new AbortController();
        setLoading(true);
        setNotice({ text: '', status: '' });
        try {
            await request(abortRef.current.signal);
        } catch (error) {
            if (error.name !== 'AbortError') {
                setNotice({ text: error.message || __('Generation failed.', 'wacdmg-ai-content-assistant'), status: 'error' });
            }
        }
        setLoading(false);
    };

    const generator = new PostPromptGenerator({ tone: 'persuasive', language: 'English' });

    return (
        <PluginDocumentSettingPanel
            name="wacdmg-product-ai-panel"
            title={__('AI Product Assistant', 'wacdmg-ai-content-assistant')}
            className="wacdmg-product-ai-panel"
        >
            <Button
                variant="secondary"
                disabled={loading}
                onClick={() => run('description', async (signal) => {
                    const response = await callWpApi('/generate-description', 'POST', {
                        prompt: generator.descriptionPostTitle(title),
                    }, { signal });
                    if (response.success) {
                        const html = response.data.description || response.data.content || '';
                        editPost({ content: html });
                        setNotice({ text: __('Description generated.', 'wacdmg-ai-content-assistant'), status: 'success' });
                    } else {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                })}
                style={{ width: '100%', marginBottom: '8px', justifyContent: 'center' }}
            >
                {loading ? <Spinner /> : __('Generate Description', 'wacdmg-ai-content-assistant')}
            </Button>
            <Button
                variant="secondary"
                disabled={loading}
                onClick={() => run('tags', async (signal) => {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = content;
                    const text = tmp.textContent || '';
                    const response = await callWpApi('/generate-tags', 'POST', {
                        prompt: `Generate 8-12 relevant product tags for "${title}". ${text ? `Description: ${text.substring(0, 500)}` : ''} Return only a comma-separated list.`,
                        post_id: postId,
                        apply: true,
                    }, { signal });
                    if (response.success) {
                        setNotice({
                            text: __('Tags generated and applied: ', 'wacdmg-ai-content-assistant') + (response.data.tags || []).join(', '),
                            status: 'success',
                        });
                    } else {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                })}
                style={{ width: '100%', marginBottom: '8px', justifyContent: 'center' }}
            >
                {__('Generate Product Tags', 'wacdmg-ai-content-assistant')}
            </Button>
            {loading && (
                <Button
                    variant="tertiary"
                    isSmall
                    onClick={() => { abortRef.current?.abort(); setLoading(false); }}
                    style={{ width: '100%', marginBottom: '8px', justifyContent: 'center' }}
                >
                    {__('Cancel', 'wacdmg-ai-content-assistant')}
                </Button>
            )}
            {notice.text && (
                <Notice status={notice.status === 'success' ? 'success' : 'error'} isDismissible={false}>
                    {notice.text}
                </Notice>
            )}
        </PluginDocumentSettingPanel>
    );
};

if (PluginDocumentSettingPanel) {
    registerPlugin('wacdmg-product-ai-panel', { render: ProductAiPanel });
}
