<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
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
//var_dump($subscriptions);

$subscription_groups = [];
foreach($subscriptions as $subscription){
    if(!in_array($subscription['status'], ['ACTIVE', 'ONETIME']) || empty($subscription['next_charge_scheduled_at'])){
        continue;
    }
    $next_charge_date = date('m/d/Y', strtotime($subscription['next_charge_scheduled_at']));
    $frequency = $subscription['status'] == 'ONETIME' ? '' : $subscription['order_interval_frequency'].$subscription['order_interval_unit'];
    $group_key = $subscription['status'].$next_charge_date.$frequency;
    if(!array_key_exists($group_key, $subscription_groups)){
        $subscription_groups[$group_key] = [];
    }
    $subscription_groups[$group_key]['subscriptions'][] = [
    	'id' => $subscription['id'],
        'product_id' => $subscription['shopify_product_id'],
        'variant_id' => $subscription['shopify_variant_id'],
        'frequency' => empty($subscription['order_interval_frequency']) ? 'onetime' : $subscription['order_interval_frequency'],
        'quantity' => $subscription['quantity'],
    ];
}

echo json_encode(['subscriptions' => $subscription_groups]);