import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit({ attributes, setAttributes, context }) {
    const {
    } = attributes;
    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Blocks Settings', 'roduza_helper')}>
                    <p>{__('This block is rendered dynamically via PHP.', 'roduza_helper')}</p>
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