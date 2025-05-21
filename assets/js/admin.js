/**
 *Swift Checkout Admin Scripts
 */

(function($) {
    'use strict';

    /**
     * Admin Settings functionality
     */
    var SwiftCheckoutAdmin = {
        /**
         * Initialize
         */
        init: function() {
            this.setupBuilderOptions();
            this.setupTabNavigation();
        },

        /**
         * Setup Builder Options
         */
        setupBuilderOptions: function() {
            // When a builder option is changed, show a warning about needing to install the builder
            $('.swift-checkout-settings input[name^="swift_checkout_settings[builders]"]').on('change', function() {
                var $checkbox = $(this);
                var builderName = $checkbox.closest('tr').find('th').text();
                var isChecked = $checkbox.is(':checked');

                if (isChecked && $checkbox.closest('td').find('.error-message').length) {
                    alert('Warning: ' + builderName + ' is not currently installed or activated. Please install and activate it to use this integration.');
                }
            });
        },

        /**
         * Setup Tab Navigation
         */
        setupTabNavigation: function() {
            // This is handled by WordPress's built-in tab navigation
            // We just add this method for future functionality
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SwiftCheckoutAdmin.init();
    });

})(jQuery);