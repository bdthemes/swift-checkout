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
            $(document.body).on('wc_fragments_refreshed swift_checkout_fragments_refreshed', this.updateCartVisibility);
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Add to cart - combined handler for both regular and variable products
            $(document).on('click', '.swift-checkout-add-to-cart:not([type="submit"])', this.addToCart);
            $(document).on('submit', '.swift-checkout-variations-form', this.addVariableToCart);

            // Handle refresh cart button for auto-add products
            $(document).on('click', '.swift-checkout-refresh-cart', this.refreshCart);

            // Variable product handling
            $(document).on('click', '.swift-checkout-select-options', this.toggleVariations);
            $(document).on('change', '.swift-checkout-variation-select', this.updateVariation);

            // Update cart quantity
            $(document).on('click', '.swift-checkout-qty-plus', this.increaseQuantity);
            $(document).on('click', '.swift-checkout-qty-minus', this.decreaseQuantity);
            $(document).on('change', '.swift-checkout-qty-input', this.updateCartItem);

            // Remove from cart
            $(document).on('click', '.swift-checkout-remove-item', this.removeFromCart);

            // Submit order - button is now outside the form
            $(document).on('click', '#swift-checkout-submit-order', this.triggerOrderSubmit);

            // Toggle shipping address fields
            $(document).on('change', '#swift-checkout-shipping_address', this.toggleShippingFields);

            // Address fields change for shipping calculation
            $(document).on('change', '#swift-checkout-postcode, #swift-checkout-state, #swift-checkout-country, #swift-checkout-city', this.updateShippingMethods);
            $(document).on('change', '#swift-checkout-shipping_postcode, #swift-checkout-shipping_state, #swift-checkout-shipping_country, #swift-checkout-shipping_city',
                function() {
                    if ($('#swift-checkout-shipping_address').is(':checked')) {
                        SwiftCheckout.updateShippingMethods();
                    }
                }
            );

            // Shipping method selection
            $(document).on('change', '.swift-checkout-shipping-method-input', this.updateOrderTotal);
        },

        /**
         * Toggle variations visibility
         */
        toggleVariations: function(e) {
            e.preventDefault();
            const productId = $(this).data('product-id');
            const $variations = $(`#swift-checkout-variations-${productId}`);
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
            const $price = $form.find('.swift-checkout-variation-price');
            const $stock = $form.find('.swift-checkout-variation-stock');
            const $addToCart = $form.find('.swift-checkout-add-to-cart');

            const formData = new FormData($form[0]);
            formData.append('action', 'swift_checkout_get_variation');
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
                    action: 'swift_checkout_add_to_cart',
                    product_id: productId,
                    quantity: 1,
                    variations: JSON.stringify({}),
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SwiftCheckout.updateFragments(response.data.fragments);
                        // Hide other add to cart buttons since we now have an item in cart
                        $('.swift-checkout-add-to-cart:not([data-product-id="' + productId + '"])').hide();
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
            const $button = $form.find('.swift-checkout-add-to-cart');
            const productId = $form.data('product-id');

            if (!productId) {
                return;
            }

            $button.prop('disabled', true).addClass('loading');

            // Get all selected variations
            const variations = {};
            $form.find('.swift-checkout-variation-select').each(function() {
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
                    action: 'swift_checkout_add_to_cart',
                    product_id: productId,
                    quantity: 1,
                    variations: JSON.stringify(variations),
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SwiftCheckout.updateFragments(response.data.fragments);
                        // Hide variations after adding to cart
                        $form.closest('.swift-checkout-variations-wrapper').slideUp(300);
                        // Hide other add to cart buttons since we now have an item in cart
                        $('.swift-checkout-add-to-cart:not([data-product-id="' + productId + '"])').hide();
                        $('.swift-checkout-select-options:not([data-product-id="' + productId + '"])').hide();
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
            const $input = $button.siblings('.swift-checkout-qty-input');
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
            const $input = $button.siblings('.swift-checkout-qty-input');
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

            const $row = $input.closest('.swift-checkout-cart-item');
            $row.addClass('updating');

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'swift_checkout_update_cart',
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

            const $row = $button.closest('.swift-checkout-cart-item');
            $row.addClass('removing');

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'swift_checkout_remove_from_cart',
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
         * Update available shipping methods based on address
         */
        updateShippingMethods: function() {
            const $shippingMethods = $('#swift-checkout-shipping-methods');
            const $loading = $shippingMethods.find('.swift-checkout-shipping-methods-loading');

            // Check if shipping address is different
            const useShippingAddress = $('#swift-checkout-shipping_address').is(':checked');

            // Get address fields
            let country, state, postcode, city;

            if (useShippingAddress) {
                country = $('#swift-checkout-shipping_country').val();
                state = $('#swift-checkout-shipping_state').val();
                postcode = $('#swift-checkout-shipping_postcode').val();
                city = $('#swift-checkout-shipping_city').val();
            } else {
                country = $('#swift-checkout-country').val();
                state = $('#swift-checkout-state').val();
                postcode = $('#swift-checkout-postcode').val();
                city = $('#swift-checkout-city').val();
            }

            // Don't make request if address is incomplete
            if (!country) {
                return;
            }

            $loading.show();

            // Create AJAX request to update shipping methods
            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'swift_checkout_update_shipping_methods',
                    country: country,
                    state: state,
                    postcode: postcode,
                    city: city,
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Replace shipping methods with new HTML
                        $shippingMethods.html(response.data.html);

                        // Pre-select first shipping method
                        const $firstMethod = $shippingMethods.find('.swift-checkout-shipping-method-input').first();
                        if ($firstMethod.length) {
                            $firstMethod.prop('checked', true);
                            SwiftCheckout.updateOrderTotal();
                        }
                    }
                },
                complete: function() {
                    $loading.hide();
                }
            });
        },

        /**
         * Update order total when shipping method is selected
         */
        updateOrderTotal: function() {
            // This could be extended to show real-time order total updates
            // For now, we'll just highlight the selected shipping method
            $('.swift-checkout-shipping-method').removeClass('selected');
            $(this).closest('.swift-checkout-shipping-method').addClass('selected');
        },

        /**
         * Trigger order submission when the standalone button is clicked
         *
         * @param {Event} e Click event
         */
        triggerOrderSubmit: function(e) {
            e.preventDefault();

            const $form = $('#swift-checkout-checkout-form');
            const $submitButton = $(this);
            const isShippingDifferent = $('#swift-checkout-shipping_address').is(':checked');

            // Check if shipping method is selected
            // const $selectedShipping = $form.find('input[name="shipping_method"]:checked');
            // if ($selectedShipping.length === 0) {
            //     $('.swift-checkout-checkout-error').html('Please select a shipping method');
            //     return;
            // }

            // Collect required fields information
            const requiredFields = {};
            $form.find('.swift-checkout-form-input').each(function() {
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
            $('.swift-checkout-checkout-error').empty();

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: formData + '&action=swift_checkout_create_order&nonce=' + spcData.nonce,
                success: function(response) {
                    if (response.success) {
                        // Show success message or redirect
                        SwiftCheckout.showOrderConfirmation(response.data);
                    } else {
                        $('.swift-checkout-checkout-error').html(response.data.message || 'Error creating order');
                    }
                },
                error: function() {
                    $('.swift-checkout-checkout-error').text('Error connecting to server');
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
                    action: 'swift_checkout_get_order_received',
                    order_id: data.order_id,
                    nonce: spcData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Replace checkout content with order received content
                        $('.swift-checkout-container').html(response.data.html);
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
         * Update page fragments
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

            $(document.body).trigger('swift_checkout_fragments_refreshed');
        },

        /**
         * Update cart visibility based on cart contents
         */
        updateCartVisibility: function() {
            const $cartItems = $('.swift-checkout-cart-item');
            const $miniCart = $('.swift-checkout-mini-cart');
            const $checkoutForm = $('.swift-checkout-checkout-form');
            const $addToCartButtons = $('.swift-checkout-add-to-cart:not(.swift-checkout-refresh-cart)');
            const $refreshButtons = $('.swift-checkout-refresh-cart');
            const $selectOptionsButtons = $('.swift-checkout-select-options');

            if ($cartItems.length > 0) {
                // Show cart and checkout when we have items
                $miniCart.addClass('swift-checkout-visible');
                $checkoutForm.addClass('swift-checkout-visible');

                // Hide regular add to cart buttons, but keep refresh buttons visible
                $addToCartButtons.hide();
                $selectOptionsButtons.hide();
                $refreshButtons.show();
            }
            else {
                // Hide cart and checkout when empty
                $miniCart.removeClass('swift-checkout-visible');
                $checkoutForm.removeClass('swift-checkout-visible');

                // Show all buttons when cart is empty
                $addToCartButtons.show();
                $selectOptionsButtons.show();
            }
        },

        /**
         * Toggle shipping address fields visibility
         */
        toggleShippingFields: function() {
            const $checkbox = $(this);
            const $shippingFields = $('#swift-checkout-shipping-address-fields');

            if ($checkbox.is(':checked')) {
                $shippingFields.slideDown(300);
                console.log('Showing shipping fields');
            } else {
                $shippingFields.slideUp(300);
                console.log('Hiding shipping fields');
            }

            // Update shipping methods when shipping address option changes
            SwiftCheckout.updateShippingMethods();
        },

        /**
         * Handle refresh cart button for auto-add products
         *
         * @param {Event} e Click event
         */
        refreshCart: function(e) {
            e.preventDefault();
            const $button = $(this);
            const productId = $button.data('product-id');

            if (!productId) {
                return;
            }

            $button.prop('disabled', true).addClass('loading');

            // First clear the cart
            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'swift_checkout_remove_all_items',
                    nonce: spcData.nonce
                },
                success: function() {
                    // Then add the product
                    $.ajax({
                        url: spcData.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'swift_checkout_add_to_cart',
                            product_id: productId,
                            quantity: 1,
                            variations: JSON.stringify({}),
                            nonce: spcData.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                SwiftCheckout.updateFragments(response.data.fragments);
                                SwiftCheckout.updateCartVisibility();
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
                error: function() {
                    alert('Error connecting to server');
                    $button.prop('disabled', false).removeClass('loading');
                }
            });
        },

        /**
         * Set the position of the place order button
         *
         * This function can be called by the user to move the button to a specific element
         *
         * @param {string} targetSelector - CSS selector for the target element to append the button to
         */
        setPlaceOrderPosition: function(targetSelector) {
            if (!targetSelector) return;

            const $button = $('.swift-checkout-place-order-wrapper');
            const $target = $(targetSelector);

            if ($button.length && $target.length) {
                $button.appendTo($target);
                $button.addClass('custom-position');
            }
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        SwiftCheckout.init();

        // Expose the setPlaceOrderPosition function globally
        window.swiftCheckoutSetPlaceOrderPosition = SwiftCheckout.setPlaceOrderPosition;

        // Process each Swift Checkout container on the page
        $('.swift-checkout-container').each(function() {
            const $container = $(this);
            const productId = $container.data('product-id');
            const autoAddToCart = $container.data('auto-add-to-cart');

            if (productId) {
                // First clear the cart completely
                $.ajax({
                    url: spcData.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'swift_checkout_remove_all_items',
                        nonce: spcData.nonce
                    },
                    success: function() {
                        // After clearing, add the product if auto-add is enabled
                        if (autoAddToCart === 'yes') {
                            // Add product to cart
                            $.ajax({
                                url: spcData.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'swift_checkout_add_to_cart',
                                    product_id: productId,
                                    quantity: 1,
                                    variations: JSON.stringify({}),
                                    nonce: spcData.nonce
                                },
                                success: function(response) {
                                    if (response.success) {
                                        SwiftCheckout.updateFragments(response.data.fragments);
                                        SwiftCheckout.updateCartVisibility();
                                    }
                                }
                            });
                        }
                    }
                });
            }
        });

        // Initialize shipping fields visibility
        const $shippingCheckbox = $('#swift-checkout-shipping_address');
        if ($shippingCheckbox.length) {
            // Trigger the change event to set initial state
            $shippingCheckbox.trigger('change');
        }

        // Initialize shipping methods
        setTimeout(function() {
            if ($('.swift-checkout-shipping-method-input').length === 0) {
                SwiftCheckout.updateShippingMethods();
            } else {
                // Pre-select first shipping method
                $('.swift-checkout-shipping-method-input').first().prop('checked', true);
            }
        }, 500);
    });

})(jQuery);