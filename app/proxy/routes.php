<?php

$router = new Router();
$router->route('',function(){
	require_customer_id(function(){
		require('pages/index.php');
	});
	return true;
});
$router->route('/members/i', function() {
	require_customer_id(function(){
		require('pages/members.php');
	});
	return true;
});
$router->route('/subscriptions/i', function() {
	require_customer_id(function(){
		require('pages/subscriptions.php');
	});
	return true;
});

$router->route('/subscriptions\/swap/i', function() {
	require('ajax/swap.php');
	return true;
});

function require_customer_id($callback_if_true){
	$customer_id = !empty($_REQUEST['c']) ? $_REQUEST['c'] : 0;
	header('Content-Type: application/liquid');
	if(empty($customer_id)){
		echo "
{% layout 'scredirect' %}
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
	echo "{% if customer == nil %}
{% layout 'scredirect' %}
	<script>
		location.href = '/account/login?next='+location.pathname;
	</script>
	{% elsif customer.id != $customer_id %}
	<script>
		location.search = location.search.replace('c=".$customer_id."','c={{customer.id}}');
	</script>
	{% else %}";
	$callback_if_true();
	echo "{% endif %}";
	return true;
}