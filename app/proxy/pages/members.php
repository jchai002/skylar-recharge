<?php
header('Content-Type: application/liquid');
?>
{% assign portal_page = 'portal' %}
{% assign recommended_product_handles = 'sample-palette|isle|coral' | split: '|' %}
{% include 'sc-member-portal' %}