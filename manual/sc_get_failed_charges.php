<?php
require_once(__DIR__.'/../includes/config.php');

$failed_charges = [];

$page = 0;
do {
	$page++;
	$res = $rc->get('/charges', [
		'status' => 'ERROR',
		'date' => date('Y-m-d', strtotime('yesterday')),
		'page' => $page,
		'limit' => 250,
	]);
	foreach($res['charges'] as $charge){
		foreach($charge['line_items'] as $line_item){
			if($line_item['sku'] == 857243008252 || $line_item['title'] == 'Scent Club Swap-in'){
				$failed_charges[] = $charge;
			}
		}
	}
} while(count($res['charges']) == 250);

//print_r($failed_charges);

echo count($failed_charges).PHP_EOL;