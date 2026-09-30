import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    InspectorControls,
    PanelColorSettings,
} from '@wordpress/block-editor';
import {
    PanelBody,
    RangeControl,
    SelectControl,
    Spinner,
    Notice,
    ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit({ attributes, setAttributes }) {
    const {
        numberOfItems,
        categoryToDisplay,
        headingColor,
    } = attributes;
    const blockProps = useBlockProps();
    // 0 means "show all items".
    const showAll = numberOfItems === 0;

    const terms = useSelect(
        (select) =>
            select(coreStore).getEntityRecords('taxonomy', 'collection-category', {
                per_page: -1,
                hide_empty: false,
            }),
        []
    );

    const categoryOptions = [
        { label: __('All collections', 'roduza-helper'), value: '' },
        ...(terms || []).map((term) => ({ label: term.name, value: term.slug })),
    ];

    const isMissingCategory =
        Array.isArray(terms) &&
        categoryToDisplay !== '' &&
        !terms.some((term) => term.slug === categoryToDisplay);

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Block Settings', 'roduza-helper')}>
                    <ToggleControl
                        label={__('Show all items', 'roduza-helper')}
                        checked={showAll}
                        onChange={(checked) => setAttributes({ numberOfItems: checked ? 0 : 4 })}
                    />
                    {!showAll && (
                        <RangeControl
                            label={__('Number of items', 'roduza-helper')}
                            min={1}
                            max={24}
                            value={numberOfItems}
                            onChange={(value) => setAttributes({ numberOfItems: value ?? 4 })}
                        />
                    )}
                    {terms === null ? (
                        <Spinner />
                    ) : (
                        <SelectControl
                            label={__('Category', 'roduza-helper')}
                            value={categoryToDisplay}
                            options={categoryOptions}
                            onChange={(value) => setAttributes({ categoryToDisplay: value })}
                        />
                    )}
                    {isMissingCategory && (
                        <Notice status="warning" isDismissible={false}>
                            {__('The selected category no longer exists. Choose another one.', 'roduza-helper')}
                        </Notice>
                    )}
                </PanelBody>
                <PanelColorSettings
                    title={__('Colours', 'roduza-helper')}
                    colorSettings={[
                        {
                            label: __('Heading colour', 'roduza-helper'),
                            value: headingColor,
                            onChange: (value) => setAttributes({ headingColor: value ?? '' }),
                        },
                    ]}
                />
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
