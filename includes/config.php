<?php

require_once dirname(__FILE__).'/../vendor/autoload.php';

date_default_timezone_set('America/Los_Angeles');

spl_autoload_register(function($class){
	require_once(__DIR__.'/class.'.$class.'.php');
});

\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

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
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$sample_discount_code = 'SAMPLE25';

// Variants that are allowed to create subscriptions, eventually we won't use this but it's a good safeguard for now
$subscription_variant_ids = ['5672401895455', '12613449515095'];
$sample_credit_variant_ids = ['5672401895455', '12613449515095'];

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
	return;
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
	$res = $rc->post('/charges/'.$charge['id'].'/discounts/'.$code.'/apply');
	var_dump($res);
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
	return round($gross_price - $net_price, 2);
}

function calculate_discount_factors(PDO $db, RechargeClient $rc, $charge){
	global $ids_by_scent;
	$discount_factors = [];
	$scent_variant_ids = array_column($ids_by_scent, 'variant');

	$stmt_get_price = $db->prepare("SELECT price FROM variants WHERE shopify_id = ?");

	// Multi Bottle Discount
	$fullsize_count = 0;
//	var_dump($charge);
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

	// Scent Club Discount

	// Subscription Discount
	$line_item_total = 0;
	$subscription_item_total = 0;
	$notes = [];
	foreach($charge['line_items'] as $line_item){
		$res = $rc->get("/subscriptions/".$line_item['subscription_id']);
		if(empty($res['subscription'])){
			echo "No subscription!";
			var_dump($res);
			continue;
		}
		$stmt_get_price->execute([$line_item['shopify_variant_id']]);
		$price = $stmt_get_price->fetchColumn();
		$line_item_total += $line_item['price']*$line_item['quantity'];
		if($line_item['price'] < $price){
			continue;
		}
		if($res['subscription']['status'] != 'ONETIME'){
			$subscription_item_total += $line_item['price']*$line_item['quantity'];
		}
	}
	$order_subscription_percent = divide($subscription_item_total, $line_item_total);
	if($order_subscription_percent > 0){
		$discount_factors[] = ['key' => 'subscribe_and_save', 'type' => 'percent', 'amount' => $order_subscription_percent*.15, 'notes' => $notes];
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
			continue;
		}
		foreach($charge['line_items'] as $line_item){
			// don't do it for old sample products
			if(in_array($line_item['shopify_product_id'], [738567323735, 738567520343, 738394865751])){
				continue 2;
			}
		}

		$discount_factors = calculate_discount_factors($db, $rc, $charge);
//		var_dump($discount_factors);
		$discount_amount = calculate_discount_amount($charge, $discount_factors);
//		var_dump($discount_amount);

		$code = get_charge_discount_code($db, $rc, $discount_amount);
//		var_dump($code);
		apply_discount_code($rc, $charge, $code);
	}
}

function offset_date_skip_weekend($time){
	while(in_array(date('N', $time), [6,7])){ // While it's a weekend
		$time += 24*60*60; //  Add a day
	}
	return $time;
}

function insert_update_product(PDO $db, $shopify_product){
	$now = date('Y-m-d H:i:s');
	$stmt = $db->prepare("INSERT INTO products
(shopify_id, handle, title, type, tags, updated_at)
VALUES (:shopify_id, :handle, :title, :type, :tags, :updated_at)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), handle=:handle, title=:title, type=:type, tags=:tags, updated_at=:updated_at");
	$stmt->execute([
		'shopify_id' => $shopify_product['id'],
		'handle' => $shopify_product['handle'],
		'title' => $shopify_product['title'],
		'type' => $shopify_product['product_type'],
		'tags' => $shopify_product['tags'],
		'updated_at' => $now,
	]);
	print_r($shopify_product);
	$product_id = $db->lastInsertId();
	$stmt = $db->prepare("INSERT INTO variants
(product_id, shopify_id, title, price, updated_at)
VALUES (:product_id, :shopify_id, :title, :price, :updated_at)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), title=:title, price=:price, updated_at=:updated_at");
	foreach($shopify_product['variants'] as $shopify_variant){
		$stmt->execute([
			'product_id' => $product_id,
			'shopify_id' => $shopify_variant['id'],
			'title' => $shopify_variant['title'],
			'price' => $shopify_variant['price'],
			'updated_at' => $now,
		]);
	}
	return $product_id;
}
function insert_update_order(PDO $db, $shopify_order){
	$now = date('Y-m-d H:i:s');
	$stmt = $db->prepare("INSERT INTO orders
(shopify_id, app_id, cart_token, `number`, total_price, created_at, updated_at)
VALUES (:shopify_id, :app_id, :cart_token, :number, :total_price, :created_at, :updated_at)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), app_id=:app_id, cart_token=:cart_token, `number`=:number, updated_at=:updated_at");
	$stmt->execute([
		'shopify_id' => $shopify_order['id'],
		'app_id' => $shopify_order['app_id'],
		'cart_token' => $shopify_order['cart_token'],
		'number' => $shopify_order['number'],
		'total_price' => $shopify_order['total_price'],
		'created_at' => date("Y-m-d H:i:s", strtotime($shopify_order['created_at'])),
		'updated_at' => date("Y-m-d H:i:s", strtotime($shopify_order['updated_at'])),
	]);
	$error = $stmt->errorInfo();
	if($error[0] != 0){
		$stmt = $db->prepare("INSERT INTO event_log (category, action, value, value2) VALUES (:category, :action, :value, :value2)");
		$stmt->execute([
			'category' => 'DB',
			'action' => 'ERROR',
			'value' => json_encode($shopify_order),
			'value2' => json_encode($error),
		]);
	}
	return $db->lastInsertId();
}
if(!function_exists('divide')){
	function divide($numerator, $denominator){
		if(empty($denominator)){
			return 0;
		}
		return $numerator/$denominator;
	}
};
function get_next_subscription_time($start_date, $order_interval_unit, $order_interval_frequency, $order_day_of_month = null, $order_day_of_week = null){
	$next_charge_time = strtotime($start_date.' +'.$order_interval_frequency.' '.$order_interval_unit);
	if($order_interval_unit == 'month' && !empty($order_day_of_month)){
		$next_charge_time = strtotime(date('Y-m-'.$order_day_of_month, $next_charge_time));
	} else if($order_interval_unit && !empty($order_day_of_week)){
		// TODO if needed
	}
	return $next_charge_time;
}
// Start Scent Club
function generate_subscription_schedule(PDO $db, $orders, $subscriptions, $onetimes = [], $charges = [], $max_time = null){
	$schedule = [];

	$max_time = empty($max_time) ? strtotime('+12 months') : $max_time;

	$products = [];

	$stmt_get_swap = $db->prepare("SELECT * FROM sc_product_info WHERE sc_date=?");

	foreach($subscriptions as $subscription){
		$next_charge_time = strtotime($subscription['next_charge_scheduled_at']);

		while($next_charge_time < $max_time){
			$date = date('Y-m-d', $next_charge_time);
			if(empty($schedule[$date])){
				$schedule[$date] = [
					'items' => [],
					'ship_date_time' => strtotime($date),
					'discounts' => [], // TODO
					'total' => 0,
				];
			}
			if(empty($products[$subscription['shopify_product_id']])){
				$products[$subscription['shopify_product_id']] = get_product($db, $subscription['shopify_product_id']);
			}
			if(is_scent_club($products[$subscription['shopify_product_id']])){
//				$stmt_get_swap->execute([date('Y-m',$next_charge_time).'-01']);
				// Swap here if changing to non-fill in
			}
			$subscription['type'] = 'subscription';
			$subscription['subscription_id'] = $subscription['id'];
			$schedule[$date]['items'][] = $subscription;
			$next_charge_time = strtotime($date.' +'.$subscription['order_interval_frequency'].' '.$subscription['order_interval_unit']);
			if($subscription['order_interval_unit'] == 'month' && !empty($subscription['order_day_of_month'])){
				$next_charge_time = strtotime(date('Y-m-'.$subscription['order_day_of_month'], $next_charge_time));
			} else if($subscription['order_interval_unit'] == 'week' && !empty($subscription['order_day_of_week'])){
				// TODO if needed
			}
		}
	}
	foreach($orders as $order){
		$order_time = strtotime($order['scheduled_at']);
		if($order_time > $max_time){
			continue;
		}
		$date = date('Y-m-d', $order_time);
		if(empty($schedule[$date])){
			$schedule[$date] = [
				'items' => [],
				'ship_date_time' => strtotime($date),
				'discounts' => [], // TODO
				'total' => 0,
			];
		}
		foreach($order['line_items'] as $item){
			$item['id'] = $item['subscription_id'];
			$item['type'] = 'order';
			$item['order'] = $order;
			$schedule[$date]['items'][] = $item;
		}
	}
	foreach($onetimes as $onetime){
		$order_time = strtotime($onetime['next_charge_scheduled_at']);
		if($order_time < time()){
			continue;
		}
		if($order_time > $max_time){
			continue;
		}
		$date = date('Y-m-d', $order_time);
		if(empty($schedule[$date])){
			$schedule[$date] = [
				'items' => [],
				'ship_date_time' => strtotime($date),
				'discounts' => [], // TODO
				'total' => 0,
			];
		}
		$onetime['subscription_id'] = $onetime['id'];
		$onetime['type'] = 'onetime';
		$schedule[$date]['items'][] = $onetime;
	}
	foreach($charges as $charge){
		if($charge['status'] != 'QUEUED' && $charge['status'] != 'SKIPPED'){
			continue;
		}
		$order_time = strtotime($charge['scheduled_at']);
		if($order_time > $max_time){
			continue;
		}
		$date = date('Y-m-d', $order_time);
		if(empty($schedule[$date])){
			$schedule[$date] = [
				'items' => [],
				'ship_date_time' => strtotime($date),
				'discounts' => [],
				'total' => 0,
			];
		}
		$schedule[$date]['discounts'] = $charge['discount_codes'];
		foreach($charge['line_items'] as $item){
			foreach($schedule[$date]['items'] as $index=>$scheduled_item){
				if(
					(!empty($scheduled_item['subscription_id']) && $scheduled_item['subscription_id'] == $item['subscription_id'])
					|| ($scheduled_item['id'] == $item['subscription_id'])
				){
					$schedule[$date]['items'][$index]['skipped'] = $charge['status'] == 'SKIPPED';
					$schedule[$date]['items'][$index]['charge'] = $charge;
					$schedule[$date]['charge'] = $charge;
					continue 2;
				}
			}
			$item['id'] = $item['subscription_id'];
			$item['type'] = 'charge';
			$item['charge'] = $charge;
			$item['skipped'] = $charge['status'] == 'SKIPPED';
			$item['address_id'] = $charge['address_id'];
			$schedule[$date]['charge'] = $charge;
			$schedule[$date]['items'][] = $item;
		}
	}
	ksort($schedule);
	return $schedule;
}
function get_product(PDO $db, $shopify_product_id){
	$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
	$stmt->execute([$shopify_product_id]);
	return $stmt->fetch();
}
function is_scent_club($product){
	return $product['type'] == 'Scent Club';
}
function is_scent_club_month($product){
	return $product['type'] == 'Scent Club Month';
}
function is_scent_club_swap($product){
	return $product['type'] == 'Scent Club Swap';
}
function is_scent_club_any($product){
	return is_scent_club($product) || is_scent_club_month($product) || is_scent_club_swap($product);
}
function sc_swap_scent(RechargeClient $rc, $subscription_id, $new_scent, $time){

}
function sc_skip_future_charge(RechargeClient $rc, $subscription_id, $time){
	/*
	$res = $rc->get('/charges', [
		'subscription_id' => $subscription_id,
		'status' => 'QUEUED',
		'date_max' => date('Y-m-d', $time),
	]);
	if(empty($res['charges'])){
		return true;
	}
	$charges = $res['charges'];
	usort($charges, function($a, $b){
		if ($a['scheduled_at'] == $b['scheduled_at']) return 0;
		return $a['scheduled_at'] < $b['scheduled_at'] ? -1 : 1;
	});
	$charge = $charges[0];

	$charges_to_unskip = [];
	while(strtotime($charge['scheduled_at']) <= $time){
		$res = $rc->post('/charges/'.$charge['id'].'/skip', [
			'subscription_id' => $subscription_id,
		]);
		if(empty($res['charge'])){
			return false;
		}
		if($res['charge']['status'] == 'SKIPPED'){
			$charges_to_unskip[] = $res['charge'];
		} else {
			$charges_to_unskip[] = [
				'dummy' => true,
				'date' => $res['charge']['scheduled_at'],
			];
		}
		//print_r($rc->post('/charges/139403620/skip', ['subscription_id'=>37299566]));
	}
	*/
}
function sc_get_main_subscription(PDO $db, RechargeClient $rc, $filters = []){
	$res = $rc->get('/subscriptions', $filters);
	$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
	if(empty($res['subscriptions'])){
		return false;
	}
	foreach($res['subscriptions'] as $subscription){
		$stmt->execute([$subscription['shopify_product_id']]);
		$product = $stmt->fetch();
		if(is_scent_club($product)){
			return $subscription;
		}
	}
	return false;
}
function sc_calculate_next_charge_date(PDO $db, RechargeClient $rc, $address_id){
	$res = $rc->get('/onetimes', [
		'address_id' => $address_id,
	]);
	// Fix for api returning non-onetimes
	$onetimes = [];
	foreach($res['onetimes'] as $onetime){
		if($onetime['status'] == 'ONETIME'){
			$onetimes[] = $onetime;
		}
	}
	$res = $rc->get('/orders', [
		'address_id' => $address_id,
		'scheduled_at_min' => date('Y-m-t'),
		'status' => 'QUEUED',
	]);
	$orders = $res['orders'];
	$res = $rc->get('/charges', [
		'address_id' => $address_id,
		'date_min' => date('Y-m-t'),
		'status' => 'SKIPPED'
	]);
	$charges = $res['charges'];

	$products_by_id = [];
	$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
	$offset = 0;
	$next_charge_month = date('Y-m', strtotime("+1 months"));
	while(true) {
		$offset++;
		$next_charge_month = date('Y-m', strtotime("+$offset months"));
		foreach($onetimes as $item){
			$charge_date = date('Y-m', strtotime($item['next_charge_scheduled_at']));
			if($charge_date != $next_charge_month){
				continue;
			}
			if(!array_key_exists($item['shopify_product_id'], $products_by_id)){
				$stmt->execute([$item['shopify_product_id']]);
				$products_by_id[$item['shopify_product_id']] = $stmt->fetch();
			}
			if(is_scent_club_any($products_by_id[$item['shopify_product_id']])){
				continue 2;
			}
		}
		foreach($charges as $charge){
			$charge_date = date('Y-m', strtotime($charge['scheduled_at']));
			if($charge_date != $next_charge_month){
				continue;
			}
			foreach($charge['line_items'] as $item){
				if(!array_key_exists($item['shopify_product_id'], $products_by_id)){
					$stmt->execute([$item['shopify_product_id']]);
					$products_by_id[$item['shopify_product_id']] = $stmt->fetch();
				}
				if(is_scent_club_any($products_by_id[$item['shopify_product_id']])){
					continue 3;
				}
			}
		}
		foreach($orders as $order){
			if(date('Y-m', strtotime($order['scheduled_at'])) != $next_charge_month){
				continue;
			}
			foreach($order['line_items'] as $item){
				if(!array_key_exists($item['shopify_product_id'], $products_by_id)){
					$stmt->execute([$item['shopify_product_id']]);
					$products_by_id[$item['shopify_product_id']] = $stmt->fetch();
				}
				if(is_scent_club_any($products_by_id[$item['shopify_product_id']])){
					continue 3;
				}
			}
		}
		// If we've made it this far, there is no SC-related stuff scheduled for this offset
		break;
	}
	$main_sub = sc_get_main_subscription($db, $rc, [
		'address_id' => $address_id,
		'status' => 'ACTIVE',
	]);

	$res = $rc->post('/subscriptions/'.$main_sub['id'].'/set_next_charge_date',[
		'date' => $next_charge_month.'-01',
	]);
	//print_r($res);

	return $next_charge_month.'-01';
}
function sc_delete_month_onetime(PDO $db, RechargeClient $rc, $address_id, $time){
	$delete_month = date('Y-m', $time);
	$res = $rc->get('/onetimes/', [
		'address_id' => $address_id,
	]);
	$products_by_id = [];
	$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
	foreach($res['onetimes'] as $onetime){
		$ship_month = date('Y-m',strtotime($onetime['next_charge_scheduled_at']));
		if($ship_month != $delete_month){
			continue;
		}
		if(!array_key_exists($onetime['shopify_product_id'], $products_by_id)){
			$stmt->execute([$onetime['shopify_product_id']]);
			$products_by_id[$onetime['shopify_product_id']] = $stmt->fetch();
		}
		if(is_scent_club_any($products_by_id[$onetime['shopify_product_id']])){
			$rc->delete('/onetimes/'.$onetime['id']);
		}
	}
}
function sc_get_monthly_scent(PDO $db, $time = null){
	if(empty($time)){
		$time = time();
	}
	$stmt = $db->prepare("SELECT * FROM sc_product_info WHERE sc_date=?");
	$stmt->execute([date('Y-m', $time).'-01']);
	if($stmt->rowCount() < 1){
		return false;
	}
	return $stmt->fetch();
}
function sc_swap_to_monthly(PDO $db, RechargeClient $rc, $address_id, $time, $main_sub = []){
	if(empty($main_sub)){
		$main_sub = sc_get_main_subscription($db, $rc, [
			'address_id' => $address_id,
			'status' => 'ACTIVE',
		]);
	}
	if(empty($main_sub)){
//		echo "No Main Sub";
		return false;
	}
	sc_delete_month_onetime($db, $rc, $address_id, $time);
	// Look up monthly scent
	$scent_info = sc_get_monthly_scent($db, $time);
	if(empty($scent_info)){
		sc_calculate_next_charge_date($db, $rc, $address_id);
//		echo "No monthly scent";
		return false;
	}
	$res = $rc->post('/addresses/'.$address_id.'/onetimes', [
		'next_charge_scheduled_at' => date('Y-m-d H:i:s', $time),
		'shopify_variant_id' => $scent_info['shopify_variant_id'],
		'properties' => $main_sub['properties'],
		'price' => $main_sub['price'],
		'quantity' => 1,
		'product_title' => 'Monthly Scent Club',
		'variant_title' => $scent_info['variant_title'],
	]);
	sc_calculate_next_charge_date($db, $rc, $address_id);
	return $res['onetime'];
}
function sc_swap_to_signature(PDO $db, RechargeClient $rc, $address_id, $time, $shopify_variant_id){
	$main_sub = sc_get_main_subscription($db, $rc, [
		'address_id' => $address_id,
		'status' => 'ACTIVE',
	]);
	if(empty($main_sub)){
		echo "No Main Sub";
		return false;
	}
	$stmt = $db->prepare("SELECT title FROM variants WHERE shopify_id=?");
	$stmt->execute([$shopify_variant_id]);
	if($stmt->rowCount() < 1){
		echo "No Variant";
		return false;
	}
	$variant_title = $stmt->fetchColumn();
	sc_delete_month_onetime($db, $rc, $address_id, $time);
	$res = $rc->post('/addresses/'.$address_id.'/onetimes', [
		'next_charge_scheduled_at' => date('Y-m-d H:i:s', $time),
		'shopify_variant_id' => $shopify_variant_id,
		'properties' => $main_sub['properties'],
		'price' => $main_sub['price'],
		'quantity' => 1,
		'product_title' => 'Scent Club Swap-in',
		'variant_title' => $variant_title,
	]);
	sc_calculate_next_charge_date($db, $rc, $address_id);
	return $res['onetime'];
}
function price_without_trailing_zeroes($price = 0){
	if($price % 1 == 0){
		return number_format($price, 2);
	}
	return number_format($price);
}