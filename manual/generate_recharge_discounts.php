<?php
require_once(__DIR__.'/../includes/config.php');

$start_from = 2;
$num_to_generate = 500;
$discount_template_id = 21526658;
$prefix = "GS-50-";

$res = $rc->get('/discounts/'.$discount_template_id);
print_r($res);
if(empty($res['discount'])){
	die('Discount template not found');
}
$discount_template = $res['discount'];
unset($discount_template['id']);

mt_srand($discount_template_id);

// GENERATE CODES
$codes = [];
for($i = 0; $i < $num_to_generate; $i++){
	$code = $prefix.generate_discount_string();
	if($i < $start_from){
		continue;
	}
	$codes[] = [
		'code' => $code,
	];
}
$outstream = fopen("rc_discounts.csv", 'w');

foreach($codes as $index=>$code){
	$discount_template['code'] = $code['code'];
	$res = $rc->post('/discounts', $discount_template);
	$discount = $res['discount'];
	if($index === 0){
		$keys = array_keys($discount);
		fputcsv($outstream, $keys);
	}
	fputcsv($outstream, $discount);
}