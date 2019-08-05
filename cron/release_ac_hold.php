<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

// Can't search shopify orders by tag, so use db
$stmt = $db->query("SELECT shopify_id, tags as id FROM orders WHERE tags LIKE '%HOLD: AC Followup%'");

foreach($stmt->fetchAll() as $row){
	$tags = explode(', ', $row['tags']);
	$key = array_search('HOLD: AC Followup',$tags);
	if (false !== $key) {
		unset($tags[$key]);
	}
	$res = $sc->put('/orders/'.$row['id'].'.json', [
		'id' => $row['id'],
		'tags' => implode(', ', $tags),
	]);
	if(empty($res)){
		echo "Error";
		print_r($sc->last_error);
	}
}