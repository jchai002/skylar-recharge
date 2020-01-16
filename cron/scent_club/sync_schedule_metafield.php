<?php
require_once(__DIR__ . '/../../includes/config.php');

// Load schedule from DB

$last_month_sc_date = date('Y-m-01', get_last_month());
$sc_info = $stmt = $db->query("SELECT sc_date, ship_date, public_launch, member_launch, shopify_variant_id AS variant_id, variant_title, sku, shopify_product_id AS product_id, handle, product_title, tags FROM sc_product_info ORDER BY sc_date")->fetchAll();

foreach($sc_info as $index => $sc_info_row){
	if(empty($sc_info_row['ship_date'])){
		$sc_info_row['ship_date'] = ScentClubSchedule::calculate_ship_date($sc_info_row['sc_date']);
	}
	if(empty($sc_info_row['ship_date'])){
		$sc_info_row['ship_date'] = ScentClubSchedule::calculate_ship_date($sc_info_row['sc_date']);
	}
	if(empty($sc_info_row['ship_date'])){
		$sc_info_row['ship_date'] = ScentClubSchedule::calculate_ship_date($sc_info_row['sc_date']);
	}
	$sc_info[$index] = $sc_info_row;
}
foreach($sc_info as $index => $sc_info_row){
	if($index == 0){
		$sc_info_row['public_launch_time'] = 0;
		$sc_info_row['member_launch_time'] = 0;
	} else {
		$sc_info_row['public_launch_time'] = strtotime($sc_info_row['public_launch']);
		$sc_info_row['member_launch_time'] = strtotime($sc_info_row['member_launch']);
	}
	if(empty($sc_info[$index+1])){
		$sc_info_row['public_launch_end_time'] = 2147483647; // 32 bit max int
		$sc_info_row['member_launch_end_time'] = 2147483647;
	} else {
		$sc_info_row['public_launch_end_time'] = strtotime($sc_info[$index+1]['public_launch'])-1;
		$sc_info_row['member_launch_end_time'] = strtotime($sc_info[$index+1]['member_launch'])-1;
	}
	$sc_info[$index] = $sc_info_row;
}

$row = $db->query("SELECT shopify_id, value FROM metafields WHERE owner_resource='shop' AND namespace='scent_club' AND `key`='products' AND deleted_at IS NULL")->fetch();
if(empty($row)){
	$res = $sc->post('/admin/metafields.json', ['metafield'=> [
		'namespace' => 'scent_club',
		'key' => 'products',
		'value' => json_encode($sc_info),
		'value_type' => 'json_string'
	]]);
	print_r($res);
	send_alert($db, 8,
		"Finished pushing SC metafield",
		"SC Metafield Pushed",
		'tim@skylar.com',
		['log' => $res, 'smother' => false]
	);
} else if($row['value'] != json_encode($sc_info)) {
	$res = $sc->put('/admin/api/2019-10/metafields/'.$row['shopify_id'].'.json', [ 'metafield' => [
		'id' => $row['shopify_id'],
		'value' => json_encode($sc_info),
	]]);
	print_r($res);
	send_alert($db, 8,
		"Finished pushing SC metafield",
		"SC Metafield Pushed",
		'tim@skylar.com',
		['log' => $res, 'smother' => false]
	);
} else {
	echo "No updated needed";
}