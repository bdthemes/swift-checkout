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

<div class="spc-checkout-form">
	<h2 class="spc-checkout-title">Contact Information</h2>
	<form id="spc-checkout-form" method="post">
		<div class="spc-form-section">
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

				$type = $field['field_type'];
				$required = isset($field['field_required']) && ($field['field_required'] === 'yes' || $field['field_required'] === true);
				$label = !empty($field['field_label']) ? $field['field_label'] : '';
				$placeholder = isset($field['field_placeholder']) ? $field['field_placeholder'] : ' ';

				// Start phone/email group if needed
				if ($group_phone_email && ($type === 'phone') && !$in_phone_email_group) {
					echo '<div class="spc-input-group">';
					$in_phone_email_group = true;
				}

				// Start first_name/last_name group if needed
				if ($group_name_fields && ($type === 'first_name') && !$in_name_group) {
					echo '<div class="spc-input-group">';
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
					<div class="spc-form-row spc-form-row-<?php echo esc_attr($type); ?>">
						<textarea id="spc-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="spc-form-input" rows="3" <?php echo $required ? 'required' : ''; ?> placeholder="<?php echo esc_attr($placeholder); ?>"></textarea>
						<label for="spc-<?php echo esc_attr($type); ?>" class="spc-form-label">
							<?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
						</label>
					</div>
				<?php
				} elseif ($type === 'country') {
					// Country dropdown
				?>
					<div class="spc-form-row spc-form-row-<?php echo esc_attr($type); ?>">
						<select id="spc-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="spc-form-input" <?php echo $required ? 'required' : ''; ?>>
							<option value=""><?php echo esc_html($placeholder ? $placeholder : 'Select a country'); ?></option>
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
						<label for="spc-<?php echo esc_attr($type); ?>" class="spc-form-label">
							<?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
						</label>
					</div>
				<?php
				} elseif ($type === 'state') {
					// State/County dropdown
				?>
					<div class="spc-form-row spc-form-row-<?php echo esc_attr($type); ?>">
						<select id="spc-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="spc-form-input" <?php echo $required ? 'required' : ''; ?>>
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
						<label for="spc-<?php echo esc_attr($type); ?>" class="spc-form-label">
							<?php echo esc_html($label); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
						</label>
					</div>
				<?php
				} elseif ($type === 'create_account') {
					// Checkbox fields
				?>
					<div class="spc-form-row spc-form-row-<?php echo esc_attr($type); ?> spc-checkbox-row">
						<label for="spc-<?php echo esc_attr($type); ?>" class="spc-checkbox-label">
							<input type="checkbox" id="spc-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="spc-checkbox-input" <?php echo $required ? 'required' : ''; ?>>
							<?php echo esc_html($label ? $label : 'Create an account?'); ?>
						</label>
					</div>
				<?php
				} elseif ($type === 'shipping_address') {
					// Checkbox for different shipping address
				?>
					<div class="spc-form-row spc-form-row-<?php echo esc_attr($type); ?> spc-checkbox-row">
						<label for="spc-shipping_address" class="spc-checkbox-label">
							<input type="checkbox" id="spc-shipping_address" name="shipping_address" class="spc-checkbox-input" <?php echo $required ? 'required' : ''; ?>>
							<?php echo esc_html($label ? $label : 'Ship to a different address?'); ?>
						</label>
					</div>

					<!-- Shipping address form - hidden by default -->
					<div id="spc-shipping-address-fields" class="spc-shipping-address-fields" style="display: none;">
						<h3 class="spc-shipping-title">Shipping Address</h3>

						<!-- First Name and Last Name in a group -->
						<div class="spc-input-group">
							<!-- First Name -->
							<div class="spc-form-row">
								<input type="text" id="spc-shipping_first_name" name="shipping_first_name" class="spc-form-input" placeholder=" ">
								<label for="spc-shipping_first_name" class="spc-form-label">First Name</label>
							</div>

							<!-- Last Name -->
							<div class="spc-form-row">
								<input type="text" id="spc-shipping_last_name" name="shipping_last_name" class="spc-form-input" placeholder=" ">
								<label for="spc-shipping_last_name" class="spc-form-label">Last Name</label>
							</div>
						</div>

						<!-- Address Line 1 -->
						<div class="spc-form-row">
							<input type="text" id="spc-shipping_address_1" name="shipping_address_1" class="spc-form-input" placeholder=" ">
							<label for="spc-shipping_address_1" class="spc-form-label">Street Address</label>
						</div>

						<!-- Address Line 2 -->
						<div class="spc-form-row">
							<input type="text" id="spc-shipping_address_2" name="shipping_address_2" class="spc-form-input" placeholder=" ">
							<label for="spc-shipping_address_2" class="spc-form-label">Apartment, suite, etc. (Optional)</label>
						</div>

						<!-- City -->
						<div class="spc-form-row">
							<input type="text" id="spc-shipping_city" name="shipping_city" class="spc-form-input" placeholder=" ">
							<label for="spc-shipping_city" class="spc-form-label">City</label>
						</div>

						<!-- State -->
						<div class="spc-form-row">
							<select id="spc-shipping_state" name="shipping_state" class="spc-form-input">
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
							<label for="spc-shipping_state" class="spc-form-label">State/County</label>
						</div>

						<!-- Postcode -->
						<div class="spc-form-row">
							<input type="text" id="spc-shipping_postcode" name="shipping_postcode" class="spc-form-input" placeholder=" ">
							<label for="spc-shipping_postcode" class="spc-form-label">ZIP / Postal Code</label>
						</div>

						<!-- Country -->
						<div class="spc-form-row">
							<select id="spc-shipping_country" name="shipping_country" class="spc-form-input">
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
							<label for="spc-shipping_country" class="spc-form-label">Country</label>
						</div>
					</div>
				<?php
				} else {
					// Regular input field
				?>
					<div class="spc-form-row spc-form-row-<?php echo esc_attr($type); ?>">
						<input type="<?php echo esc_attr($input_type); ?>" id="spc-<?php echo esc_attr($type); ?>" name="<?php echo esc_attr($type); ?>" class="spc-form-input" <?php echo $required ? 'required' : ''; ?> placeholder="<?php echo esc_attr($placeholder); ?>">
						<label for="spc-<?php echo esc_attr($type); ?>" class="spc-form-label">
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
		<div class="spc-form-section">
			<!-- Shipping Methods Section -->
			<?php include 'cart-totals.php'; ?>

			<div class="spc-form-row spc-form-row-submit">
				<button type="submit" id="spc-submit-order" class="spc-submit-order" name="spc_submit_order">
					<?php esc_html_e('Place Order', 'swift-checkout'); ?>
				</button>
				<div class="spc-checkout-error"></div>
			</div>
		</div>
	</form>
</div>