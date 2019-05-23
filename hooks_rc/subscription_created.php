<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');

$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/subscriptions/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	log_event($db, 'webhook', $data, 'subscription_created');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
}
$subscription = $res['subscription'];
if($subscription['status'] != 'ACTIVE'){
	die();
}
var_dump($subscription);

$product = get_product($db, $subscription['shopify_product_id']);
if(!is_scent_club($product)){
	die();
}

$next_charge_time = strtotime($subscription['next_charge_scheduled_at']);
$max_time = empty($max_time) ? strtotime('+12 months') : $max_time;
echo date('Y-m-d', $max_time);
while($next_charge_time < $max_time){
	var_dump(date('Y-m-d', $next_charge_time));
	$monthly_scent = sc_get_monthly_scent($db, $next_charge_time);
	if(!empty($monthly_scent)){
		sc_swap_to_monthly($db, $rc, $subscription['address_id'], offset_date_skip_weekend($next_charge_time), $subscription);
		echo 'swap'.PHP_EOL;
	}
	$next_charge_time = get_next_subscription_time(date('Y-m-d', $next_charge_time), $subscription['order_interval_unit'], $subscription['order_interval_frequency'], $subscription['order_day_of_month'], $subscription['order_day_of_week']);
}
sc_calculate_next_charge_date($db, $rc, $subscription['address_id']);

$res = $rc->get('/customers/'.$subscription['customer_id']);
$shopify_customer_id = $res['customers'][0]['shopify_customer_id'];

$sc = new ShopifyClient();
$res = $sc->post('/admin/customers/'.$shopify_customer_id.'/metafields.json', ['metafield'=> [
	'namespace' => 'scent_club',
	'key' => 'active',
	'value' => 1,
	'value_type' => 'integer'
]]);
