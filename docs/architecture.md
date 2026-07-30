# Architecture

## Purpose

Vista Multipack gives a simple WooCommerce product two purchase paths:

1. the standard single-unit WooCommerce offer;
2. a fixed-size retailer-defined set with its own total price.

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
back to the default-language product. The admin synchronizes the set size to
the feed plugin's `_xfgmc_multipack` metadata for compatibility.

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

## Feed integration

The plugin uses the public `xfgmc_f_after_simple_offer` filter. It does not
modify `XML for Google Merchant Center`.

For every valid set configuration, the feed integration clones the standard
single-unit `<item>` and changes the clone:

- ID: `<base ID>-multipack-<set size>`;
- title and description: identify the set size;
- link: selects the set offer on the landing page;
- price: uses the complete set total;
- sale-price elements: removed from the cloned set offer;
- `g:multipack`: set to the number of grouped identical products;
- availability and optional quantity: recalculated from real product stock.

The original single-unit item remains unchanged.

## Current example

The last locally generated XML contains:

| Offer | Price data | Meaning |
| --- | --- | --- |
| `21626` | `price=970 UAH`, `sale_price=640 UAH` | One standard unit |
| `21626-multipack-7` | `price=5999 UAH`, `multipack=7` | One set of seven units |

The saved XML is valid, but the Local site database was not running during the
2026-07-30 documentation audit. Treat these values as the last generated local
snapshot and recheck live product metadata before any pricing migration.

## Compatibility boundaries

- WooCommerce simple products are supported.
- `XML for Google Merchant Center` 4.3.0 is the verified feed integration.
- Technical request and metadata names retain `pack` for backward
  compatibility.
- The plugin does not enable stock management or invent stock values.
- The plugin does not alter the regular or sale price of the base product.

