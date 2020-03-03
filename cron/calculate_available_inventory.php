<?php
require_once(__DIR__.'/../includes/config.php');

// Load held inventory
$today = date("Y-m-d");
$end_date = date("Y-m-d", get_month_by_offset(7));
$sc_start_date = date("Y-m-d", get_next_month());
$sc_end_date = date("Y-m-t", get_next_month());

echo "Pulling scheduled inventory from $today to $end_date".PHP_EOL;
$sku_holds = $db->query("SELECT v.sku, SUM(quantity) AS total_quantity FROM skylar.rc_subscriptions rcs
LEFT JOIN variants v ON v.id=rcs.variant_id
LEFT JOIN products p ON p.id=v.product_id
WHERE rcs.deleted_at IS NULL
AND rcs.cancelled_at IS NULL
AND next_charge_scheduled_at BETWEEN '$today' AND '$end_date'
AND p.type NOT IN ('Scent Club', 'Scent Club Promo')
AND p.type = 'Scent Club Month'
GROUP BY variant_id;")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);




// Load kit/sku breakdown


// Calculate sku-level inventory less held


// Calculate kit-level inventory less held


// Sync to shopify

