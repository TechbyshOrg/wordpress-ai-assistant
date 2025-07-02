import React, { useEffect, useState } from 'react';
import { callWpApi } from '../../utils/callWpApi';
import './style.css';

const DescriptionGenerator = () => {
    const [addPrompt, setAddPrompt] = useState(false);
    const [generatedDescription, setGeneratedDescription] = useState('');
    const [prompt, setPrompt] = useState('');
    const [generationMethod, setGenerationMethod] = useState(null);
    const [yourPrompt, setYourPrompt] = useState('');
    const [insertButtonText, setInsertButtonText] = useState('Insert Into Description');

    const clearGeneratedDescription = () => {
        setGeneratedDescription('');
        setGenerationMethod(null);
    };

    const generateNameDescription = () => {
        setGenerationMethod('name');
        const product_name = document.querySelector('input[name="post_title"]').value;
        const method = 'name';
        if (!product_name || product_name.trim() === '') {
            alert('Please enter a product name.');
            return;
        }
        const prompt = `Generate a WooCommerce-ready product description for the product: "${product_name}". Make it SEO-friendly, persuasive, and suitable for online shoppers. Avoid introductions or explanations—just return the product description only. Use HTML formatting.`;
        submitPrompt(method, prompt);
    };

    const generatePromptDescription = () => {
        if (!yourPrompt || yourPrompt.trim() === '') {
            alert('Please enter a prompt.');
            return;
        }
        let prompt = yourPrompt;
        let method = generationMethod || 'prompt';
        if (!prompt || prompt.trim() === '') {
            alert('Please enter a prompt.');
            return;
        }

        if (method === 'improve-prompt') {
            const currentDescription = document.getElementById('content').value || '';
            const product_name = document.querySelector('input[name="post_title"]').value;
            if (!currentDescription || currentDescription.trim() === '') {
                alert('Please enter a description to improve.');
                return;
            }
            prompt = `Improve the following product description: "${currentDescription}" to a WooCommerce-ready product description for the product "${product_name}"
             using the prompt: "${yourPrompt}". Make it SEO-friendly, persuasive, and suitable for online shoppers. Avoid introductions or explanations—just return the product description only. Use HTML formatting.`;
        }

        submitPrompt(method, prompt);
    };

    const improveCurrentDescription = () => {
        setGenerationMethod('improve');
        const currentDescription = document.getElementById('content').value || '';
        const product_name = document.querySelector('input[name="post_title"]').value;
        if (!product_name || product_name.trim() === '') {
            alert('Please enter a product name.');
            return;
        }
        if (currentDescription.trim() === '') {
            alert('Please enter a description to improve.');
            return;
        }
    
        const prompt = `Improve the following product description: "${currentDescription}" to a WooCommerce-ready product description for the product "${product_name}". Make it SEO-friendly, persuasive, and suitable for online shoppers. Avoid introductions or explanations—just return the product description only. Use HTML formatting.`;
        submitPrompt('improve', prompt);
    };

    const submitPrompt = (method, prompt) => {
        if (typeof prompt !== 'string' || prompt.trim() === '') {
            alert('Please enter a prompt.');
            return;
        }

        callWpApi('/generate-description', 'POST', { prompt, method })
            .then((response) => {
                if (response.success) {
                    setGeneratedDescription(response.data.description);
                } else {
                    alert('Failed to generate description: ' + response.data.message);
                }
            })
            .catch((error) => {
                alert('An error occurred while generating the description.');
            });

        setGeneratedDescription(`Generated description based on prompt: "${prompt}"`);
        setPrompt('');
        setAddPrompt(false);
    };

    const insertDescription = () => {
        setInsertButtonText("Inserting...")
        insertToProductDescription(generatedDescription);
    };

    function insertToProductDescription(content) {
        if (typeof tinymce !== 'undefined') {
            const editor = tinymce.get('content');
            if (editor && !editor.isHidden()) {
                editor.setContent(content);
                setInsertButtonText("Inserted");
                setTimeout(() => setInsertButtonText("Insert Into Description"), 1000);
            } else {
                const textarea = document.getElementById('content');
                if (textarea) {
                    textarea.value = content;
                    setInsertButtonText("Inserted");
                    setTimeout(() => setInsertButtonText("Insert Into Description"), 2000);
                }
            }
        }
    }

    useEffect(() => {
        setGeneratedDescription(null);
    }, []);

    return (
        <div className="wacdmg-generator-container">
            {generatedDescription && (
                <div className="wacdmg-generator-prompt-container"></div>
            )}
            
            <div className="wacdmg-generator-prompt">
                
                {addPrompt ? (
                    <div className="wacdmg-prompt-input-wrapper">
                        <div className='wacdmg-prompt-input'>
                            <textarea
                                placeholder="Enter your prompt here..."
                                className="wacdmg-prompt-textarea"
                                value={yourPrompt}
                                onChange={(e) => setYourPrompt(e.target.value)}
                                rows={4}
                            />
                        </div>
                        <div className='wacdmg-prompt-input-actions'>
                            <button
                                type="button"
                                className="wacdmg-submit-prompt"
                                onClick={generatePromptDescription}
                            >
                                Submit
                            </button>
                            <button
                                type="button"
                                className="wacdmg-cancel-prompt"
                                onClick={() => setAddPrompt(false)}
                            >
                                Cancel
                            </button>
                        </div>
                        
                        
                    </div>
                ) : !generatedDescription && (
                    <div>
                        <button
                            type="button"
                            className="wacdmg-generate-button"
                            onClick={generateNameDescription}
                        >
                            Generate Description From Title
                        </button>
                        {generationMethod !== 'name' && (
                            <button
                                type="button"
                                className="wacdmg-add-prompt"
                                onClick={() => {
                                    setAddPrompt(true);
                                    setGenerationMethod('prompt');
                                }}
                            >
                                Add Your Own Prompt
                            </button>
                        )}
                        <button
                            type="button"
                            className="wacdmg-improve-button"
                            onClick={improveCurrentDescription}
                        >
                            Improve Current Description
                        </button>

                        <button
                            type="button"
                            className="wacdmg-improve-prompt-button"
                            onClick={() => {
                                    setGenerationMethod('improve-prompt');
                                    setAddPrompt(true);
                                }}
                        >
                            Improve Current Description with Your Own Prompt
                        </button>
                        
                    </div>
                )}
            </div>
            {generatedDescription && (
                <div className="wacdmg-description-output">
                    <button
                        type="button"
                        className="wacdmg-clear-button"
                        onClick={clearGeneratedDescription}
                    >
                        Close
                    </button>
                    <button
                        type="button"
                        className="wacdmg-insert-button"
                        onClick={insertDescription}
                    >
                        {insertButtonText}
                    </button>
                    <p
                        className="wacdmg-description-text"
                        dangerouslySetInnerHTML={{ __html: generatedDescription }}
                    ></p>
                </div>
            )}
        </div>
        
    );
};

export default DescriptionGenerator;
