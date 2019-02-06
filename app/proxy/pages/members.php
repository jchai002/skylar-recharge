<?php
header('Content-Type: application/liquid');
?>
{% if customer %}
{% assign recommended_product_handles = 'sample-palette|scent-duo|scent-experience' | split: '|' %}
{% include 'sc-member-portal' %}
{% else %}
{% layout 'sc-redirect' %}
<script>
	location.href = "/account/login?next="+location.pathname;
</script>
{% endif %}