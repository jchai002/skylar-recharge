<?php
require_once('../includes/config.php');

do {
	$filters = [
		'limit' => 250,
		'created_at_min' => '2018-09-04T00:00:00',
		'order' => 'created_at asc'
	];
	if(!empty($orders)){
		$filters['since_id'] = end($orders)['id'];
	} else {
//		$filters['since_id'] = 609137295447;
	}
	echo "Getting orders".PHP_EOL;
	$orders = $sc->call("GET", "/admin/orders.json", $filters);

	foreach($orders as $order){
		echo "Order ID ".$order['id'].": ";
		// Get recharge version of order
		$res = $rc->get('/orders',['shopify_order_id'=>$order['id']]);
		//var_dump($rc_order);
		if(empty($res['orders'])){
			echo "No RC order".PHP_EOL;
			continue;
		}
		$rc_order = $res['orders'][0];

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
					$update_order = true;
				} else {
					$order_tags[] = 'Sub Type: Recurring';
					$update_order = true;
				}
			}
		} else {
			echo $rc_order['type'].PHP_EOL;
		}
		if(empty($update_order)){
			continue;
		}
		if($update_order){
			$order_tags = array_unique($order_tags);
			$res = $sc->call("PUT", "/admin/orders/".$order['id'].'.json', ['order' => [
				'id' => $order['id'],
				'tags' => implode(',', $order_tags),
			]]);
//			var_dump($res);
			echo "Tags updated".PHP_EOL;
		}
	}
} while(count($orders) <= 250);