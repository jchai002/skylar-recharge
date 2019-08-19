<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

// Can't search shopify orders by tag, so use db
$stmt = $db->query("SELECT shopify_id AS id, tags FROM orders WHERE tags LIKE '%HOLD: AC Followup%'");

foreach($stmt->fetchAll() as $row){
	$tags = explode(', ', $row['tags']);
	$key = array_search('HOLD: AC Followup',$tags);
	if (false !== $key) {
		unset($tags[$key]);
	}
	$res = $sc->put('/admin/orders/'.$row['id'].'.json', [ 'order' => [
		'id' => $row['id'],
		'tags' => implode(', ', $tags),
	]]);
	echo $row['id']." ".$res['tags'].PHP_EOL;
	if(empty($res)){
		echo "Error";
		print_r($sc->last_error);
	}
}