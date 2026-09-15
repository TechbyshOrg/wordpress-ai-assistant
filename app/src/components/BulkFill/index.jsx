import React, { useEffect, useState, useRef } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import { createGenerationSession, isAbortError } from '../../utils/generationRequest';
import ProductPromptGenerator from '../../utils/PromptGenerator';

const parseIds = () => {
    const params = new URLSearchParams(window.location.search);
    return (params.get('ids') || '')
        .split(',')
        .map(id => parseInt(id, 10))
        .filter(id => id > 0)
        .slice(0, 20);
};

const BulkFill = () => {
    const [products, setProducts] = useState([]);
    const [previews, setPreviews] = useState({});
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
            setMessage('Skip products without a title.');
            return;
        }
        const generator = new ProductPromptGenerator({ tone: 'persuasive', language: 'English' });
        const preview = { ...(previews[product.id] || {}) };
        setLoadingId(product.id);
        const gaps = (product.gaps || []).filter(g => g !== 'image');
        try {
            for (const gap of gaps) {
                const signal = genSession.start();
                try {
                    if (gap === 'description') {
                        const res = await callWpApi('/generate-description', 'POST', {
                            prompt: generator.productNameDescription(product.title),
                        }, { signal });
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
            setPreviews(prev => ({ ...prev, [product.id]: preview }));
            setMessage('Preview ready for ' + product.title + '. Review, then Apply. Existing fields were not replaced.');
        } catch (error) {
            if (!isAbortError(error)) {
                setMessage(error.message || 'Generation failed.');
            }
        }
        setLoadingId(0);
    };

    const applyPreview = async (product) => {
        const preview = previews[product.id];
        if (!preview) return;
        setLoadingId(product.id);
        try {
        const payload = { post_id: product.id };
        if (preview.content) payload.content = preview.content;
        if (preview.excerpt) payload.excerpt = preview.excerpt;
        if (preview.tags) payload.tags = preview.tags;
        if (preview.categories) payload.categories = preview.categories;
        if (preview.seo) payload.seo = preview.seo;
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
            setMessage('Applied to ' + product.title + '.');
        } catch (error) {
            setMessage(error.message || 'Could not apply content.');
        }
        setLoadingId(0);
    };

    const generateAll = async () => {
        for (const product of products) {
            if (!(product.gaps || []).filter(g => g !== 'image').length) continue;
            await generateMissing(product);
        }
    };

    return (
        <div className="wacdmg-tpl-wrap">
            <div className="wacdmg-tpl-header">
                <div>
                    <h2>Bulk fill empty product content</h2>
                    <p>Only empty or thin fields are generated. Review each preview before Apply. Images are skipped.</p>
                </div>
                <button type="button" className="wacdmg-tpl-add-btn" disabled={!!loadingId || !products.length} onClick={generateAll}>
                    Generate all missing
                </button>
            </div>
            {message && <p>{message}</p>}
            {!ids.length && (
                <p>No product IDs in the URL. Use the Products list bulk action.</p>
            )}
            <table className="widefat striped" style={{ marginTop: '12px' }}>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Empty fields</th>
                        <th>Preview</th>
                        <th>Actions</th>
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
                                <td>{gaps.length ? gaps.join(', ') : 'None'}</td>
                                <td className="wacdmg-bulk-preview">
                                    {preview.content && <div><strong>Description:</strong> {String(preview.content).replace(/<[^>]+>/g, '').slice(0, 120)}...</div>}
                                    {preview.excerpt && <div><strong>Short:</strong> {preview.excerpt}</div>}
                                    {preview.tags && <div><strong>Tags:</strong> {preview.tags.join(', ')}</div>}
                                    {preview.categories && <div><strong>Categories:</strong> {preview.categories.join(', ')}</div>}
                                    {preview.seo && <div><strong>SEO:</strong> {preview.seo.seo_title}</div>}
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
                                        disabled={!!loadingId || !previews[product.id]}
                                        onClick={() => applyPreview(product)}
                                    >
                                        Apply
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
