<?php
header('Content-Type: application/liquid');
?>
{% assign recommended_product_handles = 'sample-palette|isle|coral' | split: '|' %}
{% include 'sc-member-portal' %}