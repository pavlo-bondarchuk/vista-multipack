# Feed pricing decision

## Client request

The requested change is to export only one unit from the configured set and use
the set's per-unit price in the feed instead of exporting the complete
multipack.

This request has two different possible meanings. They must not be implemented
as if they were the same offer.

## Scenario A: one unit is independently purchasable

If any shopper can open the submitted link, add exactly one product unit, and
pay the submitted special unit price, the feed may contain a one-unit offer at
that price.

Requirements:

- the offer is a normal single-unit offer;
- do not add `g:multipack`;
- its title, link, visible landing-page price, structured data, cart, and
  checkout must all represent one unit at the submitted price;
- the offer needs a stable unique ID if the existing base offer remains;
- availability must represent the ability to buy one unit;
- duplicate-offer and product-identifier behavior must be reviewed in Merchant
  Center after upload.

This is not the current Vista Multipack purchase model. The current special
price is attached to a required set, not to an independently purchasable unit.

## Scenario B: the price applies only when buying the complete set

If a shopper must buy `N` units to receive the special per-unit rate, the feed
price must be the total amount for those `N` units. Sending only the calculated
per-unit amount as `g:price` would understate the minimum checkout charge.

Safe feed model:

```xml
<g:id>21626-multipack-7</g:id>
<g:price>5999 UAH</g:price>
<g:multipack>7</g:multipack>
<g:unit_pricing_measure>7 ct</g:unit_pricing_measure>
<g:unit_pricing_base_measure>1 ct</g:unit_pricing_base_measure>
```

The unit-pricing elements allow Google to calculate an equivalent price per
unit while `g:price` remains the amount required to purchase the advertised
offer. Whether Google displays the calculated unit value depends on the
destination, country, category, and presentation surface.

## Why `g:multipack=1` is not a solution

`g:multipack` describes multiple identical manufacturer-defined products that
the retailer grouped for sale. Setting it to one does not describe a multipack.
If the feed item represents one product, omit the attribute.

## Current plugin behavior

`_vista_multipack_price` stores a set total. The cart divides that value by the
set size only so WooCommerce can calculate a line containing real unit
quantities. The feed correctly exports the stored set total.

Changing only `class-vista-multipack-feed.php` to divide the price by the set
size would create these inconsistencies:

- Google price: one-unit equivalent;
- landing page action: complete set;
- cart charge: complete set total;
- checkout quantity: multiple real units.

Google documents price mismatch as a product-disapproval risk and notes that
systemic mismatches may lead to account suspension.

## Implemented decision

Scenario A was approved on 2026-07-30.

Version 1.1.0 therefore:

- keeps the existing standard WooCommerce offer;
- keeps the complete-set purchase path on the normal product page;
- calculates one public unit price from set total divided by set size;
- appends a separate one-unit feed offer with a unique ID;
- omits `g:multipack`;
- links to `vista_purchase=set-unit`;
- exposes the same price in visible HTML and WooCommerce structured data;
- lets any shopper buy one real unit at that price;
- preserves the special unit price in the cart and order.

The stored `_vista_multipack_price` remains a complete set total, so existing
product metadata is not silently reinterpreted.

## Residual Merchant considerations

- The standard and special offers identify the same physical product. Google
  may deduplicate them or choose one price presentation.
- Feed approval still depends on the live landing page, structured data,
  checkout, product identifiers, target country, and Merchant account state.
- The calculated unit price must be commercially valid. In the historical
  example, `5999 / 7 = 857`, which is higher than the standard sale price of
  `640`; this should be reviewed by the store owner.
- A future change to set size or total changes the special unit offer and
  requires immediate feed regeneration.

## Official references

- Google Merchant Center:
  [Multipack](https://support.google.com/merchants/answer/6324488)
- Google Merchant Center:
  [Price](https://support.google.com/merchants/answer/6324371)
- Google Merchant Center:
  [Mismatched product price](https://support.google.com/merchants/answer/12159029)
- Google Merchant Center:
  [Inaccurate price due to feed and landing-page inconsistency](https://support.google.com/merchants/answer/9773429)
- Google Merchant Center:
  [Unit pricing measure](https://support.google.com/merchants/answer/6324455)
