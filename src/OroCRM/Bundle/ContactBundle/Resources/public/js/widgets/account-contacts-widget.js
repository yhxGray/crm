define(['jquery'], function($) {
    'use strict';

    /**
     * @export  orocrm/contact/widgets/account-contacts-widget
     * @class   oro.AccountContactWidgetHandler
     */
    return {
        /**
         * @desc Fire name link click
         * @callback
         */
        boxClickHandler: function(event) {
            /**
             * @desc if target item has class contact-box-link
             * we does not click redirection link(name link)
             */
            if (!$(event.target).hasClass('contact-box-row')) {
                return;
            }
            $(this).find('.contact-box-name-link').click();
        },

        /**
         * @constructs
         */
        init: function() {
            $('.contact-box').click(this.boxClickHandler);
        }
    };
});
