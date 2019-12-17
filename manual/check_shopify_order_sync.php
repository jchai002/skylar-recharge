<?php
require_once(__DIR__.'/../includes/config.php');


// Orders
echo "Checking orders".PHP_EOL;
$page_size = 250;
$page = 0;
$max_page = 0;
$orders = [];
$min_date = date('Y-m-d H:00:00', strtotime('-10 hours'));
$stmt = $db->prepare("SELECT * FROM orders WHERE shopify_id=?");
do {
	$page++;
	$res = $sc->get('/admin/orders.json', [
		'created_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	if(empty($res)){
		print_r($sc->last_error);
		die();
	}
	echo count($res)." orders ".PHP_EOL;
	foreach($res as $order){
		echo "Checking ".$order['id']."... ";
		$stmt->execute([$order['id']]);
		$row = $stmt->fetch();
		foreach([
			'tags' => [$order['tags'], $row['tags']],
			'updated_at' => [strtotime($order['updated_at']), strtotime($row['updated_at'])],
		] as $key => $comparison){
			if($comparison[0] != $comparison[1]){
				print_r($comparison);
				echo $key." does not match!".PHP_EOL;
				continue 2;
			}
		}
		echo "OK".PHP_EOL;
	}
	if($max_page > 0 && $page >= $max_page){
		break;
	}
} while(count($res) >= $page_size);