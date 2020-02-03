<?php
require_once(__DIR__.'/../../includes/config.php');

$monthly_scent = sc_get_monthly_scent($db, get_next_month(), true);
if(empty($monthly_scent)){
	die("Monthly scent not set!");
}
$new_sku = $monthly_scent['sku'];
if(empty($new_sku)){
	die("No sku!");
}

$start_date = date('Y-m-d');
$end_date = date('Y-m-d', get_next_month());

$log = [
	'lines' => '',
	'error' => false,
];
log_echo($log, "Changing all promotions $start_date - $end_date to $new_sku");

log_echo($log, "Updating subscriptions");
$page = 0;
$subscriptions = [];
do {
	$page++;
	$res = $rc->get('/subscriptions', [
		'page' => $page,
		'limit' => 250,
		'shopify_variant_id' => 28003712663639,
		'scheduled_at_min' => $start_date,
		'scheduled_at_max' => $end_date,
		'status' => 'ACTIVE',
	]);
	foreach($res['subscriptions'] as $subscription){
		if(!is_scent_club_promo(get_product($db, $subscription['shopify_product_id']))){
			log_echo($log, "Skipping subscription because not a promo ".$subscription['id']." ".$subscription['email']);
		}
		$subscriptions[] = $subscription;
	}
} while(count($res['subscriptions']) >= 250);

log_echo($log, "Updating ".count($subscriptions)." subscriptions");
foreach($subscriptions as $subscription){
	$res = $rc->put('/subscriptions/'.$subscription['id'], [
		'sku' => $new_sku,
	]);
	if(empty($res['subscription']) || $res['subscription']['sku'] != $new_sku){
		print_r($res);
		log_echo($log, "ERROR CANNOT UPDATE SUB");
		$log['error'] = true;
	}
	log_echo($log, "Updated sub ".$subscription['id'].", address ".$subscription['address_id']." ".$res['subscription']['sku']);
	insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
}


log_echo($log, "Updating prepaid orders");
$page = 0;
$orders = [];
do {
	$page++;
	$res = $rc->get('/orders', [
		'page' => $page,
		'limit' => 250,
		'status' => 'queued',
		'scheduled_at_min' => $start_date,
		'scheduled_at_max' => $end_date,
	]);
	foreach($res['orders'] as $order){
		if(count($order['line_items']) != 1){
			log_echo($log, "WARNING: Not 1 line item!");
			$log['error'] = true;
		}
		if(!is_scent_club_promo(get_product($db, $order['line_items'][0]['shopify_product_id']))){
			log_echo($log, "Skipping order because not a promo ".$order['id']." ".$order['email']);
		}
		$orders[] = $order;
	}
} while(count($res['orders']) >= 250);

log_echo($log, "Updating ".count($orders)." orders");

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
	log_echo($log, "Updated order ".$order['id'].", address ".$order['address_id']." ".$res['order']['line_items'][0]['sku']);
	if(empty($res['order']) || $res['order']['line_items'][0]['sku'] != $new_sku){
		print_r($res);
		log_echo($log, "ERROR CANNOT UPDATE ORDER");
		$log['error'] = true;
	}
}

send_alert($db, 6,
	"Finished updating SC Promo Skus".($log['error'] ? ' with errors' : ''),
	"Promo SKU Update".($log['error'] ? ' ERROR' : ''),
	['tim@skylar.com', 'julie@skylar.com'],
	['log' => $log, 'smother' => false,]
);