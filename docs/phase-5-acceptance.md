# Phase 5 acceptance: FirmAtlas for WooCommerce

Run on 2026-11-09 against a local Firmatlas engine with the full KBO dataset.

## Setup

- WordPress 7.1.4 (first run on 7.0.4), WooCommerce 11.2.2, PHP 8.3.36, MariaDB 11.4, theme Twenty Twenty-Five 1.5. Clean install from `dev/docker-compose.yml` and `dev/setup.sh`.
- Order storage: first run on legacy posts storage (the default after a WP-CLI install), then again with HPOS on.
- Browser: Chromium 158 headless in `mcr.microsoft.com/playwright:v1.65.0-noble`. A virtual product in the cart, Cash on delivery.
- Engine at `host.docker.internal:8080`. The engine sends no CORS headers (the gateway does that), so a small nginx proxy that only adds them stood in for the gateway. The engine does not check keys, so dummy publishable and secret keys were used. The gateway's key and origin rules were not tested.
- The plugin's *API address* setting pointed browser and server at the proxy (`FIRMATLAS_SERVER_API_URL` left empty).

## Results

| # | Criterion | Result | Evidence |
| --- | --- | --- | --- |
| 1 | Fills and validates in classic and block checkout on a clean install | Pass | Both checkouts, on WP 7.0.4 and 7.1.4, posts storage and HPOS. Typing "electric indu" in the company field and picking ELECTRIC INDUSTRIES filled company, `Goede Herderstraat 54 box A001`, 1140, Evere, BE and VAT `BE1034565871` (block checkout: also in the `wc/store/cart` billing address). With "Refuse the order", `BE0403170702` (bad check digits) was refused in both checkouts; `BE0403170701` was accepted and the order placed. Classic shows the live hint ("This is not a valid…", then "Valid VAT number"). With "Accept and add a note", `BE0123456749` (not registered) was accepted in both checkouts, with status `invalid` and the FirmAtlas order note. The REST check also answered `foreign` for an NL number and `valid` for a number whose VIES answer was stale (fresh check through `refresh=true`). |
| 2 | Plugin Check and PHPCS with the WordPress coding standards | Pass with caveat | Plugin Check 2.2.0 on the release files (repo minus `.distignore`): no errors or warnings, also with low-severity and experimental checks. On the repository itself it flags only dev files that are not shipped: `.gitignore`, `.distignore`, `dev/.env` (hidden files), `dev/setup.sh`, `phpcs.xml.dist` (application files) and `tests/vat-test.php`. PHPCS 3.13.6, WPCS 3.4.1 and PHPCompatibilityWP 2.1.8 with `phpcs.xml.dist`: 0 errors, 0 warnings. The first runs found an outdated `Tested up to` header (Plugin Check), and 5 errors and 3 warnings in `includes/` plus the test script's findings (PHPCS); all fixed below. |
| 3 | Order meta keys written and documented in the README | Pass | Seen on every order, in posts storage and in `wp_wc_orders_meta`: `_billing_vat_number` (`BE0403170701`), `_kbo_enterprise_number` (`0403170701`, left out for an invalid number) and `_firmatlas_vat_status` (`valid`, `invalid`, `unknown`). All three are in the README's *Order meta* table. The block checkout also gets WooCommerce's own `_wc_other/firmatlas/vat-number`. |
| 4 | With the API unreachable, checkout still completes | Pass | API address set to a refused port (`fa-p5-cors:9`) and to an unroutable address (`10.255.255.1:9`), with "Refuse the order" on and the VAT cache cleared. Orders placed in both checkouts, VAT status `unknown`, enterprise number kept, no JS errors. Refused port: 0.2 s. Unroutable: 4.2 s in both checkouts (one 4 s timeout). |

## Fixes made

- `includes/class-validator.php`: results are kept for the rest of the request. With an unroutable API, the classic checkout's order request timed out twice (8.3 s), because the live hint's request had written the same cached answer in the meantime and WordPress's `add_option` then left its cache empty. Now 4.2 s.
- `readme.txt`: `Tested up to: 7.1` (Plugin Check error: 7.0 is older than the current 7.1). README says "tested with WordPress 7.1"; `dev/docker-compose.yml` uses `wordpress:7.1-php8.3-apache`.
- `includes/class-checkout-blocks.php`: parameter `$object` renamed to `$wc_data` (reserved keyword warning); docblock alignment.
- `includes/class-api.php`, `includes/class-order-meta.php`: alignment (phpcbf).
- `phpcs.xml.dist` (new): WordPress and PHPCompatibilityWP rules, text domain, prefixes, `manage_woocommerce` as a known capability. `tests/` (command-line scripts, not shipped) is exempt from the prefix, escaping and debug-function sniffs.
- `.distignore` (new): leaves dev tooling, tests, docs and the PHPCS config out of the release zip.
- README: how to run the checks and where these results are.

## Open

- Not tested through the real gateway: publishable-key origin rules, secret-key auth on VAT checks, the gateway's CORS. The *Test connection* button was not tried.
- With an unroutable API, placing an order waits up to 4 s for the VAT check. If the API answers but the stored VIES result is stale, a second call can wait up to 7 s more.
- Only one browser (Chromium), English locale. NL and FR translations are not shipped yet (there is no `languages/` folder); the spec asks for them.
- The block checkout has no live VAT hint; an invalid number shows only when the order is placed. This matches the README.
- Typing in the company field in the first half second after the cart page loads can lose characters: WooCommerce's "added to cart" notice takes focus. This is WooCommerce behaviour, not the plugin's.
- Engine note, not the plugin: autocomplete and search found nothing for "electrabel" or "proximus" on the local index, though `/v1/enterprises/0403170701` works. The local search index looks incomplete.
