<?php
require_once(__DIR__.'/../includes/config.php');

$start_from = 500;
$num_to_generate = 500;
//$model_after = "GET20-*****";
// For now just use this:
$price_rule_template = 612618928215;
$prefix = "RS-20-";
$batch_mode = true;

mt_srand($price_rule_template);

// GENERATE CODES
$codes = [];
for($i = 0; $i < $start_from+$num_to_generate; $i++){
	$code = $prefix.generate_discount_string();
	if($i < $start_from){
		continue;
	}
	$codes[] = [
		'code' => $code,
	];
}
print_r($codes);

if($batch_mode){
	$batch_size = 100;
	$batch_num = 0;
	$outstream = fopen("discounts.csv", 'w');
	while(count($codes) - $batch_size*$batch_num > 0){
		$batch_num++;
		$batch_codes = array_slice($codes, $batch_size*($batch_num-1), $batch_size);
		echo count($batch_codes).PHP_EOL;
		$batch = $sc->post('/admin/api/2019-10/price_rules/'.$price_rule_template."/batch.json", [
			'discount_codes' => $batch_codes,
		]);
		if($i == 0){
			fputcsv($outstream, array_keys($batch[0]));
		}
		foreach($batch as $batch_code){
			fputcsv($outstream, $batch_code);
		}

		print_r($batch);
	}
}