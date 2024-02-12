'use strict'

// Add the switch and a text field to a new "Aria Labels" section
wp.hooks.addFilter(
	'blocks.registerBlockType',
	'my-plugin/add-aria-hidden-control',
	(settings, name) => {
		// If the block doesn't have any attributes, initialize it with an empty object
		if (!settings.attributes) {
			settings.attributes = {}
		}

		// Add ariaHidden attribute to the block
		settings.attributes.ariaHidden = {
			type: 'boolean',
			default: false
		}

		// Add ariaLabel attribute to the block
		settings.attributes.ariaLabel = {
			type: 'string',
			default: ''
		}

		// Store the original edit function
		const oldEdit = settings.edit

		// Replace the edit function with a higher order component
		settings.edit = wp.compose.createHigherOrderComponent(
			(BlockEdit) => (props) => {
				// Create a ToggleControl for the ariaHidden attribute
				const ariaHiddenToggle = React.createElement(
					wp.components.ToggleControl,
					{
						label: 'Aria Hidden',
						checked: props.attributes.ariaHidden,
						onChange: (newValue) => {
							// Update the ariaHidden attribute when the ToggleControl is toggled
							props.setAttributes({
								ariaHidden: newValue
							})
						}
					}
				)

				// Create a TextControl for the ariaLabel attribute
				const ariaLabelInput = React.createElement(
					wp.components.TextControl,
					{
						label: 'Aria Label',
						value: props.attributes.ariaLabel,
						onChange: (newValue) => {
							// Update the ariaLabel attribute when the TextControl value changes
							props.setAttributes({
								ariaLabel: newValue
							})
						}
					}
				)

				// Create a PanelBody that contains the ToggleControl and TextControl
				const panelBody = React.createElement(
					wp.components.PanelBody,
					{ title: 'Aria Labels', initialOpen: true },
					ariaHiddenToggle,
					ariaLabelInput
				)

				// Create InspectorControls that contains the PanelBody
				const inspectorControls = React.createElement(
					wp.blockEditor.InspectorControls,
					{},
					panelBody
				)

				// Return the original BlockEdit component along with the new InspectorControls
				return React.createElement(
					React.Fragment,
					{},
					React.createElement(BlockEdit, props),
					inspectorControls
				)
			}
		)(oldEdit)

		// Replace the save function to include the ARIA labels
		settings.save = wp.compose.createHigherOrderComponent((BlockSave) => {
			return (props) => {
				const { attributes } = props
				const { ariaHidden, ariaLabel } = attributes

				// If ariaHidden is true, add 'aria-hidden' attribute to the block
				const ariaHiddenAttr = ariaHidden
					? { 'aria-hidden': 'true' }
					: {}

				// If ariaLabel is not empty, add 'aria-label' attribute to the block
				const ariaLabelAttr = ariaLabel
					? { 'aria-label': ariaLabel }
					: {}

				// Return the original BlockSave component along with the new ARIA attributes
				return React.createElement(BlockSave, {
					...props,
					...ariaHiddenAttr,
					...ariaLabelAttr
				})
			}
		}, 'withAriaLabels')(settings.save)

		// Return the modified block settings
		return settings
	}
)
