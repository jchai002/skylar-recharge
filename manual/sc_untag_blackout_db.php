<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

$stmt = $db->query("SELECT shopify_id as id, tags FROM orders WHERE created_at >= '2019-07-01' AND tags LIKE '%HOLD%'");

foreach($stmt->fetchAll() as $order){

	$tags = explode(', ', $order['tags']);

	$key = array_search('HOLD: Scent Club Blackout',$tags);
	if (false !== $key) {
		unset($tags[$key]);
	}

	echo $order['id'];
	$res = $sc->put('/admin/orders/'.$order['id'].'.json', ['order' => [
		'id' => $order['id'],
		'tags' => implode(', ', $tags),
	]]);
	echo " ".$res['tags'].PHP_EOL;
//	die();
}
