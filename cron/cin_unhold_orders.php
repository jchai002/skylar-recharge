<?php
require_once(__DIR__.'/../includes/config.php');

$cut_on_date = '2019-12-16T08:00:00Z';
$page_size = 250;
$page = 0;
$updates = [];
do {
	$page++;
	// Get held orders
	/* @var $res JsonAwareResponse */
	$res = $cc->get('SalesOrders', [
		'query' => [
			'fields' => implode(',', ['id', 'reference', 'logisticsStatus']),
			'where' => "LogisticsStatus = '9' AND createdDate >= '$cut_on_date'",
			'order' => 'CreatedDate ASC',
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	sleep(1);

	$cc_orders = $res->getJson();

	$stmt = $db->prepare("SELECT * FROM orders WHERE number=?");
	foreach($cc_orders as $index=>$cc_order){
		echo "Checking order ".$cc_order['reference']."... ";
		if($cc_order['logisticsStatus'] != 9){
			if(!empty($row['cancelled_at'])){
				echo "logisticsStatus is ".$cc_order['logisticsStatus'].", skipping cin7 id: ".$cc_order['id'].PHP_EOL;
				continue;
			}
		}
		if(empty($cc_order['FreightDescription'])){
			echo "Skipping, empty freight description".PHP_EOL;
			continue;
		}
		$order_number = str_ireplace('#sb','',$cc_order['reference']);
		$stmt->execute([$order_number]);
		if($stmt->rowCount() == 0){
			echo "Couldn't find order in DB, must not be Shopify";
			continue;
		}
		$row = $stmt->fetch();
		if(!empty($row['cancelled_at'])){
			echo "Order cancelled in Shopify, skipping cin7 id: ".$cc_order['id'].PHP_EOL;
			continue;
		}
		if(strpos($row['tags'], 'HOLD:') !== false){
			echo "Order held in Shopify, skipping".PHP_EOL;
			continue;
		}
		$cc_order['logisticsStatus'] = 1;
		$updates[] = $cc_order;
		echo "Added to update queue [".count($updates)."]".PHP_EOL;
		if(count($updates) == $page_size){
			echo "! Queue hit $page_size, sending... ";
			$res = send_cc_updates($cc, $updates);
			$updates = [];
			echo "Done".PHP_EOL;
			sleep(1);
		}
	}
} while(count($cc_orders) >= $page_size);

if(count($updates) > 0){
	echo "Sending last updates... ";
	$res = send_cc_updates($cc, $updates);
	echo "Done".PHP_EOL;
}

function send_cc_updates(GuzzleHttp\Client $cc, $updates){
	$res = $cc->put('SalesOrders',[
		'http_errors' => false,
		'json' => $updates,
	]);
	if($res->getStatusCode() != 200){
		echo "Error! ".$res->getStatusCode().": ".$res->getReasonPhrase()." ";
		print_r($res->getBody());
	}
	return $res;
}