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
				const ariaHiddenToggle = wp.element.createElement(
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
				const ariaLabelInput = wp.element.createElement(
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
				const panelBody = wp.element.createElement(
					wp.components.PanelBody,
					{ title: 'Aria Labels', initialOpen: true },
					ariaHiddenToggle,
					ariaLabelInput
				)

				// Create InspectorControls that contains the PanelBody
				const inspectorControls = wp.element.createElement(
					wp.blockEditor.InspectorControls,
					{},
					panelBody
				)

				// Return the original BlockEdit component along with the new InspectorControls
				return wp.element.createElement(
					wp.element.Fragment,
					{},
					wp.element.createElement(BlockEdit, props),
					inspectorControls
				)
			},
			'withAriaControls'
		)(oldEdit)

		// Store the original save function
		const oldSave = settings.save

		// Replace the save function to include the ARIA labels
		settings.save = (props) => {
			const { attributes } = props
			const { ariaHidden, ariaLabel } = attributes

			// If ariaHidden is true, add 'aria-hidden' attribute to the block
			const ariaHiddenAttr = ariaHidden ? { 'aria-hidden': 'true' } : {}

			// If ariaLabel is not empty, add 'aria-label' attribute to the block
			const ariaLabelAttr = ariaLabel ? { 'aria-label': ariaLabel } : {}

			// Return the original BlockSave component along with the new ARIA attributes
			return wp.element.createElement(oldSave, {
				...props,
				...ariaHiddenAttr,
				...ariaLabelAttr
			})
		}

		// Return the modified block settings
		return settings
	}
)
