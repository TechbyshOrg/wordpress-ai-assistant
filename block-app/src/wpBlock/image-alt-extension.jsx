/**
 * AI Alt Text — Gutenberg image block inspector.
 */
import { addFilter } from '@wordpress/hooks';
import { Fragment, useRef, useState } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Button, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { callWpApi } from '../utils/callWpApi';

function addImageAltControls(BlockEdit) {
    return function ImageAltControls(props) {
        const { attributes, setAttributes, name } = props;
        const [generating, setGenerating] = useState(false);
        const [notice, setNotice] = useState({ text: '', status: '' });
        const abortRef = useRef(null);

        if (name !== 'core/image') {
            return <BlockEdit {...props} />;
        }

        const { id, alt, url } = attributes;

        const handleGenerate = async () => {
            if (abortRef.current) {
                abortRef.current.abort();
            }
            abortRef.current = new AbortController();
            setGenerating(true);
            setNotice({ text: '', status: '' });

            const prompt = `Write concise, descriptive alt text for this image.`
                + (alt ? ` Current alt: "${alt}".` : '')
                + (url ? ` Image URL: ${url}.` : '')
                + ` Under 125 characters. Return only the alt text as plain text.`;

            try {
                const response = await callWpApi('/generate-alt-text', 'POST', {
                    prompt,
                    attachment_id: id || 0,
                }, { signal: abortRef.current.signal });

                if (response.success && response.data?.alt_text) {
                    setAttributes({ alt: response.data.alt_text });
                    setNotice({ text: __('Alt text generated.', 'wacdmg-ai-content-assistant'), status: 'success' });
                } else {
                    setNotice({
                        text: response.data?.message || __('Failed to generate alt text.', 'wacdmg-ai-content-assistant'),
                        status: 'error',
                    });
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    setNotice({ text: error.message || __('Network error.', 'wacdmg-ai-content-assistant'), status: 'error' });
                }
            }

            setGenerating(false);
        };

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__('AI Alt Text', 'wacdmg-ai-content-assistant')} initialOpen={false}>
                        <Button
                            variant="secondary"
                            onClick={handleGenerate}
                            isBusy={generating}
                            disabled={generating}
                        >
                            {generating
                                ? __('Generating...', 'wacdmg-ai-content-assistant')
                                : __('Generate Alt Text', 'wacdmg-ai-content-assistant')}
                        </Button>
                        {generating && (
                            <Button
                                variant="tertiary"
                                isSmall
                                onClick={() => {
                                    abortRef.current?.abort();
                                    setGenerating(false);
                                }}
                                style={{ marginTop: '8px' }}
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
    'wacdmg-ai-content-assistant/image-alt-inspector',
    addImageAltControls
);
