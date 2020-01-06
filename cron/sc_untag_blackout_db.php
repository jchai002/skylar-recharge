<?php
require_once(__DIR__.'/../includes/config.php');

$first_of_current_month = date('Y-m-01');
$untag_time = offset_date_skip_weekend(strtotime($first_of_current_month));
if(date('Y-m-d') != date('Y-m-d', $untag_time) && (empty($argv[1]) || $argv[1] != 'force')){
	die('Today is not the day to untag!');
}

$today = date('Y-m-d');
// First, make sure we are fully synced
$page = 0;
$page_size = 250;
$total_orders = 0;
$start_time = time();
do {
	$page++;
	$orders = $sc->get('/admin/orders.json', [
		'created_at_min' => $today,
		'limit' => $page_size,
		'page' => $page,
		'order' => 'created_at asc',
	]);
	foreach($orders as $order){
		$total_orders++;
		echo insert_update_order($db, $order, $sc).PHP_EOL;
	}
	$elapsed_time = (time()-$start_time)/60;
	echo "Synced $total_orders in ".round($elapsed_time, 2)."m ".round($total_orders/($elapsed_time), 2)." orders/min".PHP_EOL;
} while(count($orders) >= $page_size);

$stmt = $db->query("SELECT shopify_id as id, tags FROM orders WHERE tags LIKE '%HOLD: Scent Club Blackout%'");

$orders = $stmt->fetchAll();

echo "Starting untagging on ".count($orders)." orders".PHP_EOL;
foreach($orders as $order){

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
	// TODO: insert_update order
}
// TODO: Create loop that re-checks
