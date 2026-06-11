import React, { useState } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import { imageStylePresets, imageSizes, imageQualities, imageStyles } from '../../utils/variables';
import './style.css';

const ImageGenerator = () => {
    const [prompt, setPrompt]               = useState('');
    const [size, setSize]                   = useState('1024x1024');
    const [quality, setQuality]             = useState('standard');
    const [style, setStyle]                 = useState('vivid');
    const [stylePreset, setStylePreset]     = useState('');
    const [loading, setLoading]             = useState(false);
    const [enhancing, setEnhancing]         = useState(false);
    const [images, setImages]               = useState([]);
    const [saveToLibrary, setSaveToLibrary] = useState(true);
    const [message, setMessage]             = useState({ text: '', type: '' });

    const buildFinalPrompt = () => {
        return stylePreset ? `${prompt}, ${stylePreset}` : prompt;
    };

    const handleEnhancePrompt = async () => {
        if (!prompt.trim()) return;
        setEnhancing(true);
        try {
            const { callWpApi: api } = await import('../../utils/callWpApi');
            // Use the generate-description endpoint to enhance the prompt
            const response = await callWpApi('/generate-description', 'POST', {
                prompt: `Enhance this image generation prompt to make it more detailed, vivid, and specific: "${prompt}". Return only the enhanced prompt as plain text.`,
                method: 'image_prompt',
            });
            if (response.success) {
                setPrompt(response.data.description.trim().replace(/^"|"$/g, ''));
            }
        } catch {}
        setEnhancing(false);
    };

    const handleGenerate = async () => {
        if (!prompt.trim()) { setMessage({ text: 'Please enter an image prompt.', type: 'error' }); return; }
        setLoading(true);
        setMessage({ text: '', type: '' });
        const finalPrompt = buildFinalPrompt();

        try {
            const response = await callWpApi('/generate-image', 'POST', {
                prompt: finalPrompt,
                size,
                quality,
                style,
                save_to_library: saveToLibrary,
            });

            if (response.success) {
                const newImage = {
                    url: response.data.url,
                    attachment_id: response.data.attachment_id,
                    revised_prompt: response.data.revised_prompt,
                    prompt: finalPrompt,
                    timestamp: new Date().toLocaleTimeString(),
                };
                setImages(prev => [newImage, ...prev]);
                setMessage({ text: saveToLibrary ? 'Image generated and saved to Media Library!' : 'Image generated!', type: 'success' });
            } else {
                setMessage({ text: 'Error: ' + (response.data?.message || 'Failed to generate image.'), type: 'error' });
            }
        } catch (error) {
            setMessage({ text: 'Error: ' + (error.message || 'An error occurred. Please check your API settings.'), type: 'error' });
        }

        setLoading(false);
    };

    return (
        <div className="wacdmg-imggen-wrap">
            {/* Controls */}
            <div className="wacdmg-imggen-controls">
                <div className="wacdmg-imggen-header">
                    <h2>AI Image Generator</h2>
                    <p>Generate stunning images powered by DALL-E 3 or FLUX. Configure your image provider in <a href="?page=wacdmg-settings">Settings → Image Generation</a>.</p>
                </div>

                <div className="wacdmg-imggen-field">
                    <label className="wacdmg-imggen-label">Image Prompt</label>
                    <div className="wacdmg-imggen-prompt-wrap">
                        <textarea
                            className="wacdmg-imggen-textarea"
                            placeholder="Describe the image you want to generate... e.g., 'A modern e-commerce product photo of wireless headphones on a white background'"
                            value={prompt}
                            onChange={e => setPrompt(e.target.value)}
                            rows={3}
                        />
                        <button
                            type="button"
                            className="wacdmg-imggen-enhance-btn"
                            onClick={handleEnhancePrompt}
                            disabled={enhancing || !prompt.trim()}
                            title="Enhance prompt with AI"
                        >
                            {enhancing ? '⏳' : '✨ Enhance Prompt'}
                        </button>
                    </div>
                </div>

                <div className="wacdmg-imggen-options-grid">
                    <div className="wacdmg-imggen-field">
                        <label className="wacdmg-imggen-label">Style Preset</label>
                        <select value={stylePreset} onChange={e => setStylePreset(e.target.value)} className="wacdmg-imggen-select">
                            {imageStylePresets.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
                        </select>
                    </div>
                    <div className="wacdmg-imggen-field">
                        <label className="wacdmg-imggen-label">Size</label>
                        <select value={size} onChange={e => setSize(e.target.value)} className="wacdmg-imggen-select">
                            {imageSizes.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
                        </select>
                    </div>
                    <div className="wacdmg-imggen-field">
                        <label className="wacdmg-imggen-label">Quality</label>
                        <select value={quality} onChange={e => setQuality(e.target.value)} className="wacdmg-imggen-select">
                            {imageQualities.map(q => <option key={q.value} value={q.value}>{q.label}</option>)}
                        </select>
                    </div>
                    <div className="wacdmg-imggen-field">
                        <label className="wacdmg-imggen-label">Style</label>
                        <select value={style} onChange={e => setStyle(e.target.value)} className="wacdmg-imggen-select">
                            {imageStyles.map(s => <option key={s.value} value={s.value}>{s.label}</option>)}
                        </select>
                    </div>
                </div>

                <div className="wacdmg-imggen-actions">
                    <label className="wacdmg-imggen-checkbox-label">
                        <input type="checkbox" checked={saveToLibrary} onChange={e => setSaveToLibrary(e.target.checked)} />
                        Save to WordPress Media Library
                    </label>
                    <button
                        type="button"
                        className="wacdmg-imggen-generate-btn"
                        onClick={handleGenerate}
                        disabled={loading || !prompt.trim()}
                    >
                        {loading ? (
                            <><span className="wacdmg-imggen-spinner"></span> Generating...</>
                        ) : '✨ Generate Image'}
                    </button>
                </div>

                {message.text && (
                    <div className={`wacdmg-imggen-message ${message.type}`}>{message.text}</div>
                )}
            </div>

            {/* Generated Images Grid */}
            {images.length > 0 && (
                <div className="wacdmg-imggen-gallery">
                    <h3>Generated Images</h3>
                    <div className="wacdmg-imggen-grid">
                        {images.map((img, idx) => (
                            <div key={idx} className="wacdmg-imggen-card">
                                <div className="wacdmg-imggen-img-wrap">
                                    <img src={img.url} alt={img.prompt} loading="lazy" />
                                    <div className="wacdmg-imggen-overlay">
                                        <a href={img.url} target="_blank" rel="noopener noreferrer" className="wacdmg-imggen-overlay-btn">
                                            🔍 View Full
                                        </a>
                                        {img.attachment_id && (
                                            <a
                                                href={`/wp-admin/upload.php?item=${img.attachment_id}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="wacdmg-imggen-overlay-btn"
                                            >
                                                📁 Media Library
                                            </a>
                                        )}
                                    </div>
                                </div>
                                <div className="wacdmg-imggen-card-meta">
                                    <p className="wacdmg-imggen-card-prompt" title={img.prompt}>{img.prompt.substring(0, 80)}...</p>
                                    <p className="wacdmg-imggen-card-time">{img.timestamp}</p>
                                    {img.attachment_id && (
                                        <span className="wacdmg-imggen-saved-badge">✅ Saved to Library</span>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {images.length === 0 && !loading && (
                <div className="wacdmg-imggen-empty">
                    <div className="wacdmg-imggen-empty-icon">🎨</div>
                    <p>Your generated images will appear here. Try generating your first image above!</p>
                </div>
            )}
        </div>
    );
};

export default ImageGenerator;
