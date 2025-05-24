import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, TextControl, Button, Flex, FlexItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { times, remove } from 'lodash';

const Settings = ({ attributes, setAttributes }) => {
    const { productId, stylePreset, auto_add_to_cart, enable_custom_fields, checkout_fields } = attributes;

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

    // Helper function to update a checkout field
    const updateCheckoutField = (index, key, value) => {
        const updatedFields = [...checkout_fields];
        updatedFields[index] = {
            ...updatedFields[index],
            [key]: value,
        };
        setAttributes({ checkout_fields: updatedFields });
    };

    // Helper function to add a new checkout field
    const addCheckoutField = () => {
        const newField = {
            field_type: 'name',
            field_required: true,
            field_label: '',
            field_placeholder: '',
        };
        setAttributes({ checkout_fields: [...checkout_fields, newField] });
    };

    // Helper function to remove a checkout field
    const removeCheckoutField = (index) => {
        const updatedFields = [...checkout_fields];
        updatedFields.splice(index, 1);
        setAttributes({ checkout_fields: updatedFields });
    };

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

            <PanelBody title={__('Checkout Fields', 'swift-checkout')} initialOpen={false}>
                <ToggleControl
                    label={__('Customize Checkout Fields', 'swift-checkout')}
                    help={__('Enable to customize which checkout fields to display', 'swift-checkout')}
                    checked={enable_custom_fields}
                    onChange={(value) => setAttributes({ enable_custom_fields: value })}
                />

                {enable_custom_fields && (
                    <>
                        {times(checkout_fields.length, (index) => {
                            const field = checkout_fields[index];
                            return (
                                <div key={index} className="swift-checkout-field-item" style={{ marginBottom: '15px', padding: '10px', border: '1px solid #ddd', borderRadius: '4px' }}>
                                    <SelectControl
                                        label={__('Field Type', 'swift-checkout')}
                                        value={field.field_type}
                                        options={[
                                            { label: __('Full Name', 'swift-checkout'), value: 'name' },
                                            { label: __('Phone', 'swift-checkout'), value: 'phone' },
                                            { label: __('Email Address', 'swift-checkout'), value: 'email' },
                                            { label: __('Full Address', 'swift-checkout'), value: 'address' },
                                        ]}
                                        onChange={(value) => updateCheckoutField(index, 'field_type', value)}
                                    />
                                    <ToggleControl
                                        label={__('Required', 'swift-checkout')}
                                        checked={field.field_required}
                                        onChange={(value) => updateCheckoutField(index, 'field_required', value)}
                                    />
                                    <TextControl
                                        label={__('Field Label', 'swift-checkout')}
                                        value={field.field_label}
                                        onChange={(value) => updateCheckoutField(index, 'field_label', value)}
                                    />
                                    <TextControl
                                        label={__('Placeholder', 'swift-checkout')}
                                        value={field.field_placeholder}
                                        onChange={(value) => updateCheckoutField(index, 'field_placeholder', value)}
                                    />
                                    <Button
                                        isDestructive
                                        onClick={() => removeCheckoutField(index)}
                                        style={{ marginTop: '10px' }}
                                    >
                                        {__('Remove Field', 'swift-checkout')}
                                    </Button>
                                </div>
                            );
                        })}

                        <Button
                            variant="secondary"
                            onClick={addCheckoutField}
                            style={{ marginTop: '10px' }}
                        >
                            {__('Add Field', 'swift-checkout')}
                        </Button>
                    </>
                )}
            </PanelBody>
        </InspectorControls>
    );
};

export default Settings;