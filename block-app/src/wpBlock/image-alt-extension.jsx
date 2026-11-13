/**
 * AI Alt Text — Gutenberg image block inspector.
 *
 * The server sends the image itself to a vision model. Results are placed in the
 * block for review; nothing is saved until the post is saved.
 */
import { addFilter } from '@wordpress/hooks';
import { Fragment, useRef, useState } from '@wordpress/element';
import { select } from '@wordpress/data';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Button, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { callWpApi } from '../utils/callWpApi';
import { createGenerationSession, isAbortError } from '../utils/generationRequest';

function currentPostTitle() {
    try {
        return select('core/editor')?.getEditedPostAttribute('title') || '';
    } catch (e) {
        return '';
    }
}

function addImageAltControls(BlockEdit) {
    return function ImageAltControls(props) {
        const { attributes, setAttributes, name } = props;
        const [loadingTarget, setLoadingTarget] = useState('');
        const [notice, setNotice] = useState({ text: '', status: '' });
        const genSession = useRef(createGenerationSession()).current;

        if (name !== 'core/image') {
            return <BlockEdit {...props} />;
        }

        const { id } = attributes;
        const busy = loadingTarget !== '';

        const generate = async (target) => {
            const signal = genSession.start();
            setLoadingTarget(target);
            setNotice({ text: '', status: '' });

            try {
                const response = await callWpApi('/generate-alt-text', 'POST', {
                    attachment_id: id,
                    target,
                    context: currentPostTitle(),
                }, { signal });

                const text = target === 'caption' ? response.data?.caption : response.data?.alt_text;
                if (response.success && text) {
                    setAttributes(target === 'caption' ? { caption: text } : { alt: text });
                    setNotice({
                        text: target === 'caption'
                            ? __('Caption added. Review it before saving.', 'wacdmg-ai-content-assistant')
                            : __('Alt text added. Review it before saving.', 'wacdmg-ai-content-assistant'),
                        status: 'success',
                    });
                } else {
                    setNotice({
                        text: response.data?.message || __('Generation failed.', 'wacdmg-ai-content-assistant'),
                        status: 'error',
                    });
                }
            } catch (error) {
                if (!isAbortError(error)) {
                    setNotice({ text: error.message || __('Network error.', 'wacdmg-ai-content-assistant'), status: 'error' });
                }
            } finally {
                if (genSession.settle(signal)) {
                    setLoadingTarget('');
                }
            }
        };

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={__('AI Alt Text', 'wacdmg-ai-content-assistant')} initialOpen={false}>
                        {!id && (
                            <p className="components-base-control__help">
                                {__('AI alt text needs an image from the Media Library. Images inserted from a URL cannot be read.', 'wacdmg-ai-content-assistant')}
                            </p>
                        )}
                        <Button
                            variant="secondary"
                            onClick={() => generate('alt')}
                            isBusy={loadingTarget === 'alt'}
                            disabled={!id || busy}
                        >
                            {loadingTarget === 'alt'
                                ? __('Looking at the image...', 'wacdmg-ai-content-assistant')
                                : __('Generate Alt Text', 'wacdmg-ai-content-assistant')}
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() => generate('caption')}
                            isBusy={loadingTarget === 'caption'}
                            disabled={!id || busy}
                            style={{ marginTop: '8px' }}
                        >
                            {loadingTarget === 'caption'
                                ? __('Looking at the image...', 'wacdmg-ai-content-assistant')
                                : __('Generate Caption', 'wacdmg-ai-content-assistant')}
                        </Button>
                        {busy && (
                            <Button
                                variant="tertiary"
                                isSmall
                                onClick={() => {
                                    genSession.cancel();
                                    setLoadingTarget('');
                                }}
                                style={{ marginTop: '8px' }}
                            >
                                {__('Cancel', 'wacdmg-ai-content-assistant')}
                            </Button>
                        )}
                        {notice.text && (
                            <div style={{ marginTop: '10px' }} aria-live="polite">
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
