/**
 * AI Product Assistant — Gutenberg document panel for WooCommerce products.
 */
import { registerPlugin } from '@wordpress/plugins';
import { useState, useRef, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button, Notice, Spinner, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { callWpApi } from '../utils/callWpApi';
import { createGenerationSession, isAbortError } from '../utils/generationRequest';
import { getPluginDocumentSettingPanel } from '../utils/documentPanel';
import PostPromptGenerator from '../utils/PromptGenerator';

const PluginDocumentSettingPanel = getPluginDocumentSettingPanel();

const TONE_OPTIONS = [
    { label: 'Persuasive', value: 'persuasive' },
    { label: 'Professional', value: 'professional' },
    { label: 'Friendly', value: 'friendly' },
    { label: 'Luxury', value: 'luxury' },
    { label: 'Minimal', value: 'minimal' },
    { label: 'Informative', value: 'informative' },
];

const LANGUAGE_OPTIONS = [
    { label: 'English', value: 'English' },
    { label: 'Spanish', value: 'Spanish' },
    { label: 'French', value: 'French' },
    { label: 'German', value: 'German' },
    { label: 'Italian', value: 'Italian' },
    { label: 'Portuguese', value: 'Portuguese' },
    { label: 'Arabic', value: 'Arabic' },
    { label: 'Hindi', value: 'Hindi' },
    { label: 'Japanese', value: 'Japanese' },
    { label: 'Chinese (Simplified)', value: 'Chinese (Simplified)' },
];

function stripHtml(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html || '';
    return tmp.textContent || '';
}

function fillTemplatePlaceholders(prompt, { title = '', content = '' } = {}) {
    return String(prompt || '')
        .replace(/\[PRODUCT_NAME\]/gi, title)
        .replace(/\[TITLE\]/gi, title)
        .replace(/\[CONTENT\]/gi, content);
}

function writeDescriptionHtml(html, editPost) {
    const blockEditor = window.wp?.data?.dispatch('core/block-editor');
    if (blockEditor && typeof blockEditor.resetBlocks === 'function' && window.wp?.blocks?.rawHandler) {
        const blocks = window.wp.blocks.rawHandler({ HTML: html });
        blockEditor.resetBlocks(blocks.length ? blocks : window.wp.blocks.rawHandler({ HTML: `<p>${html}</p>` }));
        return;
    }
    editPost({ content: html });
}

const ProductAiPanel = () => {
    const [loading, setLoading] = useState(false);
    const [notice, setNotice] = useState({ text: '', status: '' });
    const [tone, setTone] = useState('persuasive');
    const [language, setLanguage] = useState('English');
    const [templates, setTemplates] = useState([]);
    const [selectedTemplate, setSelectedTemplate] = useState('');
    const [gaps, setGaps] = useState([]);
    const genSession = useRef(createGenerationSession()).current;

    const postType = useSelect(select => select('core/editor').getCurrentPostType());
    const postId = useSelect(select => select('core/editor').getCurrentPostId());
    const title = useSelect(select => select('core/editor').getEditedPostAttribute('title') || '');
    const content = useSelect(select => select('core/editor').getEditedPostAttribute('content') || '');
    const excerpt = useSelect(select => select('core/editor').getEditedPostAttribute('excerpt') || '');
    const { editPost } = useDispatch('core/editor');

    useEffect(() => {
        callWpApi('/get-templates', 'GET')
            .then(res => {
                if (res.success && Array.isArray(res.data)) {
                    setTemplates(res.data.filter(t => !t.type || t.type === 'product' || t.type === 'general'));
                }
            })
            .catch(() => {});
    }, []);

    useEffect(() => {
        if (!postId || postType !== 'product') {
            return;
        }
        callWpApi('/product-content-status?post_id=' + postId, 'GET')
            .then(res => {
                if (res.success && Array.isArray(res.data.gaps)) {
                    setGaps(res.data.gaps);
                }
            })
            .catch(() => {});
    }, [postId, postType]);

    if (postType !== 'product' || !PluginDocumentSettingPanel) {
        return null;
    }

    const generator = new PostPromptGenerator({ tone, language });
    const contentText = stripHtml(content);

    const run = async (request) => {
        if (!title) {
            setNotice({ text: __('Please add a product title first.', 'wacdmg-ai-content-assistant'), status: 'error' });
            return;
        }
        setLoading(true);
        setNotice({ text: '', status: '' });
        const signal = genSession.start();
        try {
            await request(signal);
        } catch (error) {
            if (!isAbortError(error)) {
                setNotice({ text: error.message || __('Generation failed.', 'wacdmg-ai-content-assistant'), status: 'error' });
            }
        } finally {
            if (genSession.settle(signal)) {
                setLoading(false);
            }
        }
    };

    const btnStyle = { width: '100%', marginBottom: '8px', justifyContent: 'center' };

    return (
        <PluginDocumentSettingPanel
            name="wacdmg-product-ai-panel"
            title={__('AI Product Assistant', 'wacdmg-ai-content-assistant')}
            className="wacdmg-product-ai-panel"
        >
            <SelectControl
                label={__('Tone', 'wacdmg-ai-content-assistant')}
                value={tone}
                options={TONE_OPTIONS}
                onChange={setTone}
            />
            <SelectControl
                label={__('Language', 'wacdmg-ai-content-assistant')}
                value={language}
                options={LANGUAGE_OPTIONS}
                onChange={setLanguage}
            />
            {templates.length > 0 && (
                <>
                    <SelectControl
                        label={__('Template', 'wacdmg-ai-content-assistant')}
                        value={selectedTemplate}
                        options={[
                            { label: __('Select template...', 'wacdmg-ai-content-assistant'), value: '' },
                            ...templates.map(tpl => ({ label: tpl.name, value: tpl.id })),
                        ]}
                        onChange={setSelectedTemplate}
                    />
                    <Button
                        variant="secondary"
                        disabled={loading || !selectedTemplate}
                        onClick={() => run(async (signal) => {
                            const tpl = templates.find(t => t.id === selectedTemplate);
                            const filled = fillTemplatePlaceholders(tpl.prompt, { title, content: contentText });
                            const response = await callWpApi('/generate-description', 'POST', { prompt: filled }, { signal });
                            if (!response.success) {
                                throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                            }
                            writeDescriptionHtml(response.data.description || response.data.content || '', editPost);
                            setNotice({ text: __('Description generated from template.', 'wacdmg-ai-content-assistant'), status: 'success' });
                        })}
                        style={btnStyle}
                    >
                        {__('Run Template', 'wacdmg-ai-content-assistant')}
                    </Button>
                </>
            )}
            {gaps.length > 0 && (
                <p style={{ fontSize: '12px', marginBottom: '8px' }}>
                    {__('Missing: ', 'wacdmg-ai-content-assistant') + gaps.filter(g => g !== 'image').join(', ')}
                </p>
            )}
            <Button
                variant="secondary"
                disabled={loading}
                onClick={() => run(async (signal) => {
                    const response = await callWpApi('/generate-description', 'POST', {
                        prompt: generator.productNameDescription(title),
                    }, { signal });
                    if (!response.success) {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                    writeDescriptionHtml(response.data.description || response.data.content || '', editPost);
                    setNotice({ text: __('Description generated.', 'wacdmg-ai-content-assistant'), status: 'success' });
                })}
                style={btnStyle}
            >
                {loading ? <Spinner /> : __('Generate Description', 'wacdmg-ai-content-assistant')}
            </Button>
            <Button
                variant="secondary"
                disabled={loading}
                onClick={() => run(async (signal) => {
                    const response = await callWpApi('/generate-description', 'POST', {
                        prompt: generator.improveTitle(title),
                    }, { signal });
                    if (!response.success) {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                    const nextTitle = stripHtml(response.data.description || '').replace(/^["']|["']$/g, '').trim();
                    editPost({ title: nextTitle });
                    setNotice({ text: __('Title updated.', 'wacdmg-ai-content-assistant'), status: 'success' });
                })}
                style={btnStyle}
            >
                {__('Improve Title', 'wacdmg-ai-content-assistant')}
            </Button>
            <Button
                variant="secondary"
                disabled={loading}
                onClick={() => run(async (signal) => {
                    const response = await callWpApi('/generate-short-description', 'POST', {
                        prompt: generator.productShortDescription(title),
                    }, { signal });
                    if (!response.success) {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                    editPost({ excerpt: response.data.short_description || '' });
                    setNotice({ text: __('Short description generated.', 'wacdmg-ai-content-assistant'), status: 'success' });
                })}
                style={btnStyle}
            >
                {__('Generate Short Description', 'wacdmg-ai-content-assistant')}
            </Button>
            <Button
                variant="secondary"
                disabled={loading}
                onClick={() => run(async (signal) => {
                    const response = await callWpApi('/generate-tags', 'POST', {
                        prompt: generator.productTags(title, contentText),
                        post_id: postId,
                        apply: true,
                    }, { signal });
                    if (!response.success) {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                    const termIds = response.data.term_ids || [];
                    const taxonomy = response.data.taxonomy || 'product_tag';
                    if (termIds.length) {
                        editPost({ [taxonomy]: termIds });
                    }
                    setNotice({
                        text: __('Tags applied: ', 'wacdmg-ai-content-assistant') + (response.data.tags || []).join(', '),
                        status: 'success',
                    });
                })}
                style={btnStyle}
            >
                {__('Generate Product Tags', 'wacdmg-ai-content-assistant')}
            </Button>
            <Button
                variant="secondary"
                disabled={loading}
                onClick={() => run(async (signal) => {
                    const response = await callWpApi('/generate-categories', 'POST', {
                        prompt: generator.productCategories(title, contentText),
                        post_id: postId,
                        apply: true,
                    }, { signal });
                    if (!response.success) {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                    if (response.data.term_ids?.length) {
                        editPost({ product_cat: response.data.term_ids });
                    }
                    setNotice({
                        text: __('Categories applied: ', 'wacdmg-ai-content-assistant') + (response.data.categories || []).join(', '),
                        status: 'success',
                    });
                })}
                style={btnStyle}
            >
                {__('Suggest Categories', 'wacdmg-ai-content-assistant')}
            </Button>
            <Button
                variant="secondary"
                disabled={loading}
                onClick={() => run(async (signal) => {
                    const response = await callWpApi('/generate-attributes', 'POST', {
                        prompt: generator.productAttributes(title, contentText),
                        post_id: postId,
                        apply: true,
                    }, { signal });
                    if (!response.success) {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                    const labels = (response.data.attributes || []).map(a => a.name + ': ' + a.value).join(', ');
                    setNotice({
                        text: __('Attributes saved. Reload Attributes if they are not visible yet. ', 'wacdmg-ai-content-assistant') + labels,
                        status: 'success',
                    });
                })}
                style={btnStyle}
            >
                {__('Extract Attributes', 'wacdmg-ai-content-assistant')}
            </Button>
            <Button
                variant="secondary"
                disabled={loading}
                onClick={() => run(async (signal) => {
                    const response = await callWpApi('/generate-seo-meta', 'POST', {
                        title_prompt: generator.seoMetaTitle(title),
                        desc_prompt: generator.seoMetaDescription(title, contentText || excerpt),
                        kw_prompt: generator.seoFocusKeywords(title, contentText),
                        post_id: postId,
                    }, { signal });
                    if (!response.success) {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                    setNotice({ text: __('SEO meta generated and saved.', 'wacdmg-ai-content-assistant'), status: 'success' });
                })}
                style={btnStyle}
            >
                {__('Generate SEO Meta', 'wacdmg-ai-content-assistant')}
            </Button>
            <Button
                variant="secondary"
                disabled={loading || !contentText.trim()}
                onClick={() => run(async (signal) => {
                    const response = await callWpApi('/generate-description', 'POST', {
                        prompt: generator.translateContent(content, 'product description'),
                    }, { signal });
                    if (!response.success) {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                    writeDescriptionHtml(response.data.description || '', editPost);
                    setNotice({ text: __('Description translated.', 'wacdmg-ai-content-assistant'), status: 'success' });
                })}
                style={btnStyle}
            >
                {__('Translate Description', 'wacdmg-ai-content-assistant')}
            </Button>
            <Button
                variant="secondary"
                disabled={loading || !contentText.trim()}
                onClick={() => run(async (signal) => {
                    const response = await callWpApi('/generate-description', 'POST', {
                        prompt: generator.summarizeContent(contentText, 80),
                    }, { signal });
                    if (!response.success) {
                        throw new Error(response.data?.message || __('Failed.', 'wacdmg-ai-content-assistant'));
                    }
                    editPost({ excerpt: stripHtml(response.data.description || '') });
                    setNotice({ text: __('Short description summarized from content.', 'wacdmg-ai-content-assistant'), status: 'success' });
                })}
                style={btnStyle}
            >
                {__('Summarize to Short Description', 'wacdmg-ai-content-assistant')}
            </Button>
            {loading && (
                <Button
                    variant="tertiary"
                    isSmall
                    onClick={() => { genSession.cancel(); setLoading(false); }}
                    style={btnStyle}
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
