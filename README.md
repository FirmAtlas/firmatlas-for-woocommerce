# FirmAtlas for WooCommerce

Belgian company search at checkout. Customers type their company name or enterprise number in the company field, pick the company, and the billing address and VAT number fill themselves. Belgian VAT numbers are checked against the KBO register and VIES. Company data comes from the KBO/BCE (FPS Economy) through the [Firmatlas](https://firmatlas.be) API.

Works with the classic (shortcode) checkout and the block checkout, and with HPOS order storage.

## What it does

- **Company search** on the billing (and shipping) company field, built on [@firmatlas/autocomplete](https://github.com/FirmAtlas/firmatlas-autocomplete). Choosing a company fills company name, street and number, postcode, city, country and VAT number.
- **VAT number field**, optional, after the company field (classic) or under contact information (block checkout).
- **VAT check** for Belgian numbers: the check digits first, then the KBO register and the last VIES answer. The store owner picks what happens with an invalid number:
  - **Accept the order and add an order note** (default)
  - **Refuse the order** until the number is fixed
  - **Don't check**
- If the API or VIES cannot answer, the check counts as "unknown" and checkout always goes ahead. VAT numbers from other countries are saved but not checked.
- With the classic checkout, the customer sees a hint under the VAT field as soon as they leave it ("Valid VAT number", or why it is not).

## Order meta

Invoicing plugins, including Peppol invoicing, can read these from the order (`$order->get_meta( … )`, HPOS and legacy storage alike):

| Key | Value | Example |
| --- | --- | --- |
| `_billing_vat_number` | The VAT number, normalized: uppercase, no spaces or dots | `BE0403170701` |
| `_kbo_enterprise_number` | The 10-digit Belgian enterprise number; only for Belgian numbers that did not fail the check | `0403170701` |
| `_firmatlas_vat_status` | Result of the check at checkout: `valid`, `invalid`, `unknown` (not checked or no answer) or `foreign` | `valid` |

The Peppol participant id of a Belgian company is `0208:` followed by `_kbo_enterprise_number`.

## Setup

1. Get a **publishable key** (`kbo_pk_live_…`) with your shop's address (e.g. `https://shop.example.be`) as an allowed origin, and a **secret key** (`kbo_sk_live_…`) at [firmatlas.be](https://firmatlas.be).
2. Install and activate the plugin, then open **WooCommerce → Settings → FirmAtlas**.
3. Paste both keys, save, and press **Test connection**.
4. Make sure checkout shows the company field: in the Checkout block's settings in the site editor, or under *Customize → WooCommerce → Checkout* for the classic checkout.

The secret key never leaves your server. The publishable key is in the page, which is fine: it only works for search and autocomplete, and only from your origins.

## Requirements

PHP 8.1+, WordPress 6.6+, WooCommerce 9.0+ (tested with WordPress 7.1 and WooCommerce 11.2).

## Develop

```sh
docker compose -f dev/docker-compose.yml up -d
sh dev/setup.sh      # WordPress, WooCommerce, a product, block checkout at /checkout, classic at /classic-checkout
```

http://localhost:8890, `admin` / `admin`. Point the plugin at a local Firmatlas gateway by setting its API address (*Advanced*) for the browser, and `FIRMATLAS_SERVER_API_URL` in `dev/.env` for PHP inside Docker (e.g. `http://host.docker.internal:18081`).

Checks: `php tests/vat-test.php`, and `phpcs` with the WordPress Coding Standards (`phpcs.xml.dist`). The release zip leaves out the files in `.distignore` (dev tooling and tests); run Plugin Check on that, not on the repository. Acceptance results: `docs/phase-5-acceptance.md`.

## Licence

GPL-2.0-or-later. The bundled widget (`assets/js/firmatlas-autocomplete.iife.js`) is MIT. Company data: KBO/BCE open data, FPS Economy, S.M.E.s, Self-employed and Energy. Not affiliated with or endorsed by the FPS Economy.
