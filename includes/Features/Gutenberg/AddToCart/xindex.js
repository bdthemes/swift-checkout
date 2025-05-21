import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";
import { registerBlockType } from "@wordpress/blocks";
import { useSelect } from "@wordpress/data";
import metadata from "./block.json";
import Settings from "./settings";

registerBlockType(metadata.name, {
	edit: function Edit({ attributes, setAttributes }) {
		const blockProps = useBlockProps({
			className: "swift-checkout-add-to-cart",
		});

		// Get product data from WooCommerce
		const product = useSelect(
			(select) => {
				const store = select("core");
				const productId = attributes.productId || 104; // Default to 66 if not set
				return store.getEntityRecord("postType", "product", productId);
			},
			[attributes.productId]
		);

		// Get product price
		const price = useSelect(
			(select) => {
				if (!product) return null;
				return select("core").getEntityRecord("postType", "product", product.id)
					?.meta?._price;
			},
			[product]
		);

		return (
			<>
				<Settings attributes={attributes} setAttributes={setAttributes} />
				<div {...blockProps}>
					<div className="spc-container" data-builder="gutenberg">
						<div className="spc-product-card" data-product-id={product?.id}>
							{product && (
								<>
									<button
										className="spc-add-to-cart"
										data-product-id={product.id}
									>
										{attributes.content || "Add to Cart"}
									</button>
								</>
							)}
						</div>
						<div className="spc-mini-cart">
							<h2 className="spc-mini-cart-title">Your Cart</h2>
							<div className="spc-mini-cart-contents">
								<table className="spc-cart-items">
									<thead>
										<tr>
											<th className="product-name">Product</th>
											<th className="product-price">Price</th>
											<th className="product-quantity">Quantity</th>
											<th className="product-subtotal">Subtotal</th>
											<th
												className="product-remove"
												style={{ textAlign: "right" }}
											>
												Remove
											</th>
										</tr>
									</thead>
									<tbody>
										{product && (
											<tr className="spc-cart-item" data-item-key={product.id}>
												<td className="product-name">
													{product.title?.rendered}
												</td>
												<td className="product-price">
													<span className="woocommerce-Price-amount amount">
														{price}
														<span className="woocommerce-Price-currencySymbol">
															৳&nbsp;
														</span>
													</span>
												</td>
												<td className="product-quantity">
													<div className="spc-quantity">
														<button
															className="spc-qty-minus"
															data-item-key={product.id}
														>
															–
														</button>
														<input
															type="number"
															min="1"
															className="spc-qty-input"
															value="1"
															data-item-key={product.id}
														/>
														<button
															className="spc-qty-plus"
															data-item-key={product.id}
														>
															+
														</button>
													</div>
												</td>
												<td className="product-subtotal">
													<span className="woocommerce-Price-amount amount">
														{price}
														<span className="woocommerce-Price-currencySymbol">
															৳&nbsp;
														</span>
													</span>
												</td>
												<td
													className="product-remove"
													style={{ textAlign: "right" }}
												>
													<button
														className="spc-remove-item"
														data-item-key={product.id}
													>
														×
													</button>
												</td>
											</tr>
										)}
									</tbody>
									<tfoot>
										<tr>
											<td colSpan="3" className="cart-subtotal-label">
												Total
											</td>
											<td colSpan="2" className="cart-subtotal-value">
												<span className="woocommerce-Price-amount amount">
													{price}
													<span className="woocommerce-Price-currencySymbol">
														৳&nbsp;
													</span>
												</span>
											</td>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
						<div className="spc-checkout-form">
							<h2 className="spc-checkout-title">Contact Information</h2>
							<form id="spc-checkout-form" method="post">
								<div className="spc-form-section">
									<div className="spc-form-row spc-form-row-name">
										<label htmlFor="spc-name" className="spc-form-label">
											Full Name <span className="required">*</span>
										</label>
										<input
											type="text"
											id="spc-name"
											name="name"
											className="spc-form-input"
											required
										/>
									</div>
									<div className="spc-input-group">
										<div className="spc-form-row spc-form-row-phone">
											<label htmlFor="spc-phone" className="spc-form-label">
												Phone <span className="required">*</span>
											</label>
											<input
												type="tel"
												id="spc-phone"
												name="phone"
												className="spc-form-input"
												required
											/>
										</div>
										<div className="spc-form-row spc-form-row-email">
											<label htmlFor="spc-email" className="spc-form-label">
												Email Address (Optional)
											</label>
											<input
												type="email"
												id="spc-email"
												name="email"
												className="spc-form-input"
											/>
										</div>
									</div>
									<div className="spc-form-row spc-form-row-address">
										<label htmlFor="spc-address" className="spc-form-label">
											Full Address <span className="required">*</span>
										</label>
										<textarea
											id="spc-address"
											name="address"
											className="spc-form-input"
											rows="3"
											required
										/>
									</div>
								</div>
								<div className="spc-form-section">
									<div className="spc-form-row spc-form-row-submit">
										<button
											type="submit"
											id="spc-submit-order"
											className="spc-submit-order"
											name="spc_submit_order"
										>
											Place Order
										</button>
										<div className="spc-checkout-error" />
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</>
		);
	},
});
