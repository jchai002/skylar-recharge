<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

$order_id = null;
$sc->put("/admin/orders/$order_id.json",['order' => [
	'id' => $order_id,
	'note_attributes' => [
		'metrilo_uid' => '',
		'gift_message_email' => '',
		'gift_message' => '',
	],
]]);