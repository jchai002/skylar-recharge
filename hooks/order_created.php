<?php
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

$headers = getallheaders();
$shop_url = null;
if(!empty($headers['X-Shopify-Shop-Domain'])){
	$shop_url = $headers['X-Shopify-Shop-Domain'];
}
if(empty($shop_url)){
	$shop_url = 'maven-and-muse.myshopify.com';
}

$sc = new ShopifyPrivateClient($shop_url);

if(!empty($_REQUEST['id'])){
	$order = $sc->call('GET', '/admin/orders/'.intval($_REQUEST['id']).'.json');
} else {
	$data = file_get_contents('php://input');
	log_event($db, 'log', $data);
	$order = json_decode($data, true);
}
if(empty($order)){
	die('no data');
}
$rc = new RechargeClient();

// Get recharge version of order
$rc_order = $rc->get('/orders',['shopify_order_id'=>$order['id']])['orders'][0];
//var_dump($rc_order);
if(empty($rc_order)){
	die('no rc order');
}

// Tag orders that aren't samples as either onetime or subscription, with subscription
$order_tags = explode(',',$order['tags']);
$res = $rc->get('/subscriptions/', ['address_id' => $rc_order['address_id']]);
$subscriptions = [];
$update_order = false;
foreach($res['subscriptions'] as $subscription){
	$subscriptions[$subscription['id']] = $subscription;
}
if($rc_order['type'] == "RECURRING"){
	foreach($rc_order['line_items'] as $line_item){
		if(in_array($line_item['shopify_variant_id'], [738567520343,738394865751,738567323735])){
			echo $line_item['shopify_variant_id'].PHP_EOL;
			continue;
		}
		if(empty($line_item['subscription_id']) || empty($subscriptions[$line_item['subscription_id']])){
			echo $line_item['subscription_id']." not in subscriptions ".$rc_order['address_id'].PHP_EOL;
			continue;
		}
		$subscription = $subscriptions[$line_item['subscription_id']];
		echo $subscription['status'].PHP_EOL;
		if($subscription['status'] == 'ONETIME'){
			$order_tags[] = 'Sub Type: One-time';
		} else {
			$order_tags[] = 'Sub Type: Recurring';
		}
		$update_order = true;
	}
} else {
	echo $rc_order['type'].PHP_EOL;
}
if($update_order){
	$order_tags = array_unique($order_tags);
	$sc->call("PUT", "/admin/orders/".$order['id'].'.json', ['order' => [
		'id' => $order['id'],
		'tags' => implode(',', $order_tags),
	]]);
}

// Get subs we need to create for this order
$has_subscription_line_item = false;
$subs_to_create = [];
$sample_credit = 0;
var_dump($sample_credit_variant_ids);
var_dump(array_column($order['line_items'], 'variant_id'));
foreach($order['line_items'] as $line_item){
	// TEMP: Skip for old sub type
	if(in_array($line_item['variant_id'], [738567520343,738394865751,738567323735])){
		die('variant '.$line_item['variant_id']);
	}
	if(in_array($line_item['variant_id'], $sample_credit_variant_ids)){
		echo "Crediting sample paletted: ".$line_item['price'].PHP_EOL;
		$sample_credit = $line_item['price'];
	}
	if(!in_array($line_item['variant_id'], $subscription_variant_ids)){
//		echo "skipping, not in subscription_variant_ids".PHP_EOL;
//		continue;
	}
	if(empty($line_item['properties'])){
		echo "skipping, no properties".PHP_EOL;
		continue;
	}
	$sub_scent = $sub_frequency = null;
	foreach($line_item['properties'] as $property){
		if($property['name'] == '_sub_frequency'){
			$sub_frequency = intval($property['value']);
		}
		if($property['name'] == '_sub_scent' && !empty($ids_by_scent[$property['value']])){
			$sub_scent = $property['value'];
		}
	}
	if(!empty($sub_scent) || !empty($sub_frequency)){
		$subs_to_create[] = [
			'ids' => $ids_by_scent[$sub_scent],
			'frequency' => $sub_frequency,
		];
	} else {
		echo "doesn't have scent and freq";
	}
}
var_dump($sample_credit);
$res = $rc->get('/addresses/'.$rc_order['address_id']);
$address = $res['address'];
if(!empty($sample_credit)){
	if(!in_array('_sample_credit',array_column($address['cart_attributes'], 'name'))){
		$address['cart_attributes'][] = ['name' => '_sample_credit', 'value' => $sample_credit];
		$res = $rc->put('/addresses/'.$rc_order['address_id'], [
			'cart_attributes' => $address['cart_attributes'],
		]);
		var_dump($res);
	}
}
if(empty($subs_to_create)){
	die('no subs to create');
}

if($address)

$delay_days = 17; // TODO: more if international
$order_created_time = strtotime($order['created_at']);
$offset = 0;
do {
	$next_charge_time = $order_created_time + (($delay_days+$offset) * 24*60*60);
	$offset++;
} while(in_array(date('N', $next_charge_time), [6,7]));

$product_cache = [];

foreach($subs_to_create as $sub_data){
	if(empty($product_cache[$sub_data['ids']['product']])){
		$product = $sc->call('GET', '/admin/products/'.$sub_data['ids']['product'].'.json');
		$product_cache[$product['id']] = $product;
	} else {
		$product = $product_cache[$product['id']];
	}
	foreach($product['variants'] as $variant){
		if($variant['id'] == $sub_data['ids']['variant']){
			break;
		}
	}
	if($variant['id'] != $sub_data['ids']['variant']){
		continue;
	}

	$subscription = add_subscription($rc, $product, $variant, $rc_order['address_id'], $next_charge_time, 1, $sub_data['frequency']);
	$subscription_id = $subscription['id'];
}