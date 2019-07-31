<?php
require_once(__DIR__.'/../includes/config.php');

// Pull data from table:
// reminder email not sent
// on or before x date (X days before fullsize drops)
// not cancelled

// Send email

$send_threshold = date('Y-m-d', strtotime('-5 days'));
$stmt = $db->prepare("SELECT aco.id, oli.sku, DATE(f.delivered_at) AS delivered_at, o.email FROM ac_orders aco
LEFT JOIN order_line_items oli ON oli.id=aco.order_line_item_id
LEFT JOIN fulfillments f ON oli.fulfillment_id=f.id
LEFT JOIN orders o ON o.id=oli.order_id
LEFT JOIN rc_subscriptions s ON s.id=aco.followup_subscription_id
WHERE aco.reminder_email_id IS NULL
AND s.variant_id = 115
AND s.deleted_at IS NULL
AND f.delivered_at IS NOT NULL
AND f.delivered_at <= ?");

$stmt->execute([$send_threshold]);

$orders = $stmt->fetchAll();

$stmt = $db->prepare("UPDATE ac_orders SET reminder_email_id=:email_id WHERE id=:id");
foreach($orders as $ac_order){
    $res = klaviyo_send_transactional_email($db, $ac_order['email'], 'autocharge_choose_scent_reminder', [
        'sample_deliver_date' => $ac_order['delivered_at'],
        'sample_sku' => $ac_order['sku'],
    ]);
    if(!empty($res)){
        $stmt->execute([
            'id' => $ac_order['id'],
            'email_id' => $res['id'],
        ]);
    }
    var_dump($res);
}