import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';

const Settings = ({ attributes, setAttributes }) => {
    const { productId, stylePreset, auto_add_to_cart } = attributes;

    // Get all products for the dropdown
    const products = useSelect((select) => {
        return select('core').getEntityRecords('postType', 'product', {
            per_page: -1,
            _fields: ['id', 'title'],
            status: 'publish',
        });
    }, []);

    // Memoize product options to prevent unnecessary re-renders
    const productOptions = useMemo(() => {
        if (!products) return [];
        return products.map(product => ({
            label: product.title.rendered,
            value: product.id.toString()
        }));
    }, [products]);

    return (
        <InspectorControls>
            <PanelBody title={__('Add to Cart Settings', 'swift-checkout')}>
                <SelectControl
                    label={__('Product', 'swift-checkout')}
                    value={productId}
                    options={[
                        { label: __('Select a product...', 'swift-checkout'), value: '' },
                        ...productOptions
                    ]}
                    onChange={(value) => setAttributes({ productId: parseInt(value) })}
                />
                <SelectControl
                    label={__('Style Preset', 'swift-checkout')}
                    value={stylePreset}
                    options={[
                        { label: __('Simple', 'swift-checkout'), value: 'simple' },
                        { label: __('Modern', 'swift-checkout'), value: 'modern' }
                    ]}
                    onChange={(value) => setAttributes({ stylePreset: value })}
                />
                <ToggleControl
                    label={__('Auto Add to Cart', 'swift-checkout')}
                    help={__('Automatically add the selected product to cart when page loads', 'swift-checkout')}
                    checked={auto_add_to_cart}
                    onChange={(value) => setAttributes({ auto_add_to_cart: value })}
                />
            </PanelBody>
        </InspectorControls>
    );
};

export default Settings;