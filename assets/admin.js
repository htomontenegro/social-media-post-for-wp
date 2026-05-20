(function ($) {
    'use strict';

    $(function () {
        var $box = $('.smp-meta-box');
        if (!$box.length) {
            return;
        }

        var $sourceRadios = $box.find('input[name="smp_media_source"]');
        var $typeRadios = $box.find('input[name="smp_media_type"]');
        var $library = $box.find('.smp-media-library');
        var $urlGroup = $box.find('.smp-media-url');
        var $attachmentInput = $('#smp_media_attachment_id');
        var $urlInput = $('#smp_media_url');
        var $preview = $box.find('.smp-preview');
        var $previewInner = $preview.find('.smp-preview__inner');
        var $clearBtn = $box.find('.smp-clear-media');
        var $fetchStatus = $box.find('.smp-fetch-status');
        var mediaFrame = null;

        function updateSourceVisibility() {
            var source = $sourceRadios.filter(':checked').val();
            $library.attr('data-active', source === 'library' ? '1' : '0');
            $urlGroup.attr('data-active', source === 'url' ? '1' : '0');
        }

        function setPreview(node) {
            $previewInner.empty();
            if (!node) {
                $preview.attr('hidden', true);
                return;
            }
            $previewInner.append(node);
            $preview.removeAttr('hidden');
        }

        function renderPreviewFromAttachment(attachment) {
            if (!attachment) {
                setPreview('');
                return;
            }
            var type = $typeRadios.filter(':checked').val();
            if (type === 'video' && attachment.type === 'video') {
                var videoUrl = attachment.url;
                var safe = $('<video controls></video>').attr('src', videoUrl);
                setPreview(safe);
            } else {
                var imgUrl = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;
                var img = $('<img alt="">').attr('src', imgUrl);
                setPreview(img);
            }
        }

        function renderPreviewFromUrl(url) {
            if (!url) {
                setPreview('');
                return;
            }
            var type = $typeRadios.filter(':checked').val();
            if (type === 'video') {
                setPreview($('<video controls></video>').attr('src', url));
            } else {
                setPreview($('<img alt="">').attr('src', url));
            }
        }

        function refreshPreview() {
            var source = $sourceRadios.filter(':checked').val();
            if (source === 'url') {
                renderPreviewFromUrl($urlInput.val());
            } else {
                var attachmentId = parseInt($attachmentInput.val(), 10);
                if (!attachmentId) {
                    setPreview('');
                    return;
                }
                wp.media.attachment(attachmentId).fetch().then(function () {
                    renderPreviewFromAttachment(wp.media.attachment(attachmentId).toJSON());
                });
            }
        }

        $sourceRadios.on('change', function () {
            updateSourceVisibility();
            refreshPreview();
        });

        $typeRadios.on('change', refreshPreview);

        $box.on('click', '.smp-pick-media', function (e) {
            e.preventDefault();
            var type = $typeRadios.filter(':checked').val();
            var libraryType = type === 'video' ? 'video' : 'image';
            mediaFrame = wp.media({
                title: type === 'video' ? SMP_Admin.i18n.pickVideo : SMP_Admin.i18n.pickImage,
                button: { text: SMP_Admin.i18n.useThis },
                library: { type: libraryType },
                multiple: false
            });

            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                $attachmentInput.val(attachment.id);
                $clearBtn.removeAttr('hidden');
                renderPreviewFromAttachment(attachment);
            });

            mediaFrame.open();
        });

        $box.on('click', '.smp-clear-media', function (e) {
            e.preventDefault();
            $attachmentInput.val('');
            $clearBtn.attr('hidden', true);
            setPreview('');
        });

        $urlInput.on('input', function () {
            if ($sourceRadios.filter(':checked').val() === 'url') {
                renderPreviewFromUrl($urlInput.val());
            }
        });

        $box.on('click', '.smp-fetch-post-data', function (e) {
            e.preventDefault();
            var postUrl = $('#smp_url').val();
            var $postFetchStatus = $('#smp_url').closest('.smp-field').find('.smp-fetch-status');
            $postFetchStatus.removeClass('is-error is-success');
            if (!postUrl) {
                $postFetchStatus.addClass('is-error').text(SMP_Admin.i18n.enterUrl);
                return;
            }
            var $btn = $(this);
            $btn.prop('disabled', true);
            $postFetchStatus.text(SMP_Admin.i18n.fetching);

            $.post(SMP_Admin.ajaxUrl, {
                action: 'smp_fetch_post_metadata',
                nonce: SMP_Admin.nonce,
                url: postUrl
            }).done(function (response) {
                if (response && response.success && response.data) {
                    var data = response.data;
                    if (data.author_name) {
                        $('#smp_author_name').val(data.author_name);
                    }
                    if (data.author_handle) {
                        $('#smp_author_handle').val(data.author_handle);
                    }
                    if (data.author_bio) {
                        $('#smp_author_bio').val(data.author_bio);
                    }
                    if (data.description) {
                        $('#smp_description').val(data.description);
                    }
                    if (data.platform) {
                        $('#smp_platform').val(data.platform);
                    }
                    if (data.image_url || data.video_url) {
                        var mediaUrl = data.video_url || data.image_url;
                        $urlInput.val(mediaUrl);
                        var mediaType = data.video_url ? 'video' : 'image';
                        $typeRadios.filter('[value="' + mediaType + '"]').prop('checked', true).trigger('change');
                        $sourceRadios.filter('[value="url"]').prop('checked', true).trigger('change');
                    }
                    $postFetchStatus.addClass('is-success').text(SMP_Admin.i18n.fetchOk);
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : SMP_Admin.i18n.fetchFailed;
                    $postFetchStatus.addClass('is-error').text(msg);
                }
            }).fail(function (xhr) {
                var msg = SMP_Admin.i18n.fetchFailed;
                if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                $postFetchStatus.addClass('is-error').text(msg);
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        $box.on('click', '.smp-fetch-oembed', function (e) {
            e.preventDefault();
            var postUrl = $('#smp_url').val();
            $fetchStatus.removeClass('is-error is-success');
            if (!postUrl) {
                $fetchStatus.addClass('is-error').text(SMP_Admin.i18n.enterUrl);
                return;
            }
            var $btn = $(this);
            $btn.prop('disabled', true);
            $fetchStatus.text(SMP_Admin.i18n.fetching);

            $.post(SMP_Admin.ajaxUrl, {
                action: 'smp_fetch_oembed',
                nonce: SMP_Admin.nonce,
                url: postUrl
            }).done(function (response) {
                if (response && response.success && response.data && response.data.thumbnail_url) {
                    $urlInput.val(response.data.thumbnail_url);
                    $sourceRadios.filter('[value="url"]').prop('checked', true).trigger('change');
                    $fetchStatus.addClass('is-success').text(SMP_Admin.i18n.fetchOk);
                } else {
                    var msg = (response && response.data && response.data.message) ? response.data.message : SMP_Admin.i18n.fetchFailed;
                    $fetchStatus.addClass('is-error').text(msg);
                }
            }).fail(function (xhr) {
                var msg = SMP_Admin.i18n.fetchFailed;
                if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    msg = xhr.responseJSON.data.message;
                }
                $fetchStatus.addClass('is-error').text(msg);
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        updateSourceVisibility();
    });
})(jQuery);
