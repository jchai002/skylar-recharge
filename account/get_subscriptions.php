<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

header('Content-Type: application/json');
if(empty($_REQUEST['customer_id'])){
    die(json_encode([
        'success' => false,
        'errors' => [
            ['message' => 'No customer ID'],
        ]
    ]));
}

$rc = new RechargeClient();
$subscriptions = $rc->get('/subscriptions', [
    'shopify_customer_id' => $_REQUEST['customer_id'],
]);
if(empty($subscriptions['subscriptions'])){
	die(json_encode($subscriptions));
}
$subscriptions = $subscriptions['subscriptions'];
var_dump($subscriptions);

$subscription_groups = [];
foreach($subscriptions as $subscription){
    if(!in_array($subscription['status'], ['ACTIVE', 'ONETIME'])){
        continue;
    }
    $next_charge_date = date('m/d/Y', strtotime($subscription['next_charge_scheduled_at']));
    $group_key = $subscription['status'].$next_charge_date.$subscription['shipping_interval_frequency'].$subscription['order_interval_unit'];
    if(!array_key_exists($group_key, $subscription_groups)){
        $subscription_groups[$group_key] = [];
    }
    $subscription_groups[$group_key]['products'] = [
        'product_id' => $subscription['shopify_product_id'],
        'variant_id' => $subscription['shopify_variant_id'],
        'frequency' => $subscription['shipping_interval_frequency'],
        'quantity' => $subscription['quantity'],
    ];
}

echo json_encode($subscription_groups);