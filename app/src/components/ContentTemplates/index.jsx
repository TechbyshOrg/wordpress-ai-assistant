import React, { useState, useEffect } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import { templateTypes } from '../../utils/variables';
import './style.css';

const ContentTemplates = () => {
    const [templates, setTemplates]   = useState([]);
    const [loading, setLoading]       = useState(true);
    const [showForm, setShowForm]     = useState(false);
    const [editId, setEditId]         = useState(null);
    const [message, setMessage]       = useState({ text: '', type: '' });
    const [filter, setFilter]         = useState('all');

    const [form, setForm] = useState({ name: '', prompt: '', type: 'general' });

    useEffect(() => { fetchTemplates(); }, []);

    const fetchTemplates = async () => {
        setLoading(true);
        try {
            const res = await callWpApi('/get-templates', 'GET');
            if (res.success) setTemplates(res.data);
        } catch {}
        setLoading(false);
    };

    const handleSave = async () => {
        if (!form.name.trim() || !form.prompt.trim()) {
            setMessage({ text: 'Name and prompt are required.', type: 'error' });
            return;
        }
        try {
            const res = await callWpApi('/save-template', 'POST', { template: form });
            if (res.success) {
                setMessage({ text: 'Template saved!', type: 'success' });
                setShowForm(false);
                setForm({ name: '', prompt: '', type: 'general' });
                fetchTemplates();
            }
        } catch (error) {
            setMessage({ text: error.message || 'Error saving template.', type: 'error' });
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Delete this template?')) return;
        try {
            await callWpApi('/delete-template', 'POST', { id });
            setTemplates(prev => prev.filter(t => t.id !== id));
            setMessage({ text: 'Template deleted.', type: 'success' });
        } catch {}
    };

    const copyPrompt = (prompt) => {
        navigator.clipboard?.writeText(prompt);
        setMessage({ text: '📋 Prompt copied to clipboard!', type: 'success' });
        setTimeout(() => setMessage({ text: '', type: '' }), 2000);
    };

    const filtered = filter === 'all' ? templates : templates.filter(t => t.type === filter);

    return (
        <div className="wacdmg-tpl-wrap">
            <div className="wacdmg-tpl-header">
                <div>
                    <h2>📝 Content Templates</h2>
                    <p>Save and reuse custom prompt templates for different content types.</p>
                </div>
                <button className="wacdmg-tpl-add-btn" onClick={() => { setShowForm(true); setForm({ name: '', prompt: '', type: 'general' }); }}>
                    + New Template
                </button>
            </div>

            {message.text && (
                <div className={`wacdmg-tpl-message ${message.type}`}>{message.text}</div>
            )}

            {/* New template form */}
            {showForm && (
                <div className="wacdmg-tpl-form-card">
                    <h3>New Template</h3>
                    <div className="wacdmg-tpl-field">
                        <label>Template Name</label>
                        <input type="text" value={form.name} onChange={e => setForm(p => ({ ...p, name: e.target.value }))} placeholder="e.g., E-commerce Product" className="wacdmg-tpl-input" />
                    </div>
                    <div className="wacdmg-tpl-field">
                        <label>Type</label>
                        <select value={form.type} onChange={e => setForm(p => ({ ...p, type: e.target.value }))} className="wacdmg-tpl-select">
                            {templateTypes.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
                        </select>
                    </div>
                    <div className="wacdmg-tpl-field">
                        <label>Prompt <small style={{ color: '#94a3b8' }}>(use [PRODUCT_NAME], [TITLE], etc. as placeholders)</small></label>
                        <textarea value={form.prompt} onChange={e => setForm(p => ({ ...p, prompt: e.target.value }))} placeholder="Write a compelling product description for [PRODUCT_NAME]..." className="wacdmg-tpl-textarea" rows={5} />
                    </div>
                    <div style={{ display: 'flex', gap: '10px' }}>
                        <button className="wacdmg-tpl-save-btn" onClick={handleSave}>💾 Save Template</button>
                        <button className="wacdmg-tpl-cancel-btn" onClick={() => setShowForm(false)}>Cancel</button>
                    </div>
                </div>
            )}

            {/* Filter tabs */}
            <div className="wacdmg-tpl-filters">
                {['all', ...templateTypes.map(t => t.value)].map(f => (
                    <button key={f} className={`wacdmg-tpl-filter-btn${filter === f ? ' active' : ''}`} onClick={() => setFilter(f)}>
                        {f === 'all' ? 'All' : templateTypes.find(t => t.value === f)?.label || f}
                    </button>
                ))}
            </div>

            {/* Templates grid */}
            {loading ? (
                <div className="wacdmg-tpl-loading">Loading templates...</div>
            ) : filtered.length === 0 ? (
                <div className="wacdmg-tpl-empty">
                    <div style={{ fontSize: '40px', marginBottom: '12px' }}>📋</div>
                    <p>No templates yet. Create your first template above!</p>
                </div>
            ) : (
                <div className="wacdmg-tpl-grid">
                    {filtered.map(tpl => (
                        <div key={tpl.id} className="wacdmg-tpl-card">
                            <div className="wacdmg-tpl-card-header">
                                <span className="wacdmg-tpl-name">{tpl.name}</span>
                                <span className="wacdmg-tpl-type-badge">{tpl.type}</span>
                            </div>
                            <p className="wacdmg-tpl-prompt-preview">{tpl.prompt}</p>
                            <div className="wacdmg-tpl-card-actions">
                                <button onClick={() => copyPrompt(tpl.prompt)} className="wacdmg-tpl-action-btn wacdmg-tpl-copy-btn">📋 Copy Prompt</button>
                                {!tpl.id.startsWith('default_') && (
                                    <button onClick={() => handleDelete(tpl.id)} className="wacdmg-tpl-action-btn wacdmg-tpl-delete-btn">🗑️ Delete</button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};

export default ContentTemplates;
