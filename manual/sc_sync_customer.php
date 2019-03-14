<?php

require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();

$main_sub = sc_get_main_subscription($db, $rc, [
	'status' => 'ACTIVE',
	'shopify_customer_id' => $_REQUEST['id'],
]);

$res = $sc->post('/admin/customers/'.$_REQUEST['id'].'/metafields.json', ['metafield'=> [
	'namespace' => 'scent_club',
	'key' => 'active',
	'value' => empty($main_sub) ? 0 : 1,
	'value_type' => 'integer'
]]);