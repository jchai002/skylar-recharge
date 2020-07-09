<?php

//die();

require_once(__DIR__.'/../includes/config.php');

$cut_on_date = '2019-12-16T08:00:00Z';
$page_size = 250;
$page = 0;
$updates = [];
$last_send_time = time();
$buffer_date = gmdate('Y-m-d\TH:i:s\Z', strtotime('-5 minutes'));

$stmt_get_prev_order = $db->prepare("SELECT 1 FROM orders WHERE email = :email AND id != :id LIMIT 1");
$stmt_get_order_skus = $db->prepare("SELECT sku FROM order_line_items WHERE order_id=?");

echo "Pulling $buffer_date to $cut_on_date".PHP_EOL;

do {
	$page++;
	// Get held orders
	/* @var $res JsonAwareResponse */
	$res = $cc->get('SalesOrders', [
		'query' => [
			'fields' => implode(',', ['id', 'email', 'status', 'reference', 'logisticsStatus', 'freightDescription', 'deliveryPostalCode', 'deliveryCountry', 'lineItems']),
			'where' => "LogisticsStatus = '9' AND createdDate >= '$cut_on_date' AND createdDate < '$buffer_date' AND status = 'APPROVED' AND (stage = 'New' OR stage = 'Fraud Warning')",
//			'where' => "LogisticsStatus = '9' AND createdDate >= '$cut_on_date' AND createdDate < '$buffer_date' AND status = 'APPROVED' AND stage = 'New' AND id = 220380",
			'order' => 'CreatedDate DESC',
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	sleep(1);

	$cc_orders = $res->getJson();

	$stmt = $db->prepare("SELECT * FROM orders WHERE number=?");
	foreach($cc_orders as $index=>$cc_order){
		$send_updates = false;
		if(count($updates) > 5){
			echo "Updates hit ".count($updates).", ";
			$send_updates = true;
		}
		if(count($updates) > 0 && time()-$last_send_time > 30){
			echo "It's been ".(time()-$last_send_time)."s since last update, ";
			$send_updates = true;
		}
		if($send_updates){
			echo "Sending updates... ";
			$res = send_cc_updates($cc, $updates);
			$updates = [];
			echo "Done".PHP_EOL;
			sleep(1);
		}
		if(empty($updates)){
			$last_send_time = time();
		}
		$order_number = str_ireplace('#sb','',$cc_order['reference']);
		$stmt->execute([$order_number]);
		if($stmt->rowCount() == 0){
			echo " - Couldn't find order in DB, must not be Shopify";
			continue;
		}
		$db_order = $stmt->fetch();
		log_echo_multi("Checking order ".$cc_order['reference']."... ");
		log_echo_multi(" - Line Items:");
		foreach($cc_order['lineItems'] as $lineItem){
			log_echo_multi("   - ".$lineItem['code']." x".$lineItem['qty']." [".$lineItem['name']."]");
		}
		if($cc_order['logisticsStatus'] != 9){
			if(!empty($db_order['cancelled_at'])){
				log_echo_multi(" - logisticsStatus is ".$cc_order['logisticsStatus'].", skipping cin7 id: ".$cc_order['id']);
				continue;
			}
		}
		// TODO: If it's only scent club default to SKCLUB
		if(empty($cc_order['freightDescription'])){
			log_echo_multi(" - Order doesn't have freight description, skipping and alerting");
			print_r(send_alert($db, 15, "Order is being held because it doesn't have a freight description: https://go.cin7.com/Cloud/TransactionEntry/TransactionEntry.aspx?idCustomerAppsLink=800541&OrderId=".$cc_order['id'], 'Skylar Alert - No Freight Description on Order', ['tim@skylar.com', 'kristin@skylar.com'], [
				'smother_window' => date('Y-m-d H:i:s', strtotime('-48 hours')),
			]));
			continue;
		}
		if(!empty($db_order['cancelled_at'])){
			log_echo_multi("Order cancelled in Shopify, skipping cin7 id: ".$cc_order['id']);
			continue;
		}
		if(strpos($db_order['tags'], 'HOLD:') !== false){
			log_echo_multi("Order held in Shopify, skipping");
			continue;
		}
		$cc_order['branchId'] = BranchService::calc_branch_id($db, $cc_order);

		switch($cc_order['branchId']){
			default: break;
			case -1:
				log_echo_multi(" - Order doesn't have zip code, skipping and alerting");
				print_r(send_alert($db, 13, "Order is being held because it doesn't have a shipping address zip: https://go.cin7.com/Cloud/TransactionEntry/TransactionEntry.aspx?idCustomerAppsLink=800541&OrderId=".$cc_order['id'], 'Skylar Alert - No Zip on Order', ['kristin@skylar.com'], [
					'smother_window' => date('Y-m-d H:i:s', strtotime('-48 hours')),
					'last_log' => end($log),
				]));
				continue 2; // Switch statements are treated as loops
			case -2:
				log_echo_multi(" - No branch can fulfill this order, skipping and alerting");
				print_r(send_alert($db, 14, "Order is being held because it doesn't have stock available: https://go.cin7.com/Cloud/TransactionEntry/TransactionEntry.aspx?idCustomerAppsLink=800541&OrderId=".$cc_order['id'], 'Skylar Alert - No Stock Available', ['tim@skylar.com', 'kristin@skylar.com'], [
					'smother_window' => date('Y-m-d H:i:s', strtotime('-48 hours')),
					'last_log' => end($log),
				]));
				continue 2; // Switch statements are treated as loops
			case -3:
				log_echo_multi(" - No branch can fulfill this order, skipping and alerting");
				print_r(send_alert($db, 17, "Order is being held because no branch is available: https://go.cin7.com/Cloud/TransactionEntry/TransactionEntry.aspx?idCustomerAppsLink=800541&OrderId=".$cc_order['id'], 'Skylar Alert - Cannot Fulfill Order', ['tim@skylar.com', 'kristin@skylar.com'], [
					'smother_window' => date('Y-m-d H:i:s', strtotime('-48 hours')),
					'last_log' => end($log),
				]));
				continue 2; // Switch statements are treated as loops
		}
		// Salt air sample add
		$add_salt_air = false;
		if(count(array_intersect([
			'70221408-100', // Scent experience
			'10450506-101', // Sample Palette
		], array_column($cc_order['lineItems'], 'code'))) > 0){
			$add_salt_air = true;
		} else{
			$stmt_get_prev_order->execute([
				'email' => $cc_order['email'],
				'id' => $db_order['id'],
			]);
			if($stmt_get_prev_order->rowCount() == 0){
				$add_salt_air = true;
			}
		}
		if($add_salt_air && !empty(array_intersect(array_column($cc_order['lineItems'], 'code'), [
			'99238701-112', // Peel
			'10450504-112', // full size
			'10450505-112', // rollie
		]))){
			$add_salt_air = false;
		}
		if($add_salt_air){
			$stmt_get_order_skus->execute([
				$db_order['id'],
			]);
			$order_skus = $stmt_get_order_skus->fetchAll(PDO::FETCH_COLUMN);
			$missing_skus = array_diff($order_skus, array_column($cc_order['lineItems'], 'code'));
			if(count($missing_skus) > 0){
				log_echo_multi("Missing sku ".implode(',', $missing_skus).", sending alert");
				print_r($cc_order['lineItems']);
				print_r(send_alert($db, 16, "Order is being held because it is missing line items that are in shopify (".implode(',', $missing_skus)."): https://go.cin7.com/Cloud/TransactionEntry/TransactionEntry.aspx?idCustomerAppsLink=800541&OrderId=" . $cc_order['id'] . " , https://skylar.com/admin/orders/" . $db_order['shopify_id'], 'Skylar Alert - Missing Line Items'));
				continue;
			}
			log_echo_multi(" - Adding salt air to order...");
			add_salt_air_sample($cc_order);
			$tags = explode(', ', $db_order['tags']);
			$tags[] = 'Added Salt Air Sample';
			$res = $sc->put('orders/'.$db_order['shopify_id'].'.json', ['order' => ['tags' => implode(', ', array_unique($tags))]]);
		} else {
			unset($cc_order['lineItems']);
		}

		$cc_order['logisticsStatus'] = 1;
		$updates[] = $cc_order;
		log_echo_multi(" - Added to update queue w/ branch id ".$cc_order['branchId']." [".count($updates)."]");
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

function add_salt_air_sample(&$cc_order){
	// Make sure it doesn't already have salt air
	$sort = array_reduce($cc_order['lineItems'], function($carry, $item){
		return $item['sort'] > $carry ? $item['sort'] : $carry;
	}, 1);
	$sort++;
	$cc_order['lineItems'][] = [
		'transactionId' => $cc_order['id'],
		'productId' => 1494,
		'productOptionId' => 1495,
		'sort' => $sort,
		'code' => '99238701-112',
		'name' => 'Scent Peel Back Salt Air',
		'qty' => 1,
		'styleCode' => '99238701-112',
		'lineComments' => 'Auto-added by API',
	];
	return $cc_order;
}

function log_echo_multi($line){
	global $log;
	if(empty($log)){
		$log = [];
	}
	// If it's not indented, create new non-sub line
	if($line[0] != ' '){
		$log[] = [];
	}
	$log[count($log)-1][] = $line;
	echo $line.PHP_EOL;
}