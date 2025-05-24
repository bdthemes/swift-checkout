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

			// First pass to identify phone and email for grouping
			if ($use_custom_fields) {
				foreach ($fields_to_display as $field) {
					if (isset($field['field_type'])) {
						if ($field['field_type'] === 'phone') {
							$has_phone = true;
						}
						if ($field['field_type'] === 'email') {
							$has_email = true;
						}
					}
				}
			}

			// Should we group phone and email?
			$group_phone_email = $has_phone && $has_email;
			$in_group = false;

			foreach ($fields_to_display as $field) :
				if (empty($field['field_type'])) {
					continue;
				}

				$type = $field['field_type'];
				$required = isset($field['field_required']) && $field['field_required'] === 'yes';
				$label = !empty($field['field_label']) ? $field['field_label'] : '';
				$placeholder = isset($field['field_placeholder']) ? $field['field_placeholder'] : ' ';

				// Start phone/email group if needed
				if ($group_phone_email && ($type === 'phone' || $type === 'email') && !$in_group) {
					echo '<div class="spc-input-group">';
					$in_group = true;
				}

				// Determine input type based on field_type
				$input_type = 'text';
				if ($type === 'email') {
					$input_type = 'email';
				} elseif ($type === 'phone') {
					$input_type = 'tel';
				}

				// Render the field
				if ($type === 'address') {
					// Address is a textarea
			?>
					<div class="spc-form-row spc-form-row-address">
						<textarea id="spc-address" name="address" class="spc-form-input" rows="3" <?php echo $required ? 'required' : ''; ?> placeholder="<?php echo htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'); ?>"></textarea>
						<label for="spc-address" class="spc-form-label">
							<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
						</label>
					</div>
				<?php
				} else {
					// Regular input field
				?>
					<div class="spc-form-row spc-form-row-<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
						<input type="<?php echo htmlspecialchars($input_type, ENT_QUOTES, 'UTF-8'); ?>" id="spc-<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" name="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" class="spc-form-input" <?php echo $required ? 'required' : ''; ?> placeholder="<?php echo htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'); ?>">
						<label for="spc-<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>" class="spc-form-label">
							<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?> <?php echo $required ? '<span class="required">*</span>' : ''; ?>
						</label>
					</div>
			<?php
				}

				// Close phone/email group if needed
				if ($group_phone_email && $in_group && $type === 'email') {
					echo '</div>';
					$in_group = false;
				}
			endforeach;

			// Close group if still open
			if ($in_group) {
				echo '</div>';
			}
			?>
		</div>
		<div class="spc-form-section">
			<div class="spc-form-row spc-form-row-submit">
				<button type="submit" id="spc-submit-order" class="spc-submit-order" name="spc_submit_order">
					Place Order
				</button>
				<div class="spc-checkout-error"></div>
			</div>
		</div>
	</form>
</div>