import React, { useState, useRef } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import { createGenerationSession, isAbortError } from '../../utils/generationRequest';
import { readWpEditorContent, writeWpEditorContent } from '../../utils/wpEditor';
import ProductPromptGenerator from '../../utils/PromptGenerator';

const TermAssistant = () => {
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState('');
    const genSession = useRef(createGenerationSession()).current;
    const taxonomy = window.wacdmgAdmin?.taxonomy || 'product_cat';
    const termName = (
        document.getElementById('name')
        || document.getElementById('tag-name')
        || document.querySelector('input[name="name"]')
    )?.value?.trim() || '';
    const taxonomyLabel = taxonomy === 'product_tag' ? 'product tag' : 'product category';

    const getDescription = () => {
        return readWpEditorContent('description', 'html') || readWpEditorContent('tag-description', 'html');
    };

    const insertDescription = (html) => {
        if (writeWpEditorContent('description', html) || writeWpEditorContent('tag-description', html)) {
            return true;
        }
        return false;
    };

    const run = async (prompt, label) => {
        setLoading(true);
        setMessage(label);
        const signal = genSession.start();
        try {
            const response = await callWpApi('/generate-description', 'POST', { prompt }, { signal });
            if (!response.success) {
                throw new Error(response.data?.message || 'Generation failed.');
            }
            const html = response.data.description || response.data.content || '';
            if (!insertDescription(html)) {
                throw new Error('Could not find the term description field.');
            }
            setMessage('Inserted into the description field. Save the term to keep it.');
        } catch (error) {
            if (!isAbortError(error)) {
                setMessage(error.message || 'Error generating description.');
            }
        } finally {
            if (genSession.settle(signal)) {
                setLoading(false);
            }
        }
    };

    const generate = () => {
        const name = (
            document.getElementById('name')
            || document.getElementById('tag-name')
            || document.querySelector('input[name="name"]')
        )?.value?.trim();
        if (!name) {
            setMessage('Enter the category or tag name first.');
            return;
        }
        const generator = new ProductPromptGenerator({ tone: 'informative', language: 'English' });
        run(generator.termArchiveDescription(name, taxonomyLabel), 'Generating archive description...');
    };

    const improve = () => {
        const name = (
            document.getElementById('name')
            || document.getElementById('tag-name')
            || document.querySelector('input[name="name"]')
        )?.value?.trim() || termName;
        const current = getDescription();
        if (!current.trim()) {
            setMessage('Add a description first, or use Generate.');
            return;
        }
        const generator = new ProductPromptGenerator({ tone: 'informative', language: 'English' });
        run(generator.improveTermDescription(name, current, taxonomyLabel), 'Improving description...');
    };

    return (
        <div className="wacdmg-generator-container" style={{ marginTop: '16px' }}>
            <div className="wacdmg-generator-header">
                <span className="wacdmg-ai-badge">AI Archive Description</span>
            </div>
            <div className="wacdmg-action-group" style={{ padding: '12px 14px' }}>
                <div className="wacdmg-action-buttons">
                    <button type="button" className="wacdmg-btn wacdmg-btn-primary wacdmg-btn-sm" disabled={loading} onClick={generate}>
                        {loading ? 'Working...' : 'Generate Description'}
                    </button>
                    <button type="button" className="wacdmg-btn wacdmg-btn-outline wacdmg-btn-sm" disabled={loading} onClick={improve}>
                        Improve Current
                    </button>
                    {loading && (
                        <button type="button" className="wacdmg-btn wacdmg-btn-ghost wacdmg-btn-sm" onClick={() => { genSession.cancel(); setLoading(false); }}>
                            Cancel
                        </button>
                    )}
                </div>
                {message && <p className="wacdmg-seo-note" style={{ marginTop: '10px' }}>{message}</p>}
            </div>
        </div>
    );
};

export default TermAssistant;
