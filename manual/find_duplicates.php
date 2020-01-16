<?php

require_once('../includes/config.php');

$page = 1;
$remove = 1;
do {
	echo "Starting page $page" . PHP_EOL;
	$sub_res = $rc->get('/addresses', ['limit' => 10, 'page' => $page, 'updated_at_min'=>'2018-09-05T00:00:00']);

	foreach($sub_res['addresses'] as $index=>$address){
		echo "Address #".$index.PHP_EOL;
		$res = $rc->get('/subscriptions', ['address_id' => $address['id'], 'limit' => 250]);
		$sub_variants = [];
		foreach($res['subscriptions'] as $subscription){
			if(in_array($subscription['shopify_variant_id'], $sub_variants)){
				if($remove > 0){
//					$remove--;
					echo "Removing subscription ".$subscription['id']." from address ".$address['id'].PHP_EOL;
					$rc->delete('/subscriptions/'.$subscription['id']);
					continue;
				}
				var_dump($res['subscriptions']);
				echo "Found duplicate! Address ID: ".$address['id'].PHP_EOL;
				die();
			}
			$sub_variants[] = $subscription['shopify_variant_id'];
		}
		sleep(2);
	}

	$page++;
	sleep(5);
} while(count($sub_res['addresses']) >= 10);