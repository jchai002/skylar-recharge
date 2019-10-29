<?php
require_once(__DIR__.'/../includes/config.php');

$convert_variant_ids = [
19519443370071 => 19787922014295,
19634485657687 => 19787922014295,
19706587086935 => 19787922014295,
19767325196375 => 19787922014295,
19787926241367 => 19787922014295,
19787927289943 => 19787922014295,
19788263718999 => 19787922014295,
19788263751767 => 19787922014295,
19788263784535 => 19787922014295,
28014656192599 => 19787922014295,
28014656225367 => 19787922014295,
28014656258135 => 19787922014295,
28014656290903 => 19787922014295,
28014656454743 => 19787922014295,
28014656487511 => 19787922014295,
28015643492439 => 19787922014295,
28015643525207 => 19787922014295,
28015643557975 => 19787922014295,
28023085301847 => 19787922014295,
28023085367383 => 19787922014295,
28023085334615 => 19787922014295,
28029938237527 => 19787922014295,
28029938204759 => 19787922014295,
28029938303063 => 19787922014295,
28369871274071 => 19787922014295,
28369871208535 => 19787922014295,
28369871241303 => 19787922014295,
28369872289879 => 19787922014295,
28369872322647 => 19787922014295,
28369872355415 => 19787922014295,
29209036128343 => 19787922014295,
29209036488791 => 19787922014295,
29209036750935 => 19787922014295,
29209037570135 => 19787922014295,
29209037602903 => 19787922014295,
29209037701207 => 19787922014295,
29424627384407 => 19787922014295,
29424627417175 => 19787922014295,
29424627482711 => 19787922014295,
29523350028375 => 19787922014295,
29523350061143 => 19787922014295,
29523350192215 => 19787922014295,
29523350257751 => 19787922014295,
29523352879191 => 19787922014295,
30235443593303 => 19787922014295,
30235443691607 => 19787922014295,
30235443789911 => 19787922014295,
30235451785303 => 19787922014295,
30235451916375 => 19787922014295,
30235451981911 => 19787922014295,
30725253333079 => 19787922014295,
30725257166935 => 19787922014295,
30994680152151 => 19787922014295,
30994680938583 => 19787922014295,
];

foreach($convert_variant_ids as $old_variant_id => $new_variant_id){
	echo "Checking variant ID ".$old_variant_id.PHP_EOL;
	// Load all active subscriptions
	$subscriptions = [];
	$onetimes = [];
	$page_size = 250;
	$page = 0;
	do {
		$page++;
		$res = $rc->get('/subscriptions', [
			'status' => 'ACTIVE',
			'limit' => $page_size,
			'page' => $page,
			'shopify_variant_id' => $old_variant_id,
		]);
		foreach($res['subscriptions'] as $subscription){
			// Sort them by address id
			$subscriptions[] = $subscription;
		}
	} while(count($res) >= $page_size);

	foreach($subscriptions as $subscription){
		echo "Updating ".$subscription['id']." from ".$subscription['shopify_variant_id']." to ".$new_variant_id.PHP_EOL;
		$res = $rc->put('/subscriptions/'.$subscription['id'], [
			'shopify_variant_id' => $new_variant_id,
		]);
		if(empty($res['subscription'])){
			echo "Error ".print_r($res);
			die();
		}
		insert_update_rc_subscription($db, $res['subscription'], $rc, $sc);
	}

	$onetimes = [];
	$page = 0;
	do {
		$page++;
		$res = $rc->get('/onetimes', [
			'limit' => $page_size,
			'page' => $page,
			'shopify_variant_id' => $old_variant_id,
		]);
		foreach($res['onetimes'] as $onetime){
			// Sort them by address id
			$onetimes[] = $onetime;
		}
	} while(count($res) >= $page_size);

	foreach($onetimes as $onetime){
		echo "Updating ".$onetime['id']." from ".$onetime['shopify_variant_id']." to ".$new_variant_id.PHP_EOL;
		$res = $rc->put('/onetimes/'.$onetime['id'], [
			'shopify_variant_id' => $new_variant_id,
		]);
		if(empty($res['onetime'])){
			echo "Error ".print_r($res);
			die();
		}
		insert_update_rc_subscription($db, $res['onetime'], $rc, $sc);
	}
}