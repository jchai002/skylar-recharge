<?php
require_once(__DIR__.'/../includes/config.php');

$new_sku = $db->query("SELECT sku FROM sc_product_info WHERE sc_date = '".date('Y-m', get_next_month())."-01'")->fetchColumn();
if(empty($new_sku)){
	die("No sku!");
}
echo "Changing all promotions this month to $new_sku".PHP_EOL;

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
		if($order['line_items'][0]['shopify_variant_id'] != 28003712663639){
			echo "Skipping order because not a promo ".$order['id']." ".$order['email'].PHP_EOL;
			die();
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