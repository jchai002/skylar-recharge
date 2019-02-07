<?php

require_once dirname(__FILE__).'/../../vendor/autoload.php';

$router = new Router();

$router->route('/members/i', function() {
	if(!require_customer_id($_REQUEST['c'])){
		return true;
	}
	require('pages/members.php');
	return true;
});
$router->route('/subscriptions/i', function() {
	if(!require_customer_id($_REQUEST['c'])){
		return true;
	}
	require('pages/subscriptions.php');
	return true;
});

$path = str_replace('/app/proxy/', '', parse_url($_SERVER['REQUEST_URI'])['path']);

$res = $router->execute($path);

if(!$res){
	echo "Page Not Found";
}

// Functionality Needed

function require_customer_id($customer_id = 0){
	header('Content-Type: application/liquid');
	if(empty($customer_id)){
		echo "
{% layout 'sc-redirect' %}
{% if customer == nil %}
<script>
    location.href = '/account/login?next='+location.pathname;
</script>
{% else %}
<script>
    location.search = (location.search.length < 1 ? '?' : location.search+'&') + 'c={{ customer.id }}';
</script>
{% endif %}";
		return false;
	}
	return true;
}

// Different if order is already created or not (prepaid)
function substitute_product($order_id){
	//
}

function generate_subscription_schedule($rc_customer_id, $duration=12){
	// get prepaid/scheduled orders, combine with:
	// iterate over each month of duration
		// iterate over subscription and check if it will drop in that month, checking expire_after_specific_number_of_charges
}