<?php

require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

$rc = new RechargeClient();

$page = 1;

if(!empty($_REQUEST['id'])){
	$res = $rc->get('/subscriptions', ['limit' => 250, 'page' => $page, 'created_at_max' => '2018-08-30']);
} else {
	$res = $rc->get('/subscriptions', ['limit' => 250, 'page' => $page, 'address_id' => $_REQUEST['id']]);
}

$product_cache = [];
foreach($res['subscriptions'] as $subscription){
	$old_product_id = $subscription['shopify_product_id'];
	if(!in_array($old_product_id, [738567323735, 738567520343, 738394865751])){
		continue;
	}

	$old_properties = [];
	foreach($subscription['properties'] as $property){
		$old_properties[$property['name']] = $property['value'];
	}

	if(empty($old_properties['total_items'])){
		echo "Couldn't find total_items for sub ".$subscription['id'];
		continue;
	}

	for($i = 0; $i < $old_properties['total_items']; $i++){
		$handle = $old_properties['handle_'.$i];
		if(empty($handle)){
			continue;
		}
		$handle = strtok($handle, '-');
		$ids = $ids_by_scent[$handle];
		if(empty($ids)){
			echo "Couldn't find ids for handle ".$handle;
			continue;
		}

		if(empty($product_cache[$ids['product']])){
			$product = $sc->call('GET', '/admin/products/'.$ids['product'].'.json');
		} else {
			$product = $product_cache[$ids['product']];
		}
		foreach($product['variants'] as $variant){
			if($variant['id'] == $ids['variant']){
				continue;
			}
		}
		if(empty($product) || empty($variant)){
			echo "Missing product / variant";
			var_dump($handle);
			var_dump($ids);
			var_dump($product);
			continue;
		}

		$quantity = $old_properties['qty_'.$i];
		if(empty($quantity)){
			$quantity = 1;
		}

		$frequency = $subscription['order_interval_frequency'] == '1' ? 'onetime' : $subscription['order_interval_frequency'];

		echo "add_subscription(\$rc, ".$product['id'].", ".$variant['id'].", {$subscription['address_id']}, ".strtotime($subscription['next_charge_scheduled_at']).", $quantity, $frequency)".PHP_EOL;
//		add_subscription($rc, $product, $variant, $subscription['address_id'], strtotime($subscription['next_charge_scheduled_at']), $quantity, $frequency);
	}
	// Remove old sub
	echo '$rc->post(\'/subscriptions/'.$subscription['id'].'/cancel\', [\'reason\'=>\'subscription auto upgraded\']);';
//	$rc->post('/subscriptions/'.$subscription['id'].'/cancel', ['reason'=>'subscription auto upgraded']);
}