<?php
require_once(__DIR__ . '/../../includes/config.php');

$log = [
	'lines' => '',
	'error' => false,
];

// Load live scent info
if(!empty($argv) && !empty($argv[1]) && $argv[1] == 'force'){
	$today = date('Y-m-d');
	$stmt = $db->query("SELECT * FROM sc_product_info WHERE public_launch <= '$today' ORDER BY member_launch DESC LIMIT 1");
} else {
	$sc_date = date('Y-m-', get_next_month())."01";
	$today = date('Y-m-d');
	$stmt = $db->query("SELECT * FROM sc_product_info WHERE sc_date='$sc_date' AND public_launch = '$today'");
}
if($stmt->rowCount() == 0){
	die("No Live Monthly Scent!");
}
$scent_info = $stmt->fetch();
$new_sku = $scent_info['sku'];
log_echo($log, "Scent: ".print_r($scent_info, true));

// Get metafields

$stmt = $db->prepare("SELECT value FROM skylar.metafields
WHERE owner_resource='product'
AND namespace='scent_club'
AND deleted_at IS NULL
AND owner_id='".$scent_info['shopify_product_id']."'
AND `key`=?
;");
$stmt->execute(['collection_tile_image']);
$image = $stmt->fetchColumn();
$stmt->execute(['gift_kit_skus']);
$gift_skus = json_decode($stmt->fetchColumn(), true);

if(empty($image)){
	die("No image!");
}
if(empty($gift_skus)){
	die("No gift skus!");
}
print_r($image);
print_r($gift_skus);

$base_file_url = "https://cdn.shopify.com/s/files/1/1445/2216/files/";

echo $base_file_url.$image.PHP_EOL;

// Get products we need to update

$products_to_update = $db->query("SELECT v.shopify_id AS variant_id, p.shopify_id AS product_id FROM products p
LEFT JOIN variants v ON v.product_id=p.id
WHERE p.type = 'Scent Club'
AND p.deleted_at IS NULL
AND v.deleted_at IS NULL
;")->fetchAll();

foreach($products_to_update as $product){
	// Update sku
	$variant_id = $product['variant_id'];
	$product_id = $product['product_id'];
	$res = $sc->put("variants/$variant_id.json", ['variant' => [
		"id" => $variant_id,
		"sku" => $new_sku,
	]]);
	log_echo($log, "$product_id $variant_id, ".$res['sku']);

	$old_images = $sc->get("products/$product_id/images.json");

	$res = $sc->post("products/$product_id/images.json", ["image" => [
		'position' => 1,
		'src' => $base_file_url.$image,
	]]);

	foreach($old_images as $old_image){
		$image_id = $old_image['id'];
		$sc->delete("products/$product_id/images/$image_id.json");
	}
}

$products_to_update = $db->query("SELECT v.shopify_id AS variant_id, p.shopify_id AS product_id, v.title FROM products p
LEFT JOIN variants v ON v.product_id=p.id
WHERE p.type = 'Scent Club Gift'
AND p.title LIKE '%Ships Now%'
AND p.deleted_at IS NULL
AND v.deleted_at IS NULL
;")->fetchAll();

foreach($products_to_update as $product){
	// Update sku
	$variant_id = $product['variant_id'];
	$product_id = $product['product_id'];
	$months = strtok($product['title'], ' ');
	if(empty($gift_skus[$months])){
		$log['error'] = true;
		log_echo($log, "No gift sku for $months, product: $product_id variant: $variant_id, skipping");
		continue;
	}
	$res = $sc->put("variants/$variant_id.json", ['variant' => [
		"id" => $variant_id,
		"sku" => $gift_skus[$months],
	]]);
	log_echo($log, "$product_id $variant_id, ".$res['sku']);
}

send_alert($db, 8,
	"Finished releasing SC Public Scent".($log['error'] ? ' with errors' : ''),
	"SC Public Scent Release".($log['error'] ? ' ERROR' : ' Log'),
	['tim@skylar.com', 'julie@skylar.com'],
	['log' => $log]
);