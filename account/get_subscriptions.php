<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();
if(!empty($_REQUEST['rc_customer_id'])){
	$subscriptions = $rc->get('/subscriptions', [
		'customer_id' => $_REQUEST['rc_customer_id'],
	]);
} else {
	if(empty($_REQUEST['customer_id'])){
		die(json_encode([
			'success' => false,
			'errors' => [
				['message' => 'No customer ID'],
			]
		]));
	}

	$subscriptions = $rc->get('/subscriptions', [
		'shopify_customer_id' => $_REQUEST['customer_id'],
	]);
}
if(empty($subscriptions['subscriptions'])){
	die(json_encode([
		'success' => true,
		'subscriptions' => [],
		'subscriptions_raw' => $subscriptions,
	]));
//	die(json_encode($subscriptions));
}
$subscriptions = $subscriptions['subscriptions'];
//var_dump($subscriptions);

$subscriptions = upgrade_check($sc, $rc, $db, $subscriptions);

$addresses = [];
$addresses_res = $rc->get('/customers/'.$subscriptions[0]['customer_id'].'/addresses');
if(empty($addresses_res['addresses'])){
	die(json_encode($subscriptions));
}
foreach($addresses_res['addresses'] as $address_res){
	$addresses[$address_res['id']] = $address_res;
}
//var_dump($addresses);

echo json_encode([
	'success' => true,
	'subscriptions' => group_subscriptions($subscriptions, $addresses),
	'subscriptions_raw' => $subscriptions,
]);


function upgrade_check(ShopifyPrivateClient $sc, RechargeClient $rc, PDO $db, $subscriptions){
	global $ids_by_scent;

	$product_cache = [];
	$subscription_variant_ids = [];
	foreach($subscriptions as $subscription){
		// Check if there is a subscription needing an upgrade
		$subscription_variant_ids[] = $subscription['shopify_variant_id'];
		if(!in_array($subscription['shopify_product_id'], [738567323735, 738567520343, 738394865751])){
			continue;
		}
		$onetime = false;
		foreach($subscription['properties'] as $property){
			if($property['name'] == "shipping_interval_frequency"){
				if($property['value'] == "1"){
					$onetime = true;
					break;
				}
			}
		}
		if($subscription['status'] != 'ACTIVE' && $onetime){
			continue;
		}
		$old_subscription = $subscription;
	}
	if(empty($old_subscription)){
		return $subscriptions;
	}


	// Upgrade old sub
	$old_properties = [];
	foreach($old_subscription['properties'] as $property){
		$old_properties[$property['name']] = $property['value'];
	}
	if(empty($old_properties['total_items'])){
		return $subscriptions;
	}
	$frequency = $old_subscription['order_interval_frequency'] == '1' ? 'onetime' : $old_subscription['order_interval_frequency'];

	$add_sample_discount =
		$old_properties['total_items'] == 1 && $frequency == 'onetime' && $old_subscription['price'] < 78
		|| $old_properties['total_items'] == 1 && $frequency != 'onetime' && $old_subscription['price'] < 66.3
		|| $old_properties['total_items'] == 2 && $frequency == 'onetime' && $old_subscription['price'] < 120
		|| $old_properties['total_items'] == 2 && $frequency != 'onetime' && $old_subscription['price'] < 102
		|| $old_properties['total_items'] == 3 && $frequency == 'onetime' && $old_subscription['price'] < 178
		|| $old_properties['total_items'] == 3 && $frequency != 'onetime' && $old_subscription['price'] < 151.3
		|| $old_properties['total_items'] == 4 && $frequency == 'onetime' && $old_subscription['price'] < 200
		|| $old_properties['total_items'] == 4 && $frequency != 'onetime' && $old_subscription['price'] < 170;

	if($add_sample_discount){
		$res = $rc->get('/addresses/'.$old_subscription['address_id']);
		$address = $res['address'];
		if(!in_array('_sample_credit',array_column($address['cart_attributes'], 'name'))){
			$address['cart_attributes'][] = ['name' => '_sample_credit', 'value' => 20];
			$res = $rc->put('/addresses/'.$old_subscription['address_id'], [
				'cart_attributes' => $address['cart_attributes'],
			]);
			log_event($db, 'DISCOUNT', json_encode($res), 'ADDED', json_encode($old_subscription), 'Discount added from old subscription', 'api');
		}
	}

	for($i = 1; $i <= $old_properties['total_items']; $i++){
		if(empty($old_properties['handle_' . $i])){
			continue;
		}
		$handle = strtok($old_properties['handle_' . $i], '-');
		$ids = $ids_by_scent[$handle];
		if(empty($ids)){
			continue;
		}
		if(in_array($ids['variant'], $subscription_variant_ids)){
			continue;
		}

		$quantity = $old_properties['qty_'.$i];
		if(empty($quantity)){
			$quantity = 1;
		}

		if(empty($product_cache[$ids['product']])){
			$product = $sc->call('GET', '/admin/products/'.$ids['product'].'.json');
		} else {
			$product = $product_cache[$ids['product']];
		}
		foreach($product['variants'] as $product_variant){
			if($product_variant['id'] == $ids['variant']){
				$variant = $product_variant;
				break;
			}
		}
		if(empty($product) || empty($variant)){
			continue;
		}

		$new_subscription = add_subscription($rc, $product, $variant, $old_subscription['address_id'], strtotime($old_subscription['next_charge_scheduled_at']), $quantity, $frequency);
		log_event($db, 'SUBSCRIPTION', json_encode($new_subscription), 'CREATED', json_encode($old_subscription), 'Subscription upgraded from old style', 'api');
		$subscriptions[] = $new_subscription;
	}
	$res = $rc->delete('/subscriptions/'.$old_subscription['id']);
	log_event($db, 'SUBSCRIPTION', json_encode($res), 'DELETED', json_encode($old_subscription), 'Subscription upgraded from old style', 'api');

	return $subscriptions;
}