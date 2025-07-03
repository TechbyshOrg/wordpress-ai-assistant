import { addFilter } from '@wordpress/hooks';
import { Fragment, useRef } from '@wordpress/element';
import { select } from '@wordpress/data';
import { InspectorControls } from '@wordpress/block-editor';
import { 
    PanelBody, 
    Button, 
    TextareaControl, 
    SelectControl,
    ToggleControl,
    TextControl
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { callWpApi } from '../utils/callWpApi';
import PostPromptGenerator from '../utils/PromptGenerator';

// Add custom attributes to paragraph block
function addCustomAttributes(settings, name) {
    if (name !== 'core/paragraph') {
        return settings;
    }

    return {
        ...settings,
        attributes: {
            ...settings.attributes,
            generatedContent: {
                type: 'string',
                default: ''
            },
            customPrompt: {
                type: 'string',
                default: ''
            },
            generationType: {
                type: 'string',
                default: ''
            },
            enableWordCount: {
                type: 'boolean',
                default: false
            },
            wordCount: {
                type: 'number',
                default: 100
            }
        }
    };
}

// Add custom controls to paragraph block inspector
function addCustomInspectorControls(BlockEdit) {
    return function(props) {
        const { attributes, setAttributes, name } = props;
        const previousContentRef = useRef(attributes.content || '');

        if (name !== 'core/paragraph') {
            return <BlockEdit {...props} />;
        }

        const { 
            generatedContent, 
            customPrompt, 
            generationType,
            enableWordCount,
            wordCount,
            content
        } = attributes;

        // Store previous content before generating new one
        const handleGenerateContent = async () => {
            // Save current content as previous before generating new
            previousContentRef.current = content || '';

            if (!generationType) {
                return;
            }

            let prompt = '';
            let finalPrompt = '';
            const promptGenerator = new PostPromptGenerator();
            const postTitle = select('core/editor').getEditedPostAttribute('title');

            switch (generationType) {
                case 'from_name':
                    prompt = promptGenerator.descriptionPostTitle(postTitle || '');
                    finalPrompt = enableWordCount
                        ? `${prompt} Please ensure the response is no more than ${wordCount} words.`
                        : prompt;
                    break;
                case 'custom_prompt':
                    if (!customPrompt) return;
                    prompt = customPrompt;
                    finalPrompt = enableWordCount
                        ? `${prompt} Please ensure the response is no more than ${wordCount} words.`
                        : prompt;
                    break;
                case 'improve_data':
                    prompt = promptGenerator.improveDescription(
                        content || '',
                        postTitle || ''
                    );
                    finalPrompt = enableWordCount
                        ? `${prompt} Please ensure the response is no more than ${wordCount} words.`
                        : prompt;
                    break;
                case 'improve_with_prompt':
                    if (!customPrompt) return;
                    prompt = promptGenerator.improveDescriptionCustom(
                        content || '',
                        postTitle || '',
                        customPrompt
                    );
                    finalPrompt = enableWordCount
                        ? `${prompt} Please ensure the response is no more than ${wordCount} words.`
                        : prompt;
                    break;
                default:
                    return;
            }

            try {
                setAttributes({ generatedContent: 'Generating content...' });
                callWpApi('/generate-paragraph-content', 'POST', {
                    prompt: finalPrompt,
                    method: generationType,
                    word_count: enableWordCount ? wordCount : '',
                    nonce: window.paragraphBlockAjax?.nonce || ''
                })
                .then((response) => {
                    if (response.success) {
                        setAttributes({
                            generatedContent: response.data.content,
                            content: response.data.description
                        });
                    } else {
                        setAttributes({ 
                            generatedContent: 'Error generating content. Please try again.',
                            content: 'Error generating content. Please try again.'
                        });
                        alert('Failed to generate content: ' + (response.data?.message || 'Unknown error'));
                    }
                })
                .catch(() => {
                    setAttributes({ 
                        generatedContent: 'Network error. Please check your connection.',
                        content: 'Network error. Please check your connection.'
                    });
                    alert('An error occurred while generating the content.');
                });
            } catch (error) {
                setAttributes({ 
                    generatedContent: 'Network error. Please check your connection.',
                    content: 'Network error. Please check your connection.'
                });
            }
        };

        // Handler to reuse previous content
        const handleReusePrevious = () => {
            if (!window.confirm(__('Are you sure you want to reuse the previous content? This will replace the current content with the previous one.', 'wacdmg-ai-content-assistant'))) {
                return;
            }
            setAttributes({ content: previousContentRef.current });
        };

        // Handler to reset generated content to previous
        const handleResetGeneratedToPrevious = () => {
            setAttributes({ 
                generatedContent: previousContentRef.current,
                content: previousContentRef.current
            });
        };

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody
                        title={__('AI Content Generator', 'wacdmg-ai-content-assistant')}
                        initialOpen={true}
                        icon="edit"
                    >
                        <SelectControl
                            label={__('Generation Type', 'wacdmg-ai-content-assistant')}
                            value={generationType}
                            help={__('Choose how you want to generate content', 'wacdmg-ai-content-assistant')}
                            options={[
                                { label: 'Select generation type...', value: '' },
                                { label: 'Generate from block name', value: 'from_name' },
                                { label: 'Generate from custom prompt', value: 'custom_prompt' },
                                { label: 'Improve existing data', value: 'improve_data' },
                                { label: 'Improve with custom prompt', value: 'improve_with_prompt' }
                            ]}
                            onChange={(value) => setAttributes({ generationType: value })}
                        />

                        {(generationType === 'custom_prompt' || generationType === 'improve_with_prompt') && (
                            <TextareaControl
                                label={__('Custom Prompt', 'wacdmg-ai-content-assistant')}
                                help={__('Enter specific instructions for content generation', 'wacdmg-ai-content-assistant')}
                                placeholder={__('e.g., "Write a professional introduction about our company"', 'wacdmg-ai-content-assistant')}
                                value={customPrompt}
                                rows={4}
                                onChange={(value) => setAttributes({ customPrompt: value })}
                            />
                        )}

                        <ToggleControl
                            label={__('Enable Word Count', 'wacdmg-ai-content-assistant')}
                            help={__('Set a specific word count for the generated content', 'wacdmg-ai-content-assistant')}
                            checked={enableWordCount}
                            onChange={(value) => setAttributes({ enableWordCount: value })}
                        />

                        {enableWordCount && (
                            <TextControl
                                label={__('Word Count', 'wacdmg-ai-content-assistant')}
                                type="number"
                                value={wordCount}
                                min={50}
                                max={1000}
                                onChange={(value) => setAttributes({ wordCount: parseInt(value) || 100 })}
                            />
                        )}

                        <div style={{ marginTop: '15px' }}>
                            <Button
                                isPrimary
                                onClick={handleGenerateContent}
                                disabled={!generationType || 
                                    ((generationType === 'custom_prompt' || generationType === 'improve_with_prompt') && !customPrompt)}
                            >
                                {__('Generate Content', 'wacdmg-ai-content-assistant')}
                            </Button>
                        </div>

                        {generatedContent && (
                            <div style={{ 
                                marginTop: '15px', 
                                padding: '10px', 
                                backgroundColor: '#f0f0f0', 
                                borderRadius: '4px',
                                fontSize: '12px'
                            }}>
                                <strong>{__('Generated Content Preview:', 'wacdmg-ai-content-assistant')}</strong>
                                <p style={{ margin: '5px 0 0 0', color: '#666' }}>
                                    {generatedContent.substring(0, 150)}...
                                </p>
                                <div style={{ marginTop: '10px' }}>
                                    <Button
                                        isSecondary
                                        isSmall
                                        onClick={handleResetGeneratedToPrevious}
                                        style={{ marginRight: '10px' }}
                                    >
                                        {__('Reset Generated to Previous', 'wacdmg-ai-content-assistant')}
                                    </Button>
                                    <Button
                                        isTertiary
                                        isSmall
                                        onClick={handleReusePrevious}
                                    >
                                        {__('Reuse Previous Content', 'wacdmg-ai-content-assistant')}
                                    </Button>
                                </div>
                            </div>
                        )}

                        <div style={{ marginTop: '15px' }}>
                            <Button
                                isTertiary
                                isSmall
                                onClick={handleReusePrevious}
                                style={{ marginTop: '10px' }}
                            >
                                {__('Reuse Previous Content', 'wacdmg-ai-content-assistant')}
                            </Button>
                            <Button
                                isDestructive
                                isSmall
                                onClick={() => {
                                    setAttributes({ 
                                        content: '',
                                        generatedContent: '',
                                        customPrompt: '',
                                        generationType: '',
                                        enableWordCount: false,
                                        wordCount: 100
                                    });
                                    previousContentRef.current = '';
                                }}
                            >
                                {__('Reset Settings', 'wacdmg-ai-content-assistant')}
                            </Button>
                        </div>
                    </PanelBody>
                </InspectorControls>
                
                <BlockEdit {...props} />
            </Fragment>
        );
    };
}

// Register the filters
addFilter(
    'blocks.registerBlockType',
    'wacdmg-ai-content-assistant/paragraph-add-attributes',
    addCustomAttributes
);

addFilter(
    'editor.BlockEdit',
    'wacdmg-ai-content-assistant/paragraph-add-inspector-controls',
    addCustomInspectorControls
);

export { addCustomAttributes, addCustomInspectorControls };