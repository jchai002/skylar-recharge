<?php
require_once(__DIR__.'/../includes/config.php');

do {
	$orders = $sc->get('/admin/orders.json',['tags'=>'HOLD: Scent Club Blackout', 'limit'=>250]);
	foreach($orders as $order){
		$tags = explode(', ', $order['tags']);

		$key = array_search('HOLD: Scent Club Blackout',$tags);
		if (false !== $key) {
			unset($tags[$key]);
		}
	}
} while(count($orders) >= 250);




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
