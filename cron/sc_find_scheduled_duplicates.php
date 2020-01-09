<?php
require_once(__DIR__.'/../includes/config.php');

$monthly_scent = sc_get_monthly_scent($db);
$sku = $monthly_scent['sku'];
$start_date = date('Y-m-d');
$end_date = date('Y-m-T');

// Get orders with this month's sku crossed with scheduled orders with this month's sku
$ordered_rc_customers = $db->query("
SELECT * FROM order_line_items oli
LEFT JOIN orders o ON oli.order_id=o.id
LEFT JOIN customers c ON o.customer_id=c.id
LEFT JOIN rc_customers rcc ON c.id=rcc.customer_id
LEFT JOIN rc_addresses rca ON rcc.id=rca.rc_customer_id
LEFT JOIN rc_subscriptions rcs ON rcs.address_id=rca.id
LEFT JOIN variants v ON rcs.variant_id = v.id
WHERE next_charge_scheduled_at BETWEEN '$start_date' AND '$end_date'
AND rcs.deleted_at IS NULL
AND oli.sku = '$sku' AND (v.sku = '$sku' || v.id=6650)")->fetchAll(PDO::FETCH_ASSOC);


// Check for crossover