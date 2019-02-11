<?php

require_once dirname(__FILE__).'/../../vendor/autoload.php';
require_once dirname(__FILE__).'/routes.php';
$path = str_replace('/app/proxy/', '', parse_url($_SERVER['REQUEST_URI'])['path']);
$res = $router->execute($path);
if(!$res){
	echo $path." Not Found";
}

// Functionality Needed

// Different if order is already created or not (prepaid)
function substitute_product($order_id){
	//
}

function generate_subscription_schedule($rc_customer_id, $duration=12){
	// get prepaid/scheduled orders, combine with:
	// iterate over each month of duration
		// iterate over subscription and check if it will drop in that month, checking expire_after_specific_number_of_charges
}