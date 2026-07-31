# Architecture

## Purpose

Vista Multipack gives a simple WooCommerce product three purchase paths:

1. the standard single-unit WooCommerce offer;
2. a fixed-size retailer-defined set with its own total price.
3. one independently purchasable unit at the set-equivalent unit price,
   preselected by the additional feed offer.

The implementation preserves native WooCommerce quantities so stock reduction,
refunds, cancellations, and external order integrations continue to work with
real product units.

## Product configuration

The plugin stores three product fields:

| Metadata | Meaning |
| --- | --- |
| `_vista_multipack_enabled` | Whether the set purchase option is enabled |
| `_vista_multipack_size` | Number of real product units in one set |
| `_vista_multipack_price` | Total price of one complete set |

`Vista_Multipack_Product::get_config()` returns a configuration only for a
simple product with an enabled flag, a size of at least two, and a positive set
total.

If translated WPML products do not have their own plugin metadata, reads fall
back to the default-language product. The admin removes the feed plugin's
legacy `_xfgmc_multipack` metadata when saving because the additional offer is
now one unit rather than a multipack.

## Storefront and cart flow

The product page submits:

```text
add-to-cart=<product ID>
quantity=1
vista_purchase_mode=pack
```

The cart integration then:

1. validates the current set configuration and available stock;
2. expands one submitted set to the configured number of real product units;
3. snapshots set size and total price into cart item data;
4. divides the set total by its size only for WooCommerce's internal per-unit
   line calculation;
5. displays a customer-facing set count while preserving the real quantity;
6. stores technical and human-readable set metadata on the order item.

For a seven-unit set, one customer-facing set is therefore an order quantity of
seven. This behavior is intentional and must remain compatible with stock and
order integrations.

The complete-set button shows the configured set size and the calculated
price of one unit within that set. This is display copy only: submitting the
button still purchases the complete set and charges its saved total price.

The feed landing URL uses `vista_purchase=set-unit`. On that selected URL:

- the prominent price is the set total divided by set size;
- the compact button and standard WooCommerce form submit
  `vista_purchase_mode=pack_unit`;
- the cart stores one real product unit at the calculated unit price;
- WooCommerce structured data exposes the same unit price and selected URL;
- the special unit remains a separate cart and order line.

## Feed integration

The plugin uses the public `xfgmc_f_after_simple_offer` filter. It does not
modify `XML for Google Merchant Center`.

For every valid set configuration, the feed integration preserves the standard
single-unit `<item>` and appends one independently purchasable set-rate unit:

- ID: `<base ID>-set-unit-<set size>`;
- title and description: identify the one-unit special offer;
- link: preselects the set-rate unit on the landing page;
- price: uses the rounded set total divided by set size;
- sale-price elements: removed from the cloned unit offer;
- `g:multipack`: explicitly removed from both generated offers.

The original single-unit item remains unchanged.

When a simple product gains, changes, or loses its set configuration, the
plugin waits until WooCommerce persists the product and invokes the feed
plugin's `xfgmc_cron_start_feed_creation` action for every configured feed.
Generation remains asynchronous and owned by `XML for Google Merchant Center`.
If a feed is already being assembled, one delayed retry is scheduled instead
of interrupting the active build.

## Current example

Before version 1.1.0, the last locally generated XML contained:

| Offer | Price data | Meaning |
| --- | --- | --- |
| `21626` | `price=970 UAH`, `sale_price=640 UAH` | One standard unit |
| `21626-multipack-7` | `price=5999 UAH`, `multipack=7` | One set of seven units |

Version 1.1.0 must regenerate that snapshot as:

| Offer | Price data | Meaning |
| --- | --- | --- |
| `21626` | `price=970 UAH`, `sale_price=640 UAH` | Standard WooCommerce unit |
| `21626-set-unit-7` | `price=857 UAH`, no `multipack` | Independently purchasable set-rate unit |

Recheck live product metadata before treating these example values as
commercially approved.

## Compatibility boundaries

- WooCommerce simple products are supported.
- `XML for Google Merchant Center` 4.3.0 is the verified feed integration.
- Technical request and metadata names retain `pack` for backward
  compatibility.
- The plugin does not enable stock management or invent stock values.
- The plugin does not alter the regular or sale price of the base product.
- A duplicate one-unit Merchant offer may be deduplicated by Google because it
  represents the same underlying product with another public purchase price.
