<?php
http_response_code(200);
require_once('../includes/config.php');
require_once('../includes/class.ShopifyClient.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$headers = getallheaders();
$shop_url = null;
if(!empty($headers['X-Shopify-Shop-Domain'])){
	$shop_url = $headers['X-Shopify-Shop-Domain'];
}
if(empty($shop_url)){
	$shop_url = 'maven-and-muse.myshopify.com';
}
$sc = new ShopifyClient();
$rc = new RechargeClient();

if(!empty($_REQUEST['id'])){
	$order = $sc->call('GET', '/admin/orders/'.intval($_REQUEST['id']).'.json');
} else {
	respondOK();
	$data = file_get_contents('php://input');
	$fulfillment = json_decode($data, true);
	insert_update_fulfillment($db, $fulfillment);
	log_event($db, 'webhook', $fulfillment['id'], 'fulfillment_updated', $data);
	if($fulfillment['status'] != 'delivered'){
		die();
	}
	$order = $sc->call('GET', '/admin/orders/'.intval($fulfillment['order_id']).'.json');
}

die();

$cart_attributes = [];
foreach($order['note_attributes'] as $attribute){
	$cart_attributes[$attribute['name']] = $attribute['value'];
}

// Autocharge
$stmt = $db->prepare("SELECT s.recharge_id AS id FROM skylar.ac_orders aco
LEFT JOIN rc_subscriptions s ON aco.followup_subscription_id=s.id
WHERE s.id IS NOT NULL AND s.deleted_at IS NULL
AND aco.order_line_item_id=?");
$stmt_get_order_line = $db->prepare("SELECT id FROM order_line_items WHERE shopify_id=?");
foreach($fulfillment['line_items'] as $line_item){
	$stmt_get_order_line->execute([$line_item['id']]);
	$oli_id = $stmt_get_order_line->fetchColumn();
	$stmt->execute([$oli_id]);
	if($stmt->rowCount() == 0){
		continue;
	}
	// Has AC order, update ship date
	$subscription_id = $stmt->fetchColumn();
	$rc->put('/onetimes/'.$subscription_id, [
		'next_charge_scheduled_at' => date('Y-m-d', offset_date_skip_weekend(strtotime('+14 days'))),
		'properties' => [
			'_ac_product' => $line_item['product_id'],
			'_ac_delivered' => 1,
		]
	]);

}

// Mark as processed
$stmt = $db->prepare("UPDATE fulfillments SET delivery_processed_at=:now WHERE shopify_id=:id");
$stmt->execute([
	'now' => date('Y-m-d H:i:s'),
	'id' => $fulfillment['id'],
]);

// Gift message
if(empty($cart_attributes['gift_message']) || empty($cart_attributes['gift_message_email'])){
	die();
}

$ch = curl_init('https://a.klaviyo.com/api/v2/list/HSQctC/subscribe');

curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => json_encode([
		'api_key' => 'pk_4c31e0386c15cca46c19dac063c013054c',
		'profiles' => [
			[
				'email' => $cart_attributes['gift_message_email'],
				'$source' => 'Gift Message'
			]
		],
	]),
	CURLOPT_HTTPHEADER => [
		'api-key: pk_4c31e0386c15cca46c19dac063c013054c',
		'Content-Type: application/json',
	],
]);
$res = curl_exec($ch);
$stmt = $db->prepare("INSERT INTO event_log (category, action, value, value2, note) VALUES ('KLAVIYO', 'SUBSCRIBE', :email, :list, :response)");
$stmt->execute([
	'email' => $cart_attributes['gift_message_email'],
	'list' => 'HSQctC',
	'response' => $res,
]);
$res = json_decode($res, true);
var_dump($res);

$ch = curl_init("https://a.klaviyo.com/api/v1/email-template/HrK7rW/send");
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => [
		'api_key' => 'pk_4c31e0386c15cca46c19dac063c013054c',
		'from_email' => 'hello@skylar.com',
		'from_name' => 'Skylar',
		'subject' => 'Your Gift Has Arrived!',
		'to' => json_encode([
			['email' => $cart_attributes['gift_message_email']],
			['email' => 'tim@timnolansolutions.com'],
		]),
		'context' => json_encode([
			'gift_message' => $cart_attributes['gift_message'],
		]),
	]
]);
$res = curl_exec($ch);
$stmt = $db->prepare("INSERT INTO event_log (category, action, value, value2, note) VALUES ('KLAVIYO', 'EMAIL_SENT', :email, :message, :response)");
$stmt->execute([
	'email' => $cart_attributes['gift_message_email'],
	'message' => $cart_attributes['gift_message'],
	'response' => $res,
]);
$res = json_decode($res, true);
var_dump($res);