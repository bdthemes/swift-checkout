/**
 *Swift Checkout JS
 */
(function($) {
    'use strict';

    class SwiftCheckout {
        /**
         * Constructor - initializes the Swift Checkout functionality
         */
        constructor() {
            this.init();
        }

        /**
         * Initialize
         */
        init() {
            this.bindEvents();
            this.updateCartVisibility();

            // Update cart visibility when fragments are refreshed
            $(document.body).on('wc_fragments_refreshed swift_checkout_fragments_refreshed', this.updateCartVisibility.bind(this));

            // No longer making initial AJAX request for cart totals
            // Instead, just check if there's a pre-selected shipping method
            $(document).ready(() => {
                const $selectedMethod = $('input[name="shipping_method"]:checked');
                if ($selectedMethod.length) {
                    // Just update the UI to reflect the selected method without AJAX call
                    $('.swift-checkout-shipping-method').removeClass('selected');
                    $selectedMethod.closest('.swift-checkout-shipping-method').addClass('selected');
                }
            });
        }

        /**
         * Bind events
         */
        bindEvents() {
            // Add to cart - combined handler for both regular and variable products
            $(document).on('click', '.swift-checkout-add-to-cart:not([type="submit"])', this.addToCart.bind(this));
            $(document).on('submit', '.swift-checkout-variations-form', this.addVariableToCart.bind(this));

            // Variable product handling
            $(document).on('click', '.swift-checkout-select-options', this.toggleVariations.bind(this));
            $(document).on('change', '.swift-checkout-variation-select', this.updateVariation.bind(this));

            // Update cart quantity
            $(document).on('click', '.swift-checkout-qty-plus', this.increaseQuantity.bind(this));
            $(document).on('click', '.swift-checkout-qty-minus', this.decreaseQuantity.bind(this));
            $(document).on('change', '.swift-checkout-qty-input', this.updateCartItem.bind(this));

            // Remove from cart
            $(document).on('click', '.swift-checkout-remove-item', this.removeFromCart.bind(this));

            // Submit order - button is now outside the form
            $(document).on('click', '#swift-checkout-submit-order', this.triggerOrderSubmit.bind(this));

            // Toggle shipping address fields
            $(document).on('change', '#swift-checkout-shipping_address', this.toggleShippingFields.bind(this));

            // Address fields change for shipping calculation
            $(document).on('change', '#swift-checkout-postcode, #swift-checkout-state, #swift-checkout-country, #swift-checkout-city', this.updateShippingMethods.bind(this));
            $(document).on('change', '#swift-checkout-shipping_postcode, #swift-checkout-shipping_state, #swift-checkout-shipping_country, #swift-checkout-shipping_city',
                () => {
                    if ($('#swift-checkout-shipping_address').is(':checked')) {
                        this.updateShippingMethods();
                    }
                }
            );

            // Shipping method selection
            $(document).on('change', '.swift-checkout-shipping-method-input', this.updateOrderTotal.bind(this));

            // Also handle clicks on shipping methods (in case the radio button isn't directly clicked)
            $(document).on('click', '.swift-checkout-shipping-method label', (e) => {
                // Don't process if the radio input itself was clicked (it will trigger the change event)
                if (e.target.type !== 'radio') {
                    const $input = $(e.currentTarget).find('input[type="radio"]');
                    $input.prop('checked', true).trigger('change');
                }
            });
        }

        /**
         * Toggle variations visibility
         */
        toggleVariations(e) {
            e.preventDefault();
            const productId = $(e.currentTarget).data('product-id');
            const $variations = $(`#swift-checkout-variations-${productId}`);
            const $button = $(e.currentTarget);

            if ($variations.is(':visible')) {
                $variations.slideUp(300);
                $button.text(spcData.i18n.select_options);
            } else {
                $variations.slideDown(300);
                // $button.text(spcData.i18n.hide_options);
            }
        }

        /**
         * Update variation details
         */
        updateVariation(e) {
            const $form = $(e.currentTarget).closest('form');
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
                success: (response) => {
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
        }

        /**
         * Add to cart event handler for regular products
         *
         * @param {Event} e Click event
         */
        addToCart(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
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
                success: (response) => {
                    if (response.success) {
                        this.updateFragments(response.data.fragments);
                        // Hide other add to cart buttons since we now have an item in cart
                        $('.swift-checkout-add-to-cart:not([data-product-id="' + productId + '"])').hide();
                        setTimeout(() => {
                            this.updateCartVisibility();
                        }, 100);
                    } else {
                        alert(response.data.message || 'Error adding to cart');
                    }
                },
                error: () => {
                    alert('Error connecting to server');
                },
                complete: () => {
                    $button.prop('disabled', false).removeClass('loading');
                }
            });
        }

        /**
         * Add variable product to cart
         *
         * @param {Event} e Submit event
         */
        addVariableToCart(e) {
            e.preventDefault();
            const $form = $(e.currentTarget);
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
                success: (response) => {
                    if (response.success) {
                        this.updateFragments(response.data.fragments);
                        // Hide variations after adding to cart
                        $form.closest('.swift-checkout-variations-wrapper').slideUp(300);
                        // Hide other add to cart buttons since we now have an item in cart
                        $('.swift-checkout-add-to-cart:not([data-product-id="' + productId + '"])').hide();
                        $('.swift-checkout-select-options:not([data-product-id="' + productId + '"])').hide();
                        setTimeout(() => {
                            this.updateCartVisibility();
                        }, 100);
                    } else {
                        alert(response.data.message || 'Error adding to cart');
                    }
                },
                error: () => {
                    alert('Error connecting to server');
                },
                complete: () => {
                    $button.prop('disabled', false).removeClass('loading');
                }
            });
        }

        /**
         * Increase quantity handler
         *
         * @param {Event} e Click event
         */
        increaseQuantity(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $input = $button.siblings('.swift-checkout-qty-input');
            const currentQty = parseInt($input.val(), 10);

            $input.val(currentQty + 1).trigger('change');
        }

        /**
         * Decrease quantity handler
         *
         * @param {Event} e Click event
         */
        decreaseQuantity(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $input = $button.siblings('.swift-checkout-qty-input');
            const currentQty = parseInt($input.val(), 10);

            if (currentQty > 1) {
                $input.val(currentQty - 1).trigger('change');
            }
        }

        /**
         * Update cart item handler
         *
         * @param {Event} e Change event
         */
        updateCartItem(e) {
            const $input = $(e.currentTarget);
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
                success: (response) => {
                    if (response.success) {
                        this.updateFragments(response.data.fragments);
                    } else {
                        alert(response.data.message || 'Error updating cart');
                    }
                },
                error: () => {
                    alert('Error connecting to server');
                },
                complete: () => {
                    $row.removeClass('updating');
                }
            });
        }

        /**
         * Remove from cart handler
         *
         * @param {Event} e Click event
         */
        removeFromCart(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
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
                success: (response) => {
                    if (response.success) {
                        this.updateFragments(response.data.fragments);

                        // Check if the cart is now empty and update UI immediately
                        if (response.data.cart_items_count === 0) {
                            $('.swift-checkout-place-order-wrapper').removeClass('swift-checkout-visible');
                            $('.swift-checkout-add-to-cart').show();
                            $('.swift-checkout-select-options').show();
                        }

                        // Force update cart visibility after removing item
                        setTimeout(() => {
                            this.updateCartVisibility();
                        }, 100);
                    } else {
                        alert(response.data.message || 'Error removing item');
                    }
                },
                error: () => {
                    alert('Error connecting to server');
                },
                complete: () => {
                    $row.removeClass('removing');
                }
            });
        }

        /**
         * Update available shipping methods based on address
         */
        updateShippingMethods() {
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
                success: (response) => {
                    if (response.success && response.data.html) {
                        // Replace shipping methods with new HTML
                        $shippingMethods.html(response.data.html);

                        // Pre-select first shipping method
                        const $firstMethod = $shippingMethods.find('.swift-checkout-shipping-method-input').first();
                        if ($firstMethod.length) {
                            $firstMethod.prop('checked', true);
                            this.updateOrderTotal();
                        }
                    }
                },
                complete: () => {
                    $loading.hide();
                }
            });
        }

        /**
         * Update order total when shipping method is selected
         */
        updateOrderTotal(e) {
            // Highlight the selected shipping method
            $('.swift-checkout-shipping-method').removeClass('selected');
            const $selectedMethod = $(e ? e.currentTarget : '.swift-checkout-shipping-method-input:checked').closest('.swift-checkout-shipping-method');
            $selectedMethod.addClass('selected');

            // Get selected shipping method
            const shippingMethod = $(e ? e.currentTarget : '.swift-checkout-shipping-method-input:checked').val();

            if (!shippingMethod) {
                console.warn('No shipping method selected');
                return;
            }

            this.updateSelectedShippingMethod(shippingMethod);
        }

        /**
         * Update cart totals based on selected shipping method
         *
         * @param {string} shippingMethod The selected shipping method
         */
        updateSelectedShippingMethod(shippingMethod) {
            // Don't show loading indicator that causes blinking
            const $shippingValue = $('.cart-shipping-value');
            const $totalValue = $('.cart-total-value');

            // Add a subtle loading class instead of replacing content
            $shippingValue.addClass('updating');
            $totalValue.addClass('updating');

            // Update cart totals via AJAX
            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'swift_checkout_update_cart_totals',
                    shipping_method: shippingMethod,
                    nonce: spcData.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        // Update shipping total in mini-cart
                        $shippingValue.html(response.data.shipping_total);

                        // Update cart total in mini-cart
                        $totalValue.html(response.data.cart_total);

                    } else {
                        console.error('Failed to update cart totals', response);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', status, error);
                },
                complete: () => {
                    // Remove updating class
                    $shippingValue.removeClass('updating');
                    $totalValue.removeClass('updating');
                }
            });
        }

        /**
         * Trigger order submission when the standalone button is clicked
         *
         * @param {Event} e Click event
         */
        triggerOrderSubmit(e) {
            e.preventDefault();

            const $form = $('#swift-checkout-checkout-form');
            const $submitButton = $(e.currentTarget);
            const isShippingDifferent = $('#swift-checkout-shipping_address').is(':checked');

            // Clear previous error states
            $('.swift-checkout-form-input').removeClass('swift-checkout-input-error');
            $('.swift-checkout-form-error').remove();
            $('.swift-checkout-checkout-error').empty();

            // Client-side validation
            let hasErrors = false;

            $form.find('.swift-checkout-form-input[required]').each(function() {
                const $input = $(this);
                // Skip shipping fields if shipping address is not checked
                if (!isShippingDifferent && $input.attr('name') && $input.attr('name').startsWith('shipping_')) {
                    return;
                }

                if (!$input.val().trim()) {
                    hasErrors = true;
                    $input.addClass('swift-checkout-input-error');
                    const fieldName = $input.attr('name');
                    const $parent = $input.closest('.swift-checkout-form-row');
                    const errorMsg = `<div class="swift-checkout-form-error">This field is required</div>`;
                    if (!$parent.find('.swift-checkout-form-error').length) {
                        $parent.append(errorMsg);
                    }
                }

                // Email validation
                if ($input.attr('type') === 'email' && $input.val().trim()) {
                    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailPattern.test($input.val().trim())) {
                        hasErrors = true;
                        $input.addClass('swift-checkout-input-error');
                        const $parent = $input.closest('.swift-checkout-form-row');
                        const errorMsg = `<div class="swift-checkout-form-error">Please enter a valid email address</div>`;
                        if (!$parent.find('.swift-checkout-form-error').length) {
                            $parent.append(errorMsg);
                        }
                    }
                }
            });

            if (hasErrors) {
                // Scroll to the first error
                const $firstError = $('.swift-checkout-input-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                }
                return;
            }

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

            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: formData + '&action=swift_checkout_create_order&nonce=' + spcData.nonce,
                success: (response) => {
                    if (response.success) {
                        // Show success message or redirect
                        this.showOrderConfirmation(response.data);
                    } else {
                        // Display server-side validation errors
                        if (response.data && response.data.field_errors) {
                            // Handle field-specific errors
                            const fieldErrors = response.data.field_errors;
                            for (const [fieldName, errorMsg] of Object.entries(fieldErrors)) {
                                const $field = $(`[name="${fieldName}"]`);
                                if ($field.length) {
                                    $field.addClass('swift-checkout-input-error');
                                    const $parent = $field.closest('.swift-checkout-form-row');
                                    const errorElem = `<div class="swift-checkout-form-error">${errorMsg}</div>`;
                                    if (!$parent.find('.swift-checkout-form-error').length) {
                                        $parent.append(errorElem);
                                    }
                                }
                            }

                            // Scroll to the first error
                            const $firstError = $('.swift-checkout-input-error').first();
                            if ($firstError.length) {
                                $('html, body').animate({
                                    scrollTop: $firstError.offset().top - 100
                                }, 300);
                            }
                        } else {
                            // Display general error
                            console.error(response.data.message || 'Error creating order');
                        }
                    }
                },
                error: () => {
                    console.error('Error connecting to server');
                },
                complete: () => {
                    $submitButton.prop('disabled', false).removeClass('loading');
                }
            });
        }

        /**
         * Show order confirmation
         *
         * @param {Object} data Order data
         */
        showOrderConfirmation(data) {
            // Load order received content via AJAX
            $.ajax({
                url: spcData.ajax_url,
                type: 'POST',
                data: {
                    action: 'swift_checkout_get_order_received',
                    order_id: data.order_id,
                    nonce: spcData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Replace checkout content with order received content
                        $('.swift-checkout-container').html(response.data.html);
                    } else {
                        alert(response.data.message || 'Error loading order details');
                    }
                },
                error: () => {
                    alert('Error connecting to server');
                }
            });
        }

        /**
         * Update page fragments
         *
         * @param {Object} fragments HTML fragments to update
         */
        updateFragments(fragments) {
            if (!fragments) {
                return;
            }

            $.each(fragments, function(selector, content) {
                $(selector).replaceWith(content);
            });

            // Force update cart visibility after fragments update
            setTimeout(() => {
                this.updateCartVisibility();
            }, 100);

            $(document.body).trigger('swift_checkout_fragments_refreshed');
        }

        /**
         * Update cart visibility based on cart contents
         */
        updateCartVisibility() {
            const $cartItems = $('.swift-checkout-cart-item');
            const $placeOrderWrapper = $('.swift-checkout-place-order-wrapper');
            const $addToCartButtons = $('.swift-checkout-add-to-cart:not(.swift-checkout-refresh-cart)');
            const $refreshButtons = $('.swift-checkout-refresh-cart');
            const $selectOptionsButtons = $('.swift-checkout-select-options');

            if ($cartItems.length > 0) {
                // Show place order wrapper when we have items (which will show all child elements)
                $placeOrderWrapper.addClass('swift-checkout-visible');

                // Hide regular add to cart buttons, but keep refresh buttons visible
                $addToCartButtons.hide();
                $selectOptionsButtons.hide();
                $refreshButtons.show();
            }
            else {
                // Hide place order wrapper when cart is empty (which will hide all child elements)
                $placeOrderWrapper.removeClass('swift-checkout-visible');

                // Show all buttons when cart is empty
                $addToCartButtons.show();
                $selectOptionsButtons.show();
            }
        }

        /**
         * Toggle shipping address fields visibility
         */
        toggleShippingFields(e) {
            const $checkbox = $(e.currentTarget);
            const $shippingFields = $('#swift-checkout-shipping-address-fields');
            const $shippingLabel = $checkbox.closest('label');

            if ($checkbox.is(':checked')) {
                $shippingFields.slideDown(300, function() {
                    $shippingFields.addClass('shipping-active');
                });
                $shippingLabel.addClass('shipping-enabled');
            } else {
                $shippingFields.slideUp(300, function() {
                    $shippingFields.removeClass('shipping-active');
                });
                $shippingLabel.removeClass('shipping-enabled');
            }

            // Update shipping methods when shipping address option changes
            this.updateShippingMethods();
        }

        /**
         * Set the position of the place order button
         *
         * This function can be called by the user to move the button to a specific element
         *
         * @param {string} targetSelector - CSS selector for the target element to append the button to
         */
        setPlaceOrderPosition(targetSelector) {
            if (!targetSelector) return;

            const $button = $('.swift-checkout-place-order-wrapper');
            const $target = $(targetSelector);

            if ($button.length && $target.length) {
                $button.appendTo($target);
                $button.addClass('custom-position');
            }
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Create an instance of SwiftCheckout
        const swiftCheckout = new SwiftCheckout();

        // Expose the setPlaceOrderPosition function globally
        window.swiftCheckoutSetPlaceOrderPosition = swiftCheckout.setPlaceOrderPosition.bind(swiftCheckout);

        // Immediately update cart visibility state
        swiftCheckout.updateCartVisibility();

        // Initialize trigger for when fragments are refreshed
        $(document.body).on('wc_fragments_refreshed swift_checkout_fragments_refreshed', function() {
            swiftCheckout.updateCartVisibility();
        });

        // Enhance country selectors
        enhanceCountrySelectors();

        // Process each Swift Checkout container on the page

        // Initialize shipping fields visibility
        const $shippingCheckbox = $('#swift-checkout-shipping_address');
        if ($shippingCheckbox.length) {
            // Trigger the change event to set initial state
            $shippingCheckbox.trigger('change');
        }

        // Function to enhance country selectors with search functionality
        function enhanceCountrySelectors() {
            $('.swift-checkout-country-select').each(function() {
                const $select = $(this);
                const $wrapper = $select.closest('.swift-checkout-form-row');

                // Create search input
                const $searchContainer = $('<div class="swift-checkout-country-search-container"></div>');
                const $searchInput = $('<input type="text" class="swift-checkout-country-search" placeholder="Search country...">');

                // Create dropdown
                const $dropdown = $('<div class="swift-checkout-country-dropdown"></div>');
                const $optionsList = $('<ul class="swift-checkout-country-options"></ul>');

                // Add search and dropdown to DOM
                $searchContainer.append($searchInput);
                $searchContainer.append($dropdown);
                $dropdown.append($optionsList);
                $wrapper.append($searchContainer);

                // Create country options list
                $select.find('option').each(function() {
                    const $option = $(this);
                    if ($option.val()) {
                        const $li = $('<li data-value="' + $option.val() + '">' + $option.text() + '</li>');
                        $optionsList.append($li);
                    }
                });

                // Hide the original select
                $select.css('position', 'absolute').css('opacity', '0').css('pointer-events', 'none');

                // Show selected country in search input
                function updateSelectedCountry() {
                    const selectedVal = $select.val();
                    if (selectedVal) {
                        const selectedText = $select.find('option[value="' + selectedVal + '"]').text();
                        $searchInput.val(selectedText);
                    }
                }

                // Initialize with selected value
                updateSelectedCountry();

                // Handle search input focus
                $searchInput.on('focus', function() {
                    $dropdown.addClass('active');
                });

                // Handle search input blur
                $searchInput.on('blur', function() {
                    setTimeout(function() {
                        $dropdown.removeClass('active');
                    }, 200);
                });

                // Handle search input
                $searchInput.on('input', function() {
                    const searchText = $(this).val().toLowerCase();
                    $optionsList.find('li').each(function() {
                        const countryName = $(this).text().toLowerCase();
                        if (countryName.indexOf(searchText) > -1) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });

                // Handle option selection
                $optionsList.on('click', 'li', function() {
                    const value = $(this).data('value');
                    $select.val(value).trigger('change');
                    $searchInput.val($(this).text());
                    $dropdown.removeClass('active');
                });
            });
        }
    });

})(jQuery);