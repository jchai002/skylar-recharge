<?php
require_once(__DIR__.'/../includes/config.php');

//$rc->debug = true;

$page = 0;
$scent = null;

$offset = 0;
if(time() <= offset_date_skip_weekend(strtotime(date('Y-m-01')))){
	$offset = -1;
}
echo "offset: $offset".PHP_EOL;

$start_date = date('Y-m-t', get_month_by_offset($offset));
$end_date = date('Y-m-01', get_month_by_offset(2+$offset));

$scent_info = sc_get_monthly_scent($db, get_month_by_offset($offset));
if(empty($scent_info)){
	die("No Live Monthly Scent!");
}

echo "Getting $start_date to $end_date".PHP_EOL;

$stmt = $db->query("SELECT o.shopify_id AS order_id, rca.recharge_id AS address_id FROM rc_addresses rca
LEFT JOIN rc_customers rcc ON rca.rc_customer_id=rcc.id
LEFT JOIN customers c ON rcc.customer_id=c.id
LEFT JOIN orders o ON c.id=o.customer_id
WHERE o.tags like '%Scent Club Blackout%'
AND o.created_at >= '2019-12-01'
GROUP BY rca.id");

foreach($stmt->fetchAll() as $row){
	echo "Checking address id ".$row['address_id']." order id ".$row['order_id']."... ";
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $end_date,
		'status' => 'QUEUED',
		'limit' => 250,
        'address_id' => $row['address_id'],
	]);
	if(empty($res['charges'])){
		echo "Ok".PHP_EOL;
		continue;
	}
	echo "Found charge ";
	foreach($res['charges'] as $charge){
		foreach($charge['line_items'] as $line_item){
			if(is_scent_club_month(get_product($db, $line_item['shopify_product_id']))){
				echo "Removing onetime ".$line_item['subscription_id']."... ".PHP_EOL;
				$rc->delete('/onetimes/'.$line_item['subscription_id']);
			}
		}
	}
	echo "Recalculating... ";
	echo sc_calculate_next_charge_date($db, $rc, $row['address_id']).PHP_EOL;
}