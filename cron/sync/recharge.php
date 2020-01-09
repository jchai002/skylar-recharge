<?php
require_once(__DIR__.'/../../includes/config.php');

$rc = new RechargeClient();
$sc = new ShopifyClient();

$interval = 5;
$page_size = 250;
$min_date = date('Y-m-d H:i:00P', time()-60*6);

// Discounts
echo "Updating Discounts".PHP_EOL;
$page = 0;
do {
	$page++;
	$res = $rc->get('/discounts', [
		'updated_at_min' => $min_date,
		'limit' => $page_size,
		'page' => $page,
	]);
	foreach($res['discounts'] as $discount){
		echo insert_update_rc_discount($db, $discount).PHP_EOL;
	}
} while(count($res) >= $page_size);

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

// Hourly sync
if(
	(date('i') < 5)
	|| (!empty($argv) && !empty($argv[1]) && $argv[1] == 'all')
){
	$sync_start = time();
	echo "Pulling all subscriptions".PHP_EOL;
	$page = 0;
	do {
		$page++;
		$res = $rc->get('/subscriptions', [
			'limit' => $page_size,
			'page' => $page,
		]);
		echo count($res['subscriptions'])." subscriptions on this page".PHP_EOL;
		foreach($res['subscriptions'] as $subscription){
			echo insert_update_rc_subscription($db, $subscription, $rc, $sc).PHP_EOL;
		}
	} while(count($res['subscriptions']) >= $page_size);

	echo "Pulling all onetimes".PHP_EOL;
	$page = 0;
	do {
		$page++;
		$res = $rc->get('/onetimes', [
			'limit' => $page_size,
			'page' => $page,
		]);
		echo count($res['onetimes'])." subscriptions on this page".PHP_EOL;
		foreach($res['onetimes'] as $onetime){
			echo insert_update_rc_subscription($db, $onetime, $rc, $sc).PHP_EOL;
		}
	} while(count($res['onetimes']) >= $page_size);

	echo "Updating subscriptions with old charge dates".PHP_EOL;
	$stmt = $db->query("
SELECT * FROM rc_subscriptions
WHERE (synced_at < '".date('Y-m-d H:i:s', $sync_start)."' OR synced_at IS NULL)
AND next_charge_scheduled_at IS NOT NULL
AND cancelled_at IS NULL
AND deleted_at IS NULL
");
	echo "Updating ".$stmt->rowCount()." unsynced subs".PHP_EOL;
	$stmt_mark_deleted = $db->prepare("UPDATE rc_subscriptions SET deleted_at=:deleted_at WHERE recharge_id=:recharge_id");
	foreach($stmt->fetchAll() as $subscription){
		if(empty($subscription['recharge_id'])){
			continue;
		}
		$res = $rc->get('/subscriptions/'.$subscription['recharge_id']);
		if(!empty($res['subscription'])){
			echo insert_update_rc_subscription($db, $res['subscription'], $rc, $sc) . PHP_EOL;
		} else if(empty($res['errors'])){
			print_r($res);
		} else if($res['errors'] == 'Not Found') {
			echo "Marking ".$subscription['recharge_id'].' deleted'.PHP_EOL;
			$stmt_mark_deleted->execute([
				'deleted_at' => date('Y-m-d H:i:s'),
				'recharge_id' => $subscription['recharge_id'],
			]);
		}
	}
}
