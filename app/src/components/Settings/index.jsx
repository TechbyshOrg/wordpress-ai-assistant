import React, { useState, useEffect } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import './style.css';
import { providerMeta, imageProviders, imageSizes, imageQualities, imageStyles } from '../../utils/variables';

const PROVIDERS = ['chatgpt', 'groq', 'gemini', 'claude', 'mistral', 'openrouter'];
const TABS = [
    { id: 'provider', label: 'AI Provider' },
    { id: 'image', label: 'Image Generation' },
    { id: 'seo', label: 'SEO Integration' },
    { id: 'usage', label: 'Usage & Limits' },
];

const Settings = () => {
    const [activeTab, setActiveTab] = useState('provider');
    const [provider, setProvider] = useState('chatgpt');
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState({ text: '', type: '' });
    const [seoPlugins, setSeoPlugins] = useState([]);
    const [showKeys, setShowKeys] = useState({});

    // API keys per provider
    const [apiKeys, setApiKeys] = useState({
        chatgpt: '', groq: '', gemini: '', claude: '', mistral: '', openrouter: '',
    });
    // Models per provider
    const [models, setModels] = useState({
        chatgpt: 'gpt-4o',
        groq: 'llama-3.3-70b-versatile',
        gemini: 'gemini-3.5-flash',
        claude: 'claude-sonnet-4-6',
        mistral: 'mistral-large-latest',
        openrouter: 'openai/gpt-4o',
    });

    const [providerModels, setProviderModels] = useState({});

    const fetchProviderModels = async (prov, key = '') => {
        try {
            const res = await callWpApi('/fetch-models', 'POST', { provider: prov, api_key: key });
            if (res.success && res.models && res.models.length > 0) {
                const formatted = res.models.map(m => ({
                    value: m.value,
                    label: m.label || m.value
                }));
                setProviderModels(prev => ({ ...prev, [prov]: formatted }));
            }
        } catch (error) {
            console.error('Failed to fetch models for ' + prov, error);
        }
    };

    // Image generation settings
    const [imageProvider, setImageProvider] = useState('dalle');
    const [togetherKey, setTogetherKey] = useState('');
    const [imageSize, setImageSize] = useState('1024x1024');
    const [imageQuality, setImageQuality] = useState('standard');
    const [imageStyle, setImageStyle] = useState('vivid');

    // SEO settings
    const [seoIntegration, setSeoIntegration] = useState('auto');
    const [autoSeoOnPublish, setAutoSeoOnPublish] = useState(false);

    // Usage limits
    const [rateLimitDay, setRateLimitDay] = useState(100);
    const [usage, setUsage] = useState(null);

    const [testingProvider, setTestingProvider] = useState(null);
    const [testResults, setTestResults] = useState({});

    const handleTestConnection = async (prov, key) => {
        setTestingProvider(prov);
        setTestResults(prev => ({ ...prev, [prov]: null }));
        try {
            const response = await callWpApi('/test-connection', 'POST', {
                provider: prov,
                api_key: key,
                model: models[prov] || '',
            });
            if (response.success) {
                setTestResults(prev => ({
                    ...prev,
                    [prov]: { success: true, message: response.data.message }
                }));
                if (response.data.models && response.data.models.length > 0) {
                    const formatted = response.data.models.map(m => ({
                        value: m.value,
                        label: m.label || m.value
                    }));
                    setProviderModels(prev => ({ ...prev, [prov]: formatted }));
                }
            } else {
                setTestResults(prev => ({
                    ...prev,
                    [prov]: { success: false, message: response.data?.message || 'Connection failed.' }
                }));
            }
        } catch (error) {
            setTestResults(prev => ({
                ...prev,
                [prov]: { success: false, message: 'Network or configuration error.' }
            }));
        }
        setTestingProvider(null);
    };

    useEffect(() => {
        // Detect SEO plugins from localized data
        if (window.wacdmgAdmin?.seoPlugins) {
            setSeoPlugins(window.wacdmgAdmin.seoPlugins);
        }
        // Load existing settings (masked)
        callWpApi('/get-settings', 'GET')
            .then(res => {
                if (res.success && res.data) {
                    const d = res.data;
                    const activeProv = d.provider || 'chatgpt';
                    if (d.provider) setProvider(activeProv);
                    if (d.image_provider) setImageProvider(d.image_provider);
                    if (d.image_size) setImageSize(d.image_size);
                    if (d.image_quality) setImageQuality(d.image_quality);
                    if (d.image_style) setImageStyle(d.image_style);
                    if (d.seo_integration) setSeoIntegration(d.seo_integration);
                    if (d.auto_seo_on_publish !== undefined) setAutoSeoOnPublish(d.auto_seo_on_publish);
                    if (d.rate_limit_day) setRateLimitDay(d.rate_limit_day);
                    if (d.model) {
                        setModels(prev => ({ ...prev, [activeProv]: d.model }));
                    }
                    const keys = {
                        chatgpt: d.chatgpt_key || d.apiKey || '',
                        groq: d.groq_key || '',
                        gemini: d.gemini_key || '',
                        claude: d.claude_key || '',
                        mistral: d.mistral_key || '',
                        openrouter: d.openrouter_key || '',
                    };
                    setApiKeys(keys);
                    if (d.together_key) setTogetherKey(d.together_key);

                    // Fetch models for active provider using loaded key
                    const activeKey = activeProv === 'chatgpt' ? (d.chatgpt_key || d.apiKey || '')
                                    : activeProv === 'groq' ? d.groq_key
                                    : activeProv === 'gemini' ? d.gemini_key
                                    : activeProv === 'claude' ? d.claude_key
                                    : activeProv === 'mistral' ? d.mistral_key
                                    : activeProv === 'openrouter' ? d.openrouter_key
                                    : '';
                    fetchProviderModels(activeProv, activeKey);
                }
                if (res.seo_plugins) setSeoPlugins(res.seo_plugins);
            })
            .catch(() => {});

        // Load usage stats
        callWpApi('/get-usage', 'GET')
            .then(res => { if (res.success) setUsage(res.data); })
            .catch(() => {});
    }, []);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setMessage({ text: '', type: '' });

        const formData = {
            provider,
            model: models[provider],
            chatgptKey: apiKeys.chatgpt,
            groqKey: apiKeys.groq,
            geminiKey: apiKeys.gemini,
            claudeKey: apiKeys.claude,
            mistralKey: apiKeys.mistral,
            openrouterKey: apiKeys.openrouter,
            imageProvider,
            togetherKey,
            imageSize,
            imageQuality,
            imageStyle,
            seoIntegration,
            autoSeoOnPublish,
            rateLimitDay,
        };

        try {
            const response = await callWpApi('/save-settings', 'POST', { formData });
            if (response.success) {
                setMessage({ text: 'Settings saved successfully!', type: 'success' });
            } else {
                setMessage({ text: 'Error: ' + (response.data?.message || 'Failed to save.'), type: 'error' });
            }
        } catch {
            setMessage({ text: '❌ An error occurred while saving settings.', type: 'error' });
        }
        setSaving(false);
    };

    const handleResetUsage = async () => {
        if (!window.confirm('Reset all usage statistics?')) return;
        try {
            await callWpApi('/reset-usage', 'POST');
            setUsage({});
            setMessage({ text: 'Usage stats reset.', type: 'success' });
        } catch {}
    };

    const toggleShowKey = (prov) => {
        setShowKeys(prev => ({ ...prev, [prov]: !prev[prov] }));
    };

    const currentMeta = providerMeta[provider];

    return (
        <div className="wacdmg-settings-wrap">
            {/* Tabs */}
            <nav className="wacdmg-tabs" role="tablist">
                {TABS.map(tab => (
                    <button
                        key={tab.id}
                        role="tab"
                        aria-selected={activeTab === tab.id}
                        className={`wacdmg-tab-btn${activeTab === tab.id ? ' active' : ''}`}
                        onClick={() => setActiveTab(tab.id)}
                        type="button"
                    >
                        {tab.label}
                    </button>
                ))}
            </nav>

            <form className="wacdmg-settings-form" onSubmit={handleSubmit}>

                {/* ============================================================
                    TAB 1: AI PROVIDER
                ============================================================ */}
                {activeTab === 'provider' && (
                    <div className="wacdmg-tab-content">
                        <div className="wacdmg-section-header">
                            <h2>Select AI Provider</h2>
                            <p>Choose your default text generation provider and enter your API key.</p>
                        </div>

                        {/* Provider Cards */}
                        <div className="wacdmg-provider-grid">
                            {PROVIDERS.map(p => {
                                const meta = providerMeta[p];
                                return (
                                    <label
                                        key={p}
                                        className={`wacdmg-provider-card${provider === p ? ' selected' : ''}`}
                                        style={{ '--provider-color': meta.color }}
                                    >
                                        <input
                                            type="radio"
                                            name="provider"
                                            value={p}
                                            checked={provider === p}
                                            onChange={() => {
                                                setProvider(p);
                                                setMessage({ text: '', type: '' });
                                                fetchProviderModels(p, apiKeys[p]);
                                            }}
                                            className="wacdmg-sr-only"
                                        />
                                        <span className="wacdmg-card-indicator"></span>
                                        <span className="wacdmg-card-name">{meta.label}</span>
                                        <span className="wacdmg-card-desc">{meta.description}</span>
                                    </label>
                                );
                            })}
                        </div>

                        {/* Provider Configuration */}
                        <div className="wacdmg-provider-config">
                            <h3 style={{ color: currentMeta.color }}>{currentMeta.label} Configuration</h3>

                            {PROVIDERS.map(p => provider === p && (
                                <div key={p} className="wacdmg-config-fields">
                                    <div className="wacdmg-field-group">
                                        <label className="wacdmg-field-label">API Key</label>
                                        <div className="wacdmg-key-input-wrap">
                                            <input
                                                type={showKeys[p] ? 'text' : 'password'}
                                                value={apiKeys[p]}
                                                onChange={e => setApiKeys(prev => ({ ...prev, [p]: e.target.value }))}
                                                className="wacdmg-input"
                                                placeholder={providerMeta[p].keyPlaceholder}
                                                autoComplete="off"
                                            />
                                            <button
                                                type="button"
                                                className="wacdmg-show-key-btn"
                                                onClick={() => toggleShowKey(p)}
                                                title={showKeys[p] ? 'Hide key' : 'Show key'}
                                            >
                                                {showKeys[p] ? 'Hide' : 'Show'}
                                            </button>
                                            <button
                                                type="button"
                                                className="wacdmg-btn wacdmg-btn-outline wacdmg-test-key-btn"
                                                onClick={() => handleTestConnection(p, apiKeys[p])}
                                                disabled={testingProvider !== null}
                                            >
                                                {testingProvider === p ? 'Testing...' : 'Test Connection'}
                                            </button>
                                        </div>
                                        {testResults[p] && (
                                            <div className={`wacdmg-test-result ${testResults[p].success ? 'success' : 'error'}`}>
                                                {testResults[p].success ? '[Success]' : '[Error]'} {testResults[p].message}
                                            </div>
                                        )}
                                        <a href={providerMeta[p].docsUrl} target="_blank" rel="noopener noreferrer" className="wacdmg-docs-link">
                                            Get your {providerMeta[p].label} API key →
                                        </a>
                                    </div>

                                    <div className="wacdmg-field-group">
                                        <label className="wacdmg-field-label">Model</label>
                                        <select
                                            value={models[p]}
                                            onChange={e => setModels(prev => ({ ...prev, [p]: e.target.value }))}
                                            className="wacdmg-select"
                                        >
                                            {(providerModels[p] || providerMeta[p].models).map(m => (
                                                <option key={m.value} value={m.value}>{m.label}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* ============================================================
                    TAB 2: IMAGE GENERATION
                ============================================================ */}
                {activeTab === 'image' && (
                    <div className="wacdmg-tab-content">
                        <div className="wacdmg-section-header">
                            <h2>Image Generation</h2>
                            <p>Configure AI image generation. DALL-E 3 uses your OpenAI key. Together.ai requires a separate key.</p>
                        </div>

                        <div className="wacdmg-notice wacdmg-notice-warning">
                            <strong>Cost notice:</strong> AI image generation incurs API charges per image. DALL-E 3 standard costs approximately $0.04/image. Monitor usage in the Usage & Limits tab.
                        </div>

                        <div className="wacdmg-field-group">
                            <label className="wacdmg-field-label">Image Provider</label>
                            <div className="wacdmg-radio-group">
                                {imageProviders.map(ip => (
                                    <label key={ip.value} className="wacdmg-radio-label">
                                        <input
                                            type="radio"
                                            name="imageProvider"
                                            value={ip.value}
                                            checked={imageProvider === ip.value}
                                            onChange={() => setImageProvider(ip.value)}
                                        />
                                        {ip.label}
                                    </label>
                                ))}
                            </div>
                        </div>

                        {imageProvider === 'together' && (
                            <div className="wacdmg-field-group">
                                <label className="wacdmg-field-label">Together.ai API Key</label>
                                <div className="wacdmg-key-input-wrap">
                                    <input
                                        type={showKeys['together'] ? 'text' : 'password'}
                                        value={togetherKey}
                                        onChange={e => setTogetherKey(e.target.value)}
                                        className="wacdmg-input"
                                        placeholder="your-together-api-key"
                                        autoComplete="off"
                                    />
                                    <button
                                        type="button"
                                        className="wacdmg-show-key-btn"
                                        onClick={() => toggleShowKey('together')}
                                        title={showKeys['together'] ? 'Hide key' : 'Show key'}
                                    >
                                        {showKeys['together'] ? 'Hide' : 'Show'}
                                    </button>
                                    <button
                                        type="button"
                                        className="wacdmg-btn wacdmg-btn-outline wacdmg-test-key-btn"
                                        onClick={() => handleTestConnection('together', togetherKey)}
                                        disabled={testingProvider !== null}
                                    >
                                        {testingProvider === 'together' ? 'Testing...' : 'Test Connection'}
                                    </button>
                                </div>
                                {testResults['together'] && (
                                    <div className={`wacdmg-test-result ${testResults['together'].success ? 'success' : 'error'}`}>
                                        {testResults['together'].success ? '[Success]' : '[Error]'} {testResults['together'].message}
                                    </div>
                                )}
                                <a href="https://api.together.ai/settings/api-keys" target="_blank" rel="noopener noreferrer" className="wacdmg-docs-link">
                                    Get Together.ai API key →
                                </a>
                            </div>
                        )}

                        <div className="wacdmg-fields-row">
                            <div className="wacdmg-field-group">
                                <label className="wacdmg-field-label">Default Size</label>
                                <select value={imageSize} onChange={e => setImageSize(e.target.value)} className="wacdmg-select">
                                    {imageSizes.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
                                </select>
                            </div>
                            {imageProvider === 'dalle' && (
                                <>
                                    <div className="wacdmg-field-group">
                                        <label className="wacdmg-field-label">Quality</label>
                                        <select value={imageQuality} onChange={e => setImageQuality(e.target.value)} className="wacdmg-select">
                                            {imageQualities.map(q => <option key={q.value} value={q.value}>{q.label}</option>)}
                                        </select>
                                    </div>
                                    <div className="wacdmg-field-group">
                                        <label className="wacdmg-field-label">Style</label>
                                        <select value={imageStyle} onChange={e => setImageStyle(e.target.value)} className="wacdmg-select">
                                            {imageStyles.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
                                        </select>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                )}

                {/* ============================================================
                    TAB 3: SEO INTEGRATION
                ============================================================ */}
                {activeTab === 'seo' && (
                    <div className="wacdmg-tab-content">
                        <div className="wacdmg-section-header">
                            <h2>SEO Integration</h2>
                            <p>Configure how AI-generated SEO meta is written to your SEO plugin.</p>
                        </div>

                        <div className="wacdmg-seo-detected">
                            <strong>Detected SEO Plugins:</strong>
                            {seoPlugins.length === 0 ? (
                                <span className="wacdmg-badge wacdmg-badge-gray">None detected (generic meta will be used)</span>
                            ) : (
                                seoPlugins.map(p => (
                                    <span key={p} className="wacdmg-badge wacdmg-badge-green">
                                        {p === 'yoast' ? 'Yoast SEO' : p === 'rankmath' ? 'Rank Math' : 'AIOSEO'}
                                    </span>
                                ))
                            )}
                        </div>

                        <div className="wacdmg-field-group">
                            <label className="wacdmg-field-label">Write meta to</label>
                            <select value={seoIntegration} onChange={e => setSeoIntegration(e.target.value)} className="wacdmg-select">
                                <option value="auto">Auto-detect (recommended)</option>
                                <option value="yoast">Yoast SEO</option>
                                <option value="rankmath">Rank Math</option>
                                <option value="aioseo">AIOSEO</option>
                                <option value="generic">Generic post meta</option>
                            </select>
                        </div>

                        <div className="wacdmg-toggle-group">
                            <label className="wacdmg-toggle-label">
                                <input
                                    type="checkbox"
                                    checked={autoSeoOnPublish}
                                    onChange={e => setAutoSeoOnPublish(e.target.checked)}
                                    className="wacdmg-toggle-input"
                                />
                                <span className="wacdmg-toggle-track"></span>
                                Auto-generate SEO meta on post publish (if meta is empty)
                            </label>
                        </div>

                        <div className="wacdmg-notice wacdmg-notice-info">
                            AI SEO meta generation is available in the Gutenberg Document panel and on WooCommerce product edit pages.
                        </div>
                    </div>
                )}

                {/* ============================================================
                    TAB 4: USAGE & LIMITS
                ============================================================ */}
                {activeTab === 'usage' && (
                    <div className="wacdmg-tab-content">
                        <div className="wacdmg-section-header">
                            <h2>Usage & Limits</h2>
                            <p>Monitor your AI generations and set daily rate limits.</p>
                        </div>

                        <div className="wacdmg-usage-stats">
                            {usage ? (
                                <>
                                    <div className="wacdmg-stat-card">
                                        <span className="wacdmg-stat-number">{usage.total || 0}</span>
                                        <span className="wacdmg-stat-label">Total Generations</span>
                                    </div>
                                    {usage.by_type && Object.entries(usage.by_type).map(([type, count]) => (
                                        <div key={type} className="wacdmg-stat-card">
                                            <span className="wacdmg-stat-number">{count}</span>
                                            <span className="wacdmg-stat-label">{type.replace(/_/g, ' ')}</span>
                                        </div>
                                    ))}
                                </>
                            ) : (
                                <p>No usage data yet.</p>
                            )}
                        </div>

                        <div className="wacdmg-field-group" style={{ marginTop: '24px' }}>
                            <label className="wacdmg-field-label">Daily Rate Limit</label>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                                <input
                                    type="number"
                                    value={rateLimitDay}
                                    onChange={e => setRateLimitDay(parseInt(e.target.value) || 100)}
                                    className="wacdmg-input"
                                    style={{ width: '120px' }}
                                    min="1"
                                    max="10000"
                                />
                                <span style={{ color: '#666', fontSize: '13px' }}>generations per day</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            className="wacdmg-btn wacdmg-btn-danger"
                            onClick={handleResetUsage}
                            style={{ marginTop: '16px' }}
                        >
                            Reset Usage Statistics
                        </button>
                    </div>
                )}

                {/* ============================================================
                    SAVE BUTTON
                ============================================================ */}
                <div className="wacdmg-settings-footer">
                    <button className="wacdmg-btn wacdmg-btn-primary" type="submit" disabled={saving}>
                        {saving ? (
                            <><span className="wacdmg-spinner"></span> Saving...</>
                        ) : '💾 Save Settings'}
                    </button>
                    {message.text && (
                        <div className={`wacdmg-message ${message.type}`}>
                            {message.text}
                        </div>
                    )}
                </div>
            </form>
        </div>
    );
};

export default Settings;