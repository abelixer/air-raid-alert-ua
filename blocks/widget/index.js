(function (blocks, element, blockEditor, components, serverSideRender, i18n) {
	'use strict';

	var el = element.createElement;
	var Fragment = element.Fragment;
	var __ = i18n.__;
	var ServerSideRender = serverSideRender && serverSideRender.default ? serverSideRender.default : serverSideRender;
	var data = window.araBlockData || {};
	var regions = data.regions || [];
	var types = data.types || [];
	var sizes = data.sizes || [];
	var themes = data.themes || [];
	var defaults = data.defaults || {};

	function valueOr(attributes, key, fallback) {
		return attributes[key] !== undefined && attributes[key] !== null ? attributes[key] : fallback;
	}

	function regionControl(attributes, setAttributes, isUa) {
		var props = {
			label: __('Region', 'air-raid-alert-ua'),
			value: String(valueOr(attributes, 'region', defaults.region || 0)),
			options: regions,
			disabled: isUa,
			help: isUa ? __('Whole-Ukraine view does not use a region.', 'air-raid-alert-ua') : undefined,
			onChange: function (value) {
				setAttributes({ region: parseInt(value, 10) || 0 });
			}
		};

		if (components.ComboboxControl) {
			return el(components.ComboboxControl, props);
		}

		return el(components.SelectControl, props);
	}

	function edit(props) {
		var attributes = props.attributes;
		var setAttributes = props.setAttributes;
		var blockProps = blockEditor.useBlockProps ? blockEditor.useBlockProps() : {};
		var isUa = valueOr(attributes, 'type', defaults.type) === 'ua';

		var inspector = el(
			blockEditor.InspectorControls,
			{},
			el(
				components.PanelBody,
				{
					title: __('Widget settings', 'air-raid-alert-ua'),
					initialOpen: true
				},
				regionControl(attributes, setAttributes, isUa),
				el(components.SelectControl, {
					label: __('Type', 'air-raid-alert-ua'),
					value: valueOr(attributes, 'type', defaults.type),
					options: types,
					onChange: function (value) {
						setAttributes({ type: value });
					}
				}),
				el(components.SelectControl, {
					label: __('Size', 'air-raid-alert-ua'),
					value: valueOr(attributes, 'size', defaults.size),
					options: sizes,
					onChange: function (value) {
						setAttributes({ size: value });
					}
				}),
				el(components.SelectControl, {
					label: __('Theme', 'air-raid-alert-ua'),
					value: valueOr(attributes, 'theme', defaults.theme),
					options: themes,
					onChange: function (value) {
						setAttributes({ theme: value });
					}
				}),
				el(components.ToggleControl, {
					label: __('Use image fallback (CSP-safe)', 'air-raid-alert-ua'),
					checked: !!valueOr(attributes, 'fallbackImage', defaults.fallback_image),
					onChange: function (value) {
						setAttributes({ fallbackImage: !!value });
					}
				}),
				el(components.ToggleControl, {
					label: __('Show source attribution', 'air-raid-alert-ua'),
					checked: !!valueOr(attributes, 'attribution', defaults.attribution),
					onChange: function (value) {
						setAttributes({ attribution: !!value });
					}
				})
			)
		);

		var notice = null;
		if (!data.enabled) {
			notice = el(
				components.Notice,
				{ status: 'warning', isDismissible: false },
				__('The Air Raid Alert widget is disabled in the plugin settings.', 'air-raid-alert-ua')
			);
		}

		var preview = ServerSideRender
			? el(ServerSideRender, {
				block: 'ara/air-raid-alert',
				attributes: attributes
			})
			: el('p', {}, __('Widget preview is unavailable.', 'air-raid-alert-ua'));

		return el(
			Fragment,
			{},
			inspector,
			el(
				'div',
				blockProps,
				el(
					'div',
					{ className: 'ara-block-preview' },
					notice,
					preview
				)
			)
		);
	}

	blocks.registerBlockType('ara/air-raid-alert', {
		edit: edit,
		save: function () {
			return null;
		}
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender, window.wp.i18n);
