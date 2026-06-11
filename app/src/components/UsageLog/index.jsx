import React, { useState, useEffect } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import './style.css';

const UsageLog = () => {
    const [usage, setUsage]   = useState(null);
    const [loading, setLoading] = useState(true);
    const [message, setMessage] = useState({ text: '', type: '' });

    const typeLabels = {
        description: '📝 Descriptions',
        short_description: '📋 Short Descriptions',
        tags: '🏷️ Tags',
        seo_meta: '🔍 SEO Meta',
        alt_text: '🖼️ Alt Text',
        image: '🎨 Images',
        chat: '💬 Chat',
        improve: '✨ Improvements',
    };

    useEffect(() => { fetchUsage(); }, []);

    const fetchUsage = async () => {
        try {
            const res = await callWpApi('/get-usage', 'GET');
            if (res.success) setUsage(res.data);
        } catch {}
        setLoading(false);
    };

    const handleReset = async () => {
        if (!window.confirm('Reset all usage statistics? This cannot be undone.')) return;
        try {
            await callWpApi('/reset-usage', 'POST');
            setUsage({});
            setMessage({ text: '✅ Usage statistics reset.', type: 'success' });
        } catch {
            setMessage({ text: 'Error resetting usage.', type: 'error' });
        }
    };

    const totalToday = () => {
        const today = new Date().toISOString().split('T')[0];
        if (!usage?.daily?.[today]) return 0;
        return Object.values(usage.daily[today]).reduce((a, b) => a + b, 0);
    };

    return (
        <div className="wacdmg-usage-wrap">
            <div className="wacdmg-usage-page-header">
                <h2>📊 AI Usage Log</h2>
                <p>Track your AI generation activity. Stats are stored locally — no data leaves your server.</p>
            </div>

            {message.text && <div className={`wacdmg-usage-msg ${message.type}`}>{message.text}</div>}

            {loading ? (
                <div className="wacdmg-usage-loading">Loading usage data...</div>
            ) : !usage || Object.keys(usage).length === 0 ? (
                <div className="wacdmg-usage-empty">
                    <p>No usage data yet. Start generating content to see your stats here.</p>
                </div>
            ) : (
                <>
                    {/* Summary cards */}
                    <div className="wacdmg-usage-summary">
                        <div className="wacdmg-usage-card wacdmg-usage-card-total">
                            <span className="wacdmg-usage-card-num">{usage.total || 0}</span>
                            <span className="wacdmg-usage-card-lbl">Total Generations</span>
                        </div>
                        <div className="wacdmg-usage-card">
                            <span className="wacdmg-usage-card-num">{totalToday()}</span>
                            <span className="wacdmg-usage-card-lbl">Today</span>
                        </div>
                        {usage.monthly && (
                            <div className="wacdmg-usage-card">
                                <span className="wacdmg-usage-card-num">
                                    {usage.monthly[new Date().toISOString().substring(0, 7)] || 0}
                                </span>
                                <span className="wacdmg-usage-card-lbl">This Month</span>
                            </div>
                        )}
                    </div>

                    {/* By type */}
                    {usage.by_type && (
                        <div className="wacdmg-usage-section">
                            <h3>By Generation Type</h3>
                            <div className="wacdmg-usage-type-grid">
                                {Object.entries(usage.by_type).map(([type, count]) => (
                                    <div key={type} className="wacdmg-usage-type-item">
                                        <span className="wacdmg-usage-type-label">{typeLabels[type] || type}</span>
                                        <div className="wacdmg-usage-bar-wrap">
                                            <div
                                                className="wacdmg-usage-bar"
                                                style={{ width: `${Math.min(100, (count / (usage.total || 1)) * 100)}%` }}
                                            />
                                        </div>
                                        <span className="wacdmg-usage-type-count">{count}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Daily activity */}
                    {usage.daily && Object.keys(usage.daily).length > 0 && (
                        <div className="wacdmg-usage-section">
                            <h3>Recent Daily Activity</h3>
                            <table className="wacdmg-usage-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Total</th>
                                        <th>By Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {Object.entries(usage.daily).sort((a, b) => b[0].localeCompare(a[0])).slice(0, 14).map(([date, types]) => (
                                        <tr key={date}>
                                            <td>{date}</td>
                                            <td><strong>{Object.values(types).reduce((a, b) => a + b, 0)}</strong></td>
                                            <td className="wacdmg-usage-type-list">
                                                {Object.entries(types).map(([t, c]) => (
                                                    <span key={t} className="wacdmg-usage-type-pill">{typeLabels[t] || t}: {c}</span>
                                                ))}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </>
            )}

            <button className="wacdmg-usage-reset-btn" onClick={handleReset}>
                🗑️ Reset All Statistics
            </button>
        </div>
    );
};

export default UsageLog;
