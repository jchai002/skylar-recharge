<?php
global $rc;
sc_conditional_billing($rc, $_REQUEST['c']);
?>
{% assign portal_page = 'portal' %}
{% assign recommended_product_handles = 'isle|meadow|capri' | split: '|' %}
{% include 'sc-member-portal' %}