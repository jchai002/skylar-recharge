<?php
require_once(__DIR__.'/../includes/config.php');

$log = [
	'lines' => '',
	'error' => false,
];

$now = date('Y-m-d H:i:s');
$stmt = $db->query("SELECT * FROM change_schedule
WHERE object_type='product' AND field='price' AND scheduled_at < '$now' AND processed_at IS NULL");

if(empty($stmt->rowCount())){
	die("Nothing to process");
}

$stmt_update_schedule = $db->prepare("UPDATE change_schedule SET processed_at='".date('Y-m-d H:i:s')."' WHERE id=?");
$stmt_get_variants = $db->prepare("SELECT shopify_id FROM variants WHERE product_id=? AND deleted_at IS NULL");
foreach($stmt->fetchAll() as $row){
	log_echo($log, "Processing scheduler id ".$row['id']." product id ".$row['object_id']." to price ".$row['new_value']."... ");
	$product = get_product($db, $row['object_id']);
	$stmt_get_variants->execute([
		$product['id'],
	]);
	if(!empty($stmt_get_variants->rowCount())){
		foreach($stmt_get_variants->fetchAll(PDO::FETCH_COLUMN) as $variant_id){
			log_echo($log, "Updating variant id $variant_id... ");
			$sc->put('/admin/api/2020-01/variants/'.$variant_id.'.json', ['variant' => [
				'id' => $variant_id,
				'price' => $row['new_value'],
			]]);
		}
	}
	$stmt_update_schedule->execute([
		$row['id']
	]);
	log_echo($log, "Processed");
}

send_alert($db, 9,
	"Finished updating product prices",
	"Product Prices Updated".($log['error'] ? ' ERROR' : ' Log'),
	'tim@skylar.com',
	['log' => $log]
);