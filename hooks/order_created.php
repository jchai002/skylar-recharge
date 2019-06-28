<?php
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

echo insert_update_order($db, $order);

$alert_id = 2;
$smother_message = false;
$alert_sent = false;
$msg = null;
if($order['total_line_items_price'] <= 0 && !in_array('28003712663639', array_column($order['line_items'], 'variant_id'))){
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

$rc = new RechargeClient();

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


// Get recharge version of order
$rc_order = $rc->get('/orders',['shopify_order_id'=>$order['id']]);
print_r($rc_order);
if(empty($rc_order['orders'])){
	die('no rc order');
}
$rc_order = $rc_order['orders'][0];

// Insert any autocharge items
foreach($order['line_items'] as $line_item){
    if(is_autocharge_product(get_product($db, $line_item['product_id']))){
        $stmt = $db->prepare("INSERT IGNORE INTO ac_orders (order_line_item_id) VALUES (?)");
        $stmt->execute([$line_item['id']]);
    }
}

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


// Legacy Autocharge
// Get subs we need to create for this order
$has_subscription_line_item = false;
$subs_to_create = [];
$sample_credit = 0;
var_dump($sample_credit_variant_ids);
var_dump(array_column($order['line_items'], 'variant_id'));
foreach($order['line_items'] as $line_item){
	// TEMP: Skip for old sub type
	if(in_array($line_item['variant_id'], [738567520343,738394865751,738567323735])){
		die('variant '.$line_item['variant_id']);
	}
	if(in_array($line_item['variant_id'], $sample_credit_variant_ids)){
		echo "Crediting sample paletted: ".$line_item['price'].PHP_EOL;
		$sample_credit = $line_item['price'];
	}
	if(!in_array($line_item['variant_id'], $subscription_variant_ids)){
//		echo "skipping, not in subscription_variant_ids".PHP_EOL;
//		continue;
	}
	if(empty($line_item['properties'])){
		echo "skipping, no properties".PHP_EOL;
		continue;
	}
	$sub_scent = $sub_frequency = null;
	foreach($line_item['properties'] as $property){
		if($property['name'] == '_sub_frequency'){
			$sub_frequency = intval($property['value']);
		}
		if($property['name'] == '_sub_scent' && !empty($ids_by_scent[$property['value']])){
			$sub_scent = $property['value'];
		}
	}
	if(!empty($sub_scent) || !empty($sub_frequency)){
		$subs_to_create[] = [
			'ids' => $ids_by_scent[$sub_scent],
			'frequency' => $sub_frequency,
		];
	} else {
		echo "doesn't have scent and freq";
	}
}
var_dump($sample_credit);
$res = $rc->get('/addresses/'.$rc_order['address_id']);
$address = $res['address'];
if(!empty($sample_credit)){
	if(!in_array('_sample_credit',array_column($address['cart_attributes'], 'name'))){
		$address['cart_attributes'][] = ['name' => '_sample_credit', 'value' => $sample_credit];
		$res = $rc->put('/addresses/'.$rc_order['address_id'], [
			'cart_attributes' => $address['cart_attributes'],
		]);
		var_dump($res);
	}
}
if(empty($subs_to_create)){
	die('no subs to create');
}


$delay_days = 17; // TODO: more if international
$order_created_time = strtotime($order['created_at']);
$offset = 0;
do {
	$next_charge_time = $order_created_time + (($delay_days+$offset) * 24*60*60);
	$offset++;
} while(in_array(date('N', $next_charge_time), [6,7]));

$product_cache = [];

foreach($subs_to_create as $sub_data){
	if(empty($product_cache[$sub_data['ids']['product']])){
		$product = $sc->call('GET', '/admin/products/'.$sub_data['ids']['product'].'.json');
		$product_cache[$product['id']] = $product;
	} else {
		$product = $product_cache[$product['id']];
	}
	foreach($product['variants'] as $variant){
		if($variant['id'] == $sub_data['ids']['variant']){
			break;
		}
	}
	if($variant['id'] != $sub_data['ids']['variant']){
		continue;
	}

	$subscription = add_subscription($rc, $product, $variant, $rc_order['address_id'], $next_charge_time, 1, $sub_data['frequency']);
	$subscription_id = $subscription['id'];
}