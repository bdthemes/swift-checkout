<?php

/**
 * Checkout form template
 *
 * @package swift_checkout
 */


if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
?>

<div class="spc-checkout-form">
	<h2 class="spc-checkout-title">Contact Information</h2>
	<form id="spc-checkout-form" method="post">
		<div class="spc-form-section">

			<div class="spc-form-row spc-form-row-name">
				<label for="spc-name" class="spc-form-label">
					Full Name <span class="required">*</span>
				</label>
				<input type="text" id="spc-name" name="name" class="spc-form-input" required>
			</div>


			<div class="spc-input-group">

				<div class="spc-form-row spc-form-row-phone">
					<label for="spc-phone" class="spc-form-label">
						Phone <span class="required">*</span>
					</label>
					<input type="tel" id="spc-phone" name="phone" class="spc-form-input" required>
				</div>

				<div class="spc-form-row spc-form-row-email">
					<label for="spc-email" class="spc-form-label">
						Email Address (Optional)
					</label>
					<input type="email" id="spc-email" name="email" class="spc-form-input">
				</div>
			</div>
			<div class="spc-form-row spc-form-row-address">
				<label for="spc-address" class="spc-form-label">
					Full Address <span class="required">*</span>
				</label>
				<textarea id="spc-address" name="address" class="spc-form-input" rows="3" required></textarea>
			</div>
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