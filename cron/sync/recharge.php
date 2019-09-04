<?php
require_once(__DIR__.'/../../includes/config.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();

$interval = 5;
$page_size = 250;
$min_date = date('Y-m-d H:i:00P', time()-60*6);

// Customers
echo "Updating Customers".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $rc->get('/customers', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res['customers'] as $customer){
		echo insert_update_rc_customer($db, $customer, $sc).PHP_EOL;
	}
} while(count($res) >= $page_size);

// Addresses
echo "Updating Addresses".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $rc->get('/addresses', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res['addresses'] as $address){
		echo insert_update_rc_address($db, $address, $rc, $sc).PHP_EOL;
	}
} while(count($res) >= $page_size);

// Subscriptions
echo "Updating subscriptions".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $rc->get('/subscriptions', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res['subscriptions'] as $subscription){
		echo insert_update_rc_subscription($db, $subscription, $rc, $sc).PHP_EOL;
	}
} while(count($res) >= $page_size);

print_r($argv);
if(
	(date('G') == 12 && date('i') < 5)
	|| (!empty($argv) && !empty($argv[1]) && $argv[1] == 'all')
){
	echo "Updating subscriptions with old charge dates".PHP_EOL;
	$stmt = $db->query("
SELECT * FROM rc_subscriptions
WHERE next_charge_scheduled_at <= '".date('Y-m-d')."'
AND next_charge_scheduled_at IS NOT NULL
AND cancelled_at IS NULL
AND deleted_at IS NULL
");
	$stmt_mark_deleted = $db->prepare("UPDATE rc_subscriptions SET deleted_at=:deleted_at WHERE recharge_id=:recharge_id");
	foreach($stmt->fetchAll() as $subscription){
		$res = $rc->get('/subscriptions/'.$subscription['recharge_id']);
		if(!empty($res['subscription'])){
			echo insert_update_rc_subscription($db, $res['subscription'], $rc, $sc).PHP_EOL;
		} else if($res['errors'] == 'Not Found') {
			echo "Marking ".$subscription['recharge_id'].' deleted'.PHP_EOL;
			$stmt_mark_deleted->execute([
				'deleted_at' => date('Y-m-d H:i:s'),
				'recharge_id' => $subscription['recharge_id'],
			]);
		}
	}
}
