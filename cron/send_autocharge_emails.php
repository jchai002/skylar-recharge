<?php
require_once(__DIR__.'/../includes/config.php');

// Pull data from table:
// reminder email not sent
// on or before x date (X days before fullsize drops)
// not cancelled

// Send email

$send_threshold = date('Y-m-d', strtotime('-5 days'));
$stmt = $db->prepare("SELECT * FROM ac_orders aco
LEFT JOIN order_line_items oli ON oli.id=ado.order_line_item_id
LEFT JOIN fulfillments f ON oli.fulfillment_id=f.id
WHERE reminder_email_id IS NULL
AND f.status='delivered'"); // TODO filters

$orders = $stmt->fetchAll();

$stmt = $db->prepare("UPDATE ac_orders SET reminder_email_id=:email_id WHERE id=:id");
foreach($orders as $ac_order){
    $res = klaviyo_send_transactional_email($db, $ac_order['email'], 'autocharge_choose_scent_reminder', [
        'first_name' => $ac_order['first_name'],
    ]);
    if(!empty($res)){
        $stmt->execute([
            'id' => $ac_order['id'],
            'email_id' => $res['id'],
        ]);
    }
}