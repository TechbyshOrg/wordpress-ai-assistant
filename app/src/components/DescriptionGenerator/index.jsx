import React, { useEffect, useState, useCallback } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import './style.css';
import ProductPromptGenerator from '../../utils/PromptGenerator';
import { toneOptions, languageOptions } from '../../utils/variables';

const DescriptionGenerator = () => {
    const [tone, setTone]       = useState('persuasive');
    const [language, setLanguage] = useState('English');
    const [loading, setLoading] = useState(false);
    const [loadingAction, setLoadingAction] = useState('');

    const [generatedDescription, setGeneratedDescription] = useState('');
    const [generationMethod, setGenerationMethod] = useState(null);
    const [insertButtonText, setInsertButtonText] = useState('Insert Into Description');

    const [addPrompt, setAddPrompt] = useState(false);
    const [yourPrompt, setYourPrompt] = useState('');

    // SEO meta state
    const [seoMeta, setSeoMeta]   = useState({ seo_title: '', meta_description: '', focus_keywords: '' });
    const [showSeo, setShowSeo]   = useState(false);

    // Image state
    const [imagePrompt, setImagePrompt]     = useState('');
    const [generatedImage, setGeneratedImage] = useState(null);
    const [imageLoading, setImageLoading]   = useState(false);
    const [showImagePanel, setShowImagePanel] = useState(false);
    const [saveToLibrary, setSaveToLibrary] = useState(true);
    const [setAsFeatured, setSetAsFeatured] = useState(false);

    const promptGenerator = new ProductPromptGenerator({ tone, language });

    // Helper: get current post ID
    const getPostId = () => {
        const match = window.location.search.match(/[?&]post=(\d+)/);
        return match ? parseInt(match[1]) : 0;
    };

    // Helper: get product title
    const getProductName = () => {
        const input = document.querySelector('input[name="post_title"]');
        return input ? input.value.trim() : '';
    };

    // Helper: get current description text
    const getCurrentDescription = () => {
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('content');
            if (editor && !editor.isHidden()) {
                return editor.getContent({ format: 'text' });
            }
        }
        const textarea = document.getElementById('content');
        return textarea ? textarea.value : '';
    };

    const clearGeneratedDescription = () => {
        setGeneratedDescription('');
        setGenerationMethod(null);
        setInsertButtonText('Insert Into Description');
        setAddPrompt(false);
        setYourPrompt('');
    };

    // Core submit function
    const submitPrompt = useCallback(async (method, prompt, actionLabel = 'Generating...') => {
        setLoading(true);
        setLoadingAction(actionLabel);
        setGeneratedDescription('');

        try {
            const response = await callWpApi('/generate-description', 'POST', {
                prompt,
                method,
                tone,
                language,
            });

            if (response.success) {
                setGeneratedDescription(response.data.description);
                setGenerationMethod(method);
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            alert('Error: ' + (error.message || 'An error occurred while generating content.'));
        }

        setLoading(false);
        setLoadingAction('');
        setAddPrompt(false);
        setYourPrompt('');
    }, [tone, language]);

    // =========================================================================
    // Action Handlers
    // =========================================================================

    const generateNameDescription = () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        const generator = new ProductPromptGenerator({ tone, language });
        submitPrompt('name', generator.productNameDescription(productName), 'Generating description...');
    };

    const improveCurrentDescription = () => {
        const productName = getProductName();
        const currentDesc = getCurrentDescription();
        if (!productName) { alert('Please enter a product name first.'); return; }
        if (!currentDesc.trim()) { alert('Please enter a description to improve.'); return; }
        const generator = new ProductPromptGenerator({ tone, language });
        submitPrompt('improve', generator.improveDescription(currentDesc, productName), 'Improving description...');
    };

    const improveTitle = () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product title first.'); return; }
        const generator = new ProductPromptGenerator({ tone, language });
        setInsertButtonText('Insert to Title');
        submitPrompt('title', generator.improveTitle(productName), 'Improving title...');
    };

    const generateShortDescription = async () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        setLoading(true);
        setLoadingAction('Generating short description...');
        const generator = new ProductPromptGenerator({ tone, language });
        try {
            const response = await callWpApi('/generate-short-description', 'POST', {
                prompt: generator.productShortDescription(productName),
                tone,
                language,
            });
            if (response.success) {
                // Insert directly into the short description textarea
                const shortDescEl = document.getElementById('excerpt');
                if (shortDescEl) {
                    shortDescEl.value = response.data.short_description;
                    shortDescEl.dispatchEvent(new Event('change', { bubbles: true }));
                }
                // Also show in output area
                setGeneratedDescription(response.data.short_description);
                setGenerationMethod('short_description');
                setInsertButtonText('Insert Short Description');
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) { alert('Error: ' + (error.message || 'Error generating short description.')); }
        setLoading(false);
        setLoadingAction('');
    };

    const generateTags = async () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        setLoading(true);
        setLoadingAction('Generating tags...');
        const currentDesc = getCurrentDescription();
        const generator = new ProductPromptGenerator({ tone, language });
        try {
            const response = await callWpApi('/generate-tags', 'POST', {
                prompt: generator.productTags(productName, currentDesc),
                tone,
                language,
            });
            if (response.success) {
                setGeneratedDescription('Tags: ' + response.data.tags.join(', '));
                setGenerationMethod('tags');
                setInsertButtonText('Tags Generated');
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) { alert('Error: ' + (error.message || 'Error generating tags.')); }
        setLoading(false);
        setLoadingAction('');
    };

    const generateSeoMeta = async () => {
        const productName = getProductName();
        const currentDesc = getCurrentDescription();
        if (!productName) { alert('Please enter a product name first.'); return; }
        setLoading(true);
        setLoadingAction('Generating SEO meta...');
        const postId = getPostId();
        const generator = new ProductPromptGenerator({ tone, language });
        try {
            const response = await callWpApi('/generate-seo-meta', 'POST', {
                title_prompt: generator.seoMetaTitle(productName),
                desc_prompt: generator.seoMetaDescription(productName, currentDesc),
                kw_prompt: generator.seoFocusKeywords(productName, currentDesc),
                post_id: postId,
            });
            if (response.success) {
                setSeoMeta(response.data);
                setShowSeo(true);
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) { alert('Error: ' + (error.message || 'Error generating SEO meta.')); }
        setLoading(false);
        setLoadingAction('');
    };

    const handleCustomPromptSubmit = () => {
        if (!yourPrompt.trim()) { alert('Please enter a prompt.'); return; }
        const productName = getProductName();
        const generator = new ProductPromptGenerator({ tone, language });

        if (generationMethod === 'improve-prompt') {
            const currentDesc = getCurrentDescription();
            if (!currentDesc.trim()) { alert('Please enter a description to improve.'); return; }
            submitPrompt('improve-prompt', generator.improveDescriptionCustom(currentDesc, productName, yourPrompt), 'Improving with prompt...');
        } else if (generationMethod === 'title') {
            setInsertButtonText('Insert to Title');
            submitPrompt('title', generator.improveTitleCustom(productName, yourPrompt), 'Improving title...');
        } else {
            submitPrompt('prompt', yourPrompt, 'Generating from prompt...');
        }
    };

    const generateAiImage = async () => {
        if (!imagePrompt.trim()) { alert('Please enter an image prompt.'); return; }
        setImageLoading(true);
        const postId = getPostId();
        try {
            const response = await callWpApi('/generate-image', 'POST', {
                prompt: imagePrompt,
                save_to_library: saveToLibrary,
                set_as_featured: setAsFeatured,
                post_id: postId,
            });
            if (response.success) {
                setGeneratedImage(response.data);
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) { alert('Error: ' + (error.message || 'Error generating image.')); }
        setImageLoading(false);
    };

    // =========================================================================
    // Insert Handlers
    // =========================================================================

    const insertDescription = () => {
        if (generationMethod === 'title') {
            insertToTitle(generatedDescription);
        } else if (generationMethod === 'short_description') {
            insertToShortDescription(generatedDescription);
        } else {
            insertToProductDescription(generatedDescription);
        }
    };

    const insertToProductDescription = (content) => {
        setInsertButtonText('Inserting...');
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('content');
            if (editor && !editor.isHidden()) {
                editor.setContent(content);
                setInsertButtonText('✅ Inserted');
                setTimeout(() => setInsertButtonText('Insert Into Description'), 2000);
                return;
            }
        }
        const textarea = document.getElementById('content');
        if (textarea) {
            textarea.value = content;
            setInsertButtonText('✅ Inserted');
            setTimeout(() => setInsertButtonText('Insert Into Description'), 2000);
        }
    };

    const insertToShortDescription = (content) => {
        const el = document.getElementById('excerpt');
        if (el) {
            el.value = content;
            setInsertButtonText('✅ Inserted');
            setTimeout(() => setInsertButtonText('Insert Short Description'), 2000);
        }
    };

    const insertToTitle = (content) => {
        const titleInput = document.querySelector('input[name="post_title"]');
        if (titleInput) {
            titleInput.value = content;
            setInsertButtonText('✅ Inserted');
            setTimeout(() => setInsertButtonText('Insert to Title'), 2000);
        }
    };

    const copyToClipboard = (text) => {
        navigator.clipboard?.writeText(text).then(() => {
            // brief visual feedback handled via CSS
        });
    };

    useEffect(() => { setGeneratedDescription(''); }, []);

    return (
        <div className="wacdmg-generator-container">
            {/* Header */}
            <div className="wacdmg-generator-header">
                <span className="wacdmg-ai-badge">✨ AI Assistant</span>

                {/* Tone & Language */}
                <div className="wacdmg-header-controls">
                    <select
                        value={tone}
                        onChange={e => setTone(e.target.value)}
                        className="wacdmg-control-select"
                        title="Content Tone"
                    >
                        {toneOptions.map(t => (
                            <option key={t.value} value={t.value}>{t.label}</option>
                        ))}
                    </select>
                    <select
                        value={language}
                        onChange={e => setLanguage(e.target.value)}
                        className="wacdmg-control-select"
                        title="Language"
                    >
                        {languageOptions.map(l => (
                            <option key={l.value} value={l.value}>{l.label}</option>
                        ))}
                    </select>
                </div>
            </div>

            {/* Loading state */}
            {loading && (
                <div className="wacdmg-loading-bar">
                    <div className="wacdmg-loading-progress"></div>
                    <span className="wacdmg-loading-text">{loadingAction}</span>
                </div>
            )}

            {/* Custom prompt input */}
            {addPrompt && !loading && (
                <div className="wacdmg-prompt-input-wrapper">
                    <textarea
                        placeholder="Enter your prompt here..."
                        className="wacdmg-prompt-textarea"
                        value={yourPrompt}
                        onChange={e => setYourPrompt(e.target.value)}
                        rows={3}
                        autoFocus
                    />
                    <div className="wacdmg-prompt-actions">
                        <button type="button" className="wacdmg-btn wacdmg-btn-primary wacdmg-btn-sm" onClick={handleCustomPromptSubmit}>
                            ⚡ Generate
                        </button>
                        <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={() => { setAddPrompt(false); setYourPrompt(''); }}>
                            Cancel
                        </button>
                    </div>
                </div>
            )}

            {/* Action buttons — shown when not loading, no result, no custom prompt */}
            {!loading && !generatedDescription && !addPrompt && !showSeo && !showImagePanel && (
                <div className="wacdmg-actions-grid">
                    {/* Description group */}
                    <div className="wacdmg-action-group">
                        <span className="wacdmg-action-group-label">📝 Description</span>
                        <div className="wacdmg-action-buttons">
                            <button type="button" id="wacdmg-btn-gen-desc" className="wacdmg-btn wacdmg-btn-primary wacdmg-btn-sm" onClick={generateNameDescription}>
                                Generate from Title
                            </button>
                            <button type="button" id="wacdmg-btn-improve-desc" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={improveCurrentDescription}>
                                Improve Current
                            </button>
                            <button type="button" id="wacdmg-btn-improve-prompt" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={() => { setGenerationMethod('improve-prompt'); setAddPrompt(true); }}>
                                Improve with Prompt
                            </button>
                            <button type="button" id="wacdmg-btn-custom-prompt" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={() => { setGenerationMethod('prompt'); setAddPrompt(true); }}>
                                Custom Prompt
                            </button>
                        </div>
                    </div>

                    {/* Title group */}
                    <div className="wacdmg-action-group">
                        <span className="wacdmg-action-group-label">🏷️ Title</span>
                        <div className="wacdmg-action-buttons">
                            <button type="button" id="wacdmg-btn-improve-title" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={improveTitle}>
                                Improve Title
                            </button>
                            <button type="button" id="wacdmg-btn-improve-title-prompt" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={() => { setGenerationMethod('title'); setAddPrompt(true); }}>
                                Improve Title with Prompt
                            </button>
                        </div>
                    </div>

                    {/* WooCommerce group */}
                    <div className="wacdmg-action-group">
                        <span className="wacdmg-action-group-label">🛒 WooCommerce</span>
                        <div className="wacdmg-action-buttons">
                            <button type="button" id="wacdmg-btn-short-desc" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateShortDescription}>
                                Short Description
                            </button>
                            <button type="button" id="wacdmg-btn-tags" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateTags}>
                                Generate Tags
                            </button>
                        </div>
                    </div>

                    {/* SEO & Image group */}
                    <div className="wacdmg-action-group">
                        <span className="wacdmg-action-group-label">🔍 SEO &amp; Media</span>
                        <div className="wacdmg-action-buttons">
                            <button type="button" id="wacdmg-btn-seo" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateSeoMeta}>
                                Generate SEO Meta
                            </button>
                            <button type="button" id="wacdmg-btn-image" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={() => setShowImagePanel(true)}>
                                🎨 Generate AI Image
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Generated text output */}
            {!loading && generatedDescription && (
                <div className="wacdmg-output-panel">
                    <div className="wacdmg-output-header">
                        <span className="wacdmg-output-label">Generated Content</span>
                        <div className="wacdmg-output-actions">
                            <button
                                type="button"
                                className="wacdmg-btn wacdmg-btn-primary wacdmg-btn-sm"
                                onClick={insertDescription}
                            >
                                {insertButtonText}
                            </button>
                            <button
                                type="button"
                                className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm"
                                onClick={() => copyToClipboard(generatedDescription)}
                                title="Copy to clipboard"
                            >
                                📋 Copy
                            </button>
                            <button
                                type="button"
                                className="wacdmg-btn wacdmg-btn-ghost wacdmg-btn-sm"
                                onClick={clearGeneratedDescription}
                            >
                                ✕ Close
                            </button>
                        </div>
                    </div>
                    <div
                        className="wacdmg-output-content"
                        dangerouslySetInnerHTML={{ __html: generatedDescription }}
                    />
                </div>
            )}

            {/* SEO Meta Panel */}
            {!loading && showSeo && (
                <div className="wacdmg-seo-panel">
                    <div className="wacdmg-panel-header">
                        <span>🔍 AI-Generated SEO Meta</span>
                        <button type="button" className="wacdmg-btn wacdmg-btn-ghost wacdmg-btn-sm" onClick={() => setShowSeo(false)}>✕</button>
                    </div>
                    <div className="wacdmg-seo-fields">
                        {seoMeta.seo_title && (
                            <div className="wacdmg-seo-field">
                                <label>SEO Title <span className="wacdmg-char-count">{seoMeta.seo_title.length} chars</span></label>
                                <p>{seoMeta.seo_title}</p>
                            </div>
                        )}
                        {seoMeta.meta_description && (
                            <div className="wacdmg-seo-field">
                                <label>Meta Description <span className="wacdmg-char-count">{seoMeta.meta_description.length} chars</span></label>
                                <p>{seoMeta.meta_description}</p>
                            </div>
                        )}
                        {seoMeta.focus_keywords && (
                            <div className="wacdmg-seo-field">
                                <label>Focus Keywords</label>
                                <p>{seoMeta.focus_keywords}</p>
                            </div>
                        )}
                        <p className="wacdmg-seo-note">✅ Meta has been written to your SEO plugin fields (if configured).</p>
                    </div>
                </div>
            )}

            {/* AI Image Panel */}
            {!loading && showImagePanel && (
                <div className="wacdmg-image-panel">
                    <div className="wacdmg-panel-header">
                        <span>🎨 AI Image Generator</span>
                        <button type="button" className="wacdmg-btn wacdmg-btn-ghost wacdmg-btn-sm" onClick={() => { setShowImagePanel(false); setGeneratedImage(null); }}>✕</button>
                    </div>
                    <div className="wacdmg-image-controls">
                        <textarea
                            className="wacdmg-prompt-textarea"
                            placeholder="Describe the image you want to generate..."
                            value={imagePrompt}
                            onChange={e => setImagePrompt(e.target.value)}
                            rows={2}
                        />
                        <div className="wacdmg-image-options">
                            <label className="wacdmg-checkbox-label">
                                <input type="checkbox" checked={saveToLibrary} onChange={e => setSaveToLibrary(e.target.checked)} />
                                Save to Media Library
                            </label>
                            <label className="wacdmg-checkbox-label">
                                <input type="checkbox" checked={setAsFeatured} onChange={e => setSetAsFeatured(e.target.checked)} />
                                Set as Featured Image
                            </label>
                        </div>
                        <button
                            type="button"
                            className="wacdmg-btn wacdmg-btn-primary wacdmg-btn-sm"
                            onClick={generateAiImage}
                            disabled={imageLoading || !imagePrompt.trim()}
                        >
                            {imageLoading ? '⏳ Generating...' : '✨ Generate Image'}
                        </button>
                    </div>
                    {generatedImage && (
                        <div className="wacdmg-image-result">
                            <img src={generatedImage.url} alt="AI Generated" className="wacdmg-generated-image" />
                            {generatedImage.attachment_id && (
                                <p className="wacdmg-image-saved">✅ Saved to Media Library (ID: {generatedImage.attachment_id})</p>
                            )}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default DescriptionGenerator;
