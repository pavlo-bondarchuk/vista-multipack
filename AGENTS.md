# Vista Multipack development guide

## Scope

These instructions apply to the entire `vista-multipack` plugin.

The plugin extends simple WooCommerce products with a fixed-size set purchase
option and adds a compatible offer to the `XML for Google Merchant Center`
feed. Keep the implementation isolated in this plugin. Do not patch
WooCommerce, the active theme, or the third-party feed plugin.

Read the following documents before changing behavior:

- `docs/architecture.md`
- `docs/feed-pricing.md`
- `docs/verification.md`
- `PROJECT-HISTORY.md`

## Architecture ownership

- `class-vista-multipack-product.php` owns validated product configuration and
  WPML fallback reads.
- `class-vista-multipack-admin.php` owns product fields and prevents the
  third-party feed plugin from emitting legacy `_xfgmc_multipack` data.
- `class-vista-multipack-frontend.php` owns the product-page set form.
- `class-vista-multipack-cart.php` owns real unit quantities, set pricing,
  customer-facing cart rendering, and order metadata.
- `class-vista-multipack-feed.php` owns the extra feed offer and product-save
  regeneration requests, and must integrate only through public feed-plugin
  hooks.

## Required invariants

- Customer-facing wording uses "set" and localized equivalents such as
  "комплект". Existing technical `pack` identifiers, request values, metadata,
  and cart keys remain stable unless a migration is explicitly designed.
- A set of `N` products is stored as `N` real WooCommerce units. Do not replace
  it with one synthetic stock unit.
- `_vista_multipack_price` currently stores the total price of one complete set,
  not a per-unit price.
- The independently purchasable set-rate unit price is calculated as the saved
  set total divided by set size and rounded to WooCommerce currency precision.
- Set and normal purchases remain separate cart lines.
- Feed changes must not overwrite or remove the standard single-unit offer.
- The additional feed offer represents one independently purchasable unit, uses
  a unique ID and selected landing URL, and must not contain `g:multipack`.
- The complete-set storefront path remains available but is not exported as the
  additional Merchant offer.
- Never submit a conditional per-unit value as `g:price` when checkout requires
  the customer to buy multiple units. The submitted price must match the
  landing page and checkout.
- Any shopper must be able to purchase exactly one unit at the additional
  offer's price from the submitted landing URL. The visible price, structured
  data, cart and checkout must match the feed.

## Change workflow

1. Record the requested behavior and initial status in `PROJECT-HISTORY.md`.
2. Confirm whether an entered set price is a total or a per-unit value before
   changing data semantics.
3. Trace the change through product configuration, admin saving, storefront,
   cart, order metadata, feed output, WPML behavior, and translations.
4. Preserve existing metadata semantics or provide an explicit migration.
   Never silently reinterpret stored total prices as per-unit prices.
5. Update the plugin version when executable PHP, CSS, or translation output
   changes.
6. Recompile both `.mo` files after editing `.po` files.
7. Complete the verification in `docs/verification.md`.
8. Update `PROJECT-HISTORY.md` with the verified result before publishing.

## Repository boundaries

The WordPress-root repository intentionally tracks only the root `.gitignore`
and `wp-content/plugins/vista-multipack/**`. Do not add site configuration,
uploads, database exports, third-party plugins, generated archives, or unrelated
files to a commit.
