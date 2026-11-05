#!/bin/sh
# Installs WordPress and WooCommerce in the dev stack, with a product, cash on delivery,
# the company field on, a block checkout (/checkout) and a classic one (/classic-checkout).
set -e
cd "$(dirname "$0")"
wp() { docker compose -f docker-compose.yml run --rm -T cli wp "$@"; }
wp core is-installed 2>/dev/null || wp core install --url=http://localhost:8890 --title="Firmatlas test shop" \
  --admin_user=admin --admin_password=admin --admin_email=admin@example.test --skip-email
wp plugin is-installed woocommerce || wp plugin install woocommerce --activate
wp plugin activate woocommerce
wp option update woocommerce_default_country "BE"
wp option update woocommerce_currency "EUR"
wp option update woocommerce_coming_soon "no"
wp option update woocommerce_checkout_company_field "optional"
wp option update woocommerce_onboarding_profile '{"skipped":true}' --format=json
wp option update woocommerce_cod_settings '{"enabled":"yes","title":"Cash on delivery"}' --format=json
wp wc product list --user=admin --format=count | grep -qv '^0$' || wp wc product create --user=admin \
  --name="Test product" --type=simple --regular_price=10 --virtual=true
wp post list --post_type=page --name=classic-checkout --format=count | grep -qv '^0$' || wp post create \
  --post_type=page --post_status=publish --post_title="Classic checkout" --post_name=classic-checkout \
  --post_content='[woocommerce_checkout]'
wp rewrite structure '/%postname%/' --hard
wp plugin activate firmatlas-for-woocommerce || true
echo "ready: http://localhost:8890 (admin / admin)"
