<?php
require_once('../includes/config.php');
require_once('../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$hooks = $rc->get("/webhooks");
$hooks = $hooks['hooks'];

$needed_hooks = [
	'topic' => 'charge/created',
	'address' => 'https://ec2production.skylar.com/hooks_rc/charge_created.php',
];

foreach($needed_hooks as $needed_hook){
	foreach($hooks as $hook){
		if($hook['address'] == $needed_hook['address'] && $hook['topic'] == $needed_hook['topic']){
			continue 2;
		}
	}
	echo "Need to add: ";
	var_dump($needed_hook);
}