/* globals ajaxurl */
jQuery(document).ready(function ($) {
    $(document).on('click', '.ul-rating-notice button', function () {
            $.post(ajaxurl, {action: 'unlimit_review_dismiss'});
        }
    );
});
