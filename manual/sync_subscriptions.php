<?php

require_once(__DIR__.'/../includes/config.php');

$page_size = 250;
$page = 0;
do {
	$page++;
	$res = $rc->get("/onetimes", [
		'page' => $page,
		'limit' => $page_size,
	]);

	foreach($res['onetimes'] as $subscription){
		echo insert_update_rc_subscription($db, $subscription, $rc, $sc)." ".$subscription['id'].PHP_EOL;
	}
} while(count($res['onetimes']) >= $page_size);