<?php
require_once(__DIR__ . '/../../includes/config.php');

// Load all current subscribers
$active_customers = $db->query("
SELECT c.shopify_id, c.shopify_id AS id, c.tags, m.value AS metafield_value
FROM rc_subscriptions rcs
LEFT JOIN rc_addresses rca ON rcs.address_id=rca.id
LEFT JOIN rc_customers rcc ON rca.rc_customer_id=rcc.id
LEFT JOIN customers c ON rcc.customer_id=c.id
LEFT JOIN metafields m ON m.owner_id=c.shopify_id AND m.owner_resource='customer' AND m.namespace='scent_club' AND m.`key`='active'
WHERE rcs.status = 'ACTIVE'
AND rcs.variant_id = 6650
AND rcs.deleted_at IS NULL;")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
$cancelled_customers = $db->query("
SELECT c.shopify_id, c.shopify_id AS id, c.tags, m.value AS metafield_value
FROM rc_subscriptions rcs
LEFT JOIN rc_addresses rca ON rcs.address_id=rca.id
LEFT JOIN rc_customers rcc ON rca.rc_customer_id=rcc.id
LEFT JOIN customers c ON rcc.customer_id=c.id
LEFT JOIN metafields m ON m.owner_id=c.shopify_id AND m.owner_resource='customer' AND m.namespace='scent_club' AND m.`key`='active'
WHERE rcs.status != 'ONETIME'
AND rcs.variant_id = 6650
AND (
	rcs.deleted_at IS NOT NULL
	OR rcs.cancelled_at IS NOT NULL
);")->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);

$cancelled_customers = array_filter($cancelled_customers, function($customer) use($active_customers) {
	return !array_key_exists($customer['id'], $active_customers);
});

foreach($active_customers as $shopify_customer_id => $customer){
	print_r($customer);
	if(empty($customer['metafield_value'])){
		echo "Updating metafield ".PHP_EOL;
		$res = $sc->post('/admin/customers/'.$shopify_customer_id.'/metafields.json', ['metafield'=> [
			'namespace' => 'scent_club',
			'key' => 'active',
			'value' => 1,
			'value_type' => 'integer'
		]]);
		if(!empty($res)){
			echo insert_update_metafield($db, $res);
			print_r($res);
		}
		usleep(250*1000);
	}
	$tags = explode(', ',$customer['tags']);
	if(!in_array('Scent Club Member', $tags)){
		echo "Updating customer tags ".PHP_EOL;
		$tags[] = 'Scent Club Member';
		$shopify_customer = $sc->put('/admin/customers/'.$shopify_customer_id.'.json', ['customer' => [
			'id' => $shopify_customer_id,
			'tags' => implode(', ', $tags),
		]]);
		echo insert_update_customer($db, $shopify_customer).PHP_EOL;
		print_r($shopify_customer['tags']);
		usleep(250*1000);
	}
}

foreach($cancelled_customers as $shopify_customer_id => $customer){
	if(empty($res['subscriptions'])){
		print_r($customer);
		if(!empty($shopify_customer['metafield_value'])){
			$res = $sc->post('/admin/customers/'.$shopify_customer_id.'/metafields.json', ['metafield'=> [
				'namespace' => 'scent_club',
				'key' => 'active',
				'value' => 0,
				'value_type' => 'integer'
			]]);
			if(!empty($res)){
				echo insert_update_metafield($db, $res);
				print_r($res);
			}
			usleep(250*1000);
		}
		$tags = explode(', ',$customer['tags']);
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
			echo insert_update_customer($db, $shopify_customer).PHP_EOL;
			usleep(250*1000);
		}
	}
}