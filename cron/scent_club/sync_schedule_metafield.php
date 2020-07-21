<?php
require_once(__DIR__ . '/../../includes/config.php');

// Load schedule from DB

$last_month_sc_date = date('Y-m-01', get_last_month());
$sc_info = $stmt = $db->query("SELECT sc_date, ship_date, public_launch, member_launch, shopify_variant_id AS variant_id, variant_title, sku, shopify_product_id AS product_id, handle, product_title, tags FROM sc_product_info ORDER BY sc_date")->fetchAll();

foreach($sc_info as $index => $sc_info_row){
	if($sc_info_row['sc_date'] == '2020-06-01'){
		$sc_info_row['member_launch'] = '2020-05-21';
		$sc_info_row['public_launch'] = '2020-05-25';
	}
	if(empty($sc_info_row['ship_date'])){
		$sc_info_row['ship_date'] = ScentClubSchedule::calculate_ship_date($sc_info_row['sc_date']);
	}
	if(empty($sc_info_row['public_launch'])){
		$sc_info_row['public_launch'] = ScentClubSchedule::calculate_public_launch($sc_info_row['public_launch']);
	}
	if(empty($sc_info_row['member_launch'])){
		$sc_info_row['member_launch'] = ScentClubSchedule::calculate_member_launch($sc_info_row['member_launch']);
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

echo "Checking shop metafield... ";
$row = $db->query("SELECT shopify_id, value FROM metafields WHERE owner_resource='shop' AND namespace='scent_club' AND `key`='products' AND deleted_at IS NULL")->fetch();
if(empty($row)){
	echo "Not in DB, updating".PHP_EOL;
	$res = $sc->post('/admin/metafields.json', ['metafield'=> [
		'namespace' => 'scent_club',
		'key' => 'products',
		'value' => json_encode($sc_info),
		'value_type' => 'json_string'
	]]);
	print_r($res);
	if(!empty($res)){
		print_r(insert_update_metafield($db, $res));
	}
	send_alert($db, 8,
		"Finished pushing SC metafield",
		"SC Metafield Pushed",
		['tim@skylar.com', 'adrian@skylar.com'],
		['log' => $res, 'smother' => false]
	);
} else if($row['value'] != json_encode($sc_info)) {
	echo "Doesn't match DB, updating".PHP_EOL;
	$res = $sc->put('metafields/'.$row['shopify_id'].'.json', [ 'metafield' => [
		'id' => $row['shopify_id'],
		'value' => json_encode($sc_info),
	]]);
	print_r($res);
	if(!empty($res)){
		print_r(insert_update_metafield($db, $res));
	}
	send_alert($db, 8,
		"Finished pushing SC metafield",
		"SC Metafield Pushed",
		['tim@skylar.com', 'adrian@skylar.com'],
		['log' => $res, 'smother' => false]
	);
} else {
	echo "No updated needed".PHP_EOL;
}

// Update schedule metafield for individual products

$stmt_check_metafield = $db->prepare("SELECT shopify_id, value FROM metafields WHERE owner_resource='product' AND owner_id=:owner_id AND namespace='scent_club' AND `key`='schedule' AND deleted_at IS NULL");
foreach($sc_info as $index => $sc_info_row){
	echo "Updating product ".$sc_info_row['product_title']." ".$sc_info_row['product_id']."... ";
	$metafield_value = [
		'ship_date' => $sc_info_row['ship_date'],
		'public_launch' => $sc_info_row['public_launch'],
		'member_launch' => $sc_info_row['member_launch'],
		'public_launch_time' => $sc_info_row['public_launch_time'],
		'member_launch_time' => $sc_info_row['member_launch_time'],
		'public_launch_end_time' => $sc_info_row['public_launch_end_time'],
		'member_launch_end_time' => $sc_info_row['member_launch_end_time'],
	];
	$stmt_check_metafield->execute([
		'owner_id' => $sc_info_row['product_id'],
	]);
	if($stmt_check_metafield->rowCount() == 0){
		$res = $sc->post('/admin/products/'.$sc_info_row['product_id'].'/metafields.json', ['metafield'=> [
			'namespace' => 'scent_club',
			'key' => 'schedule',
			'value' => json_encode($metafield_value),
			'value_type' => 'json_string'
		]]);
		echo "Created metafield".PHP_EOL;
		continue;
	}
	$row = $stmt_check_metafield->fetch();
	if($row['value'] != json_encode($metafield_value)) {
		$res = $sc->post('/admin/products/'.$sc_info_row['product_id'].'/metafields.json', ['metafield'=> [
			'namespace' => 'scent_club',
			'key' => 'schedule',
			'value' => json_encode($metafield_value),
			'value_type' => 'json_string'
		]]);
		echo "Updated metafield".PHP_EOL;
	}
	echo "No update needed".PHP_EOL;
}