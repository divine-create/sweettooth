<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Combined sales points
    |--------------------------------------------------------------------------
    |
    | Maps a "primary" sales department slug to the member department slugs
    | whose products are sold and counted from the SAME point of sale.
    |
    | Effect (Option 1 — one till, one drawer):
    |   - The POS screen lists products from the primary + member departments.
    |   - Stock opening lists (and counts) products from all of them together.
    |   - The sidebar shows only the primary department as a sales point.
    |
    | What stays separate per department:
    |   - Product stock: each product's stock is opened/deducted under its own
    |     home department (products.sales_department_id), so counts never mix.
    |   - Product-level reporting (keyed off the product's home department).
    |
    | What rolls up to the primary department (by design, Option 1):
    |   - The Sale record, cash drawer and GL revenue posting.
    |
    | Keyed by SLUG (not id) so it is stable across environments where the
    | numeric department ids differ (local vs production).
    |
    */

    'combined_points' => [
        // Till Sales POS also sells & counts Concession products.
        'till_concession' => ['concession'],
    ],

];
