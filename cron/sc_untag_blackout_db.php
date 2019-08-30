<?php
require_once(__DIR__.'/../includes/config.php');

$first_of_current_month = date('Y-m-01');
$untag_time = offset_date_skip_weekend(strtotime($first_of_current_month));
if(date('Y-m-d') != date('Y-m-d', $untag_time)){
	die('Today is not the day to untag!');
}

$sc = new ShopifyClient();

$stmt = $db->query("SELECT shopify_id as id, tags FROM orders WHERE tags LIKE '%HOLD: Scent Club Blackout%'");

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
}
