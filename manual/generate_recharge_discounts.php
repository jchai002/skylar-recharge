<?php
require_once(__DIR__.'/../includes/config.php');

$start_from = 0;
$num_to_generate = 30;
$discount_template_id = 22178881;
$prefix = "PR-SC-10-";

$res = $rc->get('/discounts/'.$discount_template_id);
print_r($res);
if(empty($res['discount'])){
	die('Discount template not found');
}
$discount_template = $res['discount'];
foreach([
	'id', 'created_at', 'updated_at', 'times_used',
] as $remove_key){
	unset($discount_template[$remove_key]);
}
foreach([
	'applies_to', 'applies_to_id', 'applies_to_product_type', 'applies_to_resource', 'ends_at', 'starts_at', 'once_per_customer', 'status', 'usage_limit', 'prerequisite_subtotal_min', 'duration_usage_limit',
] as $remove_key){
	if(array_key_exists($remove_key, $discount_template) && $discount_template[$remove_key] === null){
		unset($discount_template[$remove_key]);
	}
}
if($prefix == 'RT-20-'){
	unset($discount_template['applies_to_product_type']);
}

mt_srand($discount_template_id);

// GENERATE CODES
$codes = [];
for($i = 0; $i < $num_to_generate; $i++){
	$code = $prefix.generate_discount_string();
	if($i < $start_from){
		continue;
	}
	$codes[] = $code;
}
$outstream = fopen("rc_discounts.csv", 'w');

foreach($codes as $index=>$code){
	$discount_template['code'] = $code;
	$res = $rc->post('/discounts', $discount_template);
	if(empty($res['discount'])){
		print_r($res);
		die("exiting at index $index code $code".PHP_EOL);
	}
	$discount = $res['discount'];
	echo "Created ".$discount['code'].PHP_EOL;
	if($index === 0){
		$keys = array_keys($discount);
		fputcsv($outstream, $keys);
	}
	fputcsv($outstream, $discount);
}