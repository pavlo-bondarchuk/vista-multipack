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

## 2026-07-30 — Plugin audit and per-unit feed request

Status: analysis and documentation completed; executable behavior unchanged.

- Re-audited product metadata, storefront submission, cart quantity and price
  handling, order metadata, and the XML feed extension.
- Confirmed `_vista_multipack_price` is currently a complete set total and is
  divided only for WooCommerce's internal per-unit line calculation.
- Confirmed the feed integration preserves the standard single-unit item and
  appends a separate set offer with a unique ID, complete set price, selected
  landing URL, and `g:multipack`.
- Inspected the last generated valid XML snapshot: the base offer has
  `970 UAH` with a `640 UAH` sale price, while `21626-multipack-7` has
  `5999 UAH` and `g:multipack=7`.
- The Local database was not running during this audit, so live product
  metadata and feed regeneration remain required before a future executable
  pricing change.
- Documented that a one-unit feed price is valid only when one unit can actually
  be bought at that price. If the price requires purchasing the complete set,
  Google requires the minimum purchasable total instead.
- Added plugin-scoped development instructions, architecture documentation, a
  feed-pricing decision record, and a repeatable verification checklist.
- Deliberately left feed behavior unchanged until the commercial requirement is
  confirmed as either an independently purchasable unit price or a
  set-conditional unit price.
- Published the audit and documentation to the public plugin repository's
  `main` branch.

## 2026-07-30 — Independently purchasable set-unit feed offer

Status: passed.

- Approved the one-unit scenario: the additional feed offer will represent one
  independently purchasable product unit at the set-equivalent unit price.
- The saved `_vista_multipack_price` value remains the complete set total; no
  stored product prices will be reinterpreted or migrated.
- The special unit price will be calculated as set total divided by set size.
- The standard product offer and existing complete-set purchase path will
  remain available.
- The additional feed offer will use its own ID and selected landing URL, will
  omit `g:multipack`, and will submit the actual one-unit checkout price.
- The selected landing URL, visible price, structured data, add-to-cart request,
  cart line, order metadata, and feed value will be updated together.
- Released plugin version `1.1.0`. For the current product configuration, the
  set total `5,999 UAH` divided by `7` units produces the purchasable unit price
  `857 UAH`.
- Verified Ukrainian and Russian selected landing pages, both add-to-cart
  forms, the visible unit price, and Product structured data.
- Verified three independent cart lines: the standard unit at its WooCommerce
  price, the complete set with seven stock units, and the selected set-rate unit
  with quantity one and price `857 UAH`.
- Verified the selected unit's order metadata and real WooCommerce quantity.
- Removed the legacy `_xfgmc_multipack` compatibility value through the
  `1.1.0` upgrade while preserving `_vista_multipack_size`.
- Regenerated and validated the Merchant XML. The standard `21626` offer
  remains, the additional `21626-set-unit-7` offer has price `857 UAH`, and
  neither offer contains `g:multipack`.
- Published version `1.1.0` to the public plugin repository's `main` branch.

## 2026-07-30 — Regenerate feeds after set product updates

Status: passed.

- Production showed that saving a product did not update the generated XML
  unless the feed plugin's optional product-update setting was enabled.
- The plugin will request regeneration whenever a product gains, changes, or
  loses a valid set configuration.
- Regeneration will start after WooCommerce persists the product and will use
  the feed plugin's `xfgmc_cron_start_feed_creation` action instead of
  duplicating its generation logic.
- Multiple changes in the same request will be deduplicated, and an active feed
  build will receive one delayed retry rather than being interrupted.
- Released plugin version `1.1.1`.
- Verified that saving product `21626` requests
  `xfgmc_cron_start_feed_creation` only after its set metadata is persisted.
- Verified that the feed enters its generation flow even while the feed
  plugin's optional `xfgmc_ufup` product-update setting is disabled.
- Completed the feed plugin's scheduled generation stages and validated the
  regenerated XML with `xmllint`.
- Confirmed the regenerated XML contains the standard `21626` offer and the
  `21626-set-unit-7` offer at `857 UAH`, with no `g:multipack`.
- Published version `1.1.1` to the public plugin repository's `main` branch.

## 2026-07-31 — Per-unit price in the complete-set button

Status: passed.

- The complete-set button currently shows the set size and the total charged
  for the complete set.
- The requested label will instead explain that the displayed amount is the
  price of one unit within the configured set and will keep the set size
  visible.
- The button remains a complete-set purchase action; cart quantity, complete
  set total, stock handling, and feed behavior will not change.
- Ukrainian, Russian, and English source text will be updated together.
- Released plugin version `1.1.2` and recompiled both translation catalogs.
- Verified the requested five-unit example renders as
  `Ціна за 1 од. в комплекті 5 од. — 966 грн` for a `4,830 UAH` set total.
- Verified the Russian catalog renders the matching per-unit wording.
- Confirmed the form still submits `vista_purchase_mode=pack`, so the purchase
  remains a complete set and only its button copy changed.
- PHP syntax, translation catalog, and staged whitespace checks passed. The
  Local site was not running, so verification used the plugin's price helper
  and compiled translation output instead of a live HTTP render.
- Published version `1.1.2` to the public plugin repository's `main` branch in
  separate feature and documentation commits.
