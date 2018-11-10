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
if(empty($_REQUEST['subscription_ids'])){
	die(json_encode([
		'success' => false,
		'errors' => [
			['message' => 'No subscription IDs'],
		]
	]));
}
$subscription_ids = explode(',',$_REQUEST['subscription_ids']);

$rc = new RechargeClient();
$subscriptions = $rc->get('/subscriptions', [
	'shopify_customer_id' => $_REQUEST['customer_id'],
]);
if(empty($subscriptions['subscriptions'])){
	die(json_encode($subscriptions));
}
$subscriptions = $subscriptions['subscriptions'];
$customer_subscription_ids = array_column($subscriptions, 'id');

$data = [];
if(!empty($_REQUEST['frequency'])){
	$data['order_interval_frequency'] = $data['charge_interval_frequency'] = intval($_REQUEST['frequency']);
	$data['interval_unit_type'] = 'month';
}
if(array_key_exists('quantity', $_REQUEST)){
	$data['quantity'] = intval($_REQUEST['quantity']);
	if(empty($data['quantity'])){
		header('Location: cancel_subscriptions.php?reason=Quantity Reduced&'.http_build_query($_REQUEST));
		die();
	}
}
if(!empty($_REQUEST['product_id']) && !empty($_REQUEST['variant_id'])){
	$sc = new ShopifyPrivateClient();
	$product_id = intval($_REQUEST['product_id']);
	$variant_id = intval($_REQUEST['variant_id']);
	$product = $sc->call('GET', '/admin/products/'.$product_id.'.json');
	$data['shopify_variant_id'] = $variant_id;
	$data['product_title'] = $product['title'];
	foreach($product['variants'] as $variant){
		if($variant['id'] == $variant_id){
			$data['variant_title'] = $variant['title'];
			break;
		}
	}
}
if(!empty($_REQUEST['scent_code']) && array_key_exists($_REQUEST['scent_code'], $ids_by_scent)){
	// Special logic for 'default' subs. Swap one full size bottle for another. Could expand this to work across other products/variants
	$scent_code = $_REQUEST['scent_code'];
	$sc = new ShopifyPrivateClient();
	$product = $sc->call('GET', '/admin/products/'.$ids_by_scent[$scent_code]['product'].'.json');
	$data['shopify_variant_id'] = $ids_by_scent[$scent_code]['variant'];
	$data['product_title'] = $product['title'];
	foreach($product['variants'] as $variant){
		if($variant['id'] == $ids_by_scent[$scent_code]['variant']){
			$data['variant_title'] = $variant['title'];
			break;
		}
	}
}
//var_dump($data);
$errors = [];
$remove_ids = [];
foreach($subscription_ids as $subscription_id){
	$updated_subscription = [];
	if(!in_array($subscription_id, $customer_subscription_ids)){
		continue;
	}
	if(!empty($_REQUEST['shipdate'])){
		$updated_subscription_res = $rc->post('/subscriptions/'.$subscription_id.'/set_next_charge_date', [
			'date' => date('Y-m-d', strtotime($_REQUEST['shipdate'])),
		]);
		if(empty($updated_subscription_res['subscription'])){
			$errors[] = $updated_subscription_res;
			continue;
		}
		$updated_subscription = $updated_subscription_res['subscription'];
	}
	if(!empty($data)){
		$updated_subscription_res = $rc->put('/subscriptions/'.$subscription_id, $data);
		if(empty($updated_subscription_res['subscription'])){
			$errors[] = $updated_subscription_res;
			continue;
		}
		if(!empty($updated_subscription_res['subscription'])){
			$updated_subscription = $updated_subscription_res['subscription'];
		} else {
			$remove_ids[] = $subscription_id;
		}
	}
//	var_dump($updated_subscription);
	if(!empty($updated_subscription)){
		foreach($subscriptions as $index=>$subscription){
			if($subscription['id'] == $updated_subscription['id']){
				$subscriptions[$index] = $updated_subscription;
			}
		}
	}
}
$subscription_ids = array_diff($subscription_ids, $remove_ids);

$addresses = [];
$addresses_res = $rc->get('/customers/'.$subscriptions[0]['customer_id'].'/addresses');
if(empty($addresses_res['addresses'])){
	die(json_encode($subscriptions));
}
foreach($addresses_res['addresses'] as $address_res){
	$addresses[$address_res['id']] = $address_res;
}

echo json_encode([
	'success' => empty($errors),
	'subscriptions' => group_subscriptions($subscriptions, $addresses),
	'subscriptions_raw' => $subscriptions,
	'errors' => $errors,
//	'show_ids' => implode(',',$subscription_ids),
]);