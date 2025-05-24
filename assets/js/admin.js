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
        initDraggableFields();
        initFieldTypeColors();
        initSettingsTabs();
        initCollapsibleFields();
    });

    /**
     * Initialize draggable checkout fields
     */
    function initDraggableFields() {
        // Add draggable functionality to checkout fields
        if ($('.swift-checkout-fields-container').length) {
            // Set up sortable fields
            if (typeof $.fn.sortable !== 'undefined') {
                $('.swift-checkout-fields-container').sortable({
                    items: '.swift-checkout-field-item',
                    handle: '.swift-checkout-field-header',
                    cursor: 'move',
                    axis: 'y',
                    opacity: 0.7,
                    placeholder: 'swift-checkout-field-placeholder',
                    start: function(e, ui) {
                        ui.placeholder.height(ui.item.height());
                    },
                    update: function(e, ui) {
                        // Trigger an update to the model
                        setTimeout(function() {
                            $(document).trigger('swift-checkout-fields-reordered');
                        }, 200);
                    }
                });
            }

            // For environments without jQuery UI
            // Add click handlers for move up/down buttons
            $(document).on('click', '.swift-checkout-field-move-up', function(e) {
                e.preventDefault();
                const $field = $(this).closest('.swift-checkout-field-item');
                const $prev = $field.prev('.swift-checkout-field-item');

                if ($prev.length) {
                    $field.insertBefore($prev);
                    $(document).trigger('swift-checkout-fields-reordered');
                }
            });

            $(document).on('click', '.swift-checkout-field-move-down', function(e) {
                e.preventDefault();
                const $field = $(this).closest('.swift-checkout-field-item');
                const $next = $field.next('.swift-checkout-field-item');

                if ($next.length) {
                    $field.insertAfter($next);
                    $(document).trigger('swift-checkout-fields-reordered');
                }
            });
        }
    }

    /**
     * Initialize field type color indicators
     */
    function initFieldTypeColors() {
        // Update field type indicators when type changes
        $(document).on('change', '.swift-checkout-field-item select', function() {
            const $select = $(this);
            const $field = $select.closest('.swift-checkout-field-item');
            const fieldType = $select.val();

            // Update the data attribute for styling
            $field.attr('data-field-type', fieldType);

            // Update the field title if label is empty
            const $label = $field.find('input[placeholder="Field Label"]');
            const $title = $field.find('.swift-checkout-field-title');

            if ($label.val() === '') {
                // Get the selected option text
                const optionText = $select.find('option:selected').text();
                $title.text(optionText || 'Unnamed Field');
            }
        });

        // Update field title when label changes
        $(document).on('input', '.swift-checkout-field-item input[placeholder="Field Label"]', function() {
            const $input = $(this);
            const $field = $input.closest('.swift-checkout-field-item');
            const $title = $field.find('.swift-checkout-field-title');

            $title.text($input.val() || 'Unnamed Field');
        });
    }

    /**
     * Initialize collapsible fields functionality
     */
    function initCollapsibleFields() {
        // Toggle field content when clicking on the header
        $(document).on('click', '.swift-checkout-field-header', function(e) {
            // Don't collapse if clicking on buttons within the header
            if ($(e.target).closest('.components-button').length) {
                return;
            }

            const $header = $(this);
            const $content = $header.next('.swift-checkout-field-content');
            const $fieldItem = $header.closest('.swift-checkout-field-item');
            const $icon = $header.find('.dashicons-arrow-up, .dashicons-arrow-down');

            if ($content.is(':visible')) {
                // Collapsing
                $fieldItem.addClass('collapsed');
                $icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');

                // Animate the collapse
                $content.slideUp(200, function() {
                    // This function runs after animation completes
                    $(this).addClass('collapsed');
                });
            } else {
                // Expanding
                $fieldItem.removeClass('collapsed');
                $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');

                // Make sure content is ready for animation
                $content.removeClass('collapsed').hide();

                // Animate the expansion
                $content.slideDown(200);
            }
        });

        // Ensure newly added fields have arrow icons
        $(document).on('swift-checkout-field-added', function(e, $field) {
            const $header = $field.find('.swift-checkout-field-header');
            if (!$header.find('.dashicons-arrow-up, .dashicons-arrow-down').length) {
                const $actions = $header.find('.swift-checkout-field-actions');
                if ($actions.length) {
                    $actions.prepend('<span class="dashicons dashicons-arrow-up" title="Collapse"></span>');
                } else {
                    $header.append('<div class="swift-checkout-field-actions"><span class="dashicons dashicons-arrow-up" title="Collapse"></span></div>');
                }
            }

            // Auto-expand new fields
            $field.removeClass('collapsed');
            $field.find('.swift-checkout-field-content').show();
        });

        // Initialize existing fields (expand the first one, collapse others)
        $('.swift-checkout-fields-container').each(function() {
            const $container = $(this);
            const $fields = $container.find('.swift-checkout-field-item');

            $fields.each(function(index) {
                const $field = $(this);
                const $header = $field.find('.swift-checkout-field-header');
                const $content = $field.find('.swift-checkout-field-content');
                const $icon = $header.find('.dashicons-arrow-up, .dashicons-arrow-down');

                if (index === 0) {
                    // Expand first field
                    $field.removeClass('collapsed');
                    $content.show();
                    $icon.removeClass('dashicons-arrow-down').addClass('dashicons-arrow-up');
                } else {
                    // Collapse other fields
                    $field.addClass('collapsed');
                    $content.hide();
                    $icon.removeClass('dashicons-arrow-up').addClass('dashicons-arrow-down');
                }
            });
        });
    }

    /**
     * Initialize settings tabs
     */
    function initSettingsTabs() {
        // Settings tabs functionality
        $('.swift-checkout-settings .nav-tab').on('click', function(e) {
            e.preventDefault();
            const $this = $(this);
            const target = $this.attr('href');

            // Update active tab
            $('.swift-checkout-settings .nav-tab').removeClass('nav-tab-active');
            $this.addClass('nav-tab-active');

            // Show target content
            $('.swift-checkout-settings .tab-content').hide();
            $(target).show();

            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, target);
            } else {
                location.hash = target;
            }
        });

        // Initialize active tab from URL hash
        const hash = window.location.hash;
        if (hash) {
            $('.swift-checkout-settings .nav-tab[href="' + hash + '"]').trigger('click');
        } else {
            $('.swift-checkout-settings .nav-tab:first').trigger('click');
        }
    }

})(jQuery);