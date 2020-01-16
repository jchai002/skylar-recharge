<?php
require_once(__DIR__.'/../includes/config.php');

$f = fopen(__DIR__.'/cancel_orders.csv', 'r');

$headers = array_map('strtolower',fgetcsv($f));

$rownum = 0;
while($row = fgetcsv($f)){
	$rownum++;
	if($rownum <= 2){
		continue;
	}
	$row = array_combine($headers, $row);
//	print_r($row);

//	$res = $sc->post('/admin/orders/'.$row['id'].'/cancel.json');
//	print_r($res);

	// Remake with correct sku
	$res = $rc->get('/orders', ['shopify_order_id'=>$row['id']]);
	$order = $res['orders'][0];

	if(empty($order)){
		print_r($res);
		die();
	}
	$res = $rc->post('/addresses/'.$order['address_id'].'/onetimes', [
		'address_id' => $order['address_id'],
		'next_charge_scheduled_at' => date('Y-m-d'),
		'product_title' => 'Scent Club Promo',
		'price' => '0',
		'quantity' => 1,
		'shopify_product_id' => 3516688433239,
		'shopify_variant_id' => 28003712663639,
	]);
	if(empty($res['onetime'])){
		print_r($res);
		echo $rownum;
		die();
	}
	echo $res['onetime']['address_id'].PHP_EOL;
}

$res = $rc->post('/addresses/'.$address_id.'/onetimes', ['address_id' => $address_id, 'next_charge_scheduled_at' => date('Y-m-d'), 'product_title' => 'Scent Club Promo', 'price' => '0', 'quantity' => 1, 'shopify_product_id' => 3516688433239, 'shopify_variant_id' => 28003712663639,]);