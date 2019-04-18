<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

$order_id = 1024665026647;
$sc->put("/admin/orders/$order_id.json",['order' => [
	'id' => $order_id,
	'note_attributes' => [
		'metrilo_uid' => '26fb41f2dd57291f108f30bc5d4542b3b537db3a4d',
		'gift_message_email' => 'tim@skylar.com',
		'gift_message' => 'This is a test message!',
	],
]]);