(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	function toggleColors() {
		var checkbox = document.getElementById('ara-use-custom-colors');
		var panel = document.getElementById('ara-colors-panel');

		if (!checkbox || !panel) {
			return;
		}

		panel.classList.toggle('is-disabled', !checkbox.checked);
	}

	function highlightInheritedFields() {
		var inherit = document.getElementById('ara-inherit');
		if (!inherit) {
			return;
		}

		var form = inherit.closest('form');
		if (!form) {
			return;
		}

		var shouldDim = inherit.checked;
		var rows = form.querySelectorAll('.form-table tr');
		var i;

		for (i = 0; i < rows.length; i++) {
			var row = rows[i];
			if (row.contains(inherit) || row.getAttribute('data-ara-inherit-ignore') === '1') {
				continue;
			}
			row.classList.toggle('ara-row-inherited', shouldDim);
			row.style.opacity = shouldDim ? '0.65' : '';
		}
	}

	function initRegionSelects(context) {
		var $ = window.jQuery;
		var messages = window.araAdminData || {};

		if (!$ || !$.fn || !$.fn.select2) {
			return;
		}

		function message(key, fallback) {
			return messages[key] || fallback;
		}

		$(context || document)
			.find('.ara-region-select')
			.addBack('.ara-region-select')
			.each(function () {
				var $select = $(this);

				if ($select.hasClass('select2-hidden-accessible')) {
					return;
				}

				$select.select2({
					width: '100%',
					language: {
						noResults: function () {
							return message('noResults', 'No results found.');
						},
						searching: function () {
							return message('searching', 'Searching…');
						},
						inputTooShort: function () {
							return message('inputTooShort', 'Please enter 1 or more characters.');
						}
					}
				});
			});
	}

	ready(function () {
		var checkbox = document.getElementById('ara-use-custom-colors');
		if (checkbox) {
			checkbox.addEventListener('change', toggleColors);
			toggleColors();
		}

		var inherit = document.getElementById('ara-inherit');
		if (inherit) {
			inherit.addEventListener('change', highlightInheritedFields);
			highlightInheritedFields();
		}

		initRegionSelects(document);

		if (window.jQuery) {
			window.jQuery(document).on('widget-added widget-updated', function (event, widget) {
				initRegionSelects(widget);
			});
		}
	});
})();
