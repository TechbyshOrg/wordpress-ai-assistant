(function ($) {
    'use strict';

    function getConfig() {
        return window.wacdmgMediaAlt || {};
    }

    function setStatus($btn, text, isError) {
        var $status = $btn.siblings('.wacdmg-alt-status');
        if (!$status.length) {
            $status = $btn.parent().find('.wacdmg-alt-status');
        }
        $status.text(text || '');
        $status.css('color', isError ? '#b32d2e' : '#1e8c3e');
    }

    function generateAlt($btn) {
        var cfg = getConfig();
        var attachmentId = parseInt($btn.attr('data-attachment-id'), 10) || 0;
        if (!attachmentId || !cfg.apiBaseUrl) {
            return;
        }

        var title = $('#attachment-details-two-column-title').val()
            || $('#attachment-details-title').val()
            || $('input[name="attachments[' + attachmentId + '][post_title]"]').val()
            || '';
        var prompt = 'Write concise, descriptive alt text for this WordPress media image.'
            + (title ? ' Image title: "' + title + '".' : '')
            + ' Under 125 characters. Return only the alt text as plain text.';

        $btn.prop('disabled', true);
        setStatus($btn, 'Generating...', false);

        fetch(cfg.apiBaseUrl + '/generate-alt-text', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': cfg.restNonce || '',
            },
            body: JSON.stringify({
                prompt: prompt,
                attachment_id: attachmentId,
            }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                var alt = result.data && result.data.data && result.data.data.alt_text;
                var err = (result.data && result.data.data && result.data.data.message)
                    || (result.data && result.data.message);
                if (!result.ok || !alt) {
                    setStatus($btn, err || 'Failed to generate alt text.', true);
                    return;
                }

                var $altField = $('#attachment-details-two-column-alt-text')
                    .add('#attachment-details-alt-text')
                    .add('textarea[name="attachments[' + attachmentId + '][image_alt]"]')
                    .add('input[name="attachments[' + attachmentId + '][image_alt]"]');

                $altField.val(alt).trigger('change');
                setStatus($btn, 'Alt text generated.', false);
            })
            .catch(function () {
                setStatus($btn, 'Network error. Check AI settings.', true);
            })
            .finally(function () {
                $btn.prop('disabled', false);
            });
    }

    $(document).on('click', '.wacdmg-gen-alt-btn', function (e) {
        e.preventDefault();
        generateAlt($(this));
    });

    function generateCaption($btn) {
        var cfg = getConfig();
        var attachmentId = parseInt($btn.attr('data-attachment-id'), 10) || 0;
        if (!attachmentId || !cfg.apiBaseUrl) {
            return;
        }

        var title = $('#attachment-details-two-column-title').val()
            || $('#attachment-details-title').val()
            || $('input[name="attachments[' + attachmentId + '][post_title]"]').val()
            || '';
        var alt = $('#attachment-details-two-column-alt-text').val()
            || $('#attachment-details-alt-text').val()
            || $('textarea[name="attachments[' + attachmentId + '][image_alt]"]').val()
            || '';
        var prompt = 'Write a concise image caption.'
            + (title ? ' Title: "' + title + '".' : '')
            + (alt ? ' Alt text: "' + alt + '".' : '')
            + ' One or two sentences. Return only the caption as plain text.';

        $btn.prop('disabled', true);
        var $status = $btn.siblings('.wacdmg-caption-status');
        if (!$status.length) {
            $status = $btn.parent().find('.wacdmg-caption-status');
        }
        $status.text('Generating...').css('color', '#1e8c3e');

        fetch(cfg.apiBaseUrl + '/generate-alt-text', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': cfg.restNonce || '',
            },
            body: JSON.stringify({
                prompt: prompt,
                attachment_id: attachmentId,
                target: 'caption',
            }),
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                var caption = result.data && result.data.data && result.data.data.caption;
                var err = (result.data && result.data.data && result.data.data.message)
                    || (result.data && result.data.message);
                if (!result.ok || !caption) {
                    $status.text(err || 'Failed to generate caption.').css('color', '#b32d2e');
                    return;
                }

                var $captionField = $('#attachment-details-two-column-caption')
                    .add('#attachment-details-caption')
                    .add('textarea[name="attachments[' + attachmentId + '][post_excerpt]"]');

                $captionField.val(caption).trigger('change');
                $status.text('Caption generated.').css('color', '#1e8c3e');
            })
            .catch(function () {
                $status.text('Network error. Check AI settings.').css('color', '#b32d2e');
            })
            .finally(function () {
                $btn.prop('disabled', false);
            });
    }

    $(document).on('click', '.wacdmg-gen-caption-btn', function (e) {
        e.preventDefault();
        generateCaption($(this));
    });
})(jQuery);
