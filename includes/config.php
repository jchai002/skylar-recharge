<?php

require_once dirname(__FILE__).'/../vendor/autoload.php';

date_default_timezone_set('America/Los_Angeles');

spl_autoload_register(function($class){
	require_once(__DIR__.'/class.'.$class.'.php');
});

$dotenv = new Dotenv\Dotenv(__DIR__.'/..');
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);

if(strpos(getcwd(), 'production') !== false){
    define('ENV_DIR', 'skylar-recharge-production');
    define('ENV_TYPE', 'LIVE');
	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);
	ini_set('log_errors', 1);
} else {
    define('ENV_DIR', 'skylar-recharge-staging');
	define('ENV_TYPE', 'STAGING');
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}
if(!empty($_REQUEST['c']) && ($_REQUEST['c'] == 644696211543 || $_REQUEST['c'] == 2096453255255)){
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
	if($category == 'log'){
		return;
	}
	//return;
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

	$scent_club_in_box = false;

	foreach($charge['line_items'] as $line_item){
		if(is_scent_club_any(get_product($db, $line_item['shopify_product_id']))){
			$scent_club_in_box = true;
			continue;
		}
		if(in_array($line_item['shopify_variant_id'], $scent_variant_ids)){
			$fullsize_count += $line_item['quantity'];
		}
	}

	// Scent Club Discount

	// Only apply if not scent club
	if(!$scent_club_in_box){
		$discount_factors[] = ['key' => 'multi_bottle_discount', 'type' => 'subtract', 'amount' => calculate_multi_bottle_discount($fullsize_count)];

		// Sample credit
		if(!empty($charge['note_attributes'])){
			foreach($charge['note_attributes'] as $note_attribute){
				if($note_attribute['name'] == '_sample_credit' && !empty($note_attribute['value'])){
					$discount_factors[] = ['key' => 'sample_credit', 'type' => 'subtract', 'amount' => $note_attribute['value']];
				}
			}
		}
	}

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
		$product = get_product($db, $line_item['shopify_product_id']);
		if(is_scent_club_any($product)){
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
		if(in_array($item['variant_id'], $scent_variant_ids) && $item['price'] == 78){
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
(shopify_id, handle, title, type, tags, updated_at, published_at)
VALUES (:shopify_id, :handle, :title, :type, :tags, :updated_at, :published_at)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), handle=:handle, title=:title, type=:type, tags=:tags, updated_at=:updated_at, published_at=:published_at");
	$stmt->execute([
		'shopify_id' => $shopify_product['id'],
		'handle' => $shopify_product['handle'],
		'title' => $shopify_product['title'],
		'type' => $shopify_product['product_type'],
		'tags' => $shopify_product['tags'],
		'updated_at' => $now,
		'published_at' => $shopify_product['published_at']
	]);
//	print_r($shopify_product);
	$product_id = $db->lastInsertId();
	$stmt = $db->prepare("INSERT INTO variants
(product_id, shopify_id, title, price, sku, updated_at)
VALUES (:product_id, :shopify_id, :title, :price, :sku, :updated_at)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), title=:title, price=:price, sku=:sku, updated_at=:updated_at");
	foreach($shopify_product['variants'] as $shopify_variant){
		$stmt->execute([
			'product_id' => $product_id,
			'shopify_id' => $shopify_variant['id'],
			'title' => $shopify_variant['title'],
			'sku' => $shopify_variant['sku'],
			'price' => $shopify_variant['price'],
			'updated_at' => $now,
		]);
	}
	return $product_id;
}
function insert_update_order(PDO $db, $shopify_order, ShopifyClient $sc){
	$now = date('Y-m-d H:i:s');
	if(!empty($shopify_order['customer'])){
		$customer_id = get_customer($db, $shopify_order['customer']['id'], $sc)['id'];
	} elseif(!empty($shopify_order['customer_id'])) {
		$customer_id = get_customer($db, $shopify_order['customer_id'], $sc)['id'];
	}else {
		$customer_id = null;
	}
	$stmt = $db->prepare("INSERT INTO orders
(shopify_id, customer_id, app_id, cart_token, `number`, total_line_items_price, total_discounts, total_price, tags, created_at, updated_at, cancelled_at, closed_at, email, note, attributes, source_name)
VALUES (:shopify_id, :customer_id, :app_id, :cart_token, :number, :total_line_items_price, :total_discounts, :total_price, :tags, :created_at, :updated_at, :cancelled_at, :closed_at, :email, :note, :attributes, :source_name)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), customer_id=:customer_id, app_id=:app_id, cart_token=:cart_token, `number`=:number, updated_at=:updated_at, total_line_items_price=:total_line_items_price, total_discounts=:total_discounts, total_price=:total_price, tags=:tags, cancelled_at=:cancelled_at, closed_at=:closed_at, email=:email, note=:note, attributes=:attributes, source_name=:source_name");
	$stmt->execute([
		'shopify_id' => $shopify_order['id'],
		'customer_id' => $customer_id,
		'app_id' => $shopify_order['app_id'],
		'cart_token' => $shopify_order['cart_token'],
		'number' => $shopify_order['number'],
		'total_line_items_price' => $shopify_order['total_line_items_price'],
		'total_discounts' => $shopify_order['total_discounts'],
		'total_price' => $shopify_order['total_price'],
		'tags' => $shopify_order['tags'],
		'created_at' => date("Y-m-d H:i:s", strtotime($shopify_order['created_at'])),
		'updated_at' => date("Y-m-d H:i:s", strtotime($shopify_order['updated_at'])),
		'cancelled_at' => empty($shopify_order['cancelled_at']) ? null : date("Y-m-d H:i:s", strtotime($shopify_order['cancelled_at'])),
		'closed_at' => empty($shopify_order['closed_at']) ? null : date("Y-m-d H:i:s", strtotime($shopify_order['closed_at'])),
		'email' => $shopify_order['email'],
		'note' => $shopify_order['note'],
		'attributes' => json_encode($shopify_order['note_attributes']),
		'source_name' => $shopify_order['source_name'],
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
	$order_id = $db->lastInsertId();
	$stmt = $db->prepare("INSERT INTO order_line_items (shopify_id, order_id, variant_id, total_discount, price, sku, product_title, variant_title, properties) VALUES (:shopify_id, :order_id, :variant_id, :total_discount, :price, :sku, :product_title, :variant_title, :properties) ON DUPLICATE KEY UPDATE order_id=:order_id, variant_id=:variant_id, total_discount=:total_discount, price=:price, sku=:sku, product_title=:product_title, variant_title=:variant_title, properties=:properties");
	foreach($shopify_order['line_items'] as $line_item){
		$variant = get_variant($db, $line_item['variant_id']);
		$stmt->execute([
			'shopify_id' => $line_item['id'],
			'order_id' => $order_id,
			'variant_id' => $variant['id'],
			'total_discount' => $line_item['total_discount'],
			'price' => $line_item['price'],
			'sku' => $line_item['sku'],
			'product_title' => $line_item['title'],
			'variant_title' => $line_item['variant_title'],
			'properties' => json_encode($line_item['properties']),
		]);
	}
	return $order_id;
}
function insert_update_customer(PDO $db, $shopify_customer){
	$stmt = $db->prepare("INSERT INTO customers (shopify_id, email, first_name, last_name, state, tags, updated_at) VALUES (:shopify_id, :email, :first_name, :last_name, :state, :tags, :updated_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), email=:email, first_name=:first_name, last_name=:last_name, state=:state, tags=:tags, updated_at=:updated_at");
	$stmt->execute([
		'shopify_id' => $shopify_customer['id'],
		'email' => $shopify_customer['email'],
		'first_name' => $shopify_customer['first_name'],
		'last_name' => $shopify_customer['last_name'],
		'state' => $shopify_customer['state'],
		'tags' => $shopify_customer['tags'],
		'updated_at' => date('Y-m-d H:i:s'),
	]);
	$customer_id = $db->lastInsertId();
	return $customer_id;
}
function insert_update_fulfillment(PDO $db, $shopify_fulfillment){
    $stmt = $db->prepare("SELECT delivered_at FROM fulfillments WHERE shopify_id = ?");
    $stmt->execute([$shopify_fulfillment['id']]);
    $delivered_at = $stmt->fetchColumn();
    if(empty($delivered_at) && $shopify_fulfillment['shipment_status'] == 'delivered'){
        $delivered_at = $shopify_fulfillment['updated_at'];
    }
    $stmt = $db->prepare("INSERT INTO fulfillments (shopify_id, name, service, shipment_status, status, delivered_at) VALUES (:shopify_id, :name, :service, :shipment_status, :status, :delivered_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), name=:name, service=:service, shipment_status=:shipment_status, status=:status, delivered_at=:delivered_at");
    $stmt->execute([
        'shopify_id' => $shopify_fulfillment['id'],
        'name' => $shopify_fulfillment['name'],
        'service' => $shopify_fulfillment['service'],
        'shipment_status' => $shopify_fulfillment['shipment_status'],
        'status' => $shopify_fulfillment['status'],
        'delivered_at' => $delivered_at,
    ]);
    $id = $db->lastInsertId();
    $stmt = $db->prepare("UPDATE order_line_items SET fulfillment_id = ? WHERE shopify_id=?");
    foreach($shopify_fulfillment['line_items'] as $line_item){
        $stmt->execute([$id, $line_item['id']]);
    }
    return $id;
}
function insert_update_rc_customer(PDO $db, $recharge_customer, ShopifyClient $sc){
	$stmt = $db->prepare("INSERT INTO rc_customers (recharge_id, customer_id, email, first_name, last_name, processor_type, status, has_valid_payment_method, reason_payment_method_invalid, updated_at) VALUES (:recharge_id, :customer_id, :email, :first_name, :last_name, :processor_type, :status, :has_valid_payment_method, :reason_payment_method_invalid, :updated_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), recharge_id=:recharge_id, customer_id=:customer_id, email=:email, first_name=:first_name, last_name=:last_name, processor_type=:processor_type, status=:status, has_valid_payment_method=:has_valid_payment_method, reason_payment_method_invalid=:reason_payment_method_invalid, updated_at=:updated_at");
	if(empty($recharge_customer['shopify_customer_id'])){
		$customer = ['id'=>null];
	} else {
		$customer = get_customer($db, $recharge_customer['shopify_customer_id'], $sc);
	}
	$stmt->execute([
		'recharge_id' => $recharge_customer['id'],
		'customer_id' => $customer['id'],
		'email' => $recharge_customer['email'],
		'first_name' => $recharge_customer['first_name'],
		'last_name' => $recharge_customer['last_name'],
		'processor_type' => $recharge_customer['processor_type'],
		'status' => $recharge_customer['status'],
		'has_valid_payment_method' => $recharge_customer['has_valid_payment_method'],
		'reason_payment_method_invalid' => $recharge_customer['reason_payment_method_not_valid'],
		'updated_at' => date('Y-m-d H:i:s'),
	]);
	return $db->lastInsertId();
}
function insert_update_rc_address(PDO $db, $recharge_address, RechargeClient $rc, ShopifyClient $sc){
	$stmt = $db->prepare("INSERT INTO rc_addresses (recharge_id, rc_customer_id, line1, line2, city, province, country, zip, company, phone, note, attributes, shipping_lines, updated_at) VALUES (:recharge_id, :rc_customer_id, :line1, :line2, :city, :province, :country, :zip, :company, :phone, :note, :attributes, :shipping_lines, :updated_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), rc_customer_id=:rc_customer_id, line1=:line1, line2=:line2, city=:city, province=:province, country=:country, zip=:zip, company=:company, phone=:phone, note=:note, attributes=:attributes, shipping_lines=:shipping_lines, updated_at=:updated_at");
	$recharge_customer = get_rc_customer($db, $recharge_address['customer_id'], $rc, $sc);
	$stmt->execute([
		'recharge_id' => $recharge_address['id'],
		'rc_customer_id' => $recharge_customer['id'],
		'line1' => $recharge_address['address1'],
		'line2' => $recharge_address['address2'],
		'city' => $recharge_address['city'],
		'province' => $recharge_address['province'],
		'country' => $recharge_address['country'],
		'zip' => $recharge_address['zip'],
		'company' => $recharge_address['company'],
		'phone' => $recharge_address['phone'],
		'note' => $recharge_address['cart_note'],
		'attributes' => json_encode($recharge_address['note_attributes']),
		'shipping_lines' => empty($recharge_address['shipping_lines_override']) ? json_encode($recharge_address['original_shipping_lines']) : json_encode($recharge_address['shipping_lines_override']),
		'updated_at' => date('Y-m-d H:i:s'),
	]);
	return $db->lastInsertId();
}
function insert_update_rc_subscription(PDO $db, $recharge_subscription, RechargeClient $rc, ShopifyClient $sc){
	$stmt = $db->prepare("INSERT INTO rc_subscriptions (recharge_id, address_id, product_title, variant_title, price, quantity, status, product_id, variant_id, order_interval_unit, order_interval_frequency, charge_interval_frequency, order_day_of_month, order_day_of_week, properties, expire_after_charges, cancellation_reason, max_retries_reached, next_charge_scheduled_at, created_at, updated_at, cancelled_at) VALUES (:recharge_id, :address_id, :product_title, :variant_title, :price, :quantity, :status, :product_id, :variant_id, :order_interval_unit, :order_interval_frequency, :charge_interval_frequency, :order_day_of_month, :order_day_of_week, :properties, :expire_after_charges, :cancellation_reason, :max_retries_reached, :next_charge_scheduled_at, :created_at, :updated_at, :cancelled_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), address_id=:address_id, product_title=:product_title, variant_title=:variant_title, price=:price, quantity=:quantity, status=:status, product_id=:product_id, variant_id=:variant_id, order_interval_unit=:order_interval_unit, order_interval_frequency=:order_interval_frequency, charge_interval_frequency=:charge_interval_frequency, order_day_of_month=:order_day_of_month, order_day_of_week=:order_day_of_week, properties=:properties, expire_after_charges=:expire_after_charges, cancellation_reason=:cancellation_reason, max_retries_reached=:max_retries_reached, next_charge_scheduled_at=:next_charge_scheduled_at, created_at=:created_at, updated_at=:updated_at, cancelled_at=:cancelled_at");
	$rc_address = get_rc_address($db, $recharge_subscription['address_id'], $rc, $sc);
	$variant = get_variant($db, $recharge_subscription['shopify_variant_id']);
	$stmt->execute([
		'recharge_id' => $recharge_subscription['id'],
		'address_id' => $rc_address['id'],
		'product_id' => $variant['product_id'],
		'variant_id' => $variant['id'],
		'price' => $recharge_subscription['price'],
		'quantity' => $recharge_subscription['quantity'],
		'status' => $recharge_subscription['status'],
		'product_title' => $recharge_subscription['product_title'],
		'variant_title' => $recharge_subscription['variant_title'],
		'order_interval_unit' => $recharge_subscription['order_interval_unit'] ?? null,
		'order_interval_frequency' => $recharge_subscription['order_interval_frequency'] ?? null,
		'charge_interval_frequency' => $recharge_subscription['charge_interval_frequency'] ?? null,
		'order_day_of_month' => $recharge_subscription['order_day_of_month'] ?? null,
		'order_day_of_week' => $recharge_subscription['order_day_of_week'] ?? null,
		'properties' => json_encode($recharge_subscription['properties']),
		'expire_after_charges' => $recharge_subscription['expire_after_specific_number_of_charges'] ?? null,
		'cancellation_reason' => $recharge_subscription['cancellation_reason'] ?? null,
		'max_retries_reached' => $recharge_subscription['max_retries_reached'] ?? null,
		'next_charge_scheduled_at' => date('Y-m-d', strtotime($recharge_subscription['next_charge_scheduled_at'])),
		'created_at' => $recharge_subscription['created_at'],
		'updated_at' => $recharge_subscription['updated_at'],
		'cancelled_at' => $recharge_subscription['cancelled_at'] ?? null,
	]);
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
	if(date('Y', $next_charge_time) == 2019 && date('m', $next_charge_time) <= 4){
		$next_charge_time = strtotime('2019-05-'.date('d', $next_charge_time)); // Don't double-dip for launch month
	}
	if($order_interval_unit == 'month' && !empty($order_day_of_month)){
		$next_charge_time = strtotime(date('Y-m-'.$order_day_of_month, $next_charge_time));
	} else if($order_interval_unit == 'week' && !empty($order_day_of_week)){
		// TODO if needed
	}
	return $next_charge_time;
}
function get_next_month($now = null){
	if(empty($now)){
		$now = time();
	}
	$month = date('m', $now);
	$year = date('Y', $now);
	if($month == 12){
		$year++;
		$month = 1;
	} else {
		$month++;
	}
	return strtotime($year.'-'.$month.'-01');
}
function get_last_month($now = null){
	if(empty($now)){
		$now = time();
	}
	$month = date('m', $now);
	$year = date('Y', $now);
	if($month == 1){
		$year--;
		$month = 12;
	} else {
		$month--;
	}
	return strtotime($year.'-'.$month.'-01');
}
// Start Scent Club
function sc_is_address_in_blackout(PDO $db, RechargeClient $rc, $address_id){
	$next_month_scent = sc_get_monthly_scent($db, get_next_month());
	if(!empty($next_month_scent)){
		$res = $rc->get('/orders', [
			'address_id' => $address_id,
			'scheduled_at_min' => date('Y-m-t', get_last_month()),
			'scheduled_at_max' => date('Y-m', get_next_month()).'-01',
			'status' => 'SUCCESS'
		]);
		if(!empty($res['orders'])){
			$this_month_orders = $res['orders'];
			foreach($this_month_orders as $this_month_order){
				foreach($this_month_order['line_items'] as $line_item){
					if($line_item['sku'] == $next_month_scent['sku']){
						return true;
						break 2;
					}
				}
			}
		}
	}
	return false;
}
function generate_subscription_schedule(PDO $db, $orders, $subscriptions, $onetimes = [], $charges = [], $max_time = null, $in_blackout = false){
	$schedule = [];

	$max_time = empty($max_time) ? strtotime('+12 months') : $max_time;

	/* // Because they show up as charges?
	foreach($onetimes as $onetime){
		$order_time = strtotime($onetime['next_charge_scheduled_at']);
		if(empty($order_time)){
			continue;
		}
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
	*/
	foreach($orders as $order){
		$order_time = strtotime($order['scheduled_at']);
		if(empty($order_time)){
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
		$order['next_charge_scheduled_at'] = $order['scheduled_at'];
		foreach($order['line_items'] as $item){
			$item['id'] = $item['subscription_id'];
			$item['type'] = 'order';
			$item['order'] = $order;
			$schedule[$date]['items'][] = $item;
		}
	}
	foreach($charges as $charge){
		if($charge['status'] != 'QUEUED' && $charge['status'] != 'SKIPPED'){
			continue;
		}
		$order_time = strtotime($charge['scheduled_at']);
		if(empty($order_time)){
			continue;
		}
		if($order_time < time()){
			continue;
		}
		if($order_time > $max_time){
			continue;
		}
		if($charge['status'] == 'SKIPPED' && date('Y', $order_time) == 2019 && date('m', $order_time) <= 4){
			continue;
		}
		$charge['next_charge_scheduled_at'] = $charge['scheduled_at'];
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
			if(empty($item['product_title']) && !empty($item['title'])){
				$item['product_title'] = $item['title'];
			}

            if(is_scent_club(get_product($db, $item['shopify_product_id']))){
                $swap = sc_get_monthly_scent($db, strtotime($charge['scheduled_at']), is_admin_address($item['address_id']));
                $item['swap'] = $swap;
                if(!empty($swap)){
                    $item['handle'] = $swap['handle'];
                    $item['shopify_product_id'] = $swap['shopify_product_id'];
                    $item['shopify_variant_id'] = $swap['shopify_variant_id'];
                    $item['product_title'] = $swap['product_title'];
                    $item['variant_title'] = $swap['variant_title'];
                }
            }
			$schedule[$date]['charge'] = $charge;
			$schedule[$date]['items'][] = $item;
		}
	}
	foreach($subscriptions as $subscription){
		$next_charge_time = strtotime($subscription['next_charge_scheduled_at']);
		if(empty($next_charge_time)){
			continue;
		}

		// Iterate through months, adding subscription as sub as individual items to each one
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
			$subscription['type'] = 'subscription';
			$subscription['subscription_id'] = $subscription['id'];
			$this_subscription = $subscription;
			if(is_scent_club(get_product($db, $this_subscription['shopify_product_id']))){
				$swap = sc_get_monthly_scent($db, $next_charge_time, is_admin_address($this_subscription['address_id']));
				$this_subscription['swap'] = $swap;
				if(!empty($swap)){
					$this_subscription['handle'] = $swap['handle'];
					$this_subscription['shopify_product_id'] = $swap['shopify_product_id'];
					$this_subscription['shopify_variant_id'] = $swap['shopify_variant_id'];
					$this_subscription['product_title'] = $swap['product_title'];
					$this_subscription['variant_title'] = $swap['variant_title'];
				}
			}
			$has_this_sub = false;
			foreach($schedule[$date]['items'] as $item){
				if(!empty($item['subscription_id']) && $item['subscription_id'] == $subscription['id']){
					$has_this_sub = true;
					break;
				}
			}
			if(!$has_this_sub){
				$schedule[$date]['items'][] = $this_subscription;
			}
			$next_charge_time = strtotime($date.' +'.$subscription['order_interval_frequency'].' '.$subscription['order_interval_unit']);
			if($subscription['order_interval_unit'] == 'month' && !empty($subscription['order_day_of_month'])){
				$next_charge_time = strtotime(date('Y-m-'.$subscription['order_day_of_month'], $next_charge_time));
			} else if($subscription['order_interval_unit'] == 'week' && !empty($subscription['order_day_of_week'])){
				// TODO if needed
			}
		}

		$next_charge_time = strtotime($subscription['next_charge_scheduled_at']);
		// Detect skips for scent club
		if(is_scent_club(get_product($db, $subscription['shopify_product_id']))){
			$offset = $in_blackout ? 2 : 1;
			$end_of_next_month_time = strtotime(date('Y-m-t', strtotime("+$offset months")));
			while($end_of_next_month_time < $next_charge_time){
				$this_subscription = $subscription;
				$date = date('Y-m', $end_of_next_month_time).'-'.(!empty($subscription['order_day_of_month']) ? str_pad($subscription['order_day_of_month'], 2, '0', STR_PAD_LEFT) : date('d', $next_charge_time));

				// Search schedule for any existing SC this month
				$date_split = explode('-', $date);
				$this_subscription['test'] = [];
				foreach($schedule as $sched_date=>$box){
					$sched_date_split = explode('-', $sched_date);
					$this_subscription['test'][] = [$sched_date_split, $date_split];
					if($sched_date_split[0] != $date_split[0] || $sched_date_split[1] != $date_split[1]){ // Month or year doesn't match
						continue;
					}
					foreach($box['items'] as $item){
						// If there is an SC item this month already, go to next month (while loop)
						if(is_scent_club_any(get_product($db, $item['shopify_product_id']))){
							$end_of_next_month_time = strtotime(date('Y-m-t', strtotime('+15 day', $end_of_next_month_time)));
							continue 3;
						}
					}
				}

				if(empty($schedule[$date])){
					$schedule[$date] = [
						'items' => [],
						'ship_date_time' => strtotime($date),
						'discounts' => [], // TODO
						'total' => 0,
					];
				}
				$swap = sc_get_monthly_scent($db, $end_of_next_month_time, is_admin_address($this_subscription['address_id']));
				$this_subscription['swap'] = $swap;
				if(!empty($swap)){
					$this_subscription['handle'] = $swap['handle'];
					$this_subscription['shopify_product_id'] = $swap['shopify_product_id'];
					$this_subscription['shopify_variant_id'] = $swap['shopify_variant_id'];
					$this_subscription['product_title'] = $swap['product_title'];
					$this_subscription['variant_title'] = $swap['variant_title'];
				}
				$this_subscription['type'] = 'subscription';
				$this_subscription['subscription_id'] = $subscription['id'];
				$this_subscription['status'] = 'SKIPPED';
				$this_subscription['skipped'] = true;
				$this_subscription['skip_info'] = [
					'end_of_next_month' => date('Y-m-d', $end_of_next_month_time),
					'next_charge' => date('Y-m-d', $next_charge_time),
					'swap' => $swap,
				];
				$schedule[$date]['items'][] = $this_subscription;
				$end_of_next_month_time = strtotime(date('Y-m-t', strtotime('+15 day', $end_of_next_month_time)));
			}
		}
	}
	ksort($schedule);
	foreach($schedule as $date=>$box){
		foreach($box['items'] as $index=>$item){
			$box['items'][$index]['is_sc_any'] = is_scent_club_any(get_product($db, $item['shopify_product_id']));
			$box['items'][$index]['is_ac_followup'] = is_ac_followup_lineitem($box['items'][$index]);
		}
		usort($box['items'], function($a, $b) use ($db) {
			if($a['is_sc_any'] == $b['is_sc_any']) {
				return 0;
			}
			return $a['is_sc_any'] ? -1 : 1;
		});
		$schedule[$date]['items'] = $box['items'];
	}
	return $schedule;
}
$customer_cache = [];
function get_customer(PDO $db, $shopify_customer_id, ShopifyClient $sc){
	global $customer_cache;
	if(!array_key_exists($shopify_customer_id, $customer_cache)){
		$stmt = $db->prepare("SELECT * FROM customers WHERE shopify_id=?");
		$stmt->execute([$shopify_customer_id]);
		if($stmt->rowCount() > 0){
			$customer_cache[$shopify_customer_id] = $stmt->fetch();
		} else {
			// Not in the DB, load it from Shopify
			$customer = $sc->get('/admin/customers/'.$shopify_customer_id.'.json');
			if(!empty($customer)){
				insert_update_customer($db, $customer);
				$stmt->execute([$shopify_customer_id]);
				$customer_cache[$shopify_customer_id] = $stmt->fetch();
			}
		}
	}
	return $customer_cache[$shopify_customer_id];
}
$rc_customer_cache = [];
function get_rc_customer(PDO $db, $recharge_customer_id, RechargeClient $rc, ShopifyClient $sc){
	global $rc_customer_cache;
	if(!array_key_exists($recharge_customer_id, $rc_customer_cache)){
		$stmt = $db->prepare("SELECT * FROM rc_customers WHERE recharge_id=?");
		$stmt->execute([$recharge_customer_id]);
		if($stmt->rowCount() > 0){
			$rc_customer_cache[$recharge_customer_id] = $stmt->fetch();
		} else {
			// Not in the DB, load it from ReCharge
			$res = $rc->get('/customers/'.$recharge_customer_id);
			if(!empty($res['customer'])){
				insert_update_rc_customer($db, $res['customer'], $sc);
				$stmt->execute([$recharge_customer_id]);
				$rc_customer_cache[$recharge_customer_id] = $stmt->fetch();
			}
		}
		$rc_customer_cache[$recharge_customer_id]['reason_payment_method_not_valid'] = $rc_customer_cache[$recharge_customer_id]['reason_payment_method_invalid'];
	}
	return $rc_customer_cache[$recharge_customer_id];
}
$rc_address_cache = [];
function get_rc_address(PDO $db, $recharge_address_id, RechargeClient $rc, ShopifyClient $sc){
	global $rc_address_cache;
	if(!array_key_exists($recharge_address_id, $rc_address_cache)){
		$stmt = $db->prepare("SELECT * FROM rc_addresses WHERE recharge_id=?");
		$stmt->execute([$recharge_address_id]);
		if($stmt->rowCount() < 1){
			// Not in the DB, load it from ReCharge
			$res = $rc->get('/addresses/'.$recharge_address_id);
			if(!empty($res['address'])){
				insert_update_rc_address($db, $res['address'], $rc, $sc);
				$stmt->execute([$recharge_address_id]);
			}
		}
		$rc_address_cache[$recharge_address_id] = $stmt->fetch();
		$rc_address_cache[$recharge_address_id]['attributes'] = json_decode($rc_address_cache[$recharge_address_id]['attributes'], true);
		$rc_address_cache[$recharge_address_id]['shipping_lines'] = json_decode($rc_address_cache[$recharge_address_id]['shipping_lines'], true);
		$rc_address_cache[$recharge_address_id]['original_shipping_lines'] = $rc_address_cache[$recharge_address_id]['shipping_lines_override'] = $rc_address_cache[$recharge_address_id]['shipping_lines'];
		$rc_address_cache[$recharge_address_id]['address1'] = $rc_address_cache[$recharge_address_id]['line1'];
		$rc_address_cache[$recharge_address_id]['address2'] = $rc_address_cache[$recharge_address_id]['line2'];
		$rc_address_cache[$recharge_address_id]['cart_note'] = $rc_address_cache[$recharge_address_id]['note'];
		$rc_address_cache[$recharge_address_id]['note_attributes'] = $rc_address_cache[$recharge_address_id]['attributes'];
	}
	return $rc_address_cache[$recharge_address_id];
}
$rc_subscription_cache = [];
function get_rc_subscription(PDO $db, $recharge_subscription_id, RechargeClient $rc, ShopifyClient $sc){
	global $rc_subscription_cache;
	if(!array_key_exists($recharge_subscription_id, $rc_subscription_cache)){
		$stmt = $db->prepare("SELECT * FROM rc_subscriptions WHERE recharge_id=?");
		$stmt->execute([$recharge_subscription_id]);
		if($stmt->rowCount() < 1){
			// Not in the DB, load it from ReCharge
			$res = $rc->get('/subscriptions/'.$recharge_subscription_id);
			if(!empty($res['subscription'])){
				insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
				$stmt->execute([$recharge_subscription_id]);
			}
		}
		$rc_subscription_cache[$recharge_subscription_id] = $stmt->fetch();
		$rc_subscription_cache[$recharge_subscription_id]['properties'] = json_decode($rc_subscription_cache[$recharge_subscription_id]['properties'], true);
		$rc_subscription_cache[$recharge_subscription_id]['expire_after_specific_number_of_charges'] = $rc_subscription_cache[$recharge_subscription_id]['expire_after_charges'];
	}
	return $rc_subscription_cache[$recharge_subscription_id];
}
$product_cache = [];
function get_product(PDO $db, $shopify_product_id){
	global $product_cache;
	if(!array_key_exists($shopify_product_id, $product_cache)){
		$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
		$stmt->execute([$shopify_product_id]);
		$product_cache[$shopify_product_id] = $stmt->fetch();
	}
	return $product_cache[$shopify_product_id];
}
$variant_cache = [];
function get_variant(PDO $db, $shopify_variant_id){
	global $variant_cache;
	if(!array_key_exists($shopify_variant_id, $variant_cache)){
		$stmt = $db->prepare("SELECT v.*, p.shopify_id AS shopify_product_id, p.title AS product_title FROM variants v LEFT JOIN products p ON p.id=v.product_id WHERE v.shopify_id=?");
		$stmt->execute([$shopify_variant_id]);
		$variant_cache[$shopify_variant_id] = $stmt->fetch();
	}
	return $variant_cache[$shopify_variant_id];
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
function is_scent_club_promo($product){
	return $product['type'] == 'Scent Club Promo';
}
function is_scent_club_any($product){
	return is_scent_club($product) || is_scent_club_month($product) || is_scent_club_swap($product) || is_scent_club_promo($product);
}
function sc_conditional_billing(RechargeClient $rc, $customer_id, $customer = false){
	if(empty($customer)){
		$res = $rc->get('/customers/', [
			'shopify_customer_id' => $customer_id,
		]);
		if(!empty($res['customers'])){
			$customer = $res['customers'][0];
		}
	}
	echo '{% assign nav_billing = '.(empty($customer) ? 'false' : 'true').' %}';
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
	if(empty($res['subscriptions'])){
		return false;
	}
	foreach($res['subscriptions'] as $subscription){
		if(is_scent_club(get_product($db, $subscription['shopify_product_id']))){
			return $subscription;
		}
	}
	return false;
}
function sc_calculate_next_charge_date(PDO $db, RechargeClient $rc, $address_id){
	$res = $rc->get('/onetimes', [
		'address_id' => $address_id,
	]);
	if(!array_key_exists('onetimes', $res)){
//		print_r($res);
	}
	// Fix for api returning non-onetimes
	$onetimes = [];
	foreach($res['onetimes'] as $onetime){
		if($onetime['status'] == 'ONETIME'){
			$onetimes[] = $onetime;
		}
	}
	//print_r($onetimes);
	$res = $rc->get('/orders', [
		'address_id' => $address_id,
		'scheduled_at_min' => date('Y-m-t'),
		'status' => 'QUEUED',
	]);
	if(!array_key_exists('orders', $res)){
//		print_r($res);
	}
	$orders = $res['orders'];
	$res = $rc->get('/charges', [
		'address_id' => $address_id,
		'date_min' => date('Y-m-t'),
		'status' => 'SKIPPED'
	]);
	if(!array_key_exists('charges', $res)){
//		print_r($res);
	}
	$charges = $res['charges'];

	$products_by_id = [];
	$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
	$offset = sc_is_address_in_blackout($db, $rc, $address_id) ? 1 : 0;
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

	$day_of_month = empty($main_sub['order_day_of_month']) ? '01' : $main_sub['order_day_of_month'];
	if($day_of_month == '1'){
		$day_of_month = date('d', offset_date_skip_weekend(strtotime($next_charge_month.'-01')));
	}
	$max_day_of_month = date('t', strtotime($next_charge_month.'-01'));
	$day_of_month = $day_of_month > $max_day_of_month ? $max_day_of_month : $day_of_month;
	$res = $rc->post('/subscriptions/'.$main_sub['id'].'/set_next_charge_date',[
		'date' => $next_charge_month.'-'.$day_of_month,
	]);
	log_event($db, 'subscription', $main_sub['id'], 'set_next_charge_date', $next_charge_month.'-'.$day_of_month, json_encode($res));
	//print_r($res);

	return $next_charge_month.'-'.$day_of_month;
}
function sc_delete_month_onetime(PDO $db, RechargeClient $rc, $address_id, $time){
	$delete_month = date('Y-m', $time);
	$res = $rc->get('/onetimes/', [
		'address_id' => $address_id,
	]);
	$monthly_scent = sc_get_monthly_scent($db, $time, is_admin_address($address_id));
	foreach($res['onetimes'] as $onetime){
		$ship_month = date('Y-m',strtotime($onetime['next_charge_scheduled_at']));
		if($ship_month != $delete_month){
			continue;
		}
		$product = get_product($db, $onetime['shopify_product_id']);
		if(is_scent_club_month($product) && $monthly_scent['sku'] != get_variant($db, $onetime['shopify_variant_id'])['sku']){
			continue;
		}
		if(is_scent_club_any($product)){
			$rc->delete('/onetimes/'.$onetime['id']);
		}
	}
}
function is_admin_address($address_id){
	return in_array($address_id, [
		29478544, // Julie
		29102064, // Jim
		//29806558, // Tim
	]);
}
function sc_get_monthly_scent(PDO $db, $time = null, $admin_preview = false){
	if(empty($time)){
		$time = time();
	}
	$preview_clause = $admin_preview ? '' : 'AND sc_live = 1';
	$stmt = $db->prepare("SELECT * FROM sc_product_info WHERE sc_date=? $preview_clause");
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
	$scent_info = sc_get_monthly_scent($db, $time, is_admin_address($address_id));
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
		'product_title' => 'Skylar Scent Club',
		'variant_title' => $scent_info['variant_title'],
	]);
	//print_r($res);
	usleep(1000); // Delay on Recharge's side :(
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
function sc_pull_profile_data(PDO $db, RechargeClient $rc, $rc_customer_id, $shopify_customer_id=false){
	$profile_data = [
		'daysoff' => '',
		'scent' => '',
		'whenwear' => '',
		'personality' => '',
	];
	if(!empty($rc_customer_id)){
		$res = $rc->get('/charges', ['customer_id' => $rc_customer_id]);
		if(!empty($res['charges'])){
			foreach($res['charges'] as $charge){
				foreach($charge['line_items'] as $line_item){
					if(!is_scent_club(get_product($db, $line_item['shopify_product_id']))){
						continue;
					}
					if(!empty($line_item['properties'])){
						foreach($line_item['properties'] as $property){
							if(strpos($property['name'], '_sc_preference_') === false){
								continue;
							}
							$profile_data[str_replace('_sc_preference_', '', $property['name'])] = $property['value'];
						}
					}
					if(!empty($profile_data)){
						$stmt = $db->prepare("INSERT INTO sc_profile_data (shopify_customer_id, data_key, data_value) VALUES (:shopify_customer_id, :data_key, :data_value) ON DUPLICATE KEY UPDATE data_value=:data_value");
						if(empty($shopify_customer_id)){
							$customer_res = $rc->get('/customers/'.$rc_customer_id);
							$shopify_customer_id = $customer_res['customer']['shopify_customer_id'];
						}
						foreach($profile_data as $key=>$value){
							$stmt->execute([
								'shopify_customer_id' => $shopify_customer_id,
								'data_key' => $key,
								'data_value' => $value,
							]);
						}
						break 2;
					}
				}
			}
		}
	}
	return $profile_data;
}
function sc_get_profile_data(PDO $db, RechargeClient $rc, $shopify_customer_id){
	$stmt = $db->prepare("SELECT data_key, data_value FROM sc_profile_data WHERE shopify_customer_id=?");
	$stmt->execute([$shopify_customer_id]);
	if($stmt->rowCount() > 0){
		return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
	}
	$res = $rc->get('/customers', ['shopify_customer_id' => $shopify_customer_id]);
	$rc_customer_id = false;
	if(!empty($res['customers'])){
		$rc_customer_id = $res['customers'][0]['id'];
	}
	return sc_pull_profile_data($db, $rc, $rc_customer_id, $shopify_customer_id);
}
function sc_get_profile_products($profile_data){
	$product_strings = [
		'arrow' => 'arrow::Full Size|rollie:12235409129559:Rollie',
		'capri' => 'capri::Full Size|rollie:12235492425815:Rollie',
		'coral' => 'coral::Full Size|rollie:12235492360279:Rollie',
		'isle' => 'isle::Full Size|rollie:12235492327511:Rollie',
		'meadow' => 'meadow::Full Size|rollie:12235492393047:Rollie',
		'willow' => 'willow::Full Size|rollie:12588614484055:Rollie',
	];
	$answer_mapping = [
		'scent' => [
			'fresh' => 'isle',
			'fruity' => 'coral',
			'woodsy' => 'willow',
			'floral' => 'meadow',
		],
		'daysoff' => [
			'hiking' => 'willow',
			'beach' => 'capri',
			'book' => 'isle',
			'friends' => 'arrow',
		],
		'personality' => [
			'adventurous' => 'capri',
			'shy' => 'meadow',
			'outgoing' => 'arrow',
			'funny' => 'coral',
		],
	];
	$product_handles = [];
	$products = [];
	foreach($answer_mapping as $profile_key => $answers){
		if(empty($profile_data[$profile_key]) || !array_key_exists($profile_data[$profile_key], $answers)){
			continue;
		}
		if(in_array($answers[$profile_data[$profile_key]], $product_handles)){
			continue;
		}
		$product_handles[] = $answers[$profile_data[$profile_key]];
	}
	if(count($product_handles) < 3){
		foreach(array_keys($product_strings) as $handle){
			if(!in_array($handle, $product_handles)){
				$product_handles[] = $handle;
			}
			if(count($product_handles) >= 3){
				break;
			}
		}
	}
	foreach($product_handles as $handle){
		$products[] = $product_strings[$handle];
	}
	return $products;
}

function price_without_trailing_zeroes($price = 0){
	if(floatval($price) == intval($price)){
		return number_format($price);
	}
	return number_format($price, 2);
}
// Autocharge
function is_ac_initial_product($sample_product){
    return $sample_product['shopify_id'] == 3875807395927; // Will probably have to be product type
}
function is_ac_followup_lineitem($followup_line_item){
	if(empty($followup_line_item['properties'])){
		return false;
	}
	if(!empty($followup_line_item['properties']['_ac_product'])){
		return true;
	}
	// Check if it's an indexed array (with name and value properties)
	if(array_keys($followup_line_item['properties'])[0] === 0){
		foreach($followup_line_item['properties'] as $property){
			if($property['name'] == '_ac_product' && !empty($property['value'])){
				return true;
			}
		}
	}
	return false;
}
function is_ac_pushed_back($followup_line_item){
	if(empty($followup_line_item['properties'])){
		return false;
	}
	if(!empty($followup_line_item['properties']['_ac_pushed_back'])){
		return true;
	}
	// Check if it's an indexed array (with name and value properties)
	if(array_keys($followup_line_item['properties'])[0] === 0){
		foreach($followup_line_item['properties'] as $property){
			if($property['name'] == '_ac_pushed_back' && !empty($property['value'])){
				return true;
			}
		}
	}
	return false;
}
function is_ac_pushed_up($followup_line_item){
	if(empty($followup_line_item['properties'])){
		return false;
	}
	if(!empty($followup_line_item['properties']['_ac_pushed_up'])){
		return true;
	}
	// Check if it's an indexed array (with name and value properties)
	if(array_keys($followup_line_item['properties'])[0] === 0){
		foreach($followup_line_item['properties'] as $property){
			if($property['name'] == '_ac_pushed_up' && !empty($property['value'])){
				return true;
			}
		}
	}
	return false;
}
function is_ac_delivered($followup_line_item){
	if(empty($followup_line_item['properties'])){
		return false;
	}
	if(!empty($followup_line_item['properties']['_ac_delivered'])){
		return true;
	}
	// Check if it's an indexed array (with name and value properties)
	if(array_keys($followup_line_item['properties'])[0] === 0){
		foreach($followup_line_item['properties'] as $property){
			if($property['name'] == '_ac_delivered' && !empty($property['value'])){
				return true;
			}
		}
	}
	return false;
}
// Klaviyo
function klaviyo_send_transactional_email(PDO $db, $to_email, $email_type, $properties=[]){
    $properties['email_type'] = $email_type;
    $stmt = $db->prepare("SELECT 1 FROM transactional_emails_sent WHERE email_type=:email_type AND to_address=:to_email AND DATE(date_created) = '".date('Y-m-d')."'");
    $stmt->execute([
        'email_type' => $email_type,
        'to_email' => $to_email,
    ]);
    if($stmt->rowCount() > 0){
        return false;
    }
    $res = klaviyo_send_event([
        'token' => "KvQM7Q",
        'event' => 'Sent Transactional Email',
        'customer_properties' => [
            '$email' => $to_email,
        ],
        'properties' => $properties,
    ]);
    $stmt = $db->prepare("INSERT INTO transactional_emails_sent (email_type, to_address, properties, response, date_created) VALUES (:email_type, :to_email, :properties, :response, :date_created)");
    $stmt->execute([
        'email_type' => $email_type,
        'to_email' => $to_email,
        'properties' => json_encode($properties),
        'response' => json_encode($res),
        'date_created' => date('Y-m-d H:i:s'),
    ]);
    return [
        'id' => $db->lastInsertId(),
        'email_type' => $email_type,
        'to_email' => $to_email,
        'properties' => $properties,
        'response' => $res,
        'date_created' => date('Y-m-d H:i:s'),
    ];
}
function klaviyo_send_event($data){
    if(empty($data['token'])){
        $data['token'] = 'KvQM7Q';
    }
    $ch = curl_init("https://a.klaviyo.com/api/track?data=".base64_encode(json_encode($data)));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
    ]);
    $res = json_decode(curl_exec($ch));
    return $res;
}