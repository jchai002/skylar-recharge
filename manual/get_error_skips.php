<?php
require_once(__DIR__.'/../includes/config.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();

$res = $rc->get('/onetimes',['created_at_min'=>'2019-07-31T13:00:00', 'created_at_max'=>'2019-07-31T14:00:00', 'limit' => 250]);
$onetimes = [];
foreach($res['onetimes'] as $onetime){
	if($onetime['shopify_variant_id'] != 28356655775831){
		continue;
	}
	$customer = get_rc_customer($db, $onetime['customer_id'], $rc, $sc);
	echo $customer['email'].PHP_EOL;
	$onetimes[] = $onetime;
}


echo count($onetimes).PHP_EOL;