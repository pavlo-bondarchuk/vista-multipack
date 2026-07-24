# Vista Multipack

WooCommerce extension for selling a simple product either as one unit or as a
fixed-size set.

## Product data

The **General > Pricing** product panel contains:

- Enable set purchase
- Units per set
- Set price (the total for one set)

The set option is available only when all three values are valid. WooCommerce
stores the real number of product units in the cart and order, so native stock
reduction, cancellations, refunds, and order integrations continue to use unit
quantities.

Product-level stock management and an actual stock quantity must be enabled if
WooCommerce is expected to reduce stock. The plugin does not invent or enable
stock values for existing products.

## Google Merchant feed

When `XML for Google Merchant Center` is active, the plugin keeps the standard
single-unit offer and appends a second offer for the set. The set offer has a
unique ID, total set price, set landing link, and `g:multipack`.

Compatibility is verified with `XML for Google Merchant Center` 4.3.0.
Regenerate the current feed with:

```bash
wp xfgmc generate --feed_id=1
```

The feed plugin's `quick` command is not used because version 4.3.0 can attempt
to assemble a feed before creating its temporary product files.

## History

See [PROJECT-HISTORY.md](PROJECT-HISTORY.md) for the audit, implementation
record, and verification results.
