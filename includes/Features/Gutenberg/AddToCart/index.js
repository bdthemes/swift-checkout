import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";
import { registerBlockType } from "@wordpress/blocks";
import ServerSideRender from "@wordpress/server-side-render";
import { Spinner } from "@wordpress/components";
import metadata from "./block.json";
import Settings from "./settings";

/**
 * Internal dependencies
 */
import './style.scss';

/**
 * Register the block
 */
registerBlockType(metadata.name, {
	edit: function Edit({ attributes, setAttributes }) {
		const blockProps = useBlockProps();

		// Memoize the ServerSideRender component to prevent unnecessary re-renders
		const renderServerSide = attributes.productId && (
			<ServerSideRender
				block={metadata.name}
				attributes={attributes}
				httpMethod="POST"
				urlQueryArgs={{
					_locale: "user",
				}}
				LoadingResponsePlaceholder={() => <Spinner />}
				skipBlockSupportAttributes={true}
				key={JSON.stringify(attributes)} // Add key to force re-render only when attributes change
			/>
		);

		return (
			<div {...blockProps}>
				<Settings attributes={attributes} setAttributes={setAttributes} />
				{!attributes.productId && (
					<p>{__("Please select a product", "swift-checkout")}</p>
				)}
				{renderServerSide}
			</div>
		);
	},
});
