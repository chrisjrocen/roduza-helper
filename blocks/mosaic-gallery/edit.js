import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit({ attributes, setAttributes }) {
    const {
        numberOfItems,
        categoryToDisplay,
        headingColor,
    } = attributes;
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Block Settings', 'roduza_helper')}>
                    <TextControl
                        label={__('Number of items', 'roduza_helper')}
                        type="number"
                        min={1}
                        value={numberOfItems}
                        onChange={(value) => setAttributes({ numberOfItems: value })}
                    />
                    <TextControl
                        label={__('Category', 'roduza_helper')}
                        value={categoryToDisplay}
                        onChange={(value) => setAttributes({ categoryToDisplay: value })}
                    />
                    <TextControl
                        label={__('Heading Color', 'roduza_helper')}
                        value={headingColor}
                        onChange={(value) => setAttributes({ headingColor: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <ServerSideRender
                    block="roduza-helper/mosaic-gallery"
                    attributes={attributes}
                />
            </div>
        </>
    );
}