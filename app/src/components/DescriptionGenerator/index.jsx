import React, { useEffect, useState, useCallback, useRef } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import { createGenerationSession, isAbortError } from '../../utils/generationRequest';
import { readWpEditorContent, writeWpEditorContent, writePostTitle, writeProductAttributes, writeTaxonomyTags, writeTaxonomyChecklist } from '../../utils/wpEditor';
import './style.css';
import ProductPromptGenerator from '../../utils/PromptGenerator';
import { toneOptions, languageOptions } from '../../utils/variables';

const fillTemplatePlaceholders = (prompt, { title = '', content = '', keywords = '' } = {}) => {
    return String(prompt || '')
        .replace(/\[PRODUCT_NAME\]/gi, title)
        .replace(/\[TITLE\]/gi, title)
        .replace(/\[CONTENT\]/gi, content)
        .replace(/\[KEYWORDS\]/gi, keywords);
};

const DescriptionGenerator = () => {
    const [tone, setTone]       = useState('persuasive');
    const [language, setLanguage] = useState('English');
    const [loading, setLoading] = useState(false);
    const [loadingAction, setLoadingAction] = useState('');
    const genSession = useRef(createGenerationSession()).current;
    const [templates, setTemplates] = useState([]);
    const [selectedTemplate, setSelectedTemplate] = useState('');
    const [generatedTags, setGeneratedTags] = useState([]);
    const [generatedCategories, setGeneratedCategories] = useState([]);
    const [generatedAttributes, setGeneratedAttributes] = useState([]);
    const [generatedBrands, setGeneratedBrands] = useState([]);
    const [contentGaps, setContentGaps] = useState([]);
    const [translationLang, setTranslationLang] = useState('');

    const adminCfg = window.wacdmgAdmin || {};
    const isProduct = adminCfg.isProduct === true || adminCfg.postType === 'product' || adminCfg.currentPage === 'product-editor';
    const hasBrands = !!adminCfg.hasBrands;
    const translationLangs = Array.isArray(adminCfg.translationLangs) ? adminCfg.translationLangs : [];
    const hasTranslation = translationLangs.length > 0;

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

    useEffect(() => {
        callWpApi('/get-templates', 'GET')
            .then(res => {
                if (res.success && Array.isArray(res.data)) {
                    setTemplates(res.data);
                }
            })
            .catch(() => {});
        const postId = getPostId();
        if (postId && isProduct) {
            callWpApi('/product-content-status?post_id=' + postId, 'GET')
                .then(res => {
                    if (res.success && Array.isArray(res.data.gaps)) {
                        setContentGaps(res.data.gaps);
                    }
                })
                .catch(() => {});
        }
    }, []);

    const cancelGeneration = () => {
        genSession.cancel();
        setLoading(false);
        setLoadingAction('');
        setImageLoading(false);
    };

    // Helper: get current post ID
    const getPostId = () => {
        const match = window.location.search.match(/[?&]post=(\d+)/);
        return match ? parseInt(match[1]) : 0;
    };

    // Helper: get product title
    const getProductName = () => {
        const input = document.getElementById('title') || document.querySelector('input[name="post_title"]');
        return input ? input.value.trim() : '';
    };

    // Helper: get current description text
    const getCurrentDescription = () => {
        return readWpEditorContent('content', 'text');
    };

    const clearGeneratedDescription = () => {
        setGeneratedDescription('');
        setGenerationMethod(null);
        setInsertButtonText('Insert Into Description');
        setAddPrompt(false);
        setYourPrompt('');
        setGeneratedTags([]);
        setGeneratedCategories([]);
        setGeneratedAttributes([]);
        setGeneratedBrands([]);
    };

    const finishIfCurrent = (signal) => {
        if (genSession.settle(signal)) {
            setLoading(false);
            setLoadingAction('');
            setImageLoading(false);
        }
    };

    // Core submit function
    const submitPrompt = useCallback(async (method, prompt, actionLabel = 'Generating...') => {
        setLoading(true);
        setLoadingAction(actionLabel);
        setGeneratedDescription('');
        const signal = genSession.start();

        try {
            const response = await callWpApi('/generate-description', 'POST', {
                prompt,
                method,
                tone,
                language,
            }, { signal });

            if (response.success) {
                setGeneratedDescription(response.data.description);
                setGenerationMethod(method);
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'An error occurred while generating content.'));
            }
        } finally {
            if (genSession.settle(signal)) {
                setLoading(false);
                setLoadingAction('');
                setAddPrompt(false);
                setYourPrompt('');
            }
        }
    }, [tone, language, genSession]);

    // =========================================================================
    // Action Handlers
    // =========================================================================

    const generateNameDescription = () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        const generator = new ProductPromptGenerator({ tone, language });
        setInsertButtonText('Insert Into Description');
        const prompt = isProduct
            ? generator.productNameDescription(productName)
            : generator.descriptionPostTitle(productName);
        submitPrompt('name', prompt, 'Generating description...');
    };

    const improveCurrentDescription = () => {
        const productName = getProductName();
        const currentDesc = getCurrentDescription();
        if (!productName) { alert('Please enter a product name first.'); return; }
        if (!currentDesc.trim()) { alert('Please enter a description to improve.'); return; }
        const generator = new ProductPromptGenerator({ tone, language });
        setInsertButtonText('Insert Into Description');
        submitPrompt('improve', generator.improveDescription(currentDesc, productName), 'Improving description...');
    };

    const improveTitle = () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product title first.'); return; }
        const generator = new ProductPromptGenerator({ tone, language });
        setInsertButtonText('Insert to Title');
        submitPrompt('title', isProduct ? generator.improveTitle(productName) : generator.improvePostTitle(productName), 'Improving title...');
    };

    const generateShortDescription = async () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        setLoading(true);
        setLoadingAction('Generating short description...');
        const generator = new ProductPromptGenerator({ tone, language });
        const signal = genSession.start();
        try {
            const response = await callWpApi('/generate-short-description', 'POST', {
                prompt: generator.productShortDescription(productName),
                tone,
                language,
            }, { signal });
            if (response.success) {
                setGeneratedDescription(response.data.short_description);
                setGenerationMethod('short_description');
                setInsertButtonText('Insert Short Description');
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Error generating short description.'));
            }
        } finally {
            finishIfCurrent(signal);
        }
    };

    const generateTags = async () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        setLoading(true);
        setLoadingAction('Generating tags...');
        const currentDesc = getCurrentDescription();
        const generator = new ProductPromptGenerator({ tone, language });
        const signal = genSession.start();
        try {
            const response = await callWpApi('/generate-tags', 'POST', {
                prompt: isProduct
                    ? generator.productTags(productName, currentDesc)
                    : generator.postTags(productName, currentDesc),
                tone,
                language,
                post_id: getPostId(),
            }, { signal });
            if (response.success) {
                const tags = response.data.tags || [];
                setGeneratedTags(tags);
                setGeneratedDescription('Tags: ' + tags.join(', '));
                setGenerationMethod('tags');
                setInsertButtonText('Insert Tags');
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Error generating tags.'));
            }
        } finally {
            finishIfCurrent(signal);
        }
    };

    const generateSeoMeta = async () => {
        const productName = getProductName();
        const currentDesc = getCurrentDescription();
        if (!productName) { alert('Please enter a product name first.'); return; }
        setLoading(true);
        setLoadingAction('Generating SEO meta...');
        const postId = getPostId();
        const generator = new ProductPromptGenerator({ tone, language });
        const signal = genSession.start();
        try {
            const response = await callWpApi('/generate-seo-meta', 'POST', {
                title_prompt: generator.seoMetaTitle(productName),
                desc_prompt: generator.seoMetaDescription(productName, currentDesc),
                kw_prompt: generator.seoFocusKeywords(productName, currentDesc),
                post_id: postId,
            }, { signal });
            if (response.success) {
                setSeoMeta(response.data);
                setShowSeo(true);
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Error generating SEO meta.'));
            }
        } finally {
            finishIfCurrent(signal);
        }
    };

    const generateCategories = async () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        setLoading(true);
        setLoadingAction('Suggesting categories...');
        const generator = new ProductPromptGenerator({ tone, language });
        const signal = genSession.start();
        try {
            const response = await callWpApi('/generate-categories', 'POST', {
                prompt: isProduct
                    ? generator.productCategories(productName, getCurrentDescription())
                    : generator.postCategories(productName, getCurrentDescription()),
                post_id: getPostId(),
                taxonomy: isProduct ? 'product_cat' : 'category',
            }, { signal });
            if (response.success) {
                const cats = response.data.categories || [];
                setGeneratedCategories(cats);
                setGeneratedDescription('Categories: ' + cats.join(', '));
                setGenerationMethod('categories');
                setInsertButtonText('Insert Categories');
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Error generating categories.'));
            }
        } finally {
            finishIfCurrent(signal);
        }
    };

    const generateAttributes = async () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        setLoading(true);
        setLoadingAction('Extracting attributes...');
        const generator = new ProductPromptGenerator({ tone, language });
        const signal = genSession.start();
        try {
            const response = await callWpApi('/generate-attributes', 'POST', {
                prompt: generator.productAttributes(productName, getCurrentDescription()),
                post_id: getPostId(),
            }, { signal });
            if (response.success) {
                const attrs = response.data.attributes || [];
                setGeneratedAttributes(attrs);
                setGeneratedDescription(attrs.map(a => a.name + ': ' + a.value).join('\n'));
                setGenerationMethod('attributes');
                setInsertButtonText('Insert Attributes');
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Error generating attributes.'));
            }
        } finally {
            finishIfCurrent(signal);
        }
    };

    const generateBrands = async () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        setLoading(true);
        setLoadingAction('Suggesting brands...');
        const generator = new ProductPromptGenerator({ tone, language });
        const signal = genSession.start();
        try {
            const response = await callWpApi('/generate-categories', 'POST', {
                prompt: generator.productBrands(productName, getCurrentDescription()),
                post_id: getPostId(),
                taxonomy: 'product_brand',
            }, { signal });
            if (response.success) {
                const brands = response.data.categories || [];
                setGeneratedBrands(brands);
                setGeneratedDescription('Brands: ' + brands.join(', '));
                setGenerationMethod('brands');
                setInsertButtonText('Insert Brands');
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Error generating brands.'));
            }
        } finally {
            finishIfCurrent(signal);
        }
    };

    const generateExcerpt = async () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a title first.'); return; }
        setLoading(true);
        setLoadingAction('Generating excerpt...');
        const generator = new ProductPromptGenerator({ tone, language });
        const signal = genSession.start();
        try {
            const response = await callWpApi('/generate-short-description', 'POST', {
                prompt: generator.postExcerpt(productName, getCurrentDescription()),
                tone,
                language,
            }, { signal });
            if (response.success) {
                setGeneratedDescription(response.data.short_description);
                setGenerationMethod('short_description');
                setInsertButtonText('Insert Excerpt');
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Error generating excerpt.'));
            }
        } finally {
            finishIfCurrent(signal);
        }
    };

    const generatePurchaseNote = () => {
        const productName = getProductName();
        if (!productName) { alert('Please enter a product name first.'); return; }
        const generator = new ProductPromptGenerator({ tone, language });
        setInsertButtonText('Insert Purchase Note');
        submitPrompt('purchase_note', generator.purchaseNote(productName), 'Generating purchase note...');
    };

    const fillGalleryAlts = async () => {
        const input = document.getElementById('product_image_gallery');
        const ids = (input ? input.value : '')
            .split(',')
            .map((id) => parseInt(id, 10))
            .filter((id) => id > 0)
            .slice(0, 10);
        if (!ids.length) {
            alert('Add gallery images first.');
            return;
        }
        setLoading(true);
        setLoadingAction('Filling empty gallery alt text...');
        const signal = genSession.start();
        let filled = 0;
        try {
            for (const attachmentId of ids) {
                const response = await callWpApi('/generate-alt-text', 'POST', {
                    prompt: 'Write concise, descriptive alt text for this WordPress product gallery image. Under 125 characters. Return only the alt text as plain text.',
                    attachment_id: attachmentId,
                    skip_if_filled: true,
                }, { signal });
                if (response.success && !response.data?.skipped) {
                    filled += 1;
                }
            }
            alert(filled ? ('Generated alt text for ' + filled + ' gallery image(s).') : 'Gallery images already have alt text.');
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Could not fill gallery alts.'));
            }
        } finally {
            finishIfCurrent(signal);
        }
    };

    const saveToTranslation = async () => {
        const lang = translationLang || (translationLangs[0] && translationLangs[0].code);
        const postId = getPostId();
        if (!lang || !postId) {
            alert('Select a translation language first.');
            return;
        }
        setLoading(true);
        setLoadingAction('Saving to translation...');
        const signal = genSession.start();
        try {
            const response = await callWpApi('/apply-translation', 'POST', {
                post_id: postId,
                lang,
                title: getProductName(),
                content: generatedDescription || readWpEditorContent('content', 'html'),
                excerpt: readWpEditorContent('excerpt', 'html'),
            }, { signal });
            if (response.success) {
                alert('Saved to the existing translation.');
            } else {
                alert('Failed: ' + (response.data?.message || 'No translation exists.'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Could not save translation.'));
            }
        } finally {
            finishIfCurrent(signal);
        }
    };

    const writePurchaseNoteField = (content) => {
        const el = document.getElementById('_purchase_note') || document.querySelector('textarea[name="_purchase_note"]');
        if (!el) {
            return false;
        }
        el.value = content == null ? '' : String(content);
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
        return true;
    };

    const translateDescription = () => {
        const currentDesc = getCurrentDescription();
        if (!currentDesc.trim()) { alert('Please enter a description to translate.'); return; }
        const generator = new ProductPromptGenerator({ tone, language });
        setInsertButtonText('Insert Into Description');
        submitPrompt('translate', generator.translateContent(currentDesc, 'product description'), 'Translating description...');
    };

    const summarizeToShort = () => {
        const currentDesc = getCurrentDescription();
        if (!currentDesc.trim()) { alert('Please enter a description to summarize.'); return; }
        const generator = new ProductPromptGenerator({ tone, language });
        setInsertButtonText('Insert Short Description');
        submitPrompt('summarize', generator.summarizeContent(currentDesc, 80), 'Summarizing...');
    };

    const handleCustomPromptSubmit = () => {
        if (!yourPrompt.trim()) { alert('Please enter a prompt.'); return; }
        const productName = getProductName();
        const generator = new ProductPromptGenerator({ tone, language });

        if (generationMethod === 'improve-prompt') {
            const currentDesc = getCurrentDescription();
            if (!currentDesc.trim()) { alert('Please enter a description to improve.'); return; }
            setInsertButtonText('Insert Into Description');
            submitPrompt('improve-prompt', generator.improveDescriptionCustom(currentDesc, productName, yourPrompt), 'Improving with prompt...');
        } else if (generationMethod === 'title') {
            setInsertButtonText('Insert to Title');
            submitPrompt('title', generator.improveTitleCustom(productName, yourPrompt), 'Improving title...');
        } else {
            setInsertButtonText('Insert Into Description');
            submitPrompt('prompt', yourPrompt, 'Generating from prompt...');
        }
    };

    const generateAiImage = async () => {
        if (!imagePrompt.trim()) { alert('Please enter an image prompt.'); return; }
        setImageLoading(true);
        const postId = getPostId();
        const signal = genSession.start();
        try {
            const response = await callWpApi('/generate-image', 'POST', {
                prompt: imagePrompt,
                save_to_library: saveToLibrary,
                set_as_featured: setAsFeatured,
                post_id: postId,
            }, { signal });
            if (response.success) {
                setGeneratedImage(response.data);
            } else {
                alert('Failed: ' + (response.data?.message || 'Unknown error'));
            }
        } catch (error) {
            if (!isAbortError(error)) {
                alert('Error: ' + (error.message || 'Error generating image.'));
            }
        } finally {
            if (genSession.settle(signal)) {
                setImageLoading(false);
            }
        }
    };

    // =========================================================================
    // Insert Handlers
    // =========================================================================

    const insertDescription = () => {
        if (generationMethod === 'title') {
            insertToTitle(generatedDescription);
        } else if (generationMethod === 'short_description' || generationMethod === 'summarize') {
            insertToShortDescription(generatedDescription);
        } else if (generationMethod === 'tags') {
            insertTags();
        } else if (generationMethod === 'categories') {
            insertCategories();
        } else if (generationMethod === 'attributes') {
            insertAttributes();
        } else if (generationMethod === 'brands') {
            insertBrands();
        } else if (generationMethod === 'purchase_note') {
            insertPurchaseNote();
        } else {
            insertToProductDescription(generatedDescription);
        }
    };

    const insertTags = async () => {
        const tags = generatedTags.length
            ? generatedTags
            : generatedDescription.replace(/^Tags:\s*/i, '').split(',').map(t => t.trim()).filter(Boolean);
        if (!tags.length) return;

        writeTaxonomyTags(isProduct ? 'product_tag' : 'post_tag', tags);

        const postId = getPostId();
        if (postId) {
            try {
                await callWpApi('/generate-tags', 'POST', {
                    tags,
                    apply: true,
                    post_id: postId,
                });
            } catch (error) {
                if (!isAbortError(error)) {
                    alert('Error: ' + (error.message || 'Could not save tags.'));
                    return;
                }
            }
        }

        setInsertButtonText('Tags Inserted');
        setTimeout(() => setInsertButtonText('Insert Tags'), 2000);
    };

    const insertCategories = async () => {
        const cats = generatedCategories.length
            ? generatedCategories
            : generatedDescription.replace(/^Categories:\s*/i, '').split(',').map(t => t.trim()).filter(Boolean);
        if (!cats.length) return;
        const postId = getPostId();
        if (postId) {
            try {
                const response = await callWpApi('/generate-categories', 'POST', {
                    categories: cats,
                    apply: true,
                    post_id: postId,
                    taxonomy: isProduct ? 'product_cat' : 'category',
                });
                writeTaxonomyChecklist(isProduct ? 'product_cat' : 'category', response.data?.term_ids || []);
            } catch (error) {
                if (!isAbortError(error)) {
                    alert('Error: ' + (error.message || 'Could not save categories.'));
                    return;
                }
            }
        }
        setInsertButtonText('Categories Inserted');
        setTimeout(() => setInsertButtonText('Insert Categories'), 2000);
    };

    const insertAttributes = async () => {
        let attrs = generatedAttributes;
        if (!attrs.length) {
            attrs = generatedDescription.split('\n').map(line => {
                const parts = line.split(':');
                if (parts.length < 2) return null;
                return { name: parts[0].trim(), value: parts.slice(1).join(':').trim() };
            }).filter(Boolean);
        }
        if (!attrs.length) return;
        writeProductAttributes(attrs);
        const postId = getPostId();
        if (postId) {
            try {
                await callWpApi('/generate-attributes', 'POST', {
                    attributes: attrs,
                    apply: true,
                    post_id: postId,
                });
            } catch (error) {
                if (!isAbortError(error)) {
                    alert('Error: ' + (error.message || 'Could not save attributes.'));
                    return;
                }
            }
        }
        setInsertButtonText('Attributes Inserted');
        setTimeout(() => setInsertButtonText('Insert Attributes'), 2000);
    };

    const insertBrands = async () => {
        const brands = generatedBrands.length
            ? generatedBrands
            : generatedDescription.replace(/^Brands:\s*/i, '').split(',').map((t) => t.trim()).filter(Boolean);
        if (!brands.length) return;
        const postId = getPostId();
        if (postId) {
            try {
                const response = await callWpApi('/generate-categories', 'POST', {
                    categories: brands,
                    apply: true,
                    post_id: postId,
                    taxonomy: 'product_brand',
                });
                writeTaxonomyChecklist('product_brand', response.data?.term_ids || []);
            } catch (error) {
                if (!isAbortError(error)) {
                    alert('Error: ' + (error.message || 'Could not save brands.'));
                    return;
                }
            }
        }
        setInsertButtonText('Brands Inserted');
        setTimeout(() => setInsertButtonText('Insert Brands'), 2000);
    };

    const insertPurchaseNote = async () => {
        if (!writePurchaseNoteField(generatedDescription)) {
            alert('Could not find the purchase note field.');
            return;
        }
        const postId = getPostId();
        if (postId) {
            try {
                await callWpApi('/apply-product-content', 'POST', {
                    post_id: postId,
                    purchase_note: generatedDescription,
                });
            } catch (error) {
                if (!isAbortError(error)) {
                    alert('Error: ' + (error.message || 'Could not save purchase note.'));
                    return;
                }
            }
        }
        setInsertButtonText('Inserted');
        setTimeout(() => setInsertButtonText('Insert Purchase Note'), 2000);
    };

    const handleUseTemplate = () => {
        const tpl = templates.find(t => t.id === selectedTemplate);
        if (!tpl) return;
        const filled = fillTemplatePlaceholders(tpl.prompt, {
            title: getProductName(),
            content: getCurrentDescription(),
        });
        setInsertButtonText('Insert Into Description');
        submitPrompt('template', filled, 'Generating from template...');
    };

    const insertToProductDescription = (content) => {
        setInsertButtonText('Inserting...');
        if (writeWpEditorContent('content', content)) {
            setInsertButtonText('✅ Inserted');
            setTimeout(() => setInsertButtonText('Insert Into Description'), 2000);
            return;
        }
        setInsertButtonText('Insert Into Description');
        alert('Could not find the product description editor.');
    };

    const insertToShortDescription = (content) => {
        if (writeWpEditorContent('excerpt', content)) {
            setInsertButtonText('✅ Inserted');
            setTimeout(() => setInsertButtonText('Insert Short Description'), 2000);
            return;
        }
        alert('Could not find the short description editor.');
    };

    const insertToTitle = (content) => {
        if (writePostTitle(content)) {
            setInsertButtonText('✅ Inserted');
            setTimeout(() => setInsertButtonText('Insert to Title'), 2000);
            return;
        }
        alert('Could not find the product title field.');
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
                    {templates.length > 0 && (
                        <>
                            <select
                                value={selectedTemplate}
                                onChange={e => setSelectedTemplate(e.target.value)}
                                className="wacdmg-control-select"
                                title="Use template"
                            >
                                <option value="">Use template...</option>
                                {templates.map(tpl => (
                                    <option key={tpl.id} value={tpl.id}>{tpl.name}</option>
                                ))}
                            </select>
                            <button
                                type="button"
                                className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm"
                                disabled={!selectedTemplate || loading}
                                onClick={handleUseTemplate}
                            >
                                Run
                            </button>
                        </>
                    )}
                </div>
            </div>

            {/* Loading state */}
            {loading && (
                <div className="wacdmg-loading-bar">
                    <div className="wacdmg-loading-progress"></div>
                    <span className="wacdmg-loading-text">{loadingAction}</span>
                    <button type="button" className="wacdmg-btn wacdmg-btn-ghost wacdmg-btn-sm" onClick={cancelGeneration}>
                        Cancel
                    </button>
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
                    {contentGaps.length > 0 && (
                        <div className="wacdmg-action-group wacdmg-gap-group">
                            <span className="wacdmg-action-group-label">Missing on this product</span>
                            <div className="wacdmg-gap-list">
                                {contentGaps.filter(g => g !== 'image').map(gap => (
                                    <button
                                        type="button"
                                        key={gap}
                                        className="wacdmg-gap-chip"
                                        onClick={() => {
                                            if (gap === 'description') generateNameDescription();
                                            else if (gap === 'excerpt') generateShortDescription();
                                            else if (gap === 'tags') generateTags();
                                            else if (gap === 'categories') generateCategories();
                                            else if (gap === 'seo') generateSeoMeta();
                                        }}
                                    >
                                        Fill {gap === 'excerpt' ? 'short description' : gap}
                                    </button>
                                ))}
                            </div>
                        </div>
                    )}
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
                            <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={translateDescription}>
                                Translate Description
                            </button>
                            {isProduct && (
                                <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={summarizeToShort}>
                                    Summarize to Short Description
                                </button>
                            )}
                            {hasTranslation && (
                                <>
                                    <select
                                        className="wacdmg-control-select"
                                        value={translationLang}
                                        onChange={(e) => setTranslationLang(e.target.value)}
                                    >
                                        <option value="">Translation language...</option>
                                        {translationLangs.map((lang) => (
                                            <option key={lang.code} value={lang.code}>{lang.name}</option>
                                        ))}
                                    </select>
                                    <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={saveToTranslation}>
                                        Save to translation
                                    </button>
                                </>
                            )}
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

                    {!isProduct && (
                        <div className="wacdmg-action-group">
                            <span className="wacdmg-action-group-label">Post</span>
                            <div className="wacdmg-action-buttons">
                                <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateExcerpt}>
                                    Generate Excerpt
                                </button>
                                <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateTags}>
                                    Generate Tags
                                </button>
                                <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateCategories}>
                                    Suggest Categories
                                </button>
                            </div>
                        </div>
                    )}

                    {/* WooCommerce group */}
                    {isProduct && (
                    <div className="wacdmg-action-group">
                        <span className="wacdmg-action-group-label">🛒 WooCommerce</span>
                        <div className="wacdmg-action-buttons">
                            <button type="button" id="wacdmg-btn-short-desc" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateShortDescription}>
                                Short Description
                            </button>
                            <button type="button" id="wacdmg-btn-tags" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateTags}>
                                Generate Tags
                            </button>
                            <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateCategories}>
                                Suggest Categories
                            </button>
                            <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateAttributes}>
                                Extract Attributes
                            </button>
                            {hasBrands && (
                                <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generateBrands}>
                                    Suggest Brands
                                </button>
                            )}
                            <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={generatePurchaseNote}>
                                Purchase Note
                            </button>
                        </div>
                    </div>
                    )}

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
                            {isProduct && (
                                <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" onClick={fillGalleryAlts}>
                                    Fill empty gallery alts
                                </button>
                            )}
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
