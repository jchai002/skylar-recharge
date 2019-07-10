<?php
header('Content-Type: application/json');

global $db, $rc;

if(empty($_REQUEST['subscription_id'])){
	die(json_encode([
		'success' => false,
		'errors' => [['msg'=>'Fields Missing']],
	]));
}

$subscription_id = intval($_REQUEST['subscription_id']);

$res = $rc->get('/charges', ['subscription_id' => $subscription_id]);
if(empty($res['charges'])){
	die(json_encode([
		'success' => false,
		'error' => 'Couldn\'t find charge. Please contact support cancel.',
		'res' => $res,
	]));
}

$charge = $res['charges'][0];
$date = new DateTime("now", new DateTimeZone('UTC') );

$res = $rc->post('/charges/'.$charge['id'].'/change_next_charge_date', [
	'next_charge_date' => $date->format('Y-m-d'),
]);

foreach($charge['line_items'] as $line_item){
	if(!is_ac_followup_lineitem($line_item)){
		continue;
	}
	$properties = $line_item['properties'];
	$properties['_ac_pushed_back'] = 1;
	$res = $rc->put('/onetimes/'.$subscription_id, [
		'properties' => $properties,
		'price' => $line_item['quantity']*78 - 10, // Will need to update this if we test price
	]);
}

echo json_encode([
	'success' => true,
	'res' => $res,
]);