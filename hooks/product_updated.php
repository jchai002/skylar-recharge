<?php
require_once('../includes/config.php');

if(!empty($_REQUEST['id'])){
	$product = $sc->call('GET', '/admin/products/'.intval($_REQUEST['id']).'.json');
} else {
	respondOK();
	$data = file_get_contents('php://input');
	$product = json_decode($data, true);
}
if(empty($product)){
	die('no data');
}

echo insert_update_product($db, $product).PHP_EOL;

$metafields = $sc->get('/admin/products/'.$product['id'].'/metafields.json');
if(empty($metafields)){
	print_r($sc->last_error);
	echo "Couldn't get metafields for ".$product['id'].PHP_EOL;
} else {
	print_r(insert_update_metafields($db, $metafields));
}
foreach($product['variants'] as $variant){
	$metafields = $sc->get('/admin/variants/'.$variant['id'].'/metafields.json');
	if(empty($metafields)){
		print_r($sc->last_error);
		echo "Couldn't get metafields for ".$variant['id'].PHP_EOL;
		continue;
	}
	print_r(insert_update_metafields($db, $metafields));
}