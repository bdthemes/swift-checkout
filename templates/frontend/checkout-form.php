<?php

/**
 * Checkout form template
 *
 * @package swift_checkout
 */


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Default fields if not configured through Elementor
$default_fields = array(
	array(
		'field_type' => 'name',
		'field_required' => 'yes',
		'field_label' => 'Full Name',
		'field_placeholder' => ''
	),
	array(
		'field_type' => 'phone',
		'field_required' => 'yes',
		'field_label' => 'Phone',
		'field_placeholder' => ''
	),
	array(
		'field_type' => 'email',
		'field_required' => 'no',
		'field_label' => 'Email Address (Optional)',
		'field_placeholder' => ''
	),
	array(
		'field_type' => 'address',
		'field_required' => 'yes',
		'field_label' => 'Full Address',
		'field_placeholder' => ''
	)
);

// Use custom fields if enabled and available, otherwise use default fields
$use_custom_fields = isset($enable_custom_fields) && $enable_custom_fields === 'yes' && !empty($checkout_fields);
$fields_to_display = $use_custom_fields ? $checkout_fields : $default_fields;
?>

<div class="swift-checkout-checkout-form">
	<h2 class="swift-checkout-checkout-title">Contact Information</h2>
	<form id="swift-checkout-checkout-form" method="post">
		<div class="swift-checkout-form-section">
			<?php
			// Track if we have phone and email for putting them in the same row
			$has_phone = false;
			$has_email = false;
			$has_first_name = false;
			$has_last_name = false;

			// First pass to identify phone and email for grouping
			foreach ($fields_to_display as $field) {
				if (isset($field['field_type'])) {
					if ($field['field_type'] === 'phone') {
						$has_phone = true;
					}
					if ($field['field_type'] === 'email') {
						$has_email = true;
					}
					if ($field['field_type'] === 'first_name') {
						$has_first_name = true;
					}
					if ($field['field_type'] === 'last_name') {
						$has_last_name = true;
					}
				}
			}

			// Should we group phone and email?
			$group_phone_email = $has_phone && $has_email;
			$group_name_fields = $has_first_name && $has_last_name;
			$in_phone_email_group = false;
			$in_name_group = false;

			foreach ($fields_to_display as $field) :
				if (empty($field['field_type'])) {
					continue;
				}

				// Skip shipping_method
				if (isset($field['field_type']) && $field['field_type'] === 'shipping_method') {
					continue;
				}

				$type = $field['field_type'];
				$required = isset($field['field_required']) && ($field['field_required'] === 'yes' || $field['field_required'] === true);
				$label = !empty($field['field_label']) ? $field['field_label'] : '';
				$placeholder = isset($field['field_placeholder']) ? $field['field_placeholder'] : ' ';

				// Start phone/email group if needed
				if ($group_phone_email && ($type === 'phone') && !$in_phone_email_group) {
					echo '<div class="swift-checkout-input-group">';
					$in_phone_email_group = true;
				}

				// Start first_name/last_name group if needed
				if ($group_name_fields && ($type === 'first_name') && !$in_name_group) {
					echo '<div class="swift-checkout-input-group">';
					$in_name_group = true;
				}

				// Determine input type based on field_type
				$input_type = 'text';
				if ($type === 'email') {
					$input_type = 'email';
				} elseif ($type === 'phone') {
					$input_type = 'tel';
				} elseif ($type === 'postcode') {
					$input_type = 'text';
				}

				// Render the field
				if ($type === 'address' || $type === 'order_notes') {
					// These fields use textarea
			?>
					<div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?>">
						<textarea id="swift-checkout-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="swift-checkout-form-input" rows="3" <?php echo $required ? 'required' : ''; ?> placeholder="<?php echo esc_attr($placeholder); ?>"></textarea>
						<label for="swift-checkout-<?php echo esc_attr($type); ?>" class="swift-checkout-form-label">
							<?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
						</label>
					</div>
				<?php
				} elseif ($type === 'country') {
					// Country dropdown with list of countries
				?>
					<div class="swift-checkout-form-row swift-checkout-form-row-country">
						<select id="swift-checkout-country" name="country" class="swift-checkout-form-input swift-checkout-country-select" <?php echo $required ? 'required' : ''; ?>>
							<option value=""><?php esc_html_e('Select country', 'swift-checkout'); ?></option>
							<?php
							// Get countries from WooCommerce
							if (function_exists('WC')) {
								$countries = WC()->countries->get_countries();
								$base_country = WC()->countries->get_base_country();

								// Show the base country first
								if (!empty($base_country) && isset($countries[$base_country])) {
									echo '<option value="' . esc_attr($base_country) . '" selected>' . esc_html($countries[$base_country]) . '</option>';
									unset($countries[$base_country]);
								}

								// Show the rest of the countries
								foreach ($countries as $code => $name) {
									echo '<option value="' . esc_attr($code) . '">' . esc_html($name) . '</option>';
								}
							}
							?>
						</select>
						<label for="swift-checkout-country" class="swift-checkout-form-label">
							<?php echo esc_html($label ? $label : __('Country', 'swift-checkout')); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
						</label>
					</div>
				<?php
				} elseif ($type === 'state') {
					// State/County dropdown
				?>
					<div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?>">
						<select id="swift-checkout-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="swift-checkout-form-input" <?php echo $required ? 'required' : ''; ?>>
							<option value=""><?php echo esc_html($placeholder ? $placeholder : 'Select a state'); ?></option>
							<?php
							// Get states from WooCommerce if available
							$states = array();
							if (function_exists('WC')) {
								$wc = WC();
								if (isset($wc->countries) && is_object($wc->countries)) {
									$base_country = $wc->countries->get_base_country();
									$states = $wc->countries->get_states($base_country);
								}
							}

							// Display states
							foreach ($states as $code => $state_name) {
								echo '<option value="' . esc_attr($code) . '">' . esc_html($state_name) . '</option>';
							}
							?>
						</select>
						<label for="swift-checkout-<?php echo esc_attr($type); ?>" class="swift-checkout-form-label">
							<?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
						</label>
					</div>
				<?php
				} elseif ($type === 'create_account') {
					// Checkbox fields
				?>
					<div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?> swift-checkout-checkbox-row">
						<label for="swift-checkout-<?php echo esc_attr($type); ?>" class="swift-checkout-checkbox-label">
							<input type="checkbox" id="swift-checkout-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="swift-checkout-checkbox-input" <?php echo $required ? 'required' : ''; ?>>
							<?php echo esc_html($label ? $label : 'Create an account?'); ?>
						</label>
					</div>
				<?php
				} elseif ($type === 'shipping_address') {
					// Shipping Address Toggle
				?>
					<div class="swift-checkout-shipping-toggle">
						<label for="swift-checkout-shipping_address">
							<input type="checkbox" id="swift-checkout-shipping_address" name="shipping_address" value="1" class="swift-checkout-form-checkbox">
							<?php esc_html_e('Ship to a different address?', 'swift-checkout'); ?>
						</label>
					</div>

					<!-- Shipping Address Fields (hidden by default) -->
					<div id="swift-checkout-shipping-address-fields" style="display: none;">
						<h3 class="swift-checkout-shipping-address-title"><?php esc_html_e('Shipping Address', 'swift-checkout'); ?></h3>

						<!-- First Name and Last Name in a group -->
						<div class="swift-checkout-input-group">
							<!-- First Name -->
							<div class="swift-checkout-form-row">
								<input type="text" id="swift-checkout-shipping_first_name" name="shipping_first_name" class="swift-checkout-form-input" placeholder=" ">
								<label for="swift-checkout-shipping_first_name" class="swift-checkout-form-label">First Name</label>
							</div>

							<!-- Last Name -->
							<div class="swift-checkout-form-row">
								<input type="text" id="swift-checkout-shipping_last_name" name="shipping_last_name" class="swift-checkout-form-input" placeholder=" ">
								<label for="swift-checkout-shipping_last_name" class="swift-checkout-form-label">Last Name</label>
							</div>
						</div>

						<!-- Address Line 1 -->
						<div class="swift-checkout-form-row">
							<input type="text" id="swift-checkout-shipping_address_1" name="shipping_address_1" class="swift-checkout-form-input" placeholder=" ">
							<label for="swift-checkout-shipping_address_1" class="swift-checkout-form-label">Street Address</label>
						</div>

						<!-- Address Line 2 -->
						<div class="swift-checkout-form-row">
							<input type="text" id="swift-checkout-shipping_address_2" name="shipping_address_2" class="swift-checkout-form-input" placeholder=" ">
							<label for="swift-checkout-shipping_address_2" class="swift-checkout-form-label">Apartment, suite, etc. (Optional)</label>
						</div>

						<!-- City -->
						<div class="swift-checkout-form-row">
							<input type="text" id="swift-checkout-shipping_city" name="shipping_city" class="swift-checkout-form-input" placeholder=" ">
							<label for="swift-checkout-shipping_city" class="swift-checkout-form-label">City</label>
						</div>

						<!-- State -->
						<div class="swift-checkout-form-row">
							<select id="swift-checkout-shipping_state" name="shipping_state" class="swift-checkout-form-input">
								<option value="">Select a state</option>
								<?php
								// Get states from WooCommerce if available
								$states = array();
								if (function_exists('WC')) {
									$wc = WC();
									if (isset($wc->countries) && is_object($wc->countries)) {
										$base_country = $wc->countries->get_base_country();
										$states = $wc->countries->get_states($base_country);
									}
								}

								// Display states
								foreach ($states as $code => $state_name) {
									echo '<option value="' . esc_attr($code) . '">' . esc_html($state_name) . '</option>';
								}
								?>
							</select>
							<label for="swift-checkout-shipping_state" class="swift-checkout-form-label">State/County</label>
						</div>

						<!-- Postcode -->
						<div class="swift-checkout-form-row">
							<input type="text" id="swift-checkout-shipping_postcode" name="shipping_postcode" class="swift-checkout-form-input" placeholder=" ">
							<label for="swift-checkout-shipping_postcode" class="swift-checkout-form-label">ZIP / Postal Code</label>
						</div>

						<!-- Country -->
						<div class="swift-checkout-form-row">
							<select id="swift-checkout-shipping_country" name="shipping_country" class="swift-checkout-form-input">
								<option value="">Select a country</option>
								<?php
								// Get countries from WooCommerce if available
								$countries = array();
								if (function_exists('WC')) {
									$wc = WC();
									if (isset($wc->countries) && is_object($wc->countries)) {
										$countries = $wc->countries->get_countries();
									}
								}

								// Display countries
								foreach ($countries as $code => $country_name) {
									echo '<option value="' . esc_attr($code) . '">' . esc_html($country_name) . '</option>';
								}
								?>
							</select>
							<label for="swift-checkout-shipping_country" class="swift-checkout-form-label">Country</label>
						</div>
					</div>
				<?php
				} else {
					// Regular input field
				?>
					<div class="swift-checkout-form-row swift-checkout-form-row-<?php echo esc_attr($type); ?>">
						<input type="<?php echo esc_attr($input_type); ?>" id="swift-checkout-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="swift-checkout-form-input" <?php echo $required ? 'required' : ''; ?> placeholder="<?php echo esc_attr($placeholder); ?>">
						<label for="swift-checkout-<?php echo esc_attr($type); ?>" class="swift-checkout-form-label">
							<?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
						</label>
					</div>
			<?php
				}

				// Close phone/email group if needed
				if ($group_phone_email && $in_phone_email_group && $type === 'email') {
					echo '</div>';
					$in_phone_email_group = false;
				}

				// Close first_name/last_name group if needed
				if ($group_name_fields && $in_name_group && $type === 'last_name') {
					echo '</div>';
					$in_name_group = false;
				}
			endforeach;

			// Close groups if still open
			if ($in_phone_email_group) {
				echo '</div>';
			}
			if ($in_name_group) {
				echo '</div>';
			}
			?>
		</div>
		<div class="swift-checkout-form-section">
			<!-- Shipping Methods Section -->

			<?php
			// Check for shipping_method in fields array
			$shipping_method_enabled = false;
			foreach ($fields_to_display as $field) {
				if (isset($field['field_type']) && $field['field_type'] === 'shipping_method') {
					$shipping_method_enabled = true;
					break;
				}
			}

			if ($shipping_method_enabled) {
				include 'shipping-methods.php';
			}
			?>

			<div class="swift-checkout-form-row swift-checkout-form-row-submit">
				<div class="swift-checkout-checkout-error"></div>
			</div>
		</div>
	</form>
</div>