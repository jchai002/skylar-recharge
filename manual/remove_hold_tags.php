<?php
require_once(__DIR__.'/../includes/config.php');

$start_date = date('Y-m-d');

$stmt = $db->query("SELECT shopify_id as id, tags FROM orders WHERE tags LIKE '%HOLD: Test Order%'");

$orders = $stmt->fetchAll();

echo "Starting untagging on ".count($orders)." orders".PHP_EOL;
foreach($orders as $order){

	$tags = explode(', ', $order['tags']);

	$key = array_search('HOLD: Test Order',$tags);
	if (false !== $key) {
		unset($tags[$key]);
	}

	echo $order['id'];
	$res = $sc->put('/admin/orders/'.$order['id'].'.json', ['order' => [
		'id' => $order['id'],
		'tags' => implode(', ', $tags),
	]]);
	echo " ".$res['tags'].PHP_EOL;
}
