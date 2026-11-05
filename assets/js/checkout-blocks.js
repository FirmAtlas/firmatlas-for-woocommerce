/* global wp, KboAutocomplete, firmatlasCheckout */
/**
 * Block checkout: company search on the company fields. A selection goes into
 * the checkout's own data stores, so React and the Store API see it.
 */
(function (data, config) {
	'use strict';

	var blocksData = (window.wc && window.wc.wcBlocksData) || {};
	var CART = blocksData.CART_STORE_KEY || 'wc/store/cart';
	var CHECKOUT = blocksData.CHECKOUT_STORE_KEY || 'wc/store/checkout';

	function streetLine(address) {
		if (!address || !address.street) {
			return '';
		}
		var line = address.street + (address.house_number ? ' ' + address.house_number : '');
		return address.box ? line + ' ' + config.box + ' ' + address.box : line;
	}

	function addressFor(company) {
		var a = company.address || {};
		var out = { company: company.name };
		if (a.street) {
			out.address_1 = streetLine(a);
		}
		if (a.zipcode) {
			out.postcode = a.zipcode;
		}
		if (a.municipality) {
			out.city = a.municipality;
		}
		if (a.country_code) {
			out.country = a.country_code;
		}
		return out;
	}

	function select(kind, company) {
		var cart = data.dispatch(CART);
		var address = addressFor(company);
		if (kind === 'shipping') {
			cart.setShippingAddress(address);
			var useShipping = data.select(CHECKOUT).getUseShippingAsBilling && data.select(CHECKOUT).getUseShippingAsBilling();
			if (useShipping) {
				cart.setBillingAddress(address);
			}
		} else {
			cart.setBillingAddress(address);
		}
		if (config.vatField) {
			var fields = {};
			fields[config.field] = company.vat_number;
			data.dispatch(CHECKOUT).setAdditionalFields(fields);
		}
	}

	function attach(input, kind) {
		if (input.firmatlasWidget) {
			return;
		}
		input.firmatlasWidget = KboAutocomplete.attach(input, {
			key: config.key,
			lang: config.lang,
			endpoint: config.endpoint,
			fields: { name: input },
			onSelect: function (company) {
				select(kind, company);
			}
		});
	}

	function scan() {
		var billing = document.getElementById('billing-company');
		var shipping = document.getElementById('shipping-company');
		if (billing) {
			attach(billing, 'billing');
		}
		if (shipping) {
			attach(shipping, 'shipping');
		}
	}

	if (!window.KboAutocomplete || !data) {
		return;
	}
	new MutationObserver(scan).observe(document.body, { childList: true, subtree: true });
	scan();
})(window.wp && window.wp.data, firmatlasCheckout);
