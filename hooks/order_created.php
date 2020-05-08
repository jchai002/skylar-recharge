<?php
require_once('../includes/config.php');

$scp = new ShopifyPrivateClient();

if(!empty($_REQUEST['id'])){
	$order = $sc->call('GET', '/admin/orders/'.intval($_REQUEST['id']).'.json');
} else {
	respondOK();
	$data = file_get_contents('php://input');
	log_event($db, 'log', $data);
	$order = json_decode($data, true);
}
if(empty($order)){
	die('no data');
}
//print_r($order);

// Cancel and refund test orders
$is_test = false;
if(empty($order['cancelled_at'])){
	foreach($order['discount_applications'] as $discount){
		if($discount['type'] != 'discount_code'){
			continue;
		}
		if($discount['code'] != 'TESTORDER'){
			continue;
		}
		echo "Canceling order, test".PHP_EOL;
		$is_test = true;
		cancel_and_refund_order($order, $sc, $rc);
		break;
	}
}

echo insert_update_order($db, $order, $sc).PHP_EOL;

$update_order = false;
$order_tags = explode(', ',$order['tags']);

echo "Checking SC hold logic".PHP_EOL;
$res = $sc->get('/admin/customers/search.json', [
	'query' => 'email:'.$order['email'],
]);
if(!empty($res)){
	$customer = $res[0];
}
$is_scent_club = false;
$stmt = $db->prepare("SELECT * FROM sc_product_info WHERE sku=?");
$hand_sanitizer_qty = 0;
foreach($order['line_items'] as $line_item){
	if(is_scent_club_promo(get_product($db, $line_item['product_id']))){
		continue;
	}
	if(is_scent_club_any(get_product($db, $line_item['product_id']))){
		$is_scent_club = true;
		$stmt->execute([$line_item['sku']]);
		if($stmt->rowCount() < 1){
			continue;
		}
		$sc_product = $stmt->fetch();
		if(time() < offset_date_skip_weekend(strtotime($sc_product['sc_date'])) - 7*60*60){ // Hold until 5 pm the day before
			$order_tags[] = 'HOLD: Scent Club Blackout';
			$update_order = true;
			echo 'Scent Club Hold'.PHP_EOL;
		}
	}
	if(in_array('Hand Sanitizer', get_product($db, $line_item['product_id'])['tags'])){
		$hand_sanitizer_qty += $line_item['quantity'];
		$order_tags[] = 'HOLD: Preorder';
		$order_tags[] = 'Preorder';
		$update_order = true;
	}
}
if($hand_sanitizer_qty > 10){
	$order_tags[] = 'HOLD: Processing';
	$order_tags[] = 'High Quantity Hand Sanitizer';
	send_alert($db, 12, "Order with $hand_sanitizer_qty hand sanitizers has been held. https://skylar.com/admin/orders/".$order['id'], 'Skylar Alert: High Qty Hand Sanitizer', ['tim@skylar.com', 'stacy@skylar.com']);
	$update_order = true;
}
echo "Check account activation".PHP_EOL;
if(!empty($customer) && $customer['state'] != 'enabled'){
	try {
		$res = $sc->post('/admin/customers/'.$customer['id'].'/account_activation_url.json');
		if(empty($res)){
			echo json_encode([
				'success' => true,
				'email_sent' => false,
				'res' => $res,
			]);
		} else {
			$url = $res;
			$data = base64_encode(json_encode([
				'token' => "KvQM7Q",
				'event' => 'Sent Transactional Email',
				'customer_properties' => [
					'$email' => $customer['email'],
				],
				'properties' => [
					'email_type' => $is_scent_club ? 'request_account_sc' : 'request_account',
					'first_name' => $customer['first_name'],
					'account_activation_url' => $url,
					'source' => 'order_created',
				]
			]));
			$ch = curl_init("https://a.klaviyo.com/api/track?data=$data");
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER => true,
			]);
			$res = json_decode(curl_exec($ch));
			log_event($db, 'EMAIL', 'account_activation', 'SENT', json_encode($res), json_encode($customer), 'order_created webhook');
			echo json_encode([
				'success' => true,
				'email_sent' => true,
				'res' => $res,
			]);
		}
	} catch(\GuzzleHttp\Exception\ClientException $e){
		log_event($db, 'EXCEPTION', 'SHOPIFY_API', json_encode($e), '', '', 'order_created webhook');
	}
}


// Checking $0
if(
	$order['source_name'] != 'shopify_draft_order'
	&& $order['total_line_items_price'] <= 0
	&& !in_array('28003712663639', array_column($order['line_items'], 'variant_id'))
){;
	send_alert($db, 5,
		"Received Order with $0 total_line_items_price price: ".PHP_EOL.print_r($order, true),
		"Skylar Alert: $0 Line item total",
		['tim@skylar.com', 'jazyln@skylar.com']
	);
	$order_tags['HOLD: Free Order'];
	$update_order = true;
}

// Add product to line item
foreach($order['line_items'] as $index=>$line_item){
	$order['line_items'][$index]['product'] = get_product($db, $line_item['product_id']);
}
echo "Checking GWPs".PHP_EOL;
if(!OrderCreatedController::are_gwps_valid($order)){
	$order_tags[] = 'HOLD: Invalid GWP';
	$update_order = true;
	send_alert($db, 3, 'Order '.$order['name'].' has been placed on hold for having an invalid GWP. https://skylar.com/admin/orders/'.$order['id'], 'Skylar Alert', ['tim@skylar.com', 'jazlyn@skylar.com']);
	echo "Sent alert - GWP invalid".PHP_EOL;
} else if(!OrderCreatedController::has_valid_gwp_quantity($order['line_items'])){
	$order_tags[] = 'HOLD: Invalid GWP Quantity';
	$update_order = true;
	send_alert($db, 3, 'Order '.$order['name'].' was has an invalid GWP quantity, likely in error. Please check it. https://skylar.com/admin/orders/'.$order['id'], 'Skylar Alert', ['tim@skylar.com', 'jazlyn@skylar.com', 'julie@skylar.com']);
	echo "Sent alert - invalid GWP quantity".PHP_EOL;
} else if(!OrderCreatedController::are_gwps_free($order['line_items'])){
	$order_tags[] = 'Charged For GWP';
	$update_order = true;
	send_alert($db, 3, 'Order '.$order['name'].' was charged for a GWP, likely in error. Please check it. https://skylar.com/admin/orders/'.$order['id'], 'Skylar Alert', ['tim@skylar.com', 'jazlyn@skylar.com']);
	echo "Sent alert - charged for gwp".PHP_EOL;
}
if(OrderCreatedController::shipping_looks_wrong($order)){
	$order_tags[] = 'HOLD: Shipping Discount Unused';
	$update_order = true;
	send_alert($db, 4, 'Order '.$order['name'].' has an unused shipping discount, likely in error. Please check it. https://skylar.com/admin/orders/'.$order['id'], 'Skylar Alert', ['tim@skylar.com', 'jazlyn@skylar.com']);
	echo "Sent alert - charged for gwp".PHP_EOL;
}

if(match_email($order['email'], $test_emails)){
	$order_tags[] = 'HOLD: Test Order';
	$update_order = true;
}
/*
// Scent experience digital codes
$scent_experience_quantity = array_sum(array_column(array_filter($order['line_items'], function($line_item){
	return $line_item['sku'] == '70804122-100'; // digital scent experience
}), 'quantity'));
if($scent_experience_quantity > 0 && $is_test){
	$codes = [];
	$value = 78;
	while(count($codes) < $scent_experience_quantity){
		// Generate code

		$gift_card = $scp->post('/admin/api/2019-10/gift_cards.json', ['gift_card' => [
			'note' => 'Digital Scent Experience for order '.$order['id'],
			'initial_value' => $value,
		]]);
		$codes[] = strtoupper($gift_card['code']);
		$order_tags[] = 'Scent Experience Code: '.$gift_card['code'];
	}
	klaviyo_send_transactional_email($db, $order['email'], 'scent_experience_gift_codes', ['codes'=>$codes, 'value' => $value]);
	$order_tags[] = 'Scent Experience Codes Emailed';
	$update_order = true;
}
*/
// Get recharge version of order
$rc_order = $rc->get('/orders',['shopify_order_id'=>$order['id']]);
//print_r($rc_order);
if(empty($rc_order['orders'])){
	if($update_order){
		$order_tags = array_unique($order_tags);
		$res = $sc->put("/admin/orders/".$order['id'].'.json', ['order' => [
			'id' => $order['id'],
			'tags' => implode(',', $order_tags),
		]]);
		if(!empty($res)){
			echo insert_update_order($db, $res, $sc).PHP_EOL;
		}
	}
	die('no rc order');
}
$rc_order = $rc_order['orders'][0];

$res = $rc->get('/subscriptions/', ['address_id' => $rc_order['address_id']]);
$subscriptions = [];
foreach($res['subscriptions'] as $subscription){
	$subscriptions[$subscription['id']] = $subscription;
	if(is_scent_club(get_product($db, $subscription['shopify_product_id']))){
		$sc_main_sub = $subscription;
	}
}

$gift_card_gwp_pricerules = [
	50 =>  ['id' => 600146706519, 'prefix' => 'GC10-', 'amount' => 10],
	100 => ['id' => 600147558487, 'prefix' => 'GC20-', 'amount' => 20],
	150 => ['id' => 600147918935, 'prefix' => 'GC30-', 'amount' => 30],
	200 => ['id' => 600148181079, 'prefix' => 'GC40-', 'amount' => 40],
];
$stmt_get_order_line = $db->prepare("SELECT id FROM order_line_items WHERE shopify_id=?");
if(empty($sc_main_sub)){
	$sc_main_sub = sc_get_main_subscription($db, $rc, [
		'customer_id' => $rc_order['customer_id'],
		'status' => 'ACTIVE',
	]);
}
echo "Checking line items".PHP_EOL;
foreach($order['line_items'] as $line_item){
	$product = get_product($db, $line_item['product_id']);
	print_r($product);
	$oli_frequency = get_oli_attribute($line_item, '_frequency') ?? 0;

	if($product['type'] == 'Gift Card'){
		if(strpos($order['email'], '@skylar.com') !== false || date('m') == 12 && date('d') >= 23 && date('d') <= 24){
			$pricerule = $gift_card_gwp_pricerules[$line_item['price']/100];
			$code = $pricerule['prefix'].generate_discount_string($line_item['id']);
			$res = $sc->post('/admin/api/2019-10/price_rules/'.$pricerule['id'].'/discount_codes.json',['discount_code' => [
				'code' => $code,
			]]);
			if(empty($res['code'])){
				log_event($db, 'CREATE_DISCOUNT', $code, 'ERROR', json_encode($res), json_encode($sc->last_error));
			} else {
				klaviyo_send_transactional_email($db, $order['email'], 'gift_card_extra_discount', ['code'=>$code, 'amount' => $pricerule['amount']]);
			}
		}
	}

	// Mark line item fulfilled in shopify
	echo "Checking fulfillment... ";
	echo $line_item['fulfillment_service'];
	echo $line_item['id'].PHP_EOL;
	if($line_item['fulfillment_service'] == 'skylar-autofulfill'){
		echo "Marking fulfilled by ".$line_item['fulfillment_service'].PHP_EOL;
		$fulfillment = $sc->post('/admin/api/2019-10/orders/'.$order['id'].'/fulfillments.json', ['fulfillment' => [
			'location_id' => 34417934423, // Autofulfill location ID
			'tracking_number' => null,
			'line_items' => [[
				'id' => $line_item['id'],
			]]
		]]);
	}

	if($product['type'] == 'Scent Club Gift' && $rc_order['type'] == 'CHECKOUT'){

		$months = [
			30725266440279 => 3,
			30725267882071 => 6,
			30725267914839 => 12,
			30995105480791 => 2, // Ship nows
			30995105513559 => 5,
			30995105546327 => 11,
		];

		$variant_map = [
			30725266440279 => 30995105480791,
			30725267882071 => 30995105513559,
			30725267914839 => 30995105546327,
			30995105480791 => 30995105480791, // Ship nows
			30995105513559 => 30995105513559,
			30995105546327 => 30995105546327,
		];

		// Create gift subscription

		$properties = [];
		foreach($line_item['properties'] as $property){
			$properties[$property['name']] = $property['value'];
		}

		$first_month_of_sub = get_oli_attribute($line_item, '_subscription_month');
		$add_gift_box = !empty(get_oli_attribute($line_item, '_add_gift_box'));
		$gift_note = get_oli_attribute($line_item, '_gift_note');
		echo $first_month_of_sub;
		if(empty($first_month_of_sub)){
			echo "next month";
			$next_charge_date = date('Y-m-d', offset_date_skip_weekend(get_next_month()));
		} else {
			echo "first month of sub";
			$next_charge_date = date('Y-m-d', offset_date_skip_weekend(strtotime($first_month_of_sub.'-01')));
		}
		var_dump($next_charge_date);

		$months = $months[$line_item['variant_id']];
		$properties = $line_item['properties'];
		$properties[] = ['name' => '_original_line_item_id', 'value' => $line_item['id']];

		$monthly_scent_info = sc_get_monthly_scent($db, strtotime($next_charge_date), true);

		$sub_data = [
			'address_id' => $rc_order['address_id'],
			'next_charge_scheduled_at' => $next_charge_date,
			'product_title' => 'Scent Club Gift',
			'variant_title' => '',
			'title' => 'Scent Club Gift',
			'price' => 0,
			'quantity' => 1,
			'shopify_variant_id' => $variant_map[$line_item['variant_id']],
			'order_interval_unit' => 'month',
			'order_interval_frequency' => 1,
			'charge_interval_frequency' => 1,
			'order_day_of_month' => 1,
			'expire_after_specific_number_of_charges' => $months,
			'properties' => $properties,
		];

		if(!empty($monthly_scent_info) && !empty($monthly_scent_info['sku'])){
			$sub_data['sku'] = $monthly_scent_info['sku'];
		}

		$res = $rc->post('/addresses/'.$rc_order['address_id'].'/subscriptions', $sub_data);
		print_r($res);
		if(!empty($res['subscription'])){
			echo insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
			if(!empty($gift_note) && !empty(get_oli_attribute($line_item, '_email'))){
				$order['note_attributes'][] = ['name' => 'gift_message', 'value' => $gift_note];
				$order['note_attributes'][] = ['name' => 'gift_message_email', 'value' => get_oli_attribute($line_item, '_email')];
				$order['note_attributes'][] = ['name' => 'gift_message_name', 'value' => get_oli_attribute($line_item, '_first_name')];
				$address_res = $rc->put('/addresses/'.$rc_order['address_id'], [
					'note_attributes' => $order['note_attributes'],
				]);
				print_r($address_res);
			}
			if($add_gift_box){
				$res = $rc->post('/addresses/'.$rc_order['address_id'].'/onetimes', [
					'next_charge_scheduled_at' => $next_charge_date,
					'price' => 0,
					'quantity' => 1,
					'shopify_variant_id' => 19811989880919, // Pink Satin Gift Bag
					'title' => 'Free Pink Satin Gift Bag',
					'product_title' => 'Free Pink Satin Gift Bag',
					'variant_title' => '',
				]);
				print_r($res);
				if(!empty($res['onetime'])){
					echo insert_update_rc_subscription($db, $res['onetimes'], $rc, $sc);
				}
			}
		}
		continue;
	}

	// Create body bundle subs
	$has_bb_sub = false;
	foreach($subscriptions as $subscription){
		if($subscription['shopify_variant_id'] == $line_item['variant_id']){
			echo "Has body bundle".PHP_EOL;
			$has_bb_sub = true;
			break;
		}
	}
	if($product['type'] == 'Body Bundle' && !$has_bb_sub && $rc_order['type'] == 'CHECKOUT' && !empty($oli_frequency)){
		$variant = get_variant($db, $line_item['variant_id']);
		echo "Adding body bundle ".PHP_EOL;

		// Set to order day
		$charge_day = date('d', strtotime($order['created_at']));
		if(date('t', get_next_month()) < $charge_day){
			$charge_day = date('t', get_next_month());
		}

		$next_charge_date = date('Y-m-'.$charge_day, get_month_by_offset(2));
		echo $charge_day.PHP_EOL;
		echo $next_charge_date.PHP_EOL;
		$res = $rc->post('/addresses/'.$rc_order['address_id'].'/subscriptions', [
			'address_id' => $rc_order['address_id'],
			'next_charge_scheduled_at' => $next_charge_date,
			'product_title' => $product['title'],
			'price' => get_subscription_price($product, $variant),
			'quantity' => 1,
			'shopify_variant_id' => $line_item['variant_id'],
			'order_interval_unit' => 'month',
			'order_interval_frequency' => $oli_frequency,
			'charge_interval_frequency' => $oli_frequency,
			'order_day_of_month' => $charge_day,
		]);
		print_r($res);
		if(!empty($res['subscription'])){
			echo insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
		}
		continue;
	}

	// Create and insert any autocharge items
	if(is_ac_followup_lineitem($line_item)){
		echo "Add AC Followup Hold Tag".PHP_EOL;
		$order_tags[] = 'HOLD: AC Followup';
		$update_order = true;
		continue;
	}
	if(is_ac_initial_lineitem($line_item)){
		echo "Attempting to create AC onetime... ";
		$stmt_get_order_line->execute([$line_item['id']]);
		$oli_id = $stmt_get_order_line->fetchColumn();
		$stmt = $db->prepare("SELECT * FROM ac_orders WHERE order_line_item_id=?");
		$stmt->execute([$oli_id]);
		echo $line_item['id'];
		print_r($stmt->errorInfo());
		if($stmt->rowCount() > 0){
			echo "Skipping, already exists";
			continue;
		}
//		print_r($stmt->fetchAll());
		$next_charge_time = offset_date_skip_weekend(strtotime('+21 days'));
		if(offset_date_skip_weekend(strtotime(date('Y-m-').'01', $next_charge_time)) == $next_charge_time){
			$next_charge_time += 25*60*60; // Add a day to offset AC from SC day
		}
    	$res = $rc->post('/addresses/'.$rc_order['address_id'].'/onetimes/',[
    		'next_charge_scheduled_at' => date('Y-m-d', $next_charge_time),
			'price' => '58',
			'quantity' => 1,
			'shopify_variant_id' => 31022109635, // Isle full size
			'product_title' => 'Isle',
			'variant_title' => '',
			'properties' => [
				'_ac_product' => $line_item['product_id'],
				'_ac_testcase' => get_oli_attribute($line_item, '_ac_testcase') ?? '1',
			],
		]);
    	if(!empty($res['onetime'])){
    		$subscription_id = insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
			var_dump($subscription_id);
			$stmt = $db->prepare("INSERT INTO ac_orders (order_line_item_id, followup_subscription_id) VALUES (?, ?)");
			$stmt->execute([$oli_id, $subscription_id]);
			print_r($stmt->errorInfo());
			echo "Created ".$res['onetime']['id']." (".$db->lastInsertId().")".PHP_EOL;
		} else {
//    		print_r($res);
		}
    }
}

// Tag orders that aren't samples as either onetime or subscription, with subscription
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
	$res = $sc->put("/admin/orders/".$order['id'].'.json', ['order' => [
		'id' => $order['id'],
		'tags' => implode(',', $order_tags),
	]]);
	if(!empty($res)){
		echo insert_update_order($db, $res, $sc).PHP_EOL;
	}
//	var_dump($res);
}


function cancel_and_refund_order($order, ShopifyClient $sc, RechargeClient $rc = null){
	$restock_line_items = [];
	foreach($order['line_items'] as $line_item){
		$restock_line_items[] = [
			'id' => $line_item['id'],
			'quantity' => $line_item['quantity'],
			'restock_type' => 'cancel',
			'location_id' => 36244366,
			'line_item' => $line_item,
			'line_item_id' => $line_item['id'],
		];
	}
	$res = $sc->post('/admin/orders/'.$order['id'].'/refunds/calculate.json', [
		'refund' => [
			'currency' => 'USD',
			'note' => 'Test order',
			'notify' => false,
			'shipping' => ['full_refund' => true],
			'refund_line_items' => $restock_line_items,
		],
	]);
	if(!empty($res)){
//		print_r($res);
		$refund = $res;
		foreach($res['transactions'] as $index => $transaction){
			$refund['transactions'][$index]['kind'] = 'refund';
		}
		$res = $sc->post('/admin/orders/'.$order['id'].'/cancel.json', [
			'note' => 'Test',
			'refund' => $refund,
		]);
//		print_r($res);
	} else {
		print_r($sc->last_error);
		$res = $sc->post('/admin/orders/'.$order['id'].'/cancel.json', [
			'note' => 'Test',
			'restock' => true,
			'amount' => $order['financial_status'] == 'refunded' ? 0 : $order['total_price_set']['shop_money']['amount'],
			'currency' => $order['total_price_set']['shop_money']['currency_code'],
		]);
//		print_r($res);
	}
	$res = $rc->get('/charges', [
		'shopify_order_id' => $order['id'],
	]);
	if(empty($res['charges'])){
		return true;
	}
	$charge = $res['charges'][0];
	$rc->post('/charges/'.$charge['id'].'/refund', [
		'amount' => $charge['total_price'],
	]);
	return true;
}