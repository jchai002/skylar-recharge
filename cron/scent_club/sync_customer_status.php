<?php
require_once(__DIR__ . '/../../includes/config.php');

// Load all current subscribers
// TODO - this script is pretty slow - how can we speed it up?
$start_time = time();
$page = 0;
$page_size = 250;
$customers_with_subs = [];
do {
	$page++;
	$res = $rc->get('/subscriptions', [
		'limit' => $page_size,
		'page' => $page,
		'shopify_variant_id' => 19787922014295,
		'status' => 'ACTIVE',
	]);
	echo count($res['subscriptions'])." subscriptions on this page".PHP_EOL;
	foreach($res['subscriptions'] as $subscription){
		$rc_customer = get_rc_customer($db, $subscription['customer_id'], $rc, $sc);
		$customer = $sc->get('/admin/customers/'.$rc_customer['shopify_customer_id'].'.json');
		$customers_with_subs[$customer['id']] = $customer;
	}
} while(count($res['subscriptions']) >= $page_size);

foreach($customers_with_subs as $shopify_customer_id => $shopify_customer){
	$res = $sc->post('/admin/customers/'.$shopify_customer_id.'/metafields.json', ['metafield'=> [
		'namespace' => 'scent_club',
		'key' => 'active',
		'value' => 1,
		'value_type' => 'integer'
	]]);
	print_r($res);
	$tags = explode(', ',$shopify_customer['tags']);
	if(!in_array('Scent Club Member', $tags)){
		$tags[] = 'Scent Club Member';
		$shopify_customer = $sc->put('/admin/customers/'.$shopify_customer_id.'.json', ['customer' => [
			'id' => $shopify_customer_id,
			'tags' => implode(', ', $tags),
		]]);
		insert_update_customer($db, $shopify_customer);
	}
}

$customer_ids = $db->query("SELECT shopify_id FROM customers WHERE tags LIKE '%Scent Club Member%' AND updated_at <= '".date('Y-m-d H:i:s', $start_time)."'")->fetchAll(PDO::FETCH_COLUMN);

foreach($customer_ids as $shopify_customer_id){
	$res = $rc->get('/subscrpitions', [
		'shopify_customer_id' => $shopify_customer_id,
		'shopify_variant_id' => 19787922014295,
		'status' => 'ACTIVE',
	]);
	if(empty($res['subscriptions'])){
		$shopify_customer = $sc->get('/admin/customers/'.$shopify_customer_id.'.json');
		$tags = explode(', ',$shopify_customer['tags']);

		$sc = new ShopifyClient();
		$res = $sc->post('/admin/customers/'.$shopify_customer_id.'/metafields.json', ['metafield'=> [
			'namespace' => 'scent_club',
			'key' => 'active',
			'value' => 0,
			'value_type' => 'integer'
		]]);
		print_r($tags);
		if(in_array('Scent Club Member', $tags)){
			$key = array_search('Scent Club Member', $tags);
			if (false !== $key) {
				unset($tags[$key]);
			}
			print_r($tags);
			$shopify_customer = $sc->put('/admin/customers/'.$shopify_customer_id.'.json', ['customer' => [
				'id' => $shopify_customer_id,
				'tags' => implode(', ', $tags),
			]]);
			insert_update_customer($db, $shopify_customer);
		}
	}
}