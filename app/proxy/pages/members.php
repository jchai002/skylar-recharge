<?php
global $rc, $db;
sc_conditional_billing($rc, $_REQUEST['c']);
$recommended_products = sc_get_profile_products(sc_get_profile_data($db, $rc, $_REQUEST['c']));
$single_reco_products = [];
mt_srand($_REQUEST['c']);
foreach($recommended_products as $recommended_product){
	$parts = explode('|',$recommended_product);
	$key = mt_rand(0,count($parts)-1);
	$single_reco_products[] = $parts[$key];
}
?>
{% assign portal_page = 'portal' %}
{% assign recommended_product_handles = '<?=implode('|',$single_reco_products)?>' | split: '|' %}
{% include 'sc-member-portal' %}