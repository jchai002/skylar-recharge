<?php
require_once(__DIR__.'/../includes/config.php');

$monthly_scent = sc_get_monthly_scent($db, get_next_month(), true);
if(empty($monthly_scent)){
	die("Monthly scent not set!");
}
$new_sku = $monthly_scent['sku'];
if(empty($new_sku)){
	die("No sku!");
}

echo "Changing all promotions ".date('Y-m-d')." to ".date('Y-m-t')." to $new_sku".PHP_EOL;

echo "Updating subscriptions".PHP_EOL;

$page = 0;
$subscriptions = [];
do {
	$page++;
	$res = $rc->get('/subscriptions', [
		'page' => $page,
		'limit' => 250,
		'shopify_variant_id' => 28003712663639,
		'scheduled_at_min' => date('Y-m-d'),
		'scheduled_at_max' => date('Y-m-t'),
	]);
	foreach($res['subscriptions'] as $subscription){
		if(!is_scent_club_promo(get_product($db, $subscription['line_items'][0]['shopify_product_id']))){
			echo "Skipping subscription because not a promo ".$subscription['id']." ".$subscription['email'].PHP_EOL;
		}
		$subscriptions[] = $subscription;
	}
} while(count($res['orders']) >= 250);

echo "Updating ".count($subscriptions)." orders".PHP_EOL;
foreach($subscriptions as $subscription){
	$res = $rc->put('/subscriptions/'.$subscription['id'], [
		'sku' => $new_sku,
	]);
	if(empty($res['subscription']) ||$res['subscription']['sku'] != $new_sku){
		print_r($res);
		echo "ERROR CANNOT UPDATE SUB";
		die();
	}
}


echo "Updating prepaid orders".PHP_EOL;

$page = 0;
$orders = [];
do {
	$page++;
	$res = $rc->get('/orders', [
		'page' => $page,
		'limit' => 250,
		'status' => 'queued',
		'scheduled_at_min' => date('Y-m-d'),
		'scheduled_at_max' => date('Y-m-t'),
	]);
	foreach($res['orders'] as $order){
		if(count($order['line_items']) != 1){
			echo "Not 1 line item!";
			die();
		}
		if(!is_scent_club_promo(get_product($db, $order['line_items'][0]['shopify_product_id']))){
			echo "Skipping order because not a promo ".$order['id']." ".$order['email'].PHP_EOL;
		}
		$orders[] = $order;
	}
} while(count($res['orders']) >= 250);

echo "Updating ".count($orders)." orders".PHP_EOL;

foreach($orders as $order){
	$line_item = [
		'sku' => $new_sku,
		'price' => $order['line_items'][0]['price'],
		'properties' => $order['line_items'][0]['properties'],
		'quantity' => $order['line_items'][0]['quantity'],
		'subscription_id' => $order['line_items'][0]['subscription_id'],
		'title' => $order['line_items'][0]['title'],
		'product_title' => $order['line_items'][0]['product_title'],
		'variant_title' => $order['line_items'][0]['variant_title'],
		'product_id' => $order['line_items'][0]['shopify_product_id'],
		'variant_id' => $order['line_items'][0]['shopify_variant_id'],
	];
	$res = $rc->put('/orders/'.$order['id'], ['line_items' => [$line_item]]);
	echo "Updated ".$order['id']." ".$order['email'].PHP_EOL;
	if(empty($res['order']) || $res['order']['line_items'][0]['sku'] != $new_sku){
		print_r($res);
		echo "ERROR CANNOT UPDATE ORDER";
		die();
	}
}