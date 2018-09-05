<?php

require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
require_once('../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

if(empty($_REQUEST['id'])){
} else {
	$res = $rc->get('/subscriptions', ['limit' => 250, 'page' => $page, 'address_id' => $_REQUEST['id']]);
}

$page = 1;
do {
	echo "Starting page $page".PHP_EOL;
	$sub_res = $rc->get('/subscriptions', ['limit' => 250, 'page' => $page, 'created_at_max' => '2018-09-05']);
	$product_cache = [];
	foreach($sub_res['subscriptions'] as $subscription){
		$old_product_id = $subscription['shopify_product_id'];
		if(!in_array($old_product_id, [738567323735, 738567520343, 738394865751])){
			continue;
		}

		$old_properties = [];
		foreach($subscription['properties'] as $property){
			$old_properties[$property['name']] = $property['value'];
		}

		if(empty($old_properties['total_items'])){
			echo "Couldn't find total_items for sub ".$subscription['id'].PHP_EOL;
			continue;
		}

		$frequency = $subscription['order_interval_frequency'] == '1' ? 'onetime' : $subscription['order_interval_frequency'];

		echo "address id: ".$subscription['address_id'].PHP_EOL;

		for($i = 1; $i <= $old_properties['total_items']; $i++){
			if(empty($old_properties['handle_'.$i])){
				continue;
			}
			$handle = strtok($old_properties['handle_'.$i], '-');
			$ids = $ids_by_scent[$handle];
			if(empty($ids)){
				echo "Couldn't find ids for handle ".$handle.PHP_EOL;
				continue;
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
				echo "Missing product / variant".PHP_EOL;
				var_dump($handle);
				var_dump($ids);
				var_dump($product);
				continue;
			}

			$quantity = $old_properties['qty_'.$i];
			if(empty($quantity)){
				$quantity = 1;
			}

			echo "add_subscription(\$rc, ".$product['id'].", ".$variant['id'].", {$subscription['address_id']}, ".strtotime($subscription['next_charge_scheduled_at']).", $quantity, $frequency);".PHP_EOL;
			$res = add_subscription($rc, $product, $variant, $subscription['address_id'], strtotime($subscription['next_charge_scheduled_at']), $quantity, $frequency);
			log_event($db, 'SUBSCRIPTION', json_encode($res), 'CREATED', json_encode($subscription), 'Subscription upgraded from old style', 'api');

		}

		// Check if we need to add sample discount
		$add_sample_discount =
			$old_properties['total_items'] == 1 && $frequency == 'onetime' && $subscription['price'] < 78
			|| $old_properties['total_items'] == 1 && $frequency != 'onetime' && $subscription['price'] < 66.3
			|| $old_properties['total_items'] == 2 && $frequency == 'onetime' && $subscription['price'] < 120
			|| $old_properties['total_items'] == 2 && $frequency != 'onetime' && $subscription['price'] < 102
			|| $old_properties['total_items'] == 3 && $frequency == 'onetime' && $subscription['price'] < 178
			|| $old_properties['total_items'] == 3 && $frequency != 'onetime' && $subscription['price'] < 151.3
			|| $old_properties['total_items'] == 4 && $frequency == 'onetime' && $subscription['price'] < 200
			|| $old_properties['total_items'] == 4 && $frequency != 'onetime' && $subscription['price'] < 170;

		if($add_sample_discount){
			echo "Add sample discount".PHP_EOL;
		}
		if($add_sample_discount && false){
			$res = $rc->get('/addresses/'.$subscription['address_id']);
			$address = $res['address'];
			if(!in_array('_sample_credit',array_column($address['cart_attributes'], 'name'))){
				$address['cart_attributes'][] = ['name' => '_sample_credit', 'value' => 20];
				$res = $rc->put('/addresses/'.$subscription['address_id'], [
					'cart_attributes' => $address['cart_attributes'],
				]);
//			log_event($db, 'DISCOUNT', json_encode($res), 'ADDED', json_encode($subscription), 'Discount added from old subscription', 'api');
			}
		}
		// Remove old sub
		echo '$rc->delete(\'/subscriptions/'.$subscription['id'].');'.PHP_EOL;
		$res = $rc->delete('/subscriptions/'.$subscription['id']);
		log_event($db, 'SUBSCRIPTION', json_encode($res), 'DELETED', json_encode($subscription), 'Subscription upgraded from old style', 'api');
		sleep(2);
	}
	$page++;
} while(count($sub_res['subscriptions']) >= 250);