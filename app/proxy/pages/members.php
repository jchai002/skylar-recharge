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

$stmt = $db->prepare("SELECT p.handle FROM orders o
LEFT JOIN order_line_items oli ON oli.order_id=o.id
LEFT JOIN variants v ON v.id=oli.variant_id
LEFT JOIN products p ON p.id=v.product_id
LEFT JOIN fulfillments f ON oli.fulfillment_id=f.id
WHERE c.shopify_id = ?
AND p.type = 'Scent Club Month'
AND f.delivered_at IS NOT NULL
GROUP BY handle;");
$stmt->execute([$_REQUEST['c']]);
$sc_received_handles = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
{% assign portal_page = 'portal' %}
{% assign recommended_product_handles = '<?=implode('|',$single_reco_products)?>' | split: '|' %}
{% assign sc_received_handles = '<?=implode('|',$sc_received_handles)?>' | split: '|' %}
{% include 'sc-member-portal' %}