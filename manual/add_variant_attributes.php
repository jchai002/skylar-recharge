<?php
require_once(__DIR__.'/../includes/config.php');

$product_ids = [
	3875807395927,
];

$product_type = 1;
$scent = 22;
$format = 4;
$size = 3;


$stmt = $db->prepare("SELECT id FROM variants v WHERE product_id = ?");
$stmt_update_attributes = $db->prepare("INSERT INTO variant_attributes (variant_id, scent_id, format_id, product_type_id, size_id) VALUES (:variant_id, :scent_id, :format_id, :product_type_id, :size_id) ON DUPLICATE KEY UPDATE scent_id=:scent_id, format_id=:format_id, product_type_id=:product_type_id, size_id=:size_id");
$stmt_get_kit = $db->prepare("SELECT child_variant FROM variant_kits WHERE parent_variant=?");
foreach($product_ids as $product_id){
	$product = get_product($db, $product_id);
	$stmt->execute([$product['id']]);
	foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $variant_id){
		$stmt_update_attributes->execute([
			'variant_id' => $variant_id,
			'scent_id' => $scent,
			'format_id' => $format,
			'product_type_id' => $product_type,
			'size_id' => $size,
		]);
	}
}