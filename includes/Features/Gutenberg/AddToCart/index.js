import { __ } from "@wordpress/i18n";
import { useBlockProps } from "@wordpress/block-editor";
import { registerBlockType } from "@wordpress/blocks";
import ServerSideRender from "@wordpress/server-side-render";
import { Spinner } from "@wordpress/components";
import metadata from "./block.json";
import Settings from "./settings";

registerBlockType(metadata.name, {
	edit: function Edit({ attributes, setAttributes }) {
		const blockProps = useBlockProps();

		return (
			<>
				<div {...blockProps}>
					<Settings attributes={attributes} setAttributes={setAttributes} />
					{!attributes.productId && (
						<div {...blockProps}>
							<p>{__("Please select a product", "swift-checkout")}</p>
						</div>
					)}
					{attributes.productId && (
						<ServerSideRender
							block={metadata?.name}
							attributes={attributes}
							httpMethod="POST"
							urlQueryArgs={{
								_locale: "user",
							}}
							LoadingResponsePlaceholder={() => <Spinner />}
							skipBlockSupportAttributes={true}
						/>
					)}
				</div>
			</>
		);
	},
});
