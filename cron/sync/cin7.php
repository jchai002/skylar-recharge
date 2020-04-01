<?php
require_once(__DIR__.'/../../includes/config.php');

$page_size = 250;
// Sync product options

$page = 0;
echo "Pulling inventory from Cin7".PHP_EOL;
do {
	break;
	$page++;
/* @var $res JsonAwareResponse */
	$res = $cc->get('ProductOptions', [
		'query' => [
			'fields' => implode(',', ['id', 'CreatedDate', 'ModifiedDate', 'Status', 'ProductId', 'Code', 'Barcode', 'StockAvailable', 'StockOnHand']),
	//		'where' => "LogisticsStatus = '9' AND createdDate >= '$cut_on_date'",
			'order' => 'CreatedDate DESC',
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	$cc_variants = $res->getJson();
	foreach($cc_variants as $cc_variant){
		echo insert_update_cin_product_option($db, $cc_variant).PHP_EOL;
	}
} while(count($cc_variants) >= $page_size);

// Update shopify inventory levels

// pull updated post-hold inventory levels
$inventory_levels = $db->query("SELECT v.inventory_item_id, v.sku, v.inventory_quantity, v.title, ROUND(cpo.stock_available) AS stock_available, COUNT(rcs.id) AS reserved_inventory, ROUND(cpo.stock_available - COUNT(*)) AS stock_available_unreserved
FROM sc_products scp
LEFT JOIN variants v ON scp.variant_id=v.id
LEFT JOIN products p ON v.product_id = p.id
LEFT JOIN cin_product_options cpo ON v.sku=cpo.sku
LEFT JOIN rc_subscriptions rcs ON rcs.variant_id=scp.variant_id
AND rcs.status IN ('ACTIVE', 'ONETIME')
AND rcs.deleted_at IS NULL
AND rcs.next_charge_scheduled_at >= '".date('Y-m-d')."'
GROUP BY v.sku")->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);

$inventory_items = $sc->get('inventory_items.json?ids=', [
	'ids' => implode(',', array_keys($inventory_levels))
]);

echo "Syncing calculated inventory to shopify".PHP_EOL;
foreach($inventory_items as $inventory_item){
	$inventory_level = $inventory_levels[$inventory_item['id']];
	if($inventory_level['inventory_quantity'] == $inventory_level['stock_available_unreserved']){
		echo "Would skip ".$inventory_level['title'].", inventory already ".$inventory_level['stock_available_unreserved'].PHP_EOL;
		continue;
	}
	echo "Setting ".$inventory_level['title']." to ".$inventory_levels[$inventory_item['id']]['stock_available_unreserved'].PHP_EOL;
	$res = $sc->post('inventory_levels/set.json', [
		'inventory_item_id' => $inventory_item['id'],
		'location_id' => 36244366, // AMS location ID
		'available' => $inventory_levels[$inventory_item['id']]['stock_available_unreserved'],
	]);
}




function insert_update_cin_product_option(PDO $db, $cin_product_option){
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_cin_product_option'])){
		$_stmt_cache['iu_cin_product_option'] = $db->prepare("INSERT INTO cin_product_options (id, created_at, modified_at, status, cin_product_id, sku, upc, stock_available, stock_on_hand) VALUES (:id, :created_at, :modified_at, :status, :cin_product_id, :sku, :upc, :stock_available, :stock_on_hand) ON DUPLICATE KEY UPDATE id=:id, created_at=:created_at, modified_at=:modified_at, status=:status, cin_product_id=:cin_product_id, sku=:sku, upc=:upc, stock_available=:stock_available, stock_on_hand=:stock_on_hand");
	}
	$_stmt_cache['iu_cin_product_option']->execute([
		'id' => $cin_product_option['id'],
		'created_at' => $cin_product_option['createdDate'],
		'modified_at' => $cin_product_option['modifiedDate'],
		'status' => $cin_product_option['status'],
		'cin_product_id' => $cin_product_option['productId'],
		'sku' => $cin_product_option['code'],
		'upc' => $cin_product_option['barcode'],
		'stock_available' => $cin_product_option['stockAvailable'],
		'stock_on_hand' => $cin_product_option['stockOnHand'],
	]);
	return $cin_product_option['id'];
}