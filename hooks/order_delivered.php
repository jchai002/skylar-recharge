<?php
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
$sc = new ShopifyPrivateClient($shop_url);

if(!empty($_REQUEST['id'])){
	$order = $sc->call('GET', '/admin/orders/'.intval($_REQUEST['id']).'.json');
} else {
	$data = file_get_contents('php://input');
	$fulfillment_event = json_decode($data, true);
	if($fulfillment_event['status'] != 'delivered'){
		die();
	}
	$order = $sc->call('GET', '/admin/orders/'.intval($fulfillment_event['order_id']).'.json');
}

$cart_attributes = [];
foreach($order['note_attributes'] as $attribute){
	$cart_attributes[$attribute['name']] = $attribute['value'];
}
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

$ch = curl_init("https://a.klaviyo.com/api/v1/email-template/LTNqPw/send");
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