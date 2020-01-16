<?php
require_once(__DIR__.'/../includes/config.php');

$fh = fopen(__DIR__."/result.csv", 'r');

$titles = array_map('strtolower',fgetcsv($fh));

$order = [];
$order_id = 0;

while($row = fgetcsv($fh)){

	$row = array_combine($titles, $row);
	$order_id = $row['order_id'];

	$order = $sc->get('/admin/orders/'.$order_id.'.json');
	$tags = explode(',', $order['tags']);

	$key = array_search('HOLD: Scent Club Blackout', $tags);
	if (false !== $key) {
		unset($tags[$key]);
	} else {
		continue;
	}

	echo $order_id;
	$res = $sc->put('/admin/orders/'.$order_id.'.json', ['order' => [
		'id' => $row['id'],
		'tags' => implode(', ', $tags),
	]]);
	echo " ".$res['tags'].PHP_EOL;
//	die();
}
