<?php

require_once dirname(__FILE__).'/../vendor/autoload.php';

date_default_timezone_set('America/Los_Angeles');

spl_autoload_register(function($class){
	require_once(__DIR__.'/class.'.$class.'.php');
});

$dotenv = new Dotenv\Dotenv(__DIR__.'/..');
$dotenv->load();

if(!empty($_ENV['STRIPE_API_KEY'])){
	\Stripe\Stripe::setApiKey($_ENV['STRIPE_API_KEY']);
}
if(!empty($_ENV['EASYPOST_API_KEY'])){
	\EasyPost\EasyPost::setApiKey($_ENV['EASYPOST_API_KEY']);
}

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

$sc = new ShopifyClient();
$rc = new RechargeClient();

$ids_by_scent = [
	'arrow'  => ['variant' => 31022048003,     'product' => 8985085187],
	'capri'  => ['variant' => 5541512970271,   'product' => 443364081695],
	'coral'  => ['variant' => 26812012355,     'product' => 8215300931],
	'isle'   => ['variant' => 31022109635,     'product' => 8985117187],
	'meadow' => ['variant' => 26812085955,     'product' => 8215317379],
	'willow' => ['variant' => 8328726413399,   'product' => 785329455191],
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
if(!function_exists('divide')){
	function divide($numerator, $denominator){
		if(empty($denominator)){
			return 0;
		}
		return $numerator/$denominator;
	}
};
if(!function_exists('is_decimal')){
	function is_decimal($val){
		return is_numeric($val) && floor($val) != $val;
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
		'value' => is_array($value) ? json_encode($value) : $value,
		'value2' => is_array($value2) ? json_encode($value2) : $value2,
		'note' => $note,
		'actor' => $actor,
		'date_created' => date('Y-m-d H:i:s'),
	]);
}
function send_alert(PDO $db, $alert_id, $msg = '', $subject = 'Skylar Alert', $to_emails = ['tim@skylar.com'], $smother_message = false){
	$msg = is_array($msg) ? print_r($msg, true) : $msg;
	$headers = [
		'From' => 'Skylar Alerts <alerts@skylar.com>',
		'Reply-To' => 'tim@skylar.com',
		'X-Mailer' => 'PHP/' . phpversion(),
	];

	if($smother_message){
		$alert_sent = false;
	} else {
		foreach($to_emails as $to){
			mail($to, $subject, $msg
//				,implode("\r\n",$headers)
			);
		}
		$alert_sent = true;
	}
	$stmt = $db->prepare("INSERT INTO alert_logs (alert_id, message, message_sent, message_smothered, date_created) VALUES ($alert_id, :message, :message_sent, :message_smothered, :date_created)");
	$stmt->execute([
		'message' => $msg,
		'message_sent' => $alert_sent ? 1 : 0,
		'message_smothered' => $smother_message ? 1 : 0,
		'date_created' => date('Y-m-d H:i:s'),
	]);
}
function offset_date_skip_weekend($time){
	while(in_array(date('N', $time), [6,7])){ // While it's a weekend
		$time += 24*60*60; //  Add a day
	}
	// Labor day
	if($time == strtotime('first monday '.date('Y-m', $time))){
		$time += 24*60*60; //  Add a day
	}
	return $time;
}

$_stmt_cache = [];
function insert_update_product(PDO $db, $shopify_product){
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_product'])){
		$_stmt_cache['iu_product'] = $db->prepare("INSERT INTO products
(shopify_id, handle, title, type, tags, updated_at, published_at)
VALUES (:shopify_id, :handle, :title, :type, :tags, :updated_at, :published_at)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), handle=:handle, title=:title, type=:type, tags=:tags, updated_at=:updated_at, published_at=:published_at");
	}
	$now = date('Y-m-d H:i:s');
	$_stmt_cache['iu_product']->execute([
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
	if(empty($_stmt_cache['iu_variant'])){
		$_stmt_cache['iu_variant'] = $db->prepare("INSERT INTO variants
(product_id, shopify_id, title, price, sku, updated_at)
VALUES (:product_id, :shopify_id, :title, :price, :sku, :updated_at)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), title=:title, price=:price, sku=:sku, updated_at=:updated_at");
	}
	foreach($shopify_product['variants'] as $shopify_variant){
		$_stmt_cache['iu_variant']->execute([
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
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_order'])){
		$_stmt_cache['iu_order'] = $db->prepare("INSERT INTO orders
(shopify_id, customer_id, app_id, cart_token, `number`, total_line_items_price, total_discounts, total_price, tags, created_at, updated_at, cancelled_at, closed_at, email, note, attributes, source_name, synced_at)
VALUES (:shopify_id, :customer_id, :app_id, :cart_token, :number, :total_line_items_price, :total_discounts, :total_price, :tags, :created_at, :updated_at, :cancelled_at, :closed_at, :email, :note, :attributes, :source_name, :synced_at)
ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), customer_id=:customer_id, app_id=:app_id, cart_token=:cart_token, `number`=:number, updated_at=:updated_at, total_line_items_price=:total_line_items_price, total_discounts=:total_discounts, total_price=:total_price, tags=:tags, cancelled_at=:cancelled_at, closed_at=:closed_at, email=:email, note=:note, attributes=:attributes, source_name=:source_name, synced_at=:synced_at");
	}
	$now = date('Y-m-d H:i:s');
	if(!empty($shopify_order['customer'])){
		$customer_id = get_customer($db, $shopify_order['customer']['id'], $sc)['id'];
	} elseif(!empty($shopify_order['customer_id'])) {
		$customer_id = get_customer($db, $shopify_order['customer_id'], $sc)['id'];
	}else {
		$customer_id = null;
	}
	$_stmt_cache['iu_order']->execute([
		'shopify_id' => $shopify_order['id'],
		'customer_id' => $customer_id,
		'app_id' => $shopify_order['app_id'],
		'cart_token' => $shopify_order['cart_token'],
		'number' => $shopify_order['order_number'],
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
		'synced_at' => date('Y-m-d H:i:s'),
	]);
	$error = $_stmt_cache['iu_order']->errorInfo();
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
	if(empty($_stmt_cache['iu_order_line_item'])){
		$_stmt_cache['iu_order_line_item'] = $db->prepare("INSERT INTO order_line_items (shopify_id, order_id, variant_id, total_discount, price, sku, product_title, variant_title, properties) VALUES (:shopify_id, :order_id, :variant_id, :total_discount, :price, :sku, :product_title, :variant_title, :properties) ON DUPLICATE KEY UPDATE order_id=:order_id, variant_id=:variant_id, total_discount=:total_discount, price=:price, sku=:sku, product_title=:product_title, variant_title=:variant_title, properties=:properties");
	}
	foreach($shopify_order['line_items'] as $line_item){
		$variant = get_variant($db, $line_item['variant_id']);
		$_stmt_cache['iu_order_line_item']->execute([
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
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_customer'])){
		$_stmt_cache['iu_customer'] = $db->prepare("INSERT INTO customers (shopify_id, email, first_name, last_name, state, tags, updated_at) VALUES (:shopify_id, :email, :first_name, :last_name, :state, :tags, :updated_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), email=:email, first_name=:first_name, last_name=:last_name, state=:state, tags=:tags, updated_at=:updated_at");
	}
	$_stmt_cache['iu_customer']->execute([
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
	global $_stmt_cache;

	if(!empty($shopify_fulfillment['tracking_number'])){
		$stmt = $db->prepare("SELECT 1 FROM ep_trackers WHERE tracking_code=?");
		$stmt->execute([$shopify_fulfillment['tracking_number']]);
		if(true || $stmt->rowCount() < 1){
			try {
				$tracker = \EasyPost\Tracker::create([
					'tracking_code' => $shopify_fulfillment['tracking_number'],
					'carrier' => $shopify_fulfillment['tracking_company'],
				]);
			} catch(\Throwable $e){
				if($shopify_fulfillment['tracking_company'] == 'UPS'){
					try {
						$tracker = \EasyPost\Tracker::create([
							'tracking_code' => $shopify_fulfillment['tracking_number'],
							'carrier' => 'UPS Mail Innovations',
						]);
					} catch(\Throwable $e){
						var_dump($e);
						log_event($db, 'EXCEPTION', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'fulfillment_tracker_create', json_encode($shopify_fulfillment), '', '');
					}
				} else if($shopify_fulfillment['tracking_company'] == 'Passport'){
					try {
						$tracker = \EasyPost\Tracker::create([
							'tracking_code' => $shopify_fulfillment['tracking_number'],
							'carrier' => 'PassportGlobal',
						]);
					} catch(\Throwable $e){
						var_dump($e);
						log_event($db, 'EXCEPTION', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'fulfillment_tracker_create', json_encode($shopify_fulfillment), '', '');
					}
				} else {
					var_dump($e);
					log_event($db, 'EXCEPTION', json_encode([$e->getLine(), $e->getFile(), $e->getCode(), $e->getMessage(), $e->getTraceAsString()]), 'fulfillment_tracker_create', json_encode($shopify_fulfillment), '', '');
				}
			}
		}
	}

	if(empty($_stmt_cache['iu_fulfillment'])){
		$_stmt_cache['iu_fulfillment'] = $db->prepare("INSERT INTO fulfillments (shopify_id, name, service, tracking_company, tracking_number, tracking_url, shipment_status, status) VALUES (:shopify_id, :name, :service, :tracking_company, :tracking_number, :tracking_url, :shipment_status, :status) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), name=:name, service=:service, tracking_company=:tracking_company, tracking_number=:tracking_number, tracking_url=:tracking_url, shipment_status=:shipment_status, status=:status");
	}
	$_stmt_cache['iu_fulfillment']->execute([
        'shopify_id' => $shopify_fulfillment['id'],
        'name' => $shopify_fulfillment['name'],
		'service' => $shopify_fulfillment['service'],
		'tracking_company' => $shopify_fulfillment['tracking_company'],
		'tracking_number' => $shopify_fulfillment['tracking_number'],
		'tracking_url' => $shopify_fulfillment['tracking_url'],
        'shipment_status' => $shopify_fulfillment['shipment_status'],
        'status' => $shopify_fulfillment['status'],
    ]);
    $id = $db->lastInsertId();
	if(empty($_stmt_cache['iu_line_item_fulfillment_id'])){
		$_stmt_cache['iu_line_item_fulfillment_id'] = $db->prepare("UPDATE order_line_items SET fulfillment_id = ? WHERE shopify_id=?");
	}
    foreach($shopify_fulfillment['line_items'] as $line_item){
    	echo $id." ".$line_item['id'];
		$_stmt_cache['iu_line_item_fulfillment_id']->execute([$id, $line_item['id']]);
    }

    return $id;
}
function insert_update_theme(PDO $db, $theme){
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_theme'])){
		$_stmt_cache['iu_theme'] = $db->prepare("INSERT INTO themes (shopify_id, name, created_at, updated_at) VALUES (:shopify_id, :name, :created_at, :updated_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), name=:name, updated_at=:updated_at");
	}
	$_stmt_cache['iu_theme']->execute([
		'shopify_id' => $theme['id'],
		'name' => $theme['name'],
		'created_at' => $theme['created_at'],
		'updated_at' => $theme['updated_at'],
	]);

	return $db->lastInsertId();
}
function insert_update_rc_customer(PDO $db, $recharge_customer, ShopifyClient $sc){
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_rc_customer'])){
		$_stmt_cache['iu_rc_customer'] = $db->prepare("INSERT INTO rc_customers (recharge_id, customer_id, email, first_name, last_name, processor_type, status, has_valid_payment_method, reason_payment_method_invalid, updated_at) VALUES (:recharge_id, :customer_id, :email, :first_name, :last_name, :processor_type, :status, :has_valid_payment_method, :reason_payment_method_invalid, :updated_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), recharge_id=:recharge_id, customer_id=:customer_id, email=:email, first_name=:first_name, last_name=:last_name, processor_type=:processor_type, status=:status, has_valid_payment_method=:has_valid_payment_method, reason_payment_method_invalid=:reason_payment_method_invalid, updated_at=:updated_at");
	}
	if(empty($recharge_customer['shopify_customer_id'])){
		$customer = ['id'=>null];
	} else {
		$customer = get_customer($db, $recharge_customer['shopify_customer_id'], $sc);
	}
	$_stmt_cache['iu_rc_customer']->execute([
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
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_rc_address'])){
		$_stmt_cache['iu_rc_address'] = $db->prepare("INSERT INTO rc_addresses (recharge_id, rc_customer_id, line1, line2, city, province, country, zip, company, phone, note, attributes, shipping_lines, updated_at) VALUES (:recharge_id, :rc_customer_id, :line1, :line2, :city, :province, :country, :zip, :company, :phone, :note, :attributes, :shipping_lines, :updated_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), rc_customer_id=:rc_customer_id, line1=:line1, line2=:line2, city=:city, province=:province, country=:country, zip=:zip, company=:company, phone=:phone, note=:note, attributes=:attributes, shipping_lines=:shipping_lines, updated_at=:updated_at");
	}
	$recharge_customer = get_rc_customer($db, $recharge_address['customer_id'], $rc, $sc);
	$_stmt_cache['iu_rc_address']->execute([
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
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_rc_subscription'])){
		$_stmt_cache['iu_rc_subscription'] = $db->prepare("INSERT INTO rc_subscriptions (recharge_id, address_id, product_title, variant_title, price, quantity, status, product_id, variant_id, order_interval_unit, order_interval_frequency, charge_interval_frequency, order_day_of_month, order_day_of_week, properties, expire_after_charges, cancellation_reason, max_retries_reached, next_charge_scheduled_at, created_at, updated_at, cancelled_at, synced_at) VALUES (:recharge_id, :address_id, :product_title, :variant_title, :price, :quantity, :status, :product_id, :variant_id, :order_interval_unit, :order_interval_frequency, :charge_interval_frequency, :order_day_of_month, :order_day_of_week, :properties, :expire_after_charges, :cancellation_reason, :max_retries_reached, :next_charge_scheduled_at, :created_at, :updated_at, :cancelled_at, :synced_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), address_id=:address_id, product_title=:product_title, variant_title=:variant_title, price=:price, quantity=:quantity, status=:status, product_id=:product_id, variant_id=:variant_id, order_interval_unit=:order_interval_unit, order_interval_frequency=:order_interval_frequency, charge_interval_frequency=:charge_interval_frequency, order_day_of_month=:order_day_of_month, order_day_of_week=:order_day_of_week, properties=:properties, expire_after_charges=:expire_after_charges, cancellation_reason=:cancellation_reason, max_retries_reached=:max_retries_reached, next_charge_scheduled_at=:next_charge_scheduled_at, created_at=:created_at, updated_at=:updated_at, cancelled_at=:cancelled_at, synced_at=:synced_at");
	}
	$rc_address = get_rc_address($db, $recharge_subscription['address_id'], $rc, $sc);
	$variant = get_variant($db, $recharge_subscription['shopify_variant_id']);
	$cancelled_at = $recharge_subscription['cancelled_at'] ?? null;
	if($cancelled_at == null && $recharge_subscription['status'] == 'CANCELLED'){
		$cancelled_at = $recharge_subscription['updated_at'];
	}
	$_stmt_cache['iu_rc_subscription']->execute([
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
		'next_charge_scheduled_at' => empty($recharge_subscription['next_charge_scheduled_at']) ? null : date('Y-m-d', strtotime($recharge_subscription['next_charge_scheduled_at'])),
		'created_at' => $recharge_subscription['created_at'],
		'updated_at' => $recharge_subscription['updated_at'],
		'cancelled_at' => $cancelled_at,
		'synced_at' => date('Y-m-d H:i:s'),
	]);
	return $db->lastInsertId();
}
function insert_update_tracker(PDO $db, $tracker){
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_ep_tracker'])){
		$_stmt_cache['iu_ep_tracker'] = $db->prepare("INSERT INTO ep_trackers (easypost_id, carrier, tracking_code, status, weight, est_delivery_date, public_url, created_at, updated_at) VALUES (:easypost_id, :carrier, :tracking_code, :status, :weight, :est_delivery_date, :public_url, :created_at, :updated_at) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), carrier=:carrier, tracking_code=:tracking_code, status=:status, weight=:weight, est_delivery_date=:est_delivery_date, public_url=:public_url, created_at=:created_at, updated_at=:updated_at");
	}
	$_stmt_cache['iu_ep_tracker']->execute([
		'easypost_id' => $tracker['id'],
		'carrier' => $tracker['carrier'],
		'tracking_code' => $tracker['tracking_code'],
		'status' => $tracker['status'],
		'weight' => $tracker['weight'],
		'est_delivery_date' => $tracker['est_delivery_date'],
		'public_url' => $tracker['public_url'],
		'created_at' => $tracker['created_at'],
		'updated_at' => $tracker['updated_at'],
	]);
	$tracker_id = $db->lastInsertId();
	if(empty($_stmt_cache['iu_ep_tracker_detail'])){
		$_stmt_cache['iu_ep_tracker_detail'] = $db->prepare("INSERT INTO ep_tracker_details (tracker_id, message, status, source, created_at) VALUES (:tracker_id, :message, :status, :source, :created_at) ON DUPLICATE KEY UPDATE message=:message");
	}
	if(empty($_stmt_cache['iu_fulfillment_delivered_at'])){
		$_stmt_cache['iu_fulfillment_delivered_at'] = $db->prepare("UPDATE fulfillments SET delivered_at = :delivered_at, status='delivered' WHERE tracking_number = :tracking_number AND delivered_at IS NULL");
	}
	foreach($tracker['tracking_details'] as $detail){
		$_stmt_cache['iu_ep_tracker_detail']->execute([
			'tracker_id' => $tracker_id,
			'message' => $detail['message'],
			'status' => $detail['status'],
			'source' => $detail['source'],
			'created_at' => $detail['datetime'],
		]);
		if($detail['status'] == 'delivered'){
			$_stmt_cache['iu_fulfillment_delivered_at']->execute([
				'delivered_at' => $detail['datetime'],
				'tracking_number' => $tracker['tracking_code'],
			]);
		}
	}
	return $tracker_id;
}
function get_next_subscription_time($start_date, $order_interval_unit, $order_interval_frequency, $order_day_of_month = null, $order_day_of_week = null){
	$next_charge_time = strtotime($start_date.' +'.$order_interval_frequency.' '.$order_interval_unit);
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
function get_month_by_offset($offset, $now = null){
	if(empty($now)){
		$now = time();
	}
	if($offset > 0){
		for($i = 0; $i < $offset; $i++){
			$now = get_next_month($now);
		}
		return $now;
	}
	if($offset < 0){
		for($i = 0; $i > $offset; $i--){
			$now = get_last_month($now);
		}
		return $now;
	}
	return strtotime(date('Y-m', $now).'-01');
}
function get_subscription_price($product, $variant, $is_sc_member=false){
	if(is_scent_club_any($product)){
		return $variant['price'];
	}
	if($product['type'] == 'Body Bundle'){
		return $variant['price'];
	}
	if(strpos($product['type'], 'Body') !== false){
		return round($variant['price']*.9);
	}
	return round($variant['price']*.9, 2);
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
$customer_cache = [];
function get_customer(PDO $db, $shopify_customer_id, ShopifyClient $sc){
	global $customer_cache, $_stmt_cache;
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
		$stmt = $db->prepare("SELECT rcs.*, rca.recharge_id AS recharge_address_id, v.shopify_id AS shopify_variant_id FROM rc_subscriptions rcs
			LEFT JOIN rc_addresses rca ON rca.id=rcs.address_id
			LEFT JOIN variants v ON v.id=rcs.variant_id
			WHERE rcs.recharge_id=?");
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
		$variant = $stmt->fetch();
		$stmt = $db->prepare("SELECT scent_id, format_id, product_type_id FROM variant_attributes WHERE variant_id=?");
		$stmt->execute([$variant['id']]);
		$variant['attributes'] = $stmt->fetch();
		$variant_cache[$shopify_variant_id] = $variant;
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
function sc_calculate_next_charge_date(PDO $db, RechargeClient $rc, $address_id, $main_sub = [], $force_offset = null){
	if(!is_null($force_offset)){
		$next_charge_month = date('Y-m', get_month_by_offset($force_offset));
	} else {
		$res = $rc->get('/onetimes', [
			'address_id' => $address_id,
		]);
		// Fix for api returning non-onetimes
		$onetimes = [];
		foreach(($res['onetimes'] ?? []) as $onetime){
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
		$orders = $res['orders'] ?? [];
		$res = $rc->get('/charges', [
			'address_id' => $address_id,
			'date_min' => date('Y-m-t'),
			'status' => 'SKIPPED'
		]);
		$charges = $res['charges'] ?? [];

		$products_by_id = [];
		$stmt = $db->prepare("SELECT * FROM products WHERE shopify_id=?");
		$offset = sc_is_address_in_blackout($db, $rc, $address_id) ? 1 : 0;
		$next_charge_month = date('Y-m', get_month_by_offset($offset));
		while(true){
			$offset++;
			$next_charge_month = date('Y-m', get_month_by_offset($offset));
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
	}
	if(empty($main_sub)){
		$main_sub = sc_get_main_subscription($db, $rc, [
			'address_id' => $address_id,
			'status' => 'ACTIVE',
		]);
	}

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
function get_order_attribute($order, $attribute_name){
	$attributes = [];
	if(!empty($order['attributes'])){
		$attributes = $order['attributes'];
	} else if(!empty($order['note_attributes'])){
		$attributes = $order['note_attributes'];
	}
	if(empty($attributes)){
		return null;
	}
	if(array_key_exists($attribute_name, $attributes)){
		return $attributes[$attribute_name];
	}
	// Check if it's an indexed array (with name and value properties)
	if(array_keys($attributes)[0] === 0){
		foreach($attributes as $property){
			if($property['name'] == $attribute_name){
				return $property['value'];
			}
		}
	}
	return null;
}
function get_oli_attribute($line_item, $attribute_name){
	if(empty($line_item['properties'])){
		return null;
	}
	if(array_key_exists($attribute_name, $line_item['properties'])){
		return $line_item['properties'][$attribute_name];
	}
	// Check if it's an indexed array (with name and value properties)
	if(array_keys($line_item['properties'])[0] === 0){
		foreach($line_item['properties'] as $property){
			if($property['name'] == $attribute_name){
				return $property['value'];
			}
		}
	}
	return null;
}
function is_ac_initial_lineitem($initial_lineitem){
	return !empty(get_oli_attribute($initial_lineitem, '_ac_trigger'));
}
function is_ac_followup_lineitem($followup_line_item){
	return !empty(get_oli_attribute($followup_line_item, '_ac_product'));
}
function is_ac_pushed_back($followup_line_item){
	return !empty(get_oli_attribute($followup_line_item, '_ac_pushed_back'));
}
function is_ac_pushed_up($followup_line_item){
	return !empty(get_oli_attribute($followup_line_item, '_ac_pushed_up'));
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

// https://stackoverflow.com/questions/15273570/continue-processing-php-after-sending-http-response/38918192
function respondOK(){
	// check if fastcgi_finish_request is callable
	if (is_callable('fastcgi_finish_request')) {
		/*
		 * This works in Nginx but the next approach not
		 */
		session_write_close();
		fastcgi_finish_request();

		return;
	}

	ignore_user_abort(true);

	ob_start();
	$serverProtocole = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
	header($serverProtocole.' 200 OK');
	header('Content-Encoding: none');
	header('Content-Length: '.ob_get_length());
	header('Connection: close');

	ob_end_flush();
	ob_flush();
	flush();
}

// https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
function uuidv4($data = null){
	$data = $data ?? random_bytes(16);
	$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
	$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}