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
$res = $sc->post('/admin/api/2020-01/products/')

// Get sku and images from monthly product, and update:
//Skylar Scent Club
//Skylar Scent Club Auto renew
//Skylar Scent Club RC
//Skylar Scent Club RC

// Get SC gift ships now sku and update:
//Scent Club Gift - Ships Now (3 months)
//Scent Club Gift - Ships Now (6 months)
//Scent Club Gift - Ships Now (12 months)
//Scent Club Gift - Ships Now RC