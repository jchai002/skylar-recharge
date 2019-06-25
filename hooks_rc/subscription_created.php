<?php
http_response_code(200);
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();
$rc = new RechargeClient();
// get $charge from webhook
if(!empty($_REQUEST['id'])){
	$res = $rc->get('/subscriptions/'.$_REQUEST['id']);
} else {
	$data = file_get_contents('php://input');
	if(!empty($data)){
		$res = json_decode($data, true);
	}
	log_event($db, 'webhook', $res['subscription']['id'], 'subscription_created', $data);
}
$subscription = $res['subscription'];
var_dump($subscription);

try {
	insert_update_rc_subscription($db, $subscription, $rc, $sc);
} catch(\Throwable $e){
	log_event($db, 'EXCEPTION', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'subscription_created_insert', json_encode($subscription), '', 'webhook');
}


if($subscription['status'] != 'ACTIVE'){
	die();
}

$product = get_product($db, $subscription['shopify_product_id']);
if(!is_scent_club($product)){
	die();
}

$next_charge_time = strtotime($subscription['next_charge_scheduled_at']);
$max_time = empty($max_time) ? strtotime('+12 months') : $max_time;
echo date('Y-m-d', $max_time);
$next_month = date('Y-m',get_next_month());
while($next_charge_time < $max_time){
	var_dump(date('Y-m-d', $next_charge_time));
	// Don't swap to monthly if we're looking at next month and it's in blackout
	if(date('Y-m',$next_charge_time) != $next_month || !sc_is_address_in_blackout($db, $rc, $subscription['address_id'])){
        $monthly_scent = sc_get_monthly_scent($db, $next_charge_time);
        if(!empty($monthly_scent)){
            sc_swap_to_monthly($db, $rc, $subscription['address_id'], offset_date_skip_weekend($next_charge_time), $subscription);
            echo 'swap'.PHP_EOL;
        }
    }
	$next_charge_time = get_next_subscription_time(date('Y-m-d', $next_charge_time), $subscription['order_interval_unit'], $subscription['order_interval_frequency'], $subscription['order_day_of_month'], $subscription['order_day_of_week']);
}
sc_calculate_next_charge_date($db, $rc, $subscription['address_id']);

$res = $rc->get('/customers/'.$subscription['customer_id']);
$shopify_customer_id = $res['customer']['shopify_customer_id'];

try {
	$res = $sc->post('/admin/customers/'.$shopify_customer_id.'/metafields.json', ['metafield'=> [
		'namespace' => 'scent_club',
		'key' => 'active',
		'value' => 1,
		'value_type' => 'integer'
	]]);
	print_r($res);
} catch(ShopifyApiException $e){
	log_event($db, 'API_ERROR', json_encode($e->getResponse()), 'POST customers/'.$shopify_customer_id.'/metafields.json', json_encode(['metafield'=> [
		'namespace' => 'scent_club',
		'key' => 'active',
		'value' => 1,
		'value_type' => 'integer'
	]]), '', 'subscription_created');
	var_dump($e->getResponse());
} catch(\Throwable $e){
	log_event($db, 'API_ERROR', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'POST customers/'.$shopify_customer_id.'/metafields.json', json_encode(['metafield'=> [
		'namespace' => 'scent_club',
		'key' => 'active',
		'value' => 1,
		'value_type' => 'integer'
	]]), '', 'subscription_created');
	var_dump($e);
}
