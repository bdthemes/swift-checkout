/**
 *Swift Checkout JS
 */
(function($) {
    'use strict';

    const SwiftCheckout = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.updateCartVisibility();

            // Update cart visibility when fragments are refreshed
            $(document.body).on('wc_fragments_refreshed spc_fragments_refreshed', this.updateCartVisibility);
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Add to cart - combined handler for both regular and variable products
            $(document).on('click', '.spc-add-to-cart:not([type="submit"])', this.addToCart);
            $(document).on('submit', '.spc-variations-form', this.addVariableToCart);

            // Variable product handling
            $(document).on('click', '.spc-select-options', this.toggleVariations);
            $(document).on('change', '.spc-variation-select', this.updateVariation);

            // Update cart quantity
            $(document).on('click', '.spc-qty-plus', this.increaseQuantity);
            $(document).on('click', '.spc-qty-minus', this.decreaseQuantity);
            $(document).on('change', '.spc-qty-input', this.updateCartItem);

            // Remove from cart
            $(document).on('click', '.spc-remove-item', this.removeFromCart);

            // Submit order
            $(document).on('submit', '#spc-checkout-form', this.submitOrder);

            // Toggle shipping address fields
            $(document).on('change', '#spc-shipping_address', this.toggleShippingFields);

            // Handle shipping method selection
            $(document).on('change', '.spc-shipping-method-select', this.updateShippingMethod);
        },

        /**
         * Toggle variations visibility
         */
        toggleVariations: function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            const $variations = $(`#spc-variations-${productId}`);
            const $button = $(this);

            if ($variations.is(':visible')) {
                $variations.slideUp(300);
                $button.text(spcData.i18n.select_options);
            } else {
                $variations.slideDown(300);
                // $button.text(spcData.i18n.hide_options);
            }
        },

        /**
         * Update variation details
         */
        updateVariation: function() {
            const $form = $(this).closest('form');
            const productId = $form.data('product-id');
            const $price = $form.find('.spc-variation-price');
            const $stock = $form.find('.spc-variation-stock');
            const $addToCart = $form.find('.spc-add-to-cart');

            const formData = new FormData($form[0]);
            formData.append('action', 'spc_get_variation');
            formData.append('product_id', productId);
            formData.append('nonce', spcData.nonce);

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $price.html(response.data.price_html);
                        $stock.html(response.data.stock_html);

                        if (response.data.is_purchasable) {
                            $addToCart.prop('disabled', false);
                        } else {
                            $addToCart.prop('disabled', true);
                        }
                    }
                }
            });
        },

        /**
         * Add to cart event handler for regular products
         *
         * @param {Event} e Click event
         */
        addToCart: function(e) {
            e.preventDefault();
            const $button = $(this);
            const productId = $button.data('product-id');

            if (!productId) {
                return;
            }

            $button.prop('disabled', true).addClass('loading');

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'spc_add_to_cart',
                    product_id: productId,
                    quantity: 1,
                    variations: JSON.stringify({}),
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SwiftCheckout.updateFragments(response.data.fragments);
                        setTimeout(function() {
                            SwiftCheckout.updateCartVisibility();
                        }, 100);
                    } else {
                        alert(response.data.message || 'Error adding to cart');
                    }
                },
                error: function() {
                    alert('Error connecting to server');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('loading');
                }
            });
        },

        /**
         * Add variable product to cart
         *
         * @param {Event} e Submit event
         */
        addVariableToCart: function(e) {
            e.preventDefault();
            const $form = $(this);
            const $button = $form.find('.spc-add-to-cart');
            const productId = $form.data('product-id');

            if (!productId) {
                return;
            }

            $button.prop('disabled', true).addClass('loading');

            // Get all selected variations
            const variations = {};
            $form.find('.spc-variation-select').each(function() {
                const $select = $(this);
                const attributeName = $select.data('attribute_name');
                const value = $select.val();
                if (value) {
                    variations[attributeName] = value;
                }
            });

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'spc_add_to_cart',
                    product_id: productId,
                    quantity: 1,
                    variations: JSON.stringify(variations),
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SwiftCheckout.updateFragments(response.data.fragments);
                        // Hide variations after adding to cart
                        $form.closest('.spc-variations-wrapper').slideUp(300);
                        setTimeout(function() {
                            SwiftCheckout.updateCartVisibility();
                        }, 100);
                    } else {
                        alert(response.data.message || 'Error adding to cart');
                    }
                },
                error: function() {
                    alert('Error connecting to server');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('loading');
                }
            });
        },

        /**
         * Increase quantity handler
         *
         * @param {Event} e Click event
         */
        increaseQuantity: function(e) {
            e.preventDefault();

            const $button = $(this);
            const $input = $button.siblings('.spc-qty-input');
            const currentQty = parseInt($input.val(), 10);

            $input.val(currentQty + 1).trigger('change');
        },

        /**
         * Decrease quantity handler
         *
         * @param {Event} e Click event
         */
        decreaseQuantity: function(e) {
            e.preventDefault();

            const $button = $(this);
            const $input = $button.siblings('.spc-qty-input');
            const currentQty = parseInt($input.val(), 10);

            if (currentQty > 1) {
                $input.val(currentQty - 1).trigger('change');
            }
        },

        /**
         * Update cart item handler
         *
         * @param {Event} e Change event
         */
        updateCartItem: function(e) {
            const $input = $(this);
            const cartItemKey = $input.data('item-key');
            const quantity = parseInt($input.val(), 10);

            if (!cartItemKey || !quantity) {
                return;
            }

            const $row = $input.closest('.spc-cart-item');
            $row.addClass('updating');

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'spc_update_cart',
                    cart_item_key: cartItemKey,
                    quantity: quantity,
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SwiftCheckout.updateFragments(response.data.fragments);
                    } else {
                        alert(response.data.message || 'Error updating cart');
                    }
                },
                error: function() {
                    alert('Error connecting to server');
                },
                complete: function() {
                    $row.removeClass('updating');
                }
            });
        },

        /**
         * Remove from cart handler
         *
         * @param {Event} e Click event
         */
        removeFromCart: function(e) {
            e.preventDefault();

            const $button = $(this);
            const cartItemKey = $button.data('item-key');

            if (!cartItemKey) {
                return;
            }

            const $row = $button.closest('.spc-cart-item');
            $row.addClass('removing');

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'spc_remove_from_cart',
                    cart_item_key: cartItemKey,
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SwiftCheckout.updateFragments(response.data.fragments);
                        // Force update cart visibility after removing item
                        setTimeout(function() {
                            SwiftCheckout.updateCartVisibility();
                        }, 100);
                    } else {
                        alert(response.data.message || 'Error removing item');
                    }
                },
                error: function() {
                    alert('Error connecting to server');
                },
                complete: function() {
                    $row.removeClass('removing');
                }
            });
        },

        /**
         * Submit order handler
         *
         * @param {Event} e Submit event
         */
        submitOrder: function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitButton = $form.find('#spc-submit-order');
            const isShippingDifferent = $('#spc-shipping_address').is(':checked');

            // Collect required fields information
            const requiredFields = {};
            $form.find('.spc-form-input').each(function() {
                const $input = $(this);
                const fieldName = $input.attr('name');

                // Skip shipping fields if shipping address is not checked
                if (!isShippingDifferent && fieldName && fieldName.startsWith('shipping_')) {
                    return;
                }

                if (fieldName) {
                    requiredFields[fieldName] = $input.prop('required');
                }
            });

            // Add to formData
            const formData = $form.serialize() + '&required_fields=' + encodeURIComponent(JSON.stringify(requiredFields));

            $submitButton.prop('disabled', true).addClass('loading');
            $('.spc-checkout-error').empty();

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: formData + '&action=spc_create_order&nonce=' + spcData.nonce,
                success: function(response) {
                    if (response.success) {
                        // Show success message or redirect
                        SwiftCheckout.showOrderConfirmation(response.data);
                    } else {
                        $('.spc-checkout-error').html(response.data.message || 'Error creating order');
                    }
                },
                error: function() {
                    $('.spc-checkout-error').text('Error connecting to server');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).removeClass('loading');
                }
            });
        },

        /**
         * Show order confirmation
         *
         * @param {Object} data Order data
         */
        showOrderConfirmation: function(data) {
            // Load order received content via AJAX
            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'spc_get_order_received',
                    order_id: data.order_id,
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Replace checkout content with order received content
                        $('.spc-container').html(response.data.html);
                    } else {
                        alert(response.data.message || 'Error loading order details');
                    }
                },
                error: function() {
                    alert('Error connecting to server');
                }
            });
        },

        /**
         * Update fragments
         *
         * @param {Object} fragments HTML fragments to update
         */
        updateFragments: function(fragments) {
            if (!fragments) {
                return;
            }

            $.each(fragments, function(selector, content) {
                $(selector).replaceWith(content);
            });

            // Force update cart visibility after fragments update
            setTimeout(function() {
                SwiftCheckout.updateCartVisibility();
            }, 100);

            $(document.body).trigger('spc_fragments_refreshed');
        },

        /**
         * Update cart visibility based on cart contents
         */
        updateCartVisibility: function() {
            const $cartItems = $('.spc-cart-item');
            const $miniCart = $('.spc-mini-cart');
            const $checkoutForm = $('.spc-checkout-form');
            const $addToCartButtons = $('.spc-add-to-cart, .spc-select-options');

            if ($cartItems.length > 0) {
                $miniCart.addClass('spc-visible');
                $checkoutForm.addClass('spc-visible');
                $addToCartButtons.hide(); // Hide add to cart buttons when cart has items
            }
            else {
                $miniCart.removeClass('spc-visible');
                $checkoutForm.removeClass('spc-visible');
                $addToCartButtons.show(); // Show add to cart buttons when cart is empty
            }
        },

        /**
         * Toggle shipping address fields visibility
         */
        toggleShippingFields: function() {
            const $checkbox = $(this);
            const $shippingFields = $('#spc-shipping-address-fields');

            if ($checkbox.is(':checked')) {
                $shippingFields.slideDown(300);
                console.log('Showing shipping fields');
            } else {
                $shippingFields.slideUp(300);
                console.log('Hiding shipping fields');
            }
        },

        /**
         * Update shipping method
         *
         * @param {Event} e Change event
         */
        updateShippingMethod: function(e) {
            const $input = $(this);
            const packageKey = $input.data('package');
            const methodId = $input.val();

            if (!methodId) {
                return;
            }

            // Add loading state
            $('.spc-totals-shipping').addClass('updating');

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'spc_update_shipping_method',
                    package_key: packageKey,
                    shipping_method: methodId,
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update the cart totals to reflect the new shipping method
                        SwiftCheckout.updateFragments(response.data.fragments);
                    } else {
                        console.error(response.data.message || 'Error updating shipping method');
                    }
                },
                error: function() {
                    console.error('Error connecting to server');
                },
                complete: function() {
                    $('.spc-totals-shipping').removeClass('updating');
                }
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        SwiftCheckout.init();

        // Initialize shipping fields visibility
        const $shippingCheckbox = $('#spc-shipping_address');
        if ($shippingCheckbox.length) {
            // Trigger the change event to set initial state
            $shippingCheckbox.trigger('change');
        }

        // Handle auto add to cart functionality
        $('.spc-container').each(function() {
            const $container = $(this);
            const $widget = $container.closest('.elementor-widget-swift-checkout-add-to-cart');

            if ($widget.length && $widget.data('auto-add-to-cart') === 'yes') {
                const productId = $widget.data('product-id');
                if (productId) {
                    // Trigger cart fragment refresh
                    $(document.body).trigger('wc_fragment_refresh');
                }
            }
        });
    });

})(jQuery);