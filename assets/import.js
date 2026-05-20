(function ($) {
    'use strict';

    $(function () {
        var $form = $('#smp-import-form');
        if (!$form.length) {
            return;
        }
        var $textarea = $('#smp-import-urls');
        var $startBtn = $('#smp-import-start');
        var $table = $('.smp-import-results');
        var $tbody = $table.find('tbody');
        var $summary = $('.smp-import-summary');
        var $collectionsSelect = $(SMP_Import.collectionsSelector);

        function makeRow(url) {
            var $tr = $('<tr/>').data('url', url);
            $('<td/>').addClass('smp-col-status').append(
                $('<span/>').addClass('smp-status-badge is-queued').text(SMP_Import.i18n.queued)
            ).appendTo($tr);

            $('<td/>').addClass('smp-col-url').append(
                $('<div/>').addClass('smp-url-cell').text(url)
            ).appendTo($tr);

            $('<td/>').addClass('smp-col-platform').text('').appendTo($tr);
            $('<td/>').addClass('smp-col-title').text('').appendTo($tr);
            $('<td/>').addClass('smp-col-actions').text('').appendTo($tr);
            return $tr;
        }

        function renderResponse($row, data) {
            var status = data.status || 'failed';
            var $badge = $row.find('.smp-status-badge');
            $badge.removeClass('is-queued is-processing is-created is-partial is-duplicate is-failed');
            var label;
            var className;
            switch (status) {
                case 'created':
                    label = SMP_Import.i18n.created;
                    className = 'is-created';
                    break;
                case 'partial':
                    label = SMP_Import.i18n.partial;
                    className = 'is-partial';
                    break;
                case 'duplicate':
                    label = SMP_Import.i18n.duplicate;
                    className = 'is-duplicate';
                    break;
                default:
                    label = SMP_Import.i18n.failed;
                    className = 'is-failed';
            }
            $badge.addClass(className).text(label);

            $row.find('.smp-col-platform').text(data.platform || '');
            $row.find('.smp-col-title').text(data.title || '');

            var $actions = $row.find('.smp-col-actions').empty();
            if (data.edit_link) {
                $('<a/>')
                    .attr('href', data.edit_link)
                    .attr('target', '_blank')
                    .attr('rel', 'noopener')
                    .text(SMP_Import.i18n.edit)
                    .appendTo($actions);
            }

            if (data.message) {
                $row.find('.smp-col-title').append(
                    $('<div/>').addClass('smp-row-message').text(data.message)
                );
            }
        }

        function processOne(url) {
            var $row = $tbody.find('tr').filter(function () {
                return $(this).data('url') === url;
            }).first();

            var $badge = $row.find('.smp-status-badge');
            $badge.removeClass('is-queued').addClass('is-processing').text(SMP_Import.i18n.processing);

            var collections = $collectionsSelect.length ? $collectionsSelect.val() || [] : [];

            return $.post(SMP_Import.ajaxUrl, {
                action: 'smp_import_url',
                nonce: SMP_Import.nonce,
                url: url,
                collections: collections
            }).then(function (response) {
                var data = (response && response.data) ? response.data : {};
                if (response && response.success) {
                    renderResponse($row, data);
                    return data.status;
                }
                renderResponse($row, $.extend({ status: 'failed' }, data));
                return 'failed';
            }, function (xhr) {
                var data = (xhr && xhr.responseJSON && xhr.responseJSON.data) ? xhr.responseJSON.data : { message: SMP_Import.i18n.networkError };
                renderResponse($row, $.extend({ status: 'failed' }, data));
                return 'failed';
            });
        }

        function summarise(counts) {
            var msg = SMP_Import.i18n.summaryDone
                .replace('%1$d', counts.created)
                .replace('%2$d', counts.partial)
                .replace('%3$d', counts.duplicate)
                .replace('%4$d', counts.failed);
            $summary.text(msg);
        }

        $form.on('submit', function (e) {
            e.preventDefault();
            var raw = $textarea.val() || '';
            var urls = raw.split(/\r?\n/).map(function (s) { return s.trim(); }).filter(function (s) { return s.length > 0; });
            urls = urls.filter(function (u, i) { return urls.indexOf(u) === i; });

            if (!urls.length) {
                $summary.text(SMP_Import.i18n.noUrls);
                return;
            }

            $tbody.empty();
            urls.forEach(function (u) { $tbody.append(makeRow(u)); });
            $table.removeAttr('hidden');
            $startBtn.prop('disabled', true);
            $summary.text('');

            var counts = { created: 0, partial: 0, duplicate: 0, failed: 0 };
            var chain = $.Deferred().resolve();
            urls.forEach(function (url) {
                chain = chain.then(function () {
                    return processOne(url).then(function (status) {
                        if (counts.hasOwnProperty(status)) {
                            counts[status] += 1;
                        } else {
                            counts.failed += 1;
                        }
                    });
                });
            });

            chain.always(function () {
                summarise(counts);
                $startBtn.prop('disabled', false);
            });
        });

    });
})(jQuery);
