/* global firmatlasAdmin */
/**
 * Settings tab: the connection test button.
 */
(function (config) {
	'use strict';
	var button = document.getElementById('firmatlas-test');
	var out = document.getElementById('firmatlas-test-result');
	if (!button || !out) {
		return;
	}
	button.addEventListener('click', function () {
		out.textContent = config.testing;
		var body = new URLSearchParams({ action: 'firmatlas_test', nonce: button.dataset.nonce });
		fetch(config.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (r) {
				return r.json();
			})
			.then(function (r) {
				out.textContent = (r.success ? '✓ ' : '✗ ') + ((r.data && r.data.message) || '');
				out.style.color = r.success ? '#007017' : '#b32d2e';
			})
			.catch(function () {
				out.textContent = '✗';
			});
	});
})(firmatlasAdmin);
