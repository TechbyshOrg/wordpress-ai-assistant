import { addFilter } from '@wordpress/hooks';
import { Fragment, useRef, useState } from '@wordpress/element';
import { select } from '@wordpress/data';
import { InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    Button,
    TextareaControl,
    SelectControl,
    ToggleControl,
    TextControl,
    Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { callWpApi } from '../utils/callWpApi';
import PostPromptGenerator from '../utils/PromptGenerator';

const TONE_OPTIONS = [
    { label: 'Persuasive', value: 'persuasive' },
    { label: 'Professional', value: 'professional' },
    { label: 'Friendly & Casual', value: 'friendly' },
    { label: 'Luxury & Premium', value: 'luxury' },
    { label: 'Minimal & Direct', value: 'minimal' },
    { label: 'Informative', value: 'informative' },
];

const LANGUAGE_OPTIONS = [
    { label: 'English', value: 'English' },
    { label: 'Spanish', value: 'Spanish' },
    { label: 'French', value: 'French' },
    { label: 'German', value: 'German' },
    { label: 'Italian', value: 'Italian' },
    { label: 'Arabic', value: 'Arabic' },
    { label: 'Hindi', value: 'Hindi' },
    { label: 'Japanese', value: 'Japanese' },
    { label: 'Chinese (Simplified)', value: 'Chinese (Simplified)' },
    { label: 'Portuguese', value: 'Portuguese' },
];

// Add custom attributes to paragraph block
function addCustomAttributes(settings, name) {
    if (name !== 'core/paragraph') {
        return settings;
    }

    return {
        ...settings,
        attributes: {
            ...settings.attributes,
            generatedContent: { type: 'string', default: '' },
            customPrompt:     { type: 'string', default: '' },
            generationType:   { type: 'string', default: '' },
            enableWordCount:  { type: 'boolean', default: false },
            wordCount:        { type: 'number', default: 150 },
            tone:             { type: 'string', default: 'persuasive' },
            language:         { type: 'string', default: 'English' },
        },
    };
}

// Add custom controls to paragraph block inspector
function addCustomInspectorControls(BlockEdit) {
    return function(props) {
        const { attributes, setAttributes, name } = props;
        const previousContentRef = useRef(attributes.content || '');
        const [generating, setGenerating] = useState(false);
        const [notice, setNotice] = useState({ text: '', status: '' });

        if (name !== 'core/paragraph') {
            return <BlockEdit {...props} />;
        }

        const {
            generatedContent,
            customPrompt,
            generationType,
            enableWordCount,
            wordCount,
            content,
            tone,
            language,
        } = attributes;

        const handleGenerateContent = async () => {
            if (!generationType) return;

            previousContentRef.current = content || '';
            setGenerating(true);
            setNotice({ text: '', status: '' });

            let prompt = '';
            const promptGenerator = new PostPromptGenerator({ tone, language });
            const postTitle = select('core/editor').getEditedPostAttribute('title') || '';

            switch (generationType) {
                case 'from_name':
                    prompt = promptGenerator.descriptionPostTitle(postTitle);
                    break;
                case 'custom_prompt':
                    if (!customPrompt) { setGenerating(false); return; }
                    prompt = customPrompt;
                    break;
                case 'improve_data':
                    prompt = promptGenerator.improveDescription(content || '', postTitle);
                    break;
                case 'improve_with_prompt':
                    if (!customPrompt) { setGenerating(false); return; }
                    prompt = promptGenerator.improveDescriptionCustom(content || '', postTitle, customPrompt);
                    break;
                case 'blog_outline':
                    prompt = promptGenerator.blogOutline(postTitle);
                    break;
                default:
                    setGenerating(false);
                    return;
            }

            const finalPrompt = enableWordCount
                ? `${prompt} Please ensure the response is no more than ${wordCount} words.`
                : prompt;

            try {
                const response = await callWpApi('/generate-paragraph-content', 'POST', {
                    prompt: finalPrompt,
                    method: generationType,
                    tone,
                    language,
                });

                if (response.success) {
                    setAttributes({
                        generatedContent: response.data.description || response.data.content || '',
                        content: response.data.description || response.data.content || '',
                    });
                    setNotice({ text: __('Content generated successfully!', 'wacdmg-ai-content-assistant'), status: 'success' });
                } else {
                    setNotice({ text: response.data?.message || __('Error generating content.', 'wacdmg-ai-content-assistant'), status: 'error' });
                }
            } catch (error) {
                setNotice({ text: error.message || __('Network error. Please check your connection.', 'wacdmg-ai-content-assistant'), status: 'error' });
            }

            setGenerating(false);
        };

        const handleReusePrevious = () => {
            if (!window.confirm(__('Replace current content with previous content?', 'wacdmg-ai-content-assistant'))) return;
            setAttributes({ content: previousContentRef.current });
        };

        return (
            <Fragment>
                <InspectorControls>
                    {/* AI Content Generator Panel */}
                    <PanelBody
                        title={__('AI Content Generator', 'wacdmg-ai-content-assistant')}
                        initialOpen={true}
                    >
                        <SelectControl
                            label={__('Generation Type', 'wacdmg-ai-content-assistant')}
                            value={generationType}
                            options={[
                                { label: 'Select type...', value: '' },
                                { label: 'Generate from post title', value: 'from_name' },
                                { label: 'Custom prompt', value: 'custom_prompt' },
                                { label: 'Blog post outline', value: 'blog_outline' },
                                { label: 'Improve existing content', value: 'improve_data' },
                                { label: 'Improve with custom prompt', value: 'improve_with_prompt' },
                            ]}
                            onChange={value => setAttributes({ generationType: value })}
                        />

                        {(generationType === 'custom_prompt' || generationType === 'improve_with_prompt') && (
                            <TextareaControl
                                label={__('Custom Prompt', 'wacdmg-ai-content-assistant')}
                                placeholder={__('Enter your instructions...', 'wacdmg-ai-content-assistant')}
                                value={customPrompt}
                                rows={3}
                                onChange={value => setAttributes({ customPrompt: value })}
                            />
                        )}

                        <SelectControl
                            label={__('Tone', 'wacdmg-ai-content-assistant')}
                            value={tone}
                            options={TONE_OPTIONS}
                            onChange={value => setAttributes({ tone: value })}
                        />

                        <SelectControl
                            label={__('Language', 'wacdmg-ai-content-assistant')}
                            value={language}
                            options={LANGUAGE_OPTIONS}
                            onChange={value => setAttributes({ language: value })}
                        />

                        <ToggleControl
                            label={__('Limit Word Count', 'wacdmg-ai-content-assistant')}
                            checked={enableWordCount}
                            onChange={value => setAttributes({ enableWordCount: value })}
                        />

                        {enableWordCount && (
                            <TextControl
                                label={__('Max Words', 'wacdmg-ai-content-assistant')}
                                type="number"
                                value={wordCount}
                                min={50}
                                max={2000}
                                onChange={value => setAttributes({ wordCount: parseInt(value) || 150 })}
                            />
                        )}

                        <div style={{ marginTop: '12px', display: 'flex', flexDirection: 'column', gap: '8px' }}>
                            <Button
                                variant="primary"
                                onClick={handleGenerateContent}
                                isBusy={generating}
                                disabled={generating || !generationType ||
                                    ((generationType === 'custom_prompt' || generationType === 'improve_with_prompt') && !customPrompt)}
                            >
                                {generating
                                    ? __('Generating...', 'wacdmg-ai-content-assistant')
                                    : __('Generate Content', 'wacdmg-ai-content-assistant')}
                            </Button>

                            <Button variant="secondary" isSmall onClick={handleReusePrevious}>
                                {__('Reuse Previous Content', 'wacdmg-ai-content-assistant')}
                            </Button>

                            <Button
                                variant="tertiary"
                                isSmall
                                isDestructive
                                onClick={() => {
                                    setAttributes({
                                        content: '',
                                        generatedContent: '',
                                        customPrompt: '',
                                        generationType: '',
                                        enableWordCount: false,
                                        wordCount: 150,
                                    });
                                    previousContentRef.current = '';
                                    setNotice({ text: '', status: '' });
                                }}
                            >
                                {__('Reset All', 'wacdmg-ai-content-assistant')}
                            </Button>
                        </div>

                        {notice.text && (
                            <div style={{ marginTop: '10px' }}>
                                <Notice status={notice.status === 'success' ? 'success' : 'error'} isDismissible={false}>
                                    {notice.text}
                                </Notice>
                            </div>
                        )}
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