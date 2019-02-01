<?php
header('Content-Type: application/liquid');
?>
{% assign recommended_product_handles = 'sample-palette|scent-duo|scent-experience' | split: '|' %}
{% include 'sc-member-portal' %}