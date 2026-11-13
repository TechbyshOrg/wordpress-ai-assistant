(function ($) {
    'use strict';

    function getConfig() {
        return window.wacdmgMediaAlt || {};
    }

    function findStatus($btn, className) {
        var $status = $btn.siblings('.' + className);
        if (!$status.length) {
            $status = $btn.parent().find('.' + className);
        }
        return $status;
    }

    function setStatus($status, text, isError) {
        $status.text(text || '');
        $status.css('color', isError ? '#b32d2e' : '#1e8c3e');
    }

    /**
     * Ask the server to look at the image and write alt text or a caption.
     * The result is placed in the field for review; the server does not save it.
     */
    function requestImageText(attachmentId, target) {
        var cfg = getConfig();
        return fetch(cfg.apiBaseUrl + '/generate-alt-text', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': cfg.restNonce || '',
            },
            body: JSON.stringify({
                attachment_id: attachmentId,
                target: target,
            }),
        }).then(function (res) {
            return res.json().catch(function () {
                return null;
            }).then(function (data) {
                var payload = (data && data.data) || {};
                var text = target === 'caption' ? payload.caption : payload.alt_text;
                if (!res.ok || !text) {
                    throw new Error(payload.message || (data && data.message) || 'Generation failed.');
                }
                return text;
            });
        });
    }

    function generate($btn, target) {
        var cfg = getConfig();
        var attachmentId = parseInt($btn.attr('data-attachment-id'), 10) || 0;
        if (!attachmentId || !cfg.apiBaseUrl) {
            return;
        }

        var isCaption = target === 'caption';
        var $status = findStatus($btn, isCaption ? 'wacdmg-caption-status' : 'wacdmg-alt-status');

        $btn.prop('disabled', true);
        setStatus($status, 'Looking at the image...', false);

        requestImageText(attachmentId, target)
            .then(function (text) {
                var $field = isCaption
                    ? $('#attachment-details-two-column-caption')
                        .add('#attachment-details-caption')
                        .add('textarea[name="attachments[' + attachmentId + '][post_excerpt]"]')
                    : $('#attachment-details-two-column-alt-text')
                        .add('#attachment-details-alt-text')
                        .add('textarea[name="attachments[' + attachmentId + '][image_alt]"]')
                        .add('input[name="attachments[' + attachmentId + '][image_alt]"]');

                if (!$field.length) {
                    setStatus($status, 'Generated, but the field was not found: ' + text, true);
                    return;
                }

                $field.val(text).trigger('change');
                setStatus($status, (isCaption ? 'Caption' : 'Alt text') + ' added. Review it and edit if needed.', false);
            })
            .catch(function (error) {
                setStatus($status, (error && error.message) || 'Network error. Check AI settings.', true);
            })
            .finally(function () {
                $btn.prop('disabled', false);
            });
    }

    $(document).on('click', '.wacdmg-gen-alt-btn', function (e) {
        e.preventDefault();
        generate($(this), 'alt');
    });

    $(document).on('click', '.wacdmg-gen-caption-btn', function (e) {
        e.preventDefault();
        generate($(this), 'caption');
    });
})(jQuery);
