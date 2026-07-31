# UI technical notes

## Complete-set price label

- The complete-set button must clearly distinguish the displayed per-unit
  price from the complete set total charged after submission.
- Keep the set size dynamic and calculate the displayed unit price through
  `Vista_Multipack_Product::get_unit_display_price()` so tax display rules and
  rounding remain consistent with the rest of the plugin.
