<?php
require_once(__DIR__.'/../../includes/config.php');

$interval = 5;
$page_size = 250;
$sc = new ShopifyClient();
$min_date = date('Y-m-d H:i:00P', time()-60*6);
$start_time = time();

// Products
echo "Updating products".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $sc->get('/admin/products.json', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res as $product){
		echo insert_update_product($db, $product).PHP_EOL;
	}
} while(count($res) >= $page_size);

// Customers
echo "Updating customers".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $sc->get('/admin/customers.json', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res as $customer){
		echo insert_update_customer($db, $customer).PHP_EOL;
	}
} while(count($res) >= $page_size);

// Orders
echo "Updating orders and fulfillments".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $sc->get('/admin/orders.json', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res as $order){
		echo insert_update_order($db, $order, $sc).PHP_EOL;
		$fulfillment_res = $sc->get('/admin/orders/'.$order['id'].'/fulfillments.json', [
			'updated_at_min' => $min_date,
			'limit' => $page_size,
			'page' => $page,
		]);
		foreach($fulfillment_res as $fulfillment){
			echo " - ".insert_update_fulfillment($db, $fulfillment).PHP_EOL;
		}
	}
} while(count($res) >= $page_size);

// metafields
echo "Updating product metafields".PHP_EOL;
$updated_product_ids = $db->query("SELECT shopify_id FROM products WHERE updated_at >= '$min_date'")->fetchAll(PDO::FETCH_COLUMN);
foreach($updated_product_ids AS $product_id){
	$metafields = $sc->get('/admin/products/'.$product_id.'/metafields.json');
	if(empty($metafields)){
		print_r($sc->last_error);
		echo "Couldn't get metafields for $product_id".PHP_EOL;
		continue;
	}
	print_r(insert_update_metafields($db, $metafields));
}
echo "Updating variant metafields".PHP_EOL;
$updated_variant_ids = $db->query("SELECT shopify_id FROM variants WHERE updated_at >= '$min_date'")->fetchAll(PDO::FETCH_COLUMN);
foreach($updated_variant_ids AS $variant_id){
	$metafields = $sc->get('/admin/variants/'.$variant_id.'/metafields.json');
	if(empty($metafields)){
		print_r($sc->last_error);
		echo "Couldn't get metafields for $variant_id".PHP_EOL;
		continue;
	}
	print_r(insert_update_metafields($db, $metafields));
}

// Daily syncs
if(
	(date('G', $start_time) == 12 && date('i', $start_time) < 4 && !in_array(date('N', $start_time), [6,7]))
	|| (!empty($argv) && !empty($argv[1]) && $argv[1] == 'all')
){

	echo "Pull all products".PHP_EOL;
	$sync_start = date('Y-m-d H:m:s');
	$page = 0;
	do {
		$page++;
		$res = $sc->get('/admin/products.json', [
			'limit' => $page_size,
			'page' => $page,
		]);
		foreach($res as $product){
			echo insert_update_product($db, $product).PHP_EOL;
		}
	} while(count($res) >= $page_size);

	echo "Check unsynced products".PHP_EOL;
	$stmt = $db->query("SELECT id, shopify_id
		FROM products
		WHERE (synced_at < '$sync_start' OR synced_at IS NULL) AND deleted_at IS NULL");
	$stmt_delete_product = $db->prepare("UPDATE products SET deleted_at='$sync_start' WHERE id=?");
	$stmt_delete_product_variants = $db->prepare("UPDATE variants SET deleted_at='$sync_start' WHERE product_id=?");
	foreach($stmt->fetchAll() as $row){
		$res = $sc->get('/admin/products/'.$row['shopify_id'].'.json');
		if(empty($res) && $sc->last_response_headers['http_status_code'] == '404'){
			echo "Deleting ".$row['shopify_id'].PHP_EOL;
			$stmt_delete_product->execute([$row['id']]);
			$stmt_delete_product_variants->execute([$row['id']]);
		} elseif(!empty($res)) {
			echo insert_update_product($db, $res);
		}
	}

	echo "Check unsynced variants".PHP_EOL;
	$stmt = $db->query("SELECT id, shopify_id
		FROM variants
		WHERE (synced_at < '$sync_start' OR synced_at IS NULL) AND deleted_at IS NULL");
	$stmt_delete_variant = $db->prepare("UPDATE variants SET deleted_at='$sync_start' WHERE id=?");
	foreach($stmt->fetchAll() as $row){
		$res = $sc->get('/admin/variants/'.$row['shopify_id'].'/.json');
		if(empty($res) && $sc->last_response_headers['http_status_code'] == '404'){
			echo "Deleting ".$row['shopify_id'].PHP_EOL;
			$stmt_delete_product->execute([$row['id']]);
		}
	}


	// Update all Metafields
	echo "Updating all product metafields".PHP_EOL;
	$updated_product_ids = $db->query("SELECT shopify_id FROM products WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
	foreach($updated_product_ids AS $product_id){
		$metafields = $sc->get('/admin/products/'.$product_id.'/metafields.json');
		if(empty($metafields)){
			print_r($sc->last_error);
			echo "Couldn't get metafields for $product_id".PHP_EOL;
			continue;
		}
		print_r(insert_update_metafields($db, $metafields));
	}
	echo "Updating variant metafields".PHP_EOL;
	$updated_variant_ids = $db->query("SELECT shopify_id FROM variants WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
	foreach($updated_variant_ids AS $variant_id){
		$metafields = $sc->get('/admin/variants/'.$variant_id.'/metafields.json');
		if(empty($metafields)){
			print_r($sc->last_error);
			echo "Couldn't get metafields for $variant_id".PHP_EOL;
			continue;
		}
		print_r(insert_update_metafields($db, $metafields));
	}

	echo "Updating missing AC fulfillments".PHP_EOL;
	$stmt = $db->query("SELECT o.shopify_id FROM ac_orders aco
		LEFT JOIN order_line_items oli ON aco.order_line_item_id=oli.id
		LEFT JOIN fulfillments f ON f.id=oli.fulfillment_id
		LEFT JOIN orders o ON oli.order_id=o.id
		WHERE oli.fulfillment_id IS NULL
		AND o.created_at < '".date('Y-m-d', $start_time - (24*60*60))."'
		AND o.cancelled_at IS NULL;");
	foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $order_id){
		echo " - ".$order_id.": ".PHP_EOL;
		$fulfillment_res = $sc->get('/admin/orders/'.$order_id.'/fulfillments.json', [
			'limit' => $page_size,
		]);
		foreach($fulfillment_res as $fulfillment){
			echo "   - ".insert_update_fulfillment($db, $fulfillment).PHP_EOL;
		}
	}

	echo "Updating missing fulfillments".PHP_EOL;
	$stmt = $db->query("SELECT o.shopify_id FROM skylar.orders o
		LEFT JOIN order_line_items oli ON o.id=oli.order_id
		WHERE oli.fulfillment_id IS NULL
		and o.created_at >= '".date('Y-m-d', strtotime('-60 days'))."'
		AND o.cancelled_at IS NULL
		AND o.closed_at IS NOT NULL
		GROUP BY o.shopify_id
;");
	foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $order_id){
		echo " - ".$order_id.": ".PHP_EOL;
		$fulfillment_res = $sc->get('/admin/orders/'.$order_id.'/fulfillments.json', [
			'limit' => $page_size,
		]);
		if(empty($fulfillment_res)){
			print_r($sc->last_error);
			echo "No fulfillment!".PHP_EOL;
		}
		foreach($fulfillment_res as $fulfillment){
			echo "   - ".insert_update_fulfillment($db, $fulfillment).PHP_EOL;
		}
	}

	echo "Updating Old EPTs".PHP_EOL;
	$stmt = $db->query("SELECT o.shopify_id FROM skylar.orders o
		LEFT JOIN order_line_items oli ON o.id=oli.order_id
		LEFT JOIN fulfillments f ON f.id=oli.fulfillment_id
		LEFT JOIN ep_trackers ept ON ept.tracking_code=f.tracking_number
		WHERE ept.id IS NULL
		AND shipment_status IS NOT NULL
		and o.created_at >= '".date('Y-m-d', strtotime('-30 days'))."'
		AND o.cancelled_at IS NULL
		AND o.closed_at IS NOT NULL
		GROUP BY o.shopify_id
;");
	echo $stmt->queryString;
	foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $order_id){
		echo " - ".$order_id.": ".PHP_EOL;
		$fulfillment_res = $sc->get('/admin/orders/'.$order_id.'/fulfillments.json', [
			'limit' => $page_size,
		]);
		if(empty($fulfillment_res)){
			print_r($sc->last_error);
			echo "No fulfillment!".PHP_EOL;
		}
		foreach($fulfillment_res as $fulfillment){
			echo "   - ".insert_update_fulfillment($db, $fulfillment).PHP_EOL;
		}
	}

}