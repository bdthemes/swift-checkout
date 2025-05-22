import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

const Settings = ({ attributes, setAttributes }) => {
    const { content, tag, productId, stylePreset } = attributes;

    // Get all products for the dropdown
const products = useSelect((select) => {
    return select('core').getEntityRecords('postType', 'product', {
        per_page: -1,
        _fields: ['id', 'title'],
        status: 'publish',
        stock_status: 'instock',
    });
}, []);

    // Format products for SelectControl options
    const productOptions = products ? products.map(product => ({
        label: product.title.rendered,
        value: product.id.toString()
    })) : [];

    return (
        <InspectorControls>
            <PanelBody title={__('Add to Cart Settings', 'swift-checkout')}>

               <SelectControl
                    label={__('Select Preset', 'swift-checkout')}
                    value={stylePreset}
                    options={[
                        { label: __('Simple', 'swift-checkout'), value: 'simple' },
                        { label: __('Modern', 'swift-checkout'), value: 'modern' },
                    ]}
                    onChange={(value) => setAttributes({ stylePreset: value })}
                />
                    
                <SelectControl
                    label={__('Select Product', 'swift-checkout')}
                    value={productId?.toString()}
                    options={[
                        { label: __('Select a product', 'swift-checkout'), value: '' },
                        ...productOptions
                    ]}
                    onChange={(value) => setAttributes({ productId: parseInt(value) })}
                    help={__('Choose the product to add to cart', 'swift-checkout')}
                />
    
                
            </PanelBody>
        </InspectorControls>
    )
};

export default Settings;