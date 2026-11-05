=== FirmAtlas for WooCommerce ===
Contributors: arnevandaele
Tags: belgium, vat, company, autocomplete, checkout
Requires at least: 6.6
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Belgian company search at checkout: pick the company and the billing address and VAT number fill themselves. Checks Belgian VAT numbers.

== Description ==

Customers type their company name or enterprise number in the checkout's company field and pick their company. The billing address and VAT number fill themselves, from the official KBO/BCE register.

* Company search in the classic and the block checkout
* An optional VAT number field
* Belgian VAT numbers checked against the KBO register and VIES; you choose whether an invalid number only adds an order note, refuses the order, or is not checked
* Checkout always works, even when the API cannot be reached
* The VAT number and enterprise number are saved on the order (`_billing_vat_number`, `_kbo_enterprise_number`) for invoicing and Peppol plugins
* HPOS compatible

= External service =

This plugin uses the Firmatlas API (https://api.firmatlas.be) by Firmatlas (https://firmatlas.be):

* In the browser, on the checkout page, while a customer types in the company field: the typed text is sent to look up matching companies.
* On the server, when a VAT number is checked: the VAT number is sent.

No other customer data is sent. Terms and privacy: https://firmatlas.be. Company data: KBO/BCE open data from the FPS Economy, S.M.E.s, Self-employed and Energy.

== Installation ==

1. Install and activate the plugin.
2. Get a publishable key (with your shop's address as an allowed origin) and a secret key at https://firmatlas.be.
3. Open WooCommerce → Settings → FirmAtlas, paste both keys, save, and press Test connection.

== Frequently Asked Questions ==

= Does an invalid VAT number block checkout? =

Only if you choose "Refuse the order" in the settings. By default the order goes through with an order note.

= What if the API is down? =

The company field works as a normal field and the VAT check counts as "unknown", which never blocks checkout.

= Are VAT numbers from other countries checked? =

No. They are saved on the order but not checked.

== Changelog ==

= 0.1.0 =
* First version: company search in the classic and block checkout, VAT number field and Belgian VAT check, order meta.
