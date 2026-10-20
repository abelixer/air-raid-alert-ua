(function () {
	'use strict';

	function refreshImage(image) {
		var source = image.getAttribute('data-ara-src');
		if (!source) {
			return;
		}

		var separator = source.indexOf('?') > -1 ? '&' : '?';
		image.src = source + separator + 't=' + Date.now();
	}

	function init() {
		var images = document.querySelectorAll('img[data-ara-image="1"][data-ara-src]');
		if (!images.length) {
			return;
		}

		window.setInterval(function () {
			if (document.hidden) {
				return;
			}

			var i;
			for (i = 0; i < images.length; i++) {
				refreshImage(images[i]);
			}
		}, 60000);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
