<?php
http_response_code(200);
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
$sc = new ShopifyClient($shop_url);
$rc = new RechargeClient();

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
//print_r($order);

// Cancel and refund test orders
foreach($order['discount_applications'] as $discount){
	if($discount['type'] != 'discount_code'){
		continue;
	}
	if($discount['code'] != 'TESTORDER'){
		continue;
	}
	echo "Canceling order, test".PHP_EOL;
	cancel_and_refund_order($order, $sc, $rc);
	break;
}

echo insert_update_order($db, $order, $sc).PHP_EOL;

echo "Checking alert".PHP_EOL;
$alert_id = 2;
$smother_message = false;
$alert_sent = false;
$msg = null;
if(
	$order['source_name'] != 'shopify_draft_order'
	&& $order['total_line_items_price'] <= 0
	&& !in_array('28003712663639', array_column($order['line_items'], 'variant_id'))
){
	$to = implode(', ',[
		'tim@timnolansolutions.com',
//		'sarah@skylar.com',
//		'cat@skylar.com',
	]);
	$msg = "Received Order with $0 total_line_items_price price: ".PHP_EOL.print_r($order, true);
	$headers = [
		'From' => 'Skylar Alerts <alerts@skylar.com>',
		'Reply-To' => 'tim@timnolansolutions.com',
		'X-Mailer' => 'PHP/' . phpversion(),
	];

	if($smother_message){
		echo "Smothering Alert";
	} else {
		echo "Sending Alert: ".PHP_EOL.$msg.PHP_EOL;

		mail($to, "ALERT: $0 Order", $msg
//				,implode("\r\n",$headers)
		);

		$alert_sent = true;
	}
	$stmt = $db->prepare("INSERT INTO alert_logs (alert_id, message, message_sent, message_smothered, date_created) VALUES ($alert_id, :message, :message_sent, :message_smothered, :date_created)");
	$stmt->execute([
		'message' => $msg,
		'message_sent' => $alert_sent ? 1 : 0,
		'message_smothered' => $smother_message ? 1 : 0,
		'date_created' => date('Y-m-d H:i:s'),
	]);
}

echo "Checking SC hold logic".PHP_EOL;
$res = $sc->get('/admin/customers/search.json', [
	'query' => 'email:'.$order['email'],
]);
if(!empty($res)){
	$customer = $res[0];
}
$is_scent_club = false;
$scent_club_hold = false;
$stmt = $db->prepare("SELECT * FROM sc_product_info WHERE sku=?");
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
		if(time() < strtotime($sc_product['sc_date']) + 10*60*60){ // Hold until 10 am
			$scent_club_hold = true;
		}
	}
}
echo $scent_club_hold ? 'Scent Club Hold'.PHP_EOL : '';
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
	} catch(ShopifyApiException $e){
		log_event($db, 'EXCEPTION', 'SHOPIFY_API', json_encode($e), '', '', 'order_created webhook');
	}
}

$order_tags = explode(',',$order['tags']);

// Get recharge version of order
$rc_order = $rc->get('/orders',['shopify_order_id'=>$order['id']]);
//print_r($rc_order);
if(empty($rc_order['orders'])){
	die('no rc order');
}
$rc_order = $rc_order['orders'][0];

// Create and insert any autocharge items
$stmt_get_order_line = $db->prepare("SELECT id FROM order_line_items WHERE shopify_id=?");
$sc_main_sub = sc_get_main_subscription($db, $rc, [
	'customer_id' => $rc_order['customer_id'],
	'status' => 'ACTIVE',
]);
echo "Checking line items".PHP_EOL;
foreach($order['line_items'] as $line_item){
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
    	$res = $rc->post('/addresses/'.$rc_order['address_id'].'/onetimes/',[
    		'next_charge_scheduled_at' => date('Y-m-d', strtotime('+28 days')),
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
//var_dump($update_order);

if($scent_club_hold){
	$order_tags[] = 'HOLD: Scent Club Blackout';
	$update_order = true;
}

if($update_order){
	$order_tags = array_unique($order_tags);
	$res = $sc->call("PUT", "/admin/orders/".$order['id'].'.json', ['order' => [
		'id' => $order['id'],
		'tags' => implode(',', $order_tags),
	]]);
	var_dump($res);
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