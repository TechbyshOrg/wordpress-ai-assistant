import React, { useEffect, useState, useRef } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import { createGenerationSession, isAbortError } from '../../utils/generationRequest';
import ProductPromptGenerator from '../../utils/PromptGenerator';
import { htmlToText } from '../../utils/sanitizeHtml';

const LANGUAGE = window?.wacdmgAdmin?.defaultLanguage || 'English';

const FIELD_LABELS = {
    content: 'Description',
    excerpt: 'Short description',
    tags: 'Tags',
    categories: 'Categories',
    seo: 'SEO meta',
};

const parseIds = () => {
    const params = new URLSearchParams(window.location.search);
    return (params.get('ids') || '')
        .split(',')
        .map(id => parseInt(id, 10))
        .filter(id => id > 0)
        .slice(0, 20);
};

const gapLabel = (gap, product) => {
    if (gap === 'description' && product.description_state === 'thin') {
        return 'description (short)';
    }
    return gap;
};

const excerptOf = (html, length = 140) => {
    const text = htmlToText(html).replace(/\s+/g, ' ').trim();
    return text.length > length ? text.slice(0, length) + '…' : text;
};

const BulkFill = () => {
    const [products, setProducts] = useState([]);
    const [previews, setPreviews] = useState({});
    // selected[productId][field] = true when that generated field should be applied.
    const [selected, setSelected] = useState({});
    const [loadingId, setLoadingId] = useState(0);
    const [message, setMessage] = useState('');
    const genSession = useRef(createGenerationSession()).current;
    const ids = parseIds();

    const loadScan = async () => {
        if (!ids.length) {
            setMessage('Select products in Products → All products, then use Bulk actions → AI fill empty content.');
            return;
        }
        setMessage('Scanning selected products...');
        try {
            const res = await callWpApi('/products-content-scan', 'POST', { ids });
            if (res.success) {
                setProducts(res.data.products || []);
                setMessage('');
            }
        } catch (error) {
            setMessage(error.message || 'Could not scan products.');
        }
    };

    useEffect(() => { loadScan(); }, []);

    const generateMissing = async (product) => {
        if (!product.has_title) {
            setMessage('Skipped "' + product.id + '": the product has no title.');
            return;
        }
        const generator = new ProductPromptGenerator({ tone: 'persuasive', language: LANGUAGE });
        const preview = {};
        const isThin = product.description_state === 'thin';
        setLoadingId(product.id);
        const gaps = (product.gaps || []).filter(g => g !== 'image');
        try {
            for (const gap of gaps) {
                const signal = genSession.start();
                try {
                    if (gap === 'description') {
                        // A short existing description is expanded from its own text, not replaced from the title alone.
                        const prompt = isThin
                            ? generator.improveDescription(product.content, product.title)
                            : generator.productNameDescription(product.title);
                        const res = await callWpApi('/generate-description', 'POST', { prompt }, { signal });
                        if (res.success) preview.content = res.data.description;
                    } else if (gap === 'excerpt') {
                        const res = await callWpApi('/generate-short-description', 'POST', {
                            prompt: generator.productShortDescription(product.title),
                        }, { signal });
                        if (res.success) preview.excerpt = res.data.short_description;
                    } else if (gap === 'tags') {
                        const res = await callWpApi('/generate-tags', 'POST', {
                            prompt: generator.productTags(product.title, product.content || preview.content || ''),
                            post_id: product.id,
                        }, { signal });
                        if (res.success) preview.tags = res.data.tags;
                    } else if (gap === 'categories') {
                        const res = await callWpApi('/generate-categories', 'POST', {
                            prompt: generator.productCategories(product.title, product.content || preview.content || ''),
                            post_id: product.id,
                        }, { signal });
                        if (res.success) preview.categories = res.data.categories;
                    } else if (gap === 'seo') {
                        const res = await callWpApi('/generate-seo-meta', 'POST', {
                            title_prompt: generator.seoMetaTitle(product.title),
                            desc_prompt: generator.seoMetaDescription(product.title, product.content || preview.content || product.excerpt || ''),
                            kw_prompt: generator.seoFocusKeywords(product.title, product.content || ''),
                            post_id: 0,
                        }, { signal });
                        if (res.success) preview.seo = res.data;
                    }
                } finally {
                    genSession.settle(signal);
                }
            }

            // Tick every generated field, except a replacement for an existing description.
            const defaults = {};
            Object.keys(preview).forEach(field => {
                defaults[field] = !(field === 'content' && isThin);
            });
            setPreviews(prev => ({ ...prev, [product.id]: preview }));
            setSelected(prev => ({ ...prev, [product.id]: defaults }));
            setMessage('Preview ready for "' + product.title + '". Tick the fields you want, then Apply.');
        } catch (error) {
            if (!isAbortError(error)) {
                setMessage(error.message || 'Generation failed.');
            }
        }
        setLoadingId(0);
    };

    const toggleField = (productId, field) => {
        setSelected(prev => ({
            ...prev,
            [productId]: { ...(prev[productId] || {}), [field]: !(prev[productId] || {})[field] },
        }));
    };

    const buildPayload = (product) => {
        const preview = previews[product.id] || {};
        const picks = selected[product.id] || {};
        const payload = { post_id: product.id, replace: [] };
        let count = 0;
        Object.keys(FIELD_LABELS).forEach(field => {
            if (preview[field] && picks[field]) {
                payload[field] = preview[field];
                count += 1;
            }
        });
        // The only field a reviewer can choose to overwrite is a short existing description.
        if (payload.content && product.description_state === 'thin') {
            payload.replace.push('content');
        }
        return count ? payload : null;
    };

    const applyPreview = async (product) => {
        const payload = buildPayload(product);
        if (!payload) {
            setMessage('Nothing selected for "' + product.title + '".');
            return false;
        }
        setLoadingId(product.id);
        try {
            const res = await callWpApi('/apply-product-content', 'POST', payload);
            if (!res.success) {
                throw new Error(res.data?.message || 'Apply failed.');
            }
            setProducts(prev => prev.map(item => item.id === product.id ? res.data : item));
            setPreviews(prev => {
                const next = { ...prev };
                delete next[product.id];
                return next;
            });
            const kept = (res.data.kept || []).map(field => FIELD_LABELS[field] || field);
            setMessage(kept.length
                ? 'Applied to "' + product.title + '". Kept existing: ' + kept.join(', ') + '.'
                : 'Applied to "' + product.title + '".');
            return true;
        } catch (error) {
            setMessage(error.message || 'Could not apply content.');
            return false;
        } finally {
            setLoadingId(0);
        }
    };

    const generateAll = async () => {
        for (const product of products) {
            if (!(product.gaps || []).filter(g => g !== 'image').length) continue;
            await generateMissing(product);
        }
    };

    const applyAll = async () => {
        let applied = 0;
        for (const product of products) {
            if (previews[product.id] && buildPayload(product) && await applyPreview(product)) {
                applied += 1;
            }
        }
        setMessage('Applied reviewed content to ' + applied + ' product' + (applied === 1 ? '' : 's') + '.');
    };

    const reviewedCount = products.filter(p => previews[p.id] && buildPayload(p)).length;

    const renderField = (product, field, body) => {
        const id = `wacdmg-bulk-${product.id}-${field}`;
        const picks = selected[product.id] || {};
        const replacesExisting = field === 'content' && product.description_state === 'thin';
        return (
            <div key={field} className="wacdmg-bulk-field">
                <input type="checkbox" id={id} checked={!!picks[field]} onChange={() => toggleField(product.id, field)} />
                <label htmlFor={id}>
                    <strong>{FIELD_LABELS[field]}{replacesExisting ? ' (replaces current)' : ''}:</strong> {body}
                </label>
                {replacesExisting && (
                    <div className="wacdmg-bulk-current">Current: {excerptOf(product.content, 200)}</div>
                )}
            </div>
        );
    };

    return (
        <div className="wacdmg-tpl-wrap">
            <div className="wacdmg-tpl-header">
                <div>
                    <h2>Bulk fill empty product content</h2>
                    <p>
                        Empty fields are generated and ticked for you. Short descriptions are expanded from their current text,
                        but only replace it if you tick them. Review, then Apply. Images are skipped.
                    </p>
                </div>
                <div>
                    <button type="button" className="wacdmg-tpl-add-btn" disabled={!!loadingId || !products.length} onClick={generateAll}>
                        Generate all missing
                    </button>
                    {' '}
                    <button type="button" className="button button-primary" disabled={!!loadingId || !reviewedCount} onClick={applyAll}>
                        Apply all reviewed ({reviewedCount})
                    </button>
                </div>
            </div>
            <p role="status" aria-live="polite">{message}</p>
            {!ids.length && (
                <p>No product IDs in the URL. Use the Products list bulk action.</p>
            )}
            <table className="widefat striped" style={{ marginTop: '12px' }}>
                <thead>
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col">Empty fields</th>
                        <th scope="col">Preview</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {products.map(product => {
                        const preview = previews[product.id] || {};
                        const gaps = (product.gaps || []).filter(g => g !== 'image');
                        return (
                            <tr key={product.id}>
                                <td>
                                    <a href={product.edit_url}>{product.title || '(no title)'}</a>
                                </td>
                                <td>{gaps.length ? gaps.map(g => gapLabel(g, product)).join(', ') : 'None'}</td>
                                <td className="wacdmg-bulk-preview">
                                    {preview.content && renderField(product, 'content', excerptOf(preview.content))}
                                    {preview.excerpt && renderField(product, 'excerpt', preview.excerpt)}
                                    {preview.tags && renderField(product, 'tags', preview.tags.join(', '))}
                                    {preview.categories && renderField(product, 'categories', preview.categories.join(', '))}
                                    {preview.seo && renderField(product, 'seo', preview.seo.seo_title)}
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        className="button"
                                        disabled={!!loadingId || !gaps.length}
                                        onClick={() => generateMissing(product)}
                                    >
                                        {loadingId === product.id ? 'Working...' : 'Generate missing'}
                                    </button>
                                    {' '}
                                    <button
                                        type="button"
                                        className="button button-primary"
                                        disabled={!!loadingId || !buildPayload(product)}
                                        onClick={() => applyPreview(product)}
                                    >
                                        Apply selected
                                    </button>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
};

export default BulkFill;
