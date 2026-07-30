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

Verify both the standard and set paths:

- product page shows the expected action and price;
- one standard-unit submission adds one real unit;
- one set submission adds exactly the configured number of real units;
- normal and set purchases remain separate lines;
- cart quantity displays set count but stores real unit count;
- cart subtotal equals the configured set total;
- stock validation uses the real unit count;
- order quantity and technical order metadata remain correct.

## Feed generation

Use the full feed command:

```bash
wp xfgmc generate --feed_id=1
xmllint --noout wp-content/uploads/feed-xml-0.xml
```

Do not use the feed plugin's `quick` command for the verified 4.3.0 integration.

Inspect the generated base and set offers. For a required set:

- both offer IDs are unique;
- the base offer remains unchanged;
- the set title and link clearly select the set;
- `g:price` equals the minimum checkout charge for the set;
- `g:multipack` equals the configured set size;
- any unit-pricing elements use the same total price as their calculation base;
- feed price, visible landing-page price, structured data, cart, and checkout
  do not contradict one another;
- availability reflects whether enough real units exist to fulfill the offer.

If the intended feed offer represents one independently purchasable unit:

- omit `g:multipack`;
- verify that exactly one unit can be purchased at `g:price`;
- verify that the link preselects that exact one-unit pricing state;
- verify the same price in HTML, structured data, cart, and checkout.

## Runtime notes

The Local site must be running before WP-CLI database checks or feed generation.
Rediscover the active Local MySQL socket after restarts instead of assuming a
previous runtime path is still valid.

