/**
 * AI Image — Gutenberg Document Settings Panel
 *
 * Adds a Document-level sidebar panel for generating AI images
 * and optionally setting them as the post's Featured Image.
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Button, TextareaControl, ToggleControl, Notice, Spinner, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { callWpApi } from '../utils/callWpApi';

const IMAGE_STYLE_PRESETS = [
    { label: 'No specific style', value: '' },
    { label: 'Photorealistic', value: 'photorealistic, high resolution, professional photography' },
    { label: 'Digital Art', value: 'digital art, concept art, highly detailed illustration' },
    { label: 'Watercolor', value: 'watercolor, soft colors, artistic illustration' },
    { label: 'Minimalist / Flat', value: 'minimalist, flat design, clean lines, vector art' },
    { label: 'Cinematic', value: 'cinematic, dramatic lighting, movie still' },
    { label: '3D Rendered', value: '3D render, octane render, studio lighting' },
];

const AIImagePanel = () => {
    const [prompt, setPrompt]           = useState('');
    const [style, setStyle]             = useState('');
    const [setFeatured, setSetFeatured] = useState(true);
    const [saveToLib, setSaveToLib]     = useState(true);
    const [loading, setLoading]         = useState(false);
    const [generatedUrl, setGeneratedUrl] = useState('');
    const [attachmentId, setAttachmentId] = useState(null);
    const [notice, setNotice]           = useState({ text: '', status: '' });

    const postId    = useSelect(select => select('core/editor').getCurrentPostId());
    const postTitle = useSelect(select => select('core/editor').getEditedPostAttribute('title') || '');

    const handleGenerate = async () => {
        const finalPrompt = style ? `${prompt}, ${style}` : prompt;
        if (!finalPrompt.trim()) {
            setNotice({ text: __('Please enter an image prompt.', 'wacdmg-ai-content-assistant'), status: 'error' });
            return;
        }

        setLoading(true);
        setNotice({ text: '', status: '' });
        setGeneratedUrl('');

        try {
            const response = await callWpApi('/generate-image', 'POST', {
                prompt: finalPrompt,
                save_to_library: saveToLib,
                set_as_featured: setFeatured,
                post_id: postId,
            });

            if (response.success) {
                setGeneratedUrl(response.data.url);
                setAttachmentId(response.data.attachment_id);
                const savedMsg = saveToLib
                    ? setFeatured
                        ? __('Image generated and set as Featured Image!', 'wacdmg-ai-content-assistant')
                        : __('Image generated and saved to Media Library!', 'wacdmg-ai-content-assistant')
                    : __('Image generated!', 'wacdmg-ai-content-assistant');
                setNotice({ text: savedMsg, status: 'success' });
            } else {
                setNotice({ text: response.data?.message || __('Failed to generate image.', 'wacdmg-ai-content-assistant'), status: 'error' });
            }
        } catch (error) {
            setNotice({ text: error.message || __('Network error. Check your image generation settings.', 'wacdmg-ai-content-assistant'), status: 'error' });
        }

        setLoading(false);
    };

    // Auto-fill prompt from post title
    const autoFillFromTitle = () => {
        if (postTitle) {
            setPrompt(`Product or featured image for: ${postTitle}. Professional, high quality, suitable for website hero image.`);
        }
    };

    return (
        <PluginDocumentSettingPanel
            name="wacdmg-ai-image-panel"
            title={__('AI Featured Image', 'wacdmg-ai-content-assistant')}
            className="wacdmg-ai-image-panel"
        >
            <TextareaControl
                label={__('Image Prompt', 'wacdmg-ai-content-assistant')}
                help={__('Describe the image you want to generate.', 'wacdmg-ai-content-assistant')}
                value={prompt}
                rows={3}
                onChange={setPrompt}
                placeholder={__('e.g., Professional product photo on white background...', 'wacdmg-ai-content-assistant')}
            />

            {postTitle && (
                <Button variant="link" onClick={autoFillFromTitle} style={{ fontSize: '11px', marginBottom: '8px', display: 'block' }}>
                    {__('Auto-fill from post title', 'wacdmg-ai-content-assistant')}
                </Button>
            )}

            <SelectControl
                label={__('Style Preset', 'wacdmg-ai-content-assistant')}
                value={style}
                options={IMAGE_STYLE_PRESETS}
                onChange={setStyle}
            />

            <ToggleControl
                label={__('Save to Media Library', 'wacdmg-ai-content-assistant')}
                checked={saveToLib}
                onChange={setSaveToLib}
            />

            <ToggleControl
                label={__('Set as Featured Image', 'wacdmg-ai-content-assistant')}
                checked={setFeatured}
                onChange={setSetFeatured}
                disabled={!saveToLib}
            />

            <Button
                variant="primary"
                onClick={handleGenerate}
                disabled={loading || !prompt.trim()}
                style={{ width: '100%', justifyContent: 'center', marginTop: '4px' }}
            >
                {loading ? <><Spinner /> {__('Generating...', 'wacdmg-ai-content-assistant')}</> : __('Generate Image', 'wacdmg-ai-content-assistant')}
            </Button>

            {notice.text && (
                <Notice status={notice.status === 'success' ? 'success' : 'error'} isDismissible={false} style={{ marginTop: '10px' }}>
                    {notice.text}
                </Notice>
            )}

            {generatedUrl && (
                <div style={{ marginTop: '12px', textAlign: 'center' }}>
                    <img
                        src={generatedUrl}
                        alt={__('AI Generated', 'wacdmg-ai-content-assistant')}
                        style={{ width: '100%', borderRadius: '6px', border: '1px solid #e2e8f0' }}
                    />
                    {attachmentId && (
                        <a
                            href={`${window.location.origin}/wp-admin/upload.php?item=${attachmentId}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            style={{ fontSize: '11px', display: 'block', marginTop: '6px', color: '#2271b1' }}
                        >
                            {__('View in Media Library', 'wacdmg-ai-content-assistant')} →
                        </a>
                    )}
                </div>
            )}
        </PluginDocumentSettingPanel>
    );
};

registerPlugin('wacdmg-ai-image-panel', { render: AIImagePanel });
