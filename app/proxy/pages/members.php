<?php
global $rc;
sc_conditional_billing($rc, $_REQUEST['c']);
?>
{% assign portal_page = 'portal' %}
{% assign recommended_product_handles = 'sample-palette|isle|coral' | split: '|' %}
{% include 'sc-member-portal' %}