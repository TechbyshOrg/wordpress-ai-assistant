/**
 * AI inspector for core text blocks other than paragraph.
 * Paragraph keeps its dedicated extension; this file must not register extra block attributes.
 */
import { addFilter } from '@wordpress/hooks';
import { Fragment, useRef, useState } from '@wordpress/element';
import { select } from '@wordpress/data';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Button, TextareaControl, SelectControl, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { callWpApi } from '../utils/callWpApi';
import { createGenerationSession, isAbortError } from '../utils/generationRequest';
import PostPromptGenerator from '../utils/PromptGenerator';

const BLOCK_ATTR = {
    'core/heading': 'content',
    'core/list': 'values',
    'core/quote': 'value',
    'core/pullquote': 'value',
    'core/button': 'text',
    'core/details': 'summary',
    'core/verse': 'content',
    'core/preformatted': 'content',
};

const TONE_OPTIONS = [
    { label: 'Persuasive', value: 'persuasive' },
    { label: 'Professional', value: 'professional' },
    { label: 'Friendly & Casual', value: 'friendly' },
    { label: 'Informative', value: 'informative' },
];

const LANGUAGE_OPTIONS = [
    { label: 'English', value: 'English' },
    { label: 'Spanish', value: 'Spanish' },
    { label: 'French', value: 'French' },
    { label: 'German', value: 'German' },
];

function addTextBlockInspector(BlockEdit) {
    return function TextBlockInspector(props) {
        const { attributes, setAttributes, name } = props;
        const attrKey = BLOCK_ATTR[name];
        const [generating, setGenerating] = useState(false);
        const [notice, setNotice] = useState({ text: '', status: '' });
        const [generationType, setGenerationType] = useState('');
        const [customPrompt, setCustomPrompt] = useState('');
        const [tone, setTone] = useState('informative');
        const [language, setLanguage] = useState('English');
        const genSession = useRef(createGenerationSession()).current;

        if (!attrKey) {
            return <BlockEdit {...props} />;
        }
        if (name === 'core/quote' && typeof attributes.value === 'undefined') {
            return <BlockEdit {...props} />;
        }

        const current = attributes[attrKey] || '';

        const handleGenerate = async () => {
            if (!generationType) {
                return;
            }
            const postTitle = select('core/editor')?.getEditedPostAttribute?.('title') || '';
            const generator = new PostPromptGenerator({ tone, language });
            let prompt = '';
            if (generationType === 'generate') {
                prompt = generator.fieldGenerate(name.replace('core/', ''), postTitle);
            } else if (generationType === 'improve') {
                if (!String(current).trim()) {
                    setNotice({ text: __('Add content first.', 'wacdmg-ai-content-assistant'), status: 'error' });
                    return;
                }
                prompt = generator.fieldImprove(name.replace('core/', ''), current, postTitle);
            } else if (generationType === 'translate') {
                if (!String(current).trim()) {
                    setNotice({ text: __('Add content first.', 'wacdmg-ai-content-assistant'), status: 'error' });
                    return;
                }
                prompt = generator.translateContent(current, name.replace('core/', ''));
            } else if (generationType === 'custom') {
                if (!customPrompt.trim()) {
                    return;
                }
                prompt = customPrompt;
            } else {
                return;
            }

            setGenerating(true);
            setNotice({ text: '', status: '' });
            const signal = genSession.start();
            try {
                const response = await callWpApi('/generate-paragraph-content', 'POST', {
                    prompt,
                    tone,
                    language,
                }, { signal });
                if (response.success) {
                    const value = response.data.description || response.data.content || '';
                    setAttributes({ [attrKey]: value });
                    setNotice({ text: __('Content generated.', 'wacdmg-ai-content-assistant'), status: 'success' });
                } else {
                    setNotice({
                        text: response.data?.message || __('Error generating content.', 'wacdmg-ai-content-assistant'),
                        status: 'error',
                    });
                }
            } catch (error) {
                if (!isAbortError(error)) {
                    setNotice({ text: error.message || __('Network error.', 'wacdmg-ai-content-assistant'), status: 'error' });
                }
            } finally {
                if (genSession.settle(signal)) {
                    setGenerating(false);
                }
            }
        };

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__('AI Content Assistant', 'wacdmg-ai-content-assistant')} initialOpen={false}>
                        <SelectControl
                            label={__('Action', 'wacdmg-ai-content-assistant')}
                            value={generationType}
                            options={[
                                { label: __('Select action...', 'wacdmg-ai-content-assistant'), value: '' },
                                { label: __('Generate', 'wacdmg-ai-content-assistant'), value: 'generate' },
                                { label: __('Improve', 'wacdmg-ai-content-assistant'), value: 'improve' },
                                { label: __('Translate', 'wacdmg-ai-content-assistant'), value: 'translate' },
                                { label: __('Custom prompt', 'wacdmg-ai-content-assistant'), value: 'custom' },
                            ]}
                            onChange={setGenerationType}
                        />
                        {generationType === 'custom' && (
                            <TextareaControl
                                label={__('Custom Prompt', 'wacdmg-ai-content-assistant')}
                                value={customPrompt}
                                onChange={setCustomPrompt}
                                rows={3}
                            />
                        )}
                        <SelectControl
                            label={__('Tone', 'wacdmg-ai-content-assistant')}
                            value={tone}
                            options={TONE_OPTIONS}
                            onChange={setTone}
                        />
                        <SelectControl
                            label={__('Language', 'wacdmg-ai-content-assistant')}
                            value={language}
                            options={LANGUAGE_OPTIONS}
                            onChange={setLanguage}
                        />
                        <Button
                            variant="primary"
                            onClick={handleGenerate}
                            isBusy={generating}
                            disabled={generating || !generationType || (generationType === 'custom' && !customPrompt)}
                        >
                            {generating
                                ? __('Generating...', 'wacdmg-ai-content-assistant')
                                : __('Generate Content', 'wacdmg-ai-content-assistant')}
                        </Button>
                        {generating && (
                            <Button
                                variant="secondary"
                                isSmall
                                onClick={() => {
                                    genSession.cancel();
                                    setGenerating(false);
                                }}
                            >
                                {__('Cancel', 'wacdmg-ai-content-assistant')}
                            </Button>
                        )}
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

addFilter(
    'editor.BlockEdit',
    'wacdmg-ai-content-assistant/text-block-inspector',
    addTextBlockInspector
);

export { addTextBlockInspector };
