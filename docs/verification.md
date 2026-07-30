# Verification

Run checks from the WordPress root.

## Static checks

```bash
find wp-content/plugins/vista-multipack -name '*.php' -print0 \
  | xargs -0 -n1 php -l

msgfmt --check \
  wp-content/plugins/vista-multipack/languages/vista-multipack-uk.po \
  -o /dev/null

msgfmt --check \
  wp-content/plugins/vista-multipack/languages/vista-multipack-ru_RU.po \
  -o /dev/null

git diff --check
```

## Product configuration

For at least one enabled product, verify:

- plugin enabled flag;
- set size;
- configured price semantics;
- regular and sale prices;
- `_xfgmc_multipack` synchronization;
- WPML source and translated-product metadata.

Never migrate or reinterpret a saved price until its existing meaning has been
confirmed.

## Storefront and cart

Verify the standard, complete-set, and selected set-rate unit paths:

- product page shows the expected action and price;
- one standard-unit submission adds one real unit;
- one set submission adds exactly the configured number of real units;
- normal and set purchases remain separate lines;
- cart quantity displays set count but stores real unit count;
- cart subtotal equals the configured set total;
- stock validation uses the real unit count;
- order quantity and technical order metadata remain correct.
- the `vista_purchase=set-unit` URL prominently shows the calculated unit price;
- both add-to-cart forms on that URL submit `pack_unit`;
- the special unit adds exactly one real unit at the calculated price;
- its cart line is separate from both a normal unit and a complete set;
- structured data contains the calculated unit price and selected URL.

## Feed generation

Use the full feed command:

```bash
wp xfgmc generate --feed_id=1
xmllint --noout wp-content/uploads/feed-xml-0.xml
```

Do not use the feed plugin's `quick` command for the verified 4.3.0 integration.

Inspect the generated base and additional unit offers:

- both offer IDs are unique;
- the base offer remains unchanged;
- the additional ID uses `<base ID>-set-unit-<set size>`;
- the additional title and link clearly select one set-rate unit;
- its `g:price` equals the one-unit landing-page and checkout charge;
- neither offer contains retailer-defined `g:multipack` from this plugin;
- feed price, visible landing-page price, structured data, cart, and checkout
  do not contradict one another;
- availability reflects whether at least one real unit can be fulfilled.

## Runtime notes

The Local site must be running before WP-CLI database checks or feed generation.
Rediscover the active Local MySQL socket after restarts instead of assuming a
previous runtime path is still valid.
