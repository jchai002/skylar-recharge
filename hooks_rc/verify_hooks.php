<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$hooks = $rc->get("/webhooks");
$hooks = $hooks['webhooks'];
var_dump($hooks);

$needed_hooks = [
	[
		'topic' => 'charge/created',
		'address' => 'https://ec2production.skylar.com/hooks_rc/charge_created.php',
	],
	[
		'topic' => 'charge/paid',
		'address' => 'https://ec2production.skylar.com/hooks_rc/charge_paid.php',
	],
	[
		'topic' => 'address/updated',
		'address' => 'https://ec2production.skylar.com/hooks_rc/address_updated.php',
	],
	/*
	[
		'topic' => 'subscription/created',
		'address' => 'https://ec2production.skylar.com/hooks_rc/subscription_all.php',
	],
	[
		'topic' => 'subscription/updated',
		'address' => 'https://ec2production.skylar.com/hooks_rc/subscription_all.php',
	],
	[
		'topic' => 'subscription/activated',
		'address' => 'https://ec2production.skylar.com/hooks_rc/subscription_all.php',
	],
	[
		'topic' => 'subscription/cancelled',
		'address' => 'https://ec2production.skylar.com/hooks_rc/subscription_all.php',
	],
	*/
	[
		'topic' => 'subscription/created',
		'address' => 'https://ec2production.skylar.com/hooks_rc/subscription_created.php',
	],
	[
		'topic' => 'subscription/activated',
		'address' => 'https://ec2production.skylar.com/hooks_rc/subscription_created.php',
	],
	[
		'topic' => 'subscription/cancelled',
		'address' => 'https://ec2production.skylar.com/hooks_rc/subscription_cancelled.php',
	],
];

foreach($needed_hooks as $needed_hook){
	foreach($hooks as $hook){
		if($hook['address'] == $needed_hook['address'] && $hook['topic'] == $needed_hook['topic']){
			continue 2;
		}
	}
	echo "Need ".$needed_hook['topic'].' : '.$needed_hook['address'].PHP_EOL;
//	$res = $rc->post('/webhooks', $needed_hook);
//	echo "Created: "; var_dump($res);
}