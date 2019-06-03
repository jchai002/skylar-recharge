<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

$fh = fopen(__DIR__."/orders_export.csv", 'r');

$titles = array_map('strtolower',fgetcsv($fh));

$order = [];
$order_id = 0;

while($row = fgetcsv($fh)){

	$row = array_combine($titles, $row);

	$tags = explode(', ', $row['tags']);

	$key = array_search('HOLD: Scent Club Blackout',$tags);
	if (false !== $key) {
		unset($tags[$key]);
	}

	echo $row['id'];
	$res = $sc->put('/admin/orders/'.$row['id'].'.json', ['order' => [
		'id' => $row['id'],
		'tags' => implode(', ', $tags),
	]]);
	echo " ".$res['tags'].PHP_EOL;
//	die();
}
