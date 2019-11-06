<?php
require_once(__DIR__.'/../includes/config.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();
if(!empty($_REQUEST['id'])){
	$stmt = $db->prepare("SELECT * FROM fulfillments WHERE shopify_id=?");
	$stmt->execute([$_REQUEST['id']]);
} else {
	$stmt = $db->prepare("SELECT * FROM fulfillments WHERE delivered_at >= ? AND delivery_processed_at IS NULL");
	$stmt->execute([date('Y-m-d H:i:s', time()-(60*60*24*7))]);
	//$stmt->execute([date('Y-m-d H:i:s', time()-(60*60*24*30))]);
}
$fulfillments = $stmt->fetchAll();


$stmt_get_order_lines = $db->prepare("SELECT oli.id, p.shopify_id AS product_id FROM order_line_items oli
LEFT JOIN variants v ON oli.variant_id=v.id
LEFT JOIN products p ON v.product_id=p.id
WHERE oli.fulfillment_id=?");

$stmt_get_subscription_id = $db->prepare("SELECT s.recharge_id AS id FROM skylar.ac_orders aco
LEFT JOIN rc_subscriptions s ON aco.followup_subscription_id=s.id
WHERE s.id IS NOT NULL AND s.deleted_at IS NULL
AND aco.order_line_item_id=?");

$stmt_get_attributes = $db->prepare("SELECT o.attributes, o.shopify_id FROM orders o LEFT JOIN order_line_items oli ON oli.order_id=o.id WHERE oli.fulfillment_id=?");

$stmt_mark_processed = $db->prepare("UPDATE fulfillments SET delivery_processed_at=:now WHERE id=:id");
foreach($fulfillments as $fulfillment){
	echo "Processing fulfillment ID ".$fulfillment['id']." (".$fulfillment['shopify_id'].")".PHP_EOL;

	$stmt_get_order_lines->execute([$fulfillment['id']]);
	foreach($stmt_get_order_lines->fetchAll() as $line_item){



		$stmt_get_subscription_id->execute([$line_item['id']]);
		if($stmt_get_subscription_id->rowCount() == 0){
			continue;
		}
		echo "Has AC Order... ";
		// Has AC order, update ship date
		$subscription_id = $stmt_get_subscription_id->fetchColumn();
		$subscription = get_rc_subscription($db, $subscription_id, $rc, $sc);
		if(!empty($subscription['cancelled_at']) || !empty($subscription['deleted_at'])){
			echo "Subscription Deleted/Cancelled, skipping".PHP_EOL;
			continue;
		}
		if(strtotime($subscription['next_charge_scheduled_at']) < time()){
			echo "Subscription already dropped, skipping".PHP_EOL;
			continue;
		}
		$move_to_time = strtotime('+14 days', strtotime($fulfillment['delivered_at']));
		if(is_ac_pushed_back($subscription)){
			$move_to_time += 7*24*60*60;
		}
		if($move_to_time < time() || is_ac_pushed_up($subscription)){
			$move_to_time = strtotime('tomorrow');
		}
		$res = $rc->put('/onetimes/'.$subscription_id, [
			'next_charge_scheduled_at' => date('Y-m-d', offset_date_skip_weekend($move_to_time)),
			'properties' => [
				'_ac_product' => $line_item['product_id'],
				'_ac_delivered' => 1,
			]
		]);
		if(empty($res['onetime'])){
			echo "Error! ".print_r($res, true).PHP_EOL;
			sleep(10);
		} else {
			insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
			echo "Moved onetime id ".$subscription_id." to ".$res['onetime']['next_charge_scheduled_at'].PHP_EOL;
		}
	}

	$stmt_get_attributes->execute([$fulfillment['id']]);
	$dummy_order = $stmt_get_attributes->fetch();
	$dummy_order['attributes'] = json_decode($dummy_order['attributes'], true);

	$gift_message = get_order_attribute($dummy_order, 'gift_message');
	$gift_message_email = get_order_attribute($dummy_order, 'gift_message_email');

	// Gift message
	if(!empty($gift_message) && !empty($gift_message_email)){
		echo "Has gift message... ";

		$res = klaviyo_send_transactional_email($db, $gift_message_email, 'gift_message', [
			'gift_message' => $gift_message,
		]);
		
		// Get RC order & address
		$res = $rc->get('/orders', ['shopify_order_id' => $dummy_order['shopify_id']]);
		if(!empty($res['orders'])){
			$rc_order = $res['orders'][0];
			$res = $rc->get('/addresses/'.$rc_order['address_id']);
			if(!empty($res['address'])){
				echo "Found address, removing gift message attributes".PHP_EOL;
				$address = $res['address'];
				$note_attributes = [];
				foreach($address['note_attributes'] as $attribute){
					$note_attributes[$attribute['name']] = $attribute['value'];
				}
				if(!empty($note_attributes['gift_message'])){
					unset($note_attributes['gift_message']);
				}
				if(!empty($note_attributes['gift_message_email'])){
					unset($note_attributes['gift_message_email']);
				}
				if(!empty($note_attributes['gift_message_name'])){
					unset($note_attributes['gift_message_name']);
				}
				$cart_attributes = [];
				foreach($note_attributes as $name => $value){
					$cart_attributes[] = ['name'=>$name, 'value'=>$value];
				}
				$res = $rc->put('/addresses/'.$address['id'], [
					'note_attributes' => $cart_attributes,
				]);
				print_r($res);
			}

		}

	}


	$stmt_mark_processed->execute([
		'now' => date('Y-m-d H:i:s'),
		'id' => $fulfillment['id'],
	]);
	echo "Marked as processed".PHP_EOL;
}
