<?php

require_once dirname(__FILE__).'/../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__.'/..');
$dotenv->load();

if(strpos(getcwd(), 'production') !== false){
    define('ENV_DIR', 'skylar-recharge-production');
} else {
    define('ENV_DIR', 'skylar-recharge-staging');
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}

$db = new PDO("mysql:host=".$_ENV['DB_HOST'].";dbname=".$_ENV['DB_NAME'].";charset=UTF8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

$sample_discount_code = 'SAMPLE25';

// Variants that are allowed to create subscriptions, eventually we won't use this but it's a good safeguard for now
$subscription_variant_ids = ['5672401895455', '5672401895455'];
$sample_credit_variant_ids = ['5672401895455', '5672401895455'];

$ids_by_scent = [
	'arrow'  => ['variant' => 31022048003,     'product' => 8985085187],
	'capri'  => ['variant' => 5541512970271,   'product' => 443364081695],
	'coral'  => ['variant' => 26812012355,     'product' => 8215300931],
	'isle'   => ['variant' => 31022109635,     'product' => 8985117187],
	'meadow' => ['variant' => 26812085955,     'product' => 8215317379],
	'willow' => ['variant' => 8328726413399,   'product' => 785329455191],
];
$multi_bottle_discounts = [
	2 => 36,
	3 => 56,
	4 => 112,
];

if (!function_exists('getallheaders')){ 
    function getallheaders(){ 
        $headers = []; 
       foreach ($_SERVER as $name => $value){ 
           if (substr($name, 0, 5) == 'HTTP_'){ 
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value; 
           } 
       } 
       return $headers; 
    } 
}
function log_event(PDO $db, $category='', $value='', $action='', $value2='', $note='', $actor=''){
	$stmt = $db->prepare("INSERT INTO event_log (category, action, value, value2, note, actor, date_created) VALUES (:category, :action, :value, :value2, :note, :actor, :date_created)");
	$stmt->execute([
		'category' => $category,
		'action' => $action,
		'value' => $value,
		'value2' => $value2,
		'note' => $note,
		'actor' => $actor,
		'date_created' => date('Y-m-d H:i:s'),
	]);
}
// Might be better to just group them by address ID
function group_subscriptions($subscriptions, $addresses){
	global $sample_credit_variant_ids;
	$subscription_groups = [];
	foreach($subscriptions as $subscription){
		/*
		if(!in_array($subscription['status'], ['ACTIVE', 'ONETIME', ]) || empty($subscription['next_charge_scheduled_at'])){
			continue;
		}
		*/
		// Sample palette
		if(in_array($subscription['shopify_variant_id'], $sample_credit_variant_ids)){
			continue;
		}
		if($subscription['shopify_product_id'] == '470601367583'){
			continue;
		}
		if($subscription['status'] == 'ONETIME' && empty($subscription['next_charge_scheduled_at'])){
			continue;
		}
		$next_charge_time = strtotime($subscription['next_charge_scheduled_at']);
		$next_charge_date = date('m/d/Y', $next_charge_time);
		$frequency = $subscription['status'] == 'ONETIME' ? '' : $subscription['order_interval_frequency'].$subscription['order_interval_unit'];
		$group_key = $subscription['status'].$next_charge_date.$frequency.$subscription['address_id'];
		$sample_credit = 0;
		foreach($addresses[$subscription['address_id']]['cart_attributes'] as $cart_attribute){
			if($cart_attribute['name'] == '_sample_credit' && is_numeric($cart_attribute['value'])){
				$sample_credit = number_format($cart_attribute['value'], 0);
			}
		}
		if(!array_key_exists($group_key, $subscription_groups)){
			$subscription_groups[$group_key] = [
				'group_key' => $group_key,
				'status' => $subscription['status'],
				'frequency' => $frequency,
				'order_interval_frequency' => empty($subscription['order_interval_frequency']) ? 'onetime' : $subscription['order_interval_frequency'],
				'onetime' => $subscription['status'] == 'ONETIME',
				'next_charge_date' => $next_charge_date,
				'next_charge_time' => $next_charge_time,
				'address_id' => $subscription['address_id'],
				'address' => $addresses[$subscription['address_id']],
				'sample_credit' => $sample_credit,
			];
		}
		// Fix recharge bug
		$price = $subscription['price'];
		if(empty($price)){
			$price = 78;
		}
		$subscription_groups[$group_key]['items'][] = [
			'id' => $subscription['id'],
			'product_id' => $subscription['shopify_product_id'],
			'variant_id' => $subscription['shopify_variant_id'],
			'quantity' => $subscription['quantity'],
			'product_title' => $subscription['product_title'],
			'variant_title' => $subscription['variant_title'],
			'title' => trim($subscription['product_title'] . ' ' .$subscription['variant_title']),
			'price' => $price,
			'total_price' => $price*$subscription['quantity'],
			'raw' => $subscription,
		];
	}

// Dynamic title generation, counts, totals
	foreach($subscription_groups as $group_key => $subscription_group){
		if(count($subscription_group['items']) == 1 && !empty($subscription_group['items'][0]['product_title'])){
			$subscription_group['title'] = trim($subscription_group['items'][0]['product_title'].' '.$subscription_group['items'][0]['variant_title']);
			$subscription_group['title'] .= $subscription_group['onetime'] ? ' Order' : ' Auto Renewal';
		} else {
			$subscription_group['title'] = $subscription_group['onetime'] ? 'Scheduled Order' : 'Scent Auto Renewal';
		}
		$subscription_group['id'] = $subscription_group['ids'] = implode(',',array_column($subscription_group['items'], 'id'));
		$subscription_group['total_quantity'] = array_sum(array_column($subscription_group['items'], 'quantity'));
		$subscription_group['total_price'] = $subscription_group['raw_price'] = number_format(array_sum(array_column($subscription_group['items'], 'total_price')), 2);
		$subscription_group['price_lines'] = calculate_price_lines($subscription_group);
		$subscription_groups[$group_key] = $subscription_group;
	}

	uasort($subscription_groups, function($a, $b){
		if($a['next_charge_time'] == $b['next_charge_time']){
			return 0;
		}
		return $a['next_charge_time'] > $b['next_charge_time'] ? 1 : -1;
	});
	return array_values($subscription_groups);
}

function add_subscription(RechargeClient $rc, $shopify_product, $shopify_variant, $address_id, $next_charge_time, $quantity = 1, $frequency='3', $frequency_unit='month', $status='ACTIVE'){
	if($frequency == 'onetime'){
		$response = $rc->post('/onetimes/address/'.$address_id, [
			'address_id' => $address_id,
			'next_charge_scheduled_at' => date('Y-m-d', $next_charge_time),
			'shopify_variant_id' => $shopify_variant['id'],
			'quantity' => $quantity,
			'price' => $shopify_variant['price'],
			'product_title' => $shopify_product['title'],
			'variant_title' => $shopify_variant['title'],
		]);
		if(empty($response['onetime'])){
			var_dump($response);
		}
		$subscription = $response['onetime'];
	} else {
		$response = $rc->post('/subscriptions', [
			'address_id' => $address_id,
			'next_charge_scheduled_at' => date('Y-m-d', $next_charge_time),
			'shopify_variant_id' => $shopify_variant['id'],
			'quantity' => $quantity,
			'order_interval_unit' => $frequency_unit,
			'order_interval_frequency' => $frequency,
			'charge_interval_frequency' => $frequency,
			'order_day_of_month' => date('d', $next_charge_time),
			'product_title' => $shopify_product['title'],
			'variant_title' => $shopify_variant['title'],
			'status' => $status,
		]);
		if(empty($response['subscription'])){
			var_dump($response);
		}
		$subscription = $response['subscription'];
	}
	return $subscription;
}

$standard_discount_codes = [];

function apply_discount_code(RechargeClient $rc, $charge, $code){
	if(!empty($charge['discount_codes'])){
		if($charge['discount_codes'][0]['code'] == $code){
			return true;
		}
		// Remove existing discount code
		$res = $rc->get('/discounts', ['discount_code' => $charge['discount_codes'][0]['code']]);
		if(empty($res['discounts'])){
			var_dump($res);
			return false;
		}
		$rc->put('/discounts/'.$res['discounts'][0]['id'].'/remove',[
			'charge_id' => $charge['id']
		]);
	}
	// Add discount code
	$rc->post('/charges/'.$charge['id'].'/discounts/'.$code.'/apply');
	return true;
}

function get_charge_discount_code(PDO $db, RechargeClient $rc, $discount_amount){
	global $standard_discount_codes;
	// See if we've got one saved locally
	if(array_key_exists(strval($discount_amount), $standard_discount_codes)){
		return $standard_discount_codes[$discount_amount];
	}
	// See if one exists via api
	$stmt = $db->prepare("SELECT code FROM recharge_discounts WHERE autogen=1 AND enabled=1 AND type='fixed_amount' AND value=?");
	$stmt->execute([$discount_amount*100]);
	if($stmt->rowCount() > 0){
		return $stmt->fetchColumn();
	}

	// Create a new discount code for this use case
	$code = 'AUTOGEN_'.rand(1000000,9999999);
	$res = $rc->post('/discounts',[
		'code' => $code,
		'value' => $discount_amount,
		'discount_type' => 'fixed_amount',
	]);
	$discount = $res['discount'];
	$stmt = $db->prepare("INSERT INTO recharge_discounts (rc_id, code, value, type, enabled, autogen) VALUES (:rc_id, :code, :value, 'fixed_amount', 1, 1)");
	$stmt->execute([
		'rc_id' => $discount['id'],
		'code' => $code,
		'value' => $discount_amount*100
	]);
//	var_dump($discount);
	return $code;
}

function calculate_discount_amount($charge, $discount_factors){
	$gross_price = 0;
	foreach($charge['line_items'] as $line_item){
		$gross_price += $line_item['price'] * $line_item['quantity'];
	}
	$net_price = $gross_price;
	foreach($discount_factors as $discount_factor){
		if($discount_factor['type'] == 'subtract'){
			$net_price -= $discount_factor['amount'];
		} else if($discount_factor['type'] == 'percent'){
			$net_price *= (1 - $discount_factor['amount']);
		}
	}
	return $gross_price - $net_price;
}

function calculate_discount_factors(RechargeClient $rc, $charge){
	global $ids_by_scent;
	$discount_factors = [];
	$scent_variant_ids = array_column($ids_by_scent, 'variant');

	// Multi Bottle Discount
	$fullsize_count = 0;
	foreach($charge['line_items'] as $line_item){
		if(in_array($line_item['shopify_variant_id'], $scent_variant_ids)){
			$fullsize_count += $line_item['quantity'];
		}
	}
	$discount_factors[] = ['key' => 'multi_bottle_discount', 'type' => 'subtract', 'amount' => calculate_multi_bottle_discount($fullsize_count)];

	// Sample credit
	if(!empty($charge['note_attributes'])){
		foreach($charge['note_attributes'] as $note_attribute){
			if($note_attribute['name'] == '_sample_credit' && !empty($note_attribute['value'])){
				$discount_factors[] = ['key' => 'sample_credit', 'type' => 'subtract', 'amount' => $note_attribute['value']];
			}
		}
	}

	// Subscription Discount
	$onetime = false;
	foreach($charge['line_items'] as $line_item){
		$res = $rc->get("/subscriptions/".$line_item['subscription_id']);
		if($res['subscription']['status'] == 'ONETIME'){
			$onetime = true;
			break;
		}
	}
	if(!$onetime){
		$discount_factors[] = ['key' => 'subscribe_and_save', 'type' => 'percent', 'amount' => .15];
	}
	return $discount_factors;
}
if(!function_exists('is_decimal')){
	function is_decimal($val){
		return is_numeric($val) && floor($val) != $val;
	}
}

function calculate_price_lines($subscription_group){
	global $ids_by_scent;
	$scent_variant_ids = array_column($ids_by_scent, 'variant');
	$carry_price = floatval(str_replace(',','',$subscription_group['total_price']));
	$price_lines = [
		['title' => 'Regular Price', 'type' => 'regular_price', 'amount' => number_format($carry_price, 2)],
	];

	// Multi Bottle Discount
	$fullsize_count = 0;
	foreach($subscription_group['items'] as $item){
		if(in_array($item['variant_id'], $scent_variant_ids)){
			$fullsize_count += $item['quantity'];
		}
	}
	$bottle_discount = calculate_multi_bottle_discount($fullsize_count);
	if($bottle_discount > 0){
		$carry_price -= $bottle_discount;
		$price_lines[] = ['title' => 'Added scent'.($bottle_discount != 1 ? 's' : '').' savings', 'type' => 'multibottle', 'amount' => number_format($carry_price, is_decimal($carry_price) ? 2 : 0), 'discount' => $bottle_discount];
	}

	// Sample Credit
	$sample_credit = 0;
	foreach($subscription_group['address']['cart_attributes'] as $cart_attribute){
		if($cart_attribute['name'] == '_sample_credit' && !empty($cart_attribute['value'])){
			$sample_credit = $cart_attribute['value'];
			break;
		}
	}
	if(!empty($sample_credit)){
		$carry_price -= $sample_credit;
		$price_lines[] = ['title' => '$'.$sample_credit.' credit auto applied', 'type' => 'sample_credit', 'amount' => number_format($carry_price, is_decimal($carry_price) ? 2 : 0)];
	}

	if(!$subscription_group['onetime']){
		$carry_price *= .85;
		$price_lines[] = ['title' => 'Subscribe and save 15%', 'type' => 'subscription', 'amount' => number_format($carry_price, is_decimal($carry_price) ? 2 : 0)];
	}

	return $price_lines;

}

function calculate_multi_bottle_discount($fullsize_count){
	global $multi_bottle_discounts;
	if($fullsize_count  <= 1 || $fullsize_count < min(array_keys($multi_bottle_discounts))){
		return 0;
	} else if(array_key_exists($fullsize_count, $multi_bottle_discounts)){
		$discount = $multi_bottle_discounts[$fullsize_count];
	} else if($fullsize_count >  max(array_keys($multi_bottle_discounts))) {
		$discount = $multi_bottle_discounts[max(array_keys($multi_bottle_discounts))];
	} else {
		$last_quantity = 1;
		$discount = 0;
		foreach($multi_bottle_discounts as $quantity => $discount){
			if($fullsize_count < $quantity){
				$discount = $multi_bottle_discounts[$last_quantity];
				break;
			}
			$last_quantity = $quantity;
		}
	}
	return $discount;
}

function update_charge_discounts(PDO $db, RechargeClient $rc, $charges){

	foreach($charges as $charge){
		if($charge['status'] != 'QUEUED'){
			exit;
		}
		foreach($charge['line_items'] as $line_item){
			// don't do it for old sample products
			if(in_array($line_item['shopify_product_id'], [738567323735, 738567520343, 738394865751])){
				return false;
			}
		}

		$discount_factors = calculate_discount_factors($rc, $charge);
		var_dump($discount_factors);
		$discount_amount = calculate_discount_amount($charge, $discount_factors);
		var_dump($discount_amount);

		$code = get_charge_discount_code($db, $rc, $discount_amount);
		var_dump($code);
		apply_discount_code($rc, $charge, $code);
	}
}

function offset_date_skip_weekend($time){
	while(in_array(date('N', $time), [6,7])){ // While it's a weekend
		$time += 24*60*60; //  Add a day
	}
	return $time;
}