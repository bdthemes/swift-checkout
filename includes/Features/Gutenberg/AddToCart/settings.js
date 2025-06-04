import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, TextControl, Button, Flex, FlexItem, DragDropContext, Draggable } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useMemo, useState } from '@wordpress/element';
import { times, remove } from 'lodash';
import { arrayMoveItem } from '@wordpress/rich-text';

const Settings = ({ attributes, setAttributes }) => {
    const { productId, stylePreset, auto_add_to_cart, enable_custom_fields, checkout_fields, cartButtonAlignment } = attributes;

    // Track which fields are expanded
    const [expandedFields, setExpandedFields] = useState({});

    // Toggle field expanded state
    const toggleFieldExpanded = (index) => {
        setExpandedFields({
            ...expandedFields,
            [index]: !expandedFields[index]
        });
    };

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
        const newIndex = checkout_fields.length;
        setAttributes({ checkout_fields: [...checkout_fields, newField] });

        // Auto-expand new field
        setTimeout(() => {
            setExpandedFields({
                ...expandedFields,
                [newIndex]: true
            });
        }, 100);
    };

    // Helper function to remove a checkout field
    const removeCheckoutField = (index) => {
        const updatedFields = [...checkout_fields];
        updatedFields.splice(index, 1);
        setAttributes({ checkout_fields: updatedFields });

        // Remove from expanded state
        const newExpandedFields = { ...expandedFields };
        delete newExpandedFields[index];
        setExpandedFields(newExpandedFields);
    };

    // Helper function to move fields (drag and drop)
    const moveField = (oldIndex, newIndex) => {
        if (oldIndex === newIndex) return;

        const updatedFields = [...checkout_fields];
        const movedField = updatedFields.splice(oldIndex, 1)[0];
        updatedFields.splice(newIndex, 0, movedField);

        setAttributes({ checkout_fields: updatedFields });

        // Update expanded states to match new indices
        const newExpandedFields = {};
        Object.keys(expandedFields).forEach(key => {
            const keyNum = parseInt(key);
            if (keyNum === oldIndex) {
                newExpandedFields[newIndex] = expandedFields[keyNum];
            } else if (keyNum > oldIndex && keyNum <= newIndex) {
                newExpandedFields[keyNum - 1] = expandedFields[keyNum];
            } else if (keyNum < oldIndex && keyNum >= newIndex) {
                newExpandedFields[keyNum + 1] = expandedFields[keyNum];
            } else {
                newExpandedFields[keyNum] = expandedFields[keyNum];
            }
        });
        setExpandedFields(newExpandedFields);
    };

    // Function to handle field dragging
    const onDragEnd = (result) => {
        // Dropped outside the list
        if (!result.destination) {
            return;
        }

        moveField(result.source.index, result.destination.index);
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
                <SelectControl
                    label={__('Cart Button Alignment', 'swift-checkout')}
                    value={cartButtonAlignment}
                    options={[
                        { label: __('Button Left', 'swift-checkout'), value: 'button-left' },
                        { label: __('Button Right', 'swift-checkout'), value: 'button-right' },
                        { label: __('Button Center', 'swift-checkout'), value: 'button-center' },
                        { label: __('Button Justify', 'swift-checkout'), value: 'button-justify' }
                    ]}
                    onChange={(value) => setAttributes({ cartButtonAlignment: value })}
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
                        <div className="swift-checkout-fields-container">
                            {times(checkout_fields.length, (index) => {
                                const field = checkout_fields[index];
                                const isExpanded = expandedFields[index] || false;

                                return (
                                    <div
                                        key={index}
                                        className="swift-checkout-field-item"
                                        draggable="true"
                                        data-field-type={field.field_type}
                                        onDragStart={(e) => {
                                            e.dataTransfer.setData('text/plain', index);
                                            e.currentTarget.classList.add('is-dragging');
                                        }}
                                        onDragEnd={(e) => {
                                            e.currentTarget.classList.remove('is-dragging');
                                        }}
                                        onDragOver={(e) => {
                                            e.preventDefault();
                                            e.currentTarget.classList.add('drag-over');
                                        }}
                                        onDragLeave={(e) => {
                                            e.currentTarget.classList.remove('drag-over');
                                        }}
                                        onDrop={(e) => {
                                            e.preventDefault();
                                            e.currentTarget.classList.remove('drag-over');
                                            const oldIndex = parseInt(e.dataTransfer.getData('text/plain'));
                                            moveField(oldIndex, index);
                                        }}
                                    >
                                        <div
                                            className="swift-checkout-field-header"
                                            onClick={() => toggleFieldExpanded(index)}
                                        >
                                            <div className="swift-checkout-field-drag-handle" title={__('Drag to reorder', 'swift-checkout')}>
                                                <span className="dashicons dashicons-menu"></span>
                                            </div>
                                            <div className="swift-checkout-field-title">
                                                {field.field_label || __('Unnamed Field', 'swift-checkout')}
                                            </div>
                                            <div className="swift-checkout-field-actions">
                                                <span
                                                    className={`dashicons ${isExpanded ? 'dashicons-arrow-up' : 'dashicons-arrow-down'}`}
                                                    title={isExpanded ? __('Collapse', 'swift-checkout') : __('Expand', 'swift-checkout')}
                                                ></span>
                                                <Button
                                                    isSmall
                                                    isDestructive
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        removeCheckoutField(index);
                                                    }}
                                                    icon="no-alt"
                                                    title={__('Remove Field', 'swift-checkout')}
                                                />
                                            </div>
                                        </div>
                                        {isExpanded && (
                                            <div className="swift-checkout-field-content">
                                                <SelectControl
                                                    label={__('Field Type', 'swift-checkout')}
                                                    value={field.field_type}
                                                    options={[
                                                        // Personal fields
                                                        { label: __('Full Name', 'swift-checkout'), value: 'name' },
                                                        { label: __('First Name', 'swift-checkout'), value: 'first_name' },
                                                        { label: __('Last Name', 'swift-checkout'), value: 'last_name' },
                                                        { label: __('Phone', 'swift-checkout'), value: 'phone' },
                                                        { label: __('Email Address', 'swift-checkout'), value: 'email' },
                                                        { label: __('Company Name', 'swift-checkout'), value: 'company' },

                                                        // Address fields
                                                        { label: __('Full Address', 'swift-checkout'), value: 'address' },
                                                        { label: __('Address Line 1', 'swift-checkout'), value: 'address_1' },
                                                        { label: __('Address Line 2', 'swift-checkout'), value: 'address_2' },
                                                        { label: __('City', 'swift-checkout'), value: 'city' },
                                                        { label: __('State/County', 'swift-checkout'), value: 'state' },
                                                        { label: __('Postcode/ZIP', 'swift-checkout'), value: 'postcode' },
                                                        { label: __('Country', 'swift-checkout'), value: 'country' },

                                                        // Order fields
                                                        { label: __('Order Notes', 'swift-checkout'), value: 'order_notes' },
                                                        // { label: __('Create Account', 'swift-checkout'), value: 'create_account' },
                                                        { label: __('Different Shipping Address', 'swift-checkout'), value: 'shipping_address' },
                                                        { label: __('Shipping Method', 'swift-checkout'), value: 'shipping_method' },
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
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        <Button
                            variant="secondary"
                            onClick={addCheckoutField}
                            style={{ marginTop: '15px' }}
                            icon="plus"
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