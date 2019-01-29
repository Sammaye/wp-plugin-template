jQuery(document).on( 'click', '.pluginname-notice .notice-dismiss', function() {
    var notice = $(this).parents('.notice');

    if (typeof notice.data('key') === 'undefined') {
        return;
    }

    jQuery.post(
        ajaxurl,
        {
            key: notice.data('key'),
            action: 'pluginname_dismiss_notice',
        }
    );
});
