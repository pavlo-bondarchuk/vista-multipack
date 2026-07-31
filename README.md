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

The complete-set button displays the set size and its calculated per-unit
price. The action still adds the complete set and charges the configured set
total.

Product-level stock management and an actual stock quantity must be enabled if
WooCommerce is expected to reduce stock. The plugin does not invent or enable
stock values for existing products.

## Google Merchant feed

When `XML for Google Merchant Center` is active, the plugin keeps the standard
single-unit offer and appends a second independently purchasable unit at the
set-equivalent unit price. The second offer has a unique ID, a landing link
that preselects that price, matching structured data and no `g:multipack`.

The saved set price remains the total for a complete set. The additional unit
price is calculated as set total divided by set size and rounded to the store's
currency precision.

Saving a product after its set configuration changes automatically starts the
feed plugin's own asynchronous regeneration flow.

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

## Development documentation

- [Architecture](docs/architecture.md)
- [Google Merchant feed pricing](docs/feed-pricing.md)
- [Verification checklist](docs/verification.md)
- [UI change log](docs/ui-change-log.md)
- [UI technical notes](docs/ui-technical-notes.md)
- [Development guide](AGENTS.md)
