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
	});
})();
