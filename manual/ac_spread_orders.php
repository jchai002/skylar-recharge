<?php
require_once(__DIR__.'/../includes/config.php');

$onetime_ids = [
	'49369383',
	'49397408',
	'49408367',
	'49438014',
];

$move_to_date = '2019-09-30';
$start_time = time();
foreach($onetime_ids as $index => $onetime_id){
	if($index > 0 && $index % 20 == 0){
		echo "$index of ".count($onetime_ids)." ".round($index/count($onetime_ids)*100)."%".PHP_EOL;
		echo $index/(time() - $start_time)." per second".PHP_EOL;
		echo round(($index/(time() - $start_time) * (count($onetime_ids)-$index))/60,2)."m remaining".PHP_EOL;
	}
	echo "Moving $onetime_id... ";
	$res = $rc->put('/onetimes/'.$onetime_id, [
		'next_charge_scheduled_at' => $move_to_date,
	]);
	if(!empty($res['error'])){
		echo "Error!".PHP_EOL;
		print_r($res);
		die();
	}
	echo $res['onetime']['next_charge_scheduled_at']." ".insert_update_rc_subscription($db, $res['onetime'], $rc, $sc).PHP_EOL;
}