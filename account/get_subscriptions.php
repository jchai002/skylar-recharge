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
var_dump($subscriptions);

$addresses = [];
$addresses_res = $rc->get('/customers/'.$subscriptions[0]['customer_id'].'/addresses');
if(empty($addresses_res['addresses'])){
	die(json_encode($subscriptions));
}
foreach($addresses_res['addresses'] as $address_res){
	$addresses[$address_res['id']] = $address_res;
}
var_dump($addresses);

$subscription_groups = [];
foreach($subscriptions as $subscription){
    if(!in_array($subscription['status'], ['ACTIVE', 'ONETIME']) || empty($subscription['next_charge_scheduled_at'])){
        continue;
    }
    $next_charge_time = strtotime($subscription['next_charge_scheduled_at']);
    $next_charge_date = date('m/d/Y', $next_charge_time);
    $frequency = $subscription['status'] == 'ONETIME' ? '' : $subscription['order_interval_frequency'].$subscription['order_interval_unit'];
    $group_key = $subscription['status'].$next_charge_date.$frequency.$subscription['address_id'];
    if(!array_key_exists($group_key, $subscription_groups)){
        $subscription_groups[$group_key] = [
        	'subscriptions' => [],
			'status' => $subscription['status'],
			'frequency' => $frequency,
			'onetime' => $subscription['status'] == 'ONETIME',
			'next_charge_date' => $next_charge_date,
			'next_charge_time' => $next_charge_time,
		];
    }
    $subscription_groups[$group_key]['items'][] = [
    	'id' => $subscription['id'],
		'address_id' => $subscription['address_id'],
        'product_id' => $subscription['shopify_product_id'],
        'variant_id' => $subscription['shopify_variant_id'],
        'frequency' => empty($subscription['order_interval_frequency']) ? 'onetime' : $subscription['order_interval_frequency'],
        'quantity' => $subscription['quantity'],
		'product_title' => $subscription['product_title'],
		'variant_title' => $subscription['variant_title'],
		'price' => $subscription['price'],
    ];
}

// Dynamic title generation, counts, totals
foreach($subscription_groups as $group_key => $subscription_group){
	if(count($subscription_group['items']) == 1 && !empty($subscription_group['items'][0]['product_title'])){
		$subscription_group['title'] = trim($subscription_group['items'][0]['product_title'].$subscription_group['items'][0]['variant_title']);
		$subscription_group['title'] .= $subscription_group['onetime'] ? ' Order' : ' Auto Renewal';
	} else {
		$subscription_group['title'] = $subscription_group['onetime'] ? 'Scheduled Order' : 'Scent Auto Renewal';
	}
	$subscription_group['total_quantity'] = array_sum(array_column($subscription_group, 'quantity'));
	$subscription_group['total_price'] = number_format(array_sum(array_column($subscription_group, 'price')), 2);
	$subscription_groups[$group_key] = $subscription_group;
}

uasort($subscription_groups, function($a, $b){
	if($a['next_charge_time'] == $b['next_charge_time']){
		return 0;
	}
	return $a['next_charge_time'] > $b['next_charge_time'] ? 1 : -1;
});

echo json_encode(['subscriptions' => array_values($subscription_groups)]);