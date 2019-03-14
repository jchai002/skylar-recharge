<?php
global $db, $rc;
$main_sub = sc_get_main_subscription($db, $rc, [
	'shopify_customer_id' => $_REQUEST['c'],
]);
if(empty($main_sub)){
	require('index.php');
} else {
sc_conditional_billing($rc, $_REQUEST['c']);
?>
{% assign portal_page = 'portal' %}
{% assign recommended_product_handles = 'isle|meadow|rollie:12235492425815' | split: '|' %}
{% include 'sc-member-portal' %}
<?php } ?>