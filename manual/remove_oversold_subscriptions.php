<?php
require_once(__DIR__.'/../includes/config.php');

$subscription_ids = [
	79750029,
	79750101,
	79750230,
	79750244,
	79750283,
	79750472,
	79750494,
	79750700,
	79750709,
	79750784,
	79750939,
	79751116,
	79751141,
	79751278,
	79751346,
	79752432,
	79752986,
	79753052,
	79753176,
	79754016,
	79754052,
	79754320,
	79754492,
	80490457,
	80499119,
	80501826,
	80504107,
	80504392,
	80507611,
	80542929,
	80544586,
];

foreach($subscription_ids as $subscription_id){
	$res = $rc->get("subscriptions/$subscription_id");
	if(empty($res['subscription'])){
		echo "Subscription $subscription_id not found".PHP_EOL;
		continue;
	}
	$subscription = $res['subscription'];
	$address_id = $res['subscription']['address_id'];

	$res = $rc->get("charges", [
		'subscription_id' => $subscription_id,
	]);
	if(empty($res['charges'])){
		echo "charge for sub $subscription_id not found".PHP_EOL;
		continue;
	}
	$charge = $res['charges'][0];

	// Add discount to address
	$res = $rc->post('charges/'.$charge['id'].'/apply_discount', [
		'discount_id' => 28257412,
	]);
	print_r($res);
	print_r($rc->delete("onetimes/$subscription_id"));
}