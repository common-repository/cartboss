jQuery.noConflict();

(function ($) {
    'use strict';

    $(document).ready(function () {

        $(".cartboss-wrapper form").submit(function () {
            $.LoadingOverlay("show");
        });

        $('#cb_marketing_checkbox_enabled').change(function () {
            $foo();
        })

        var $foo = function () {
            if ($('#cb_marketing_checkbox_enabled').is(':checked')) {
                $('#cb_marketing_checkbox_label_wrapper').show();
            } else {
                $('#cb_marketing_checkbox_label_wrapper').hide();
            }
        };

        $foo();

    });

})(jQuery);
