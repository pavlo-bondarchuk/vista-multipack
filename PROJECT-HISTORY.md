# Project history

## 2026-07-23 — Starting audit

Status: completed before implementation.

- The site runs WooCommerce 10.7.0 with 83 simple products and no variable
  products.
- Global WooCommerce stock management is enabled, but every existing product
  currently has product-level stock management disabled.
- `XML for Google Merchant Center` 4.3.0 is installed and is the current
  WordPress.org release.
- Product 21626 stores `_xfgmc_multipack = 7`.
- Feed 1 stores `xfgmc_multipack = disabled`, so the feed plugin intentionally
  skips `g:multipack`.
- The Russian WPML translation of product 21626 has no multipack meta value.
- The generated feed path is stored in the database, but the XML file is absent
  from the restored local uploads directory.
- KeyCRM reads order quantities, so pack lines must preserve the real unit
  quantity instead of representing a pack as one stock unit.
- The theme contains hard-coded bulk-price notices for selected product IDs.

## 2026-07-23 — Approved architecture

Status: approved.

- Build the feature as an isolated custom plugin.
- Provide standard unit and fixed-size pack purchase options.
- Store the pack's real unit quantity in the WooCommerce cart and order.
- Display pack counts to customers while retaining unit counts for stock,
  refunds, and external integrations.
- Keep the feed plugin unchanged and integrate through its public filters.
- Preserve the standard feed offer and append a separate Merchant pack offer
  with a total price, unique ID, pack landing link, and `g:multipack`.
- Track only this plugin and the root `.gitignore` in Git.

## 2026-07-23 — Implementation started

Status: completed.

- Initialized a Git repository at the WordPress root.
- Added a whitelist `.gitignore` that tracks only `.gitignore` and
  `wp-content/plugins/vista-multipack/**`.
- Added the plugin bootstrap, product data model, admin fields, storefront
  presentation, cart/order handling, WPML field configuration, and XML feed
  compatibility layer.
- Added Ukrainian and Russian translations for storefront, cart, order, admin,
  and feed strings.
- Kept the existing feed plugin files unchanged.

## 2026-07-23 — Integration verification

Status: passed.

- Activated Vista Multipack 1.0.0 locally.
- Confirmed migration of product 21626 from `_xfgmc_multipack = 7` to an
  enabled pack with a size of seven. No commercial pack price was invented.
- Confirmed that an enabled pack without a pack price does not render a price,
  button, or feed offer.
- Temporarily used a pack price of 4,200 UAH for testing and removed it after
  the tests.
- Confirmed Ukrainian and Russian product pages show localized pack price and
  button text through WPML.
- Confirmed one pack adds seven real units and remains a separate cart line
  from a standard single-unit purchase.
- Confirmed the cart displays one pack and seven total units.
- Confirmed changing the cart from one pack to two packs stores 14 real units
  and changes the subtotal from 4,200 UAH to 8,400 UAH.
- Confirmed order item metadata stores mode, pack size, pack price, and the
  human-readable pack count while order quantity remains seven units.
- Confirmed native stock behavior with an isolated temporary product:
  stock changed from 20 to 15 for one five-unit pack and returned to 20 when
  stock was restored. The temporary product and order were deleted afterward.
- Regenerated the real feed using the feed plugin's full generation command.
  With the temporary test price, the valid XML contained both the original
  offer and one pack offer:
  `21626-multipack-7`, `4200 UAH`, and `<g:multipack>7</g:multipack>`.
- Removed the temporary pack price and regenerated the feed again. The final
  XML is valid and correctly contains no pack offer until an administrator
  enters the real pack price.
- PHP syntax checks passed for every plugin PHP file.
- No Vista Multipack warnings, parse errors, or fatal errors were found in the
  WordPress debug log.

## 2026-07-23 — Compatibility findings

- `XML for Google Merchant Center 4.3.0` emits repeated deprecation notices
  from its own `XFGMC_Error_Log` constructor during feed generation.
- Its `wp xfgmc quick` command reported success but produced an empty feed in
  the restored local environment because it did not create the temporary
  product ID list. The full `wp xfgmc generate --feed_id=1` command works and
  was used for final verification.
- KeyCRM emits existing warnings when an artificial test order has no customer,
  payment, or shipping payload. These warnings are outside Vista Multipack.
- Product-level stock management is disabled on all current catalog products.
  Pack orders will carry correct unit quantities, but WooCommerce can only
  reduce stock after stock management and a stock quantity are configured on
  the relevant product.

## 2026-07-23 — Final local state

Status: ready for product configuration.

- Plugin is active.
- Product 21626 remains enabled with a pack size of seven inherited from the
  existing Merchant field.
- Its pack price is intentionally empty, so no unapproved price is shown or
  exported.
- The feed plugin and theme were not modified.
- Git tracks only the root `.gitignore` and Vista Multipack files.

## 2026-07-23 — Pack button layout

Status: passed.

- Moved the pack order button from the standard WooCommerce cart button row
  into the pack price block.
- Kept the existing price text layout and placed the button below it.
- Used a standalone WooCommerce POST form so the relocated button works without
  JavaScript and still submits one pack as the configured number of real units.
- Increased the plugin version to 1.0.1 to refresh the storefront stylesheet.
- Confirmed in the browser that the button is inside the price block, below the
  existing price details, and no duplicate pack button remains beside the
  standard WooCommerce buttons.
- Confirmed the desktop layout visually; the button keeps its natural width,
  while the mobile rule expands it to the full block width.
- Submitted the new form through a fresh isolated WooCommerce session and
  confirmed the cart contains one pack, seven real units, and the configured
  pack price.
- PHP syntax and Git whitespace checks passed.

## 2026-07-23 — Final price block styling

Status: published.

- Kept the bordered price details at their original content width.
- Placed the pack order button on a separate line below the bordered details.
- Preserved the full-width pack button behavior on small screens.
- Prepared the plugin directory for publication as a standalone public
  repository without site files or generated ZIP archives.
- Published the standalone plugin repository at
  `https://github.com/pavlo-bondarchuk/vista-multipack`.
- Confirmed the public repository contains only the plugin source,
  documentation and translation files.

## 2026-07-24 — Storefront rollback

Status: passed.

- Reverted the compact per-package label requested earlier on the same day.
- Restored the bordered pack summary with the pack size, complete pack total
  and per-unit comparison.
- Restored the larger green pack order button below the summary.
- Restored plugin version 1.0.1 and the previous translation catalogs.
- Kept repository ignore rules for generated ZIP archives and macOS files.
- Restored the local product to seven units and a 5,999 UAH pack total.
- Confirmed visually that the bordered summary and larger green button match
  the previous storefront implementation.
- Confirmed through an isolated cart session that one pack again adds seven
  real units with the 5,999 UAH complete pack total.
- Regenerated and validated the Merchant XML with `21626-multipack-7`,
  `5999 UAH` and `<g:multipack>7</g:multipack>`.
- Published the restored implementation to the public repository's `main`
  branch.

## 2026-07-24 — Set terminology and compact button

Status: passed.

- Removed `vista-multipack-price__details` from the product page.
- Kept the set purchase form and complete set price, but removed the unit count
  from the button label.
- Restyled the button as a compact outlined secondary action.
- Replaced customer-facing and administration terminology from pack to set in
  Ukrainian, Russian and English source strings.
- Kept technical multipack identifiers unchanged for cart data, compatibility
  and Google Merchant XML.
- Increased the plugin version to 1.0.2.
- Confirmed on the Ukrainian and Russian product pages that the details block is
  absent and the compact button displays the complete set price without a unit
  count in parentheses.
- Confirmed the Ukrainian cart shows a separate set line, one customer-facing
  set, seven real stock units and the configured 5,999 UAH set total.
- Confirmed the Ukrainian administration labels use set terminology.
- Regenerated and validated feed #1. The set offer remains
  `21626-multipack-7`, uses the localized set title, costs `5999 UAH` and keeps
  `<g:multipack>7</g:multipack>`.
- Published version 1.0.2 to the public repository's `main` branch.

## 2026-07-24 — Set quantity in the purchase button

Status: passed.

- Changed the compact purchase button to show the set size in abbreviated
  customer-facing form together with the complete set price.
- Kept the configured set size dynamic for each product.
- Increased the plugin version to 1.0.3.
- Confirmed the Ukrainian product page renders
  `Комплект (7 од.) — 5,999 грн` without the removed details block.
- Confirmed the button remains a compact 30-pixel-high secondary action.
- Published version 1.0.3 to the public repository's `main` branch.
