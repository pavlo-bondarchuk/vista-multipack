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

## Recommended decision

Do not change the current multipack offer to a one-unit price unless the
storefront and checkout are also changed so one unit is genuinely purchasable
at that price.

For the current fixed-set business model, retain the total multipack
`g:price` and add unit-pricing attributes if a per-unit comparison is required.

If the commercial requirement is instead a third price for one independently
purchasable unit, design it as a separate pricing feature rather than reusing
the multipack field. That feature needs its own eligibility rules, product
metadata, landing-page state, cart pricing, order metadata, structured data,
and feed offer.

## Required clarification before code changes

Confirm one statement:

1. "A customer may buy exactly one unit at the new price."
2. "The new price is per unit only when the customer buys the configured set."

The first statement leads to a new single-unit offer without `g:multipack`.
The second statement keeps the complete set price in `g:price`.

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

