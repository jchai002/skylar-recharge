<?php
require_once(__DIR__.'/../../includes/config.php');

$page_size = 250;

die();

// Sync product options
$since = gmdate('Y-m-d\Th:i:', ((!empty($argv) && !empty($argv[1]) && $argv[1] == 'all') ? time() - 365*24*60*60 : time()-60)).'00Z';

echo "Pulling since UTC ".$since.PHP_EOL;

$page = 0;
echo "Pulling Products from Cin7".PHP_EOL;
do {
	$page++;
	/* @var $res JsonAwareResponse */
	$res = $cc->get('Products', [
		'query' => [
			'fields' => implode(',', ['id', 'CreatedDate', 'ModifiedDate', 'Name']),
			'where' => "ModifiedDate >= '$since'",
			'order' => 'ModifiedDate DESC',
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	$cc_products = $res->getJson();
	foreach($cc_products as $cc_product){
		print_r($cc_product);
		echo insert_update_cin_product($db, $cc_product).PHP_EOL;
	}
} while(count($cc_products) >= $page_size);
sleep(1);

$page = 0;
echo "Pulling Product Options from Cin7".PHP_EOL;
do {
	$page++;
	/* @var $res JsonAwareResponse */
	$res = $cc->get('ProductOptions', [
		'query' => [
			'fields' => implode(',', ['id', 'CreatedDate', 'ModifiedDate', 'Status', 'ProductId', 'Code', 'Barcode', 'StockAvailable', 'StockOnHand']),
			'where' => "ModifiedDate >= '$since'",
			'order' => 'ModifiedDate DESC',
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	$cc_variants = $res->getJson();
	foreach($cc_variants as $cc_variant){
		echo insert_update_cin_product_option($db, $cc_variant).PHP_EOL;
	}
} while(count($cc_variants) >= $page_size);
sleep(1);

$page = 0;
echo "Pulling Branches from Cin7".PHP_EOL;
do {
	$page++;
	/* @var $res JsonAwareResponse */
	$res = $cc->get('Branches', [
		'query' => [
			'fields' => implode(',', ['id', 'CreatedDate', 'ModifiedDate', 'BranchType', 'Company']),
			'where' => "ModifiedDate >= '$since'",
			'order' => 'ModifiedDate DESC',
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	$cc_branches = $res->getJson();
	foreach($cc_branches as $cc_branch){
		echo insert_update_cin_branch($db, $cc_branch).PHP_EOL;
	}
} while(count($cc_branches) >= $page_size);
sleep(1);

$page = 0;
echo "Pulling inventory from Cin7".PHP_EOL;
do {
	$page++;
	/* @var $res JsonAwareResponse */
	$res = $cc->get('Stock', [
		'query' => [
			'fields' => implode(',', ['ProductOptionId', 'BranchId', 'ModifiedDate', 'Available', 'StockOnHand', 'OpenSales', 'Incoming', 'Virtual', 'Holding']),
			'where' => "ModifiedDate >= '$since'",
			'order' => 'ModifiedDate DESC',
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	$cc_stock_units = $res->getJson();
	foreach($cc_stock_units as $cc_stock_unit){
		echo implode('-',insert_update_cin_stock_unit($db, $cc_stock_unit)).PHP_EOL;
	}
} while(count($cc_stock_units) >= $page_size);
sleep(1);

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
		echo "Skip ".$inventory_level['title'].", inventory already ".$inventory_level['stock_available_unreserved'].PHP_EOL;
		continue;
	}
	echo "Setting ".$inventory_level['title']." to ".$inventory_levels[$inventory_item['id']]['stock_available_unreserved'].PHP_EOL;
	$res = $sc->post('inventory_levels/set.json', [
		'inventory_item_id' => $inventory_item['id'],
		'location_id' => 36244366, // AMS location ID
		'available' => $inventory_levels[$inventory_item['id']]['stock_available_unreserved'],
	]);
}




function insert_update_cin_product(PDO $db, $cin_product){
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_cin_product'])){
		$_stmt_cache['iu_cin_product'] = $db->prepare("INSERT INTO cin_products (id, created_at, modified_at, name) VALUES (:id, :created_at, :modified_at, :name) ON DUPLICATE KEY UPDATE id=:id, created_at=:created_at, modified_at=:modified_at, name=:name");
	}
	$_stmt_cache['iu_cin_product']->execute([
		'id' => $cin_product['id'],
		'created_at' => $cin_product['createdDate'],
		'modified_at' => $cin_product['modifiedDate'],
		'name' => $cin_product['name'],
	]);
	return $cin_product['id'];
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
function insert_update_cin_branch(PDO $db, $cin_branch){
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_cin_branch'])){
		$_stmt_cache['iu_cin_branch'] = $db->prepare("INSERT INTO cin_branches (id, created_at, modified_at, type, company) VALUES (:id, :created_at, :modified_at, :type, :company) ON DUPLICATE KEY UPDATE created_at=:created_at, modified_at=:modified_at, type=:type, company=:company");
	}
	$_stmt_cache['iu_cin_branch']->execute([
		'id' => $cin_branch['id'],
		'created_at' => $cin_branch['createdDate'],
		'modified_at' => $cin_branch['modifiedDate'],
		'type' => $cin_branch['branchType'],
		'company' => $cin_branch['company'],
	]);
	return $cin_branch['id'];
}
function insert_update_cin_stock_unit(PDO $db, $cin_stock_unit){
	global $_stmt_cache;
	if(empty($_stmt_cache['iu_cin_stock_unit'])){
		$_stmt_cache['iu_cin_stock_unit'] = $db->prepare("INSERT INTO cin_stock_units (cin_product_option_id, cin_branch_id, modified_at, available, on_hand, open_sales, incoming, virtual, holding) VALUES (:cin_product_option_id, :cin_branch_id, :modified_at, :available, :on_hand, :open_sales, :incoming, :virtual, :holding) ON DUPLICATE KEY UPDATE modified_at=:modified_at, available=:available, on_hand=:on_hand, open_sales=:open_sales, incoming=:incoming, virtual=:virtual, holding=:holding");
	}
	$_stmt_cache['iu_cin_stock_unit']->execute([
		'cin_product_option_id' => $cin_stock_unit['productOptionId'],
		'cin_branch_id' => $cin_stock_unit['branchId'],
		'modified_at' => $cin_stock_unit['modifiedDate'],
		'available' => $cin_stock_unit['available'],
		'on_hand' => $cin_stock_unit['stockOnHand'],
		'open_sales' => $cin_stock_unit['openSales'],
		'incoming' => $cin_stock_unit['incoming'],
		'virtual' => $cin_stock_unit['virtual'],
		'holding' => $cin_stock_unit['holding'],
	]);
	return [$cin_stock_unit['productOptionId'], $cin_stock_unit['branchId']];
}