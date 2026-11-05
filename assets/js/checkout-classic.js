/* global jQuery, KboAutocomplete, firmatlasCheckout */
/**
 * Classic checkout: company search on the company fields, and a live VAT check.
 */
(function ($, config) {
	'use strict';

	function streetLine(address) {
		if (!address || !address.street) {
			return '';
		}
		var line = address.street + (address.house_number ? ' ' + address.house_number : '');
		return address.box ? line + ' ' + config.box + ' ' + address.box : line;
	}

	function fill(prefix, company) {
		var a = company.address || {};
		var country = a.country_code || '';
		if (country && $('#' + prefix + '_country').length) {
			$('#' + prefix + '_country').val(country).trigger('change');
		}
		var values = {
			company: company.name,
			address_1: streetLine(a),
			postcode: a.zipcode || '',
			city: a.municipality || ''
		};
		Object.keys(values).forEach(function (field) {
			var $el = $('#' + prefix + '_' + field);
			if ($el.length && values[field]) {
				$el.val(values[field]).trigger('change');
			}
		});
		if (prefix === 'billing' && config.vatField) {
			$('#billing_vat_number').val(company.vat_number).trigger('change');
		}
		$(document.body).trigger('update_checkout');
	}

	function attach(prefix) {
		var input = document.getElementById(prefix + '_company');
		if (!input || input.dataset.firmatlas) {
			return;
		}
		input.dataset.firmatlas = '1';
		KboAutocomplete.attach(input, {
			key: config.key,
			lang: config.lang,
			endpoint: config.endpoint,
			fields: { name: input },
			onSelect: function (company) {
				fill(prefix, company);
			}
		});
	}

	function hint($field) {
		var $hint = $field.siblings('.firmatlas-vat-hint');
		if (!$hint.length) {
			$hint = $('<span class="firmatlas-vat-hint" aria-live="polite"></span>').insertAfter($field);
		}
		return $hint;
	}

	var pending = null;
	function checkVat() {
		var $field = $(this);
		var value = String($field.val() || '').trim();
		var $hint = hint($field);
		if (pending) {
			pending.abort();
		}
		$hint.text('').removeClass('is-valid is-invalid');
		if (!value || config.validation === 'off') {
			return;
		}
		pending = $.get(config.vatUrl, { number: value }).done(function (r) {
			if (r.status === 'invalid') {
				$hint.text(r.message).addClass('is-invalid');
			} else if (r.status === 'valid') {
				$hint.text(config.i18n.valid).addClass('is-valid');
			}
		});
	}

	$(function () {
		if (config.autocomplete && window.KboAutocomplete) {
			attach('billing');
			attach('shipping');
			$(document.body).on('updated_checkout', function () {
				attach('billing');
				attach('shipping');
			});
		}
		if (config.vatField) {
			$(document.body).on('change', '#billing_vat_number', checkVat);
		}
	});
})(jQuery, firmatlasCheckout);
