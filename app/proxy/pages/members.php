<?php
global $rc;
sc_conditional_billing($rc, $_REQUEST['c']);
$recommended_products = sc_get_profile_products(sc_get_profile_data($db, $rc, $_REQUEST['c']));
?>
{% assign portal_page = 'portal' %}
{% assign recommended_product_handles = '<?=implode('|',$recommended_products)?>' | split: '|' %}
{% include 'sc-member-portal' %}