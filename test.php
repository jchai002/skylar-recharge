<?php
require_once('includes/config.php');
require_once('includes/class.ShopifyClient.php');
require_once('includes/class.RechargeClient.php');

$sc = new ShopifyClient();

try {
	$res = $sc->put('/admin/customers/644696211543.json', [
		'customer' => [
			'id' => 644696211543,
			'password' => 'test',
			'password_confirmation' => 'testtest',
		]
	]);
	print_r($res);
} catch(ShopifyApiException $e){
	var_dump($e->getResponse());
}

die();

$rc = new RechargeClient();
$rc_customer_id = 12965232;
$rc_address_id = 18278415;

/*
$res = $rc->put('/subscriptions/36415620', [
	'price' => 10,
]);

print_r($res);
die();

$res = $rc->post('/subscriptions', [
	'address_id' => 18278415,
	'next_charge_scheduled_at' => date('Y-m-d', strtotime('april 4 2019')),
	'product_title' => 'Test Subscription',
	'variant_title' => '',
	'price' => 0,
	'quantity' => 1,
	'shopify_variant_id' => 19519443370071,
	'order_interval_unit' => 'month',
	'order_interval_frequency' => '1',
	'charge_interval_frequency' => '3',
	'order_day_of_month' => '4',
]);

print_r($res);
*/

$res = $rc->get('/orders', [
	'customer_id' => $rc_customer_id,
	'status' => 'QUEUED',
]);
$orders = $res['orders'];
//print_r($orders);
$res = $rc->get('/subscriptions', [
	'customer_id' => $rc_customer_id,
	'status' => 'ACTIVE',
]);
$subscriptions = $res['subscriptions'];
//print_r($subscriptions);

$schedule = generate_subscription_schedule($orders, $subscriptions);
$swaps = [
	['year' => 2019, 'month' => 5, 'subscription_id' => 36415620],
];


// TODO: Integrate SC Swaps
// TODO: Test with prepaid orders
function sc_schedule_swaps($schedule, $swaps){

}

die();

$output = [];
/*
$res = $rc->get('/subscriptions', ['customer_id' => 15240553]);
foreach($res['subscriptions'] as $subscription){
	if($subscription['shopify_product_id'] == '8215317379'){
		$res = $rc->delete('/subscriptions/'.$subscription['id']);
	}
}
*/
//var_dump($res);
//$res = $rc->get('/addresses/18278415');
//var_dump($res);
//die();
//$res = $rc->get('/customers/14587855');
$output[] = $rc->get('/subscriptions/', ['address_id' => 18278415]);
//$res = $rc->get('/charges/', ['customer_id' => 14954506]);
//$res = $rc->delete('/subscriptions/22190467');
//var_dump($res);

//die();

$res = $rc->get('/charges/', ['customer_id' => 12965232]);
foreach($res['charges'] as $charge){
	if($charge['status'] == 'REFUNDED'){
		continue;
	}
	$output_line = [$charge];
	$discount_factors = calculate_discount_factors($db, $rc, $charge);
	$output_line[] = $discount_factors;
	$discount_amount = calculate_discount_amount($charge, $discount_factors);
	$output_line[] = $discount_amount;
	$code = get_charge_discount_code($db, $rc, $discount_amount);
	$output_line[] = $code;
	$output[] = $output_line;
}
header('Content-Type: application/json');
echo json_encode($output);



$res = $rc->get('/subscriptions/', ['address_id' => 16130191]);
$subscriptions = $res['subscriptions'];
foreach($subscriptions as $subscription){
	$res = $rc->get('/charges/', ['subscription_id' => $subscription['id'], 'status' => 'QUEUED']);
	if(!empty($res['charges'])){
		$charge = $res['charges'][0];
		var_dump($charge);
		$discount_factors = calculate_discount_factors($db, $rc, $charge);
		var_dump($discount_factors);
		$discount_amount = calculate_discount_amount($charge, $discount_factors);
		var_dump($discount_amount);

		$code = get_charge_discount_code($db, $rc, $discount_amount);
		var_dump($code);
	}
//	var_dump($res);
}

die();


//$res = $rc->get('/addresses/16050958');
$res = $rc->get('/subscriptions/', ['address_id' => 16050958]);

var_dump($res);


die();

$res = $rc->put('/addresses/16048888', [
	'cart_attributes' => [['name' => '_sample_credit', 'value' => 20]],
]);
var_dump($res);

die();

$res = $rc->get('/addresses/15901834');
$address = $res['address'];

$ch = curl_init('https://ec2staging.skylar.com/hooks_rc/address_updated.php');
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER =>  true,
	CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => json_encode($res),
]);
echo curl_exec($ch);



die();


$res = $rc->get('/charges', [
	'subscription_id' => 21200731,
	'status' => 'QUEUED',
]);
var_dump($res);
if(empty($res['charges'])){
	exit;
}

die();


$res = $rc->get('/discounts', [
	'discount_type' => 'fixed_amount',
	'status' => 'enabled',
	'limit' => 250,
]);
var_dump($res);

die();


$res = $rc->get('/charges/count', ['status' => 'QUEUED']);
var_dump($res);

//$charges = $rc->get('/charges', ['subscription_id' => 21200731]);
//$charges = $rc->get('/charges', ['customer_id' => 12965232]);
$all_charges = [];
$page = 1;
do{
	$res = $rc->get('/charges', ['status' => 'QUEUED', 'limit' => '250', 'page' => $page]);
	if(empty($res['charges'])){
		break;
	}
	$charges = $res['charges'];
	$all_charges = array_merge($all_charges, $charges);
	$page++;
} while(count($charges) >= 250);

foreach($all_charges as $charge){
	foreach($charge['line_items'] as $line_item){
		if(in_array($line_item['shopify_product_id'], [738567323735, 738567520343, 738394865751])){
			continue 2;
		}
		var_dump($charge);
	}
}
//var_dump($charges);
