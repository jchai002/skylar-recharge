<?php

$tests_to_run = 10000;

$test = [
	'variants' => [
		['weight' => 50],
		['weight' => 50],
	]
];

$results = [];
for($i = 0; $i < $tests_to_run; $i++){
	$total_weight = array_sum(array_column($test['variants'], 'weight'));
	$weight = rand(1, $total_weight);
	foreach($test['variants'] as $index => $variant){
		$weight -= $variant['weight'];
		if($weight > 0){
			continue;
		}
		if(empty($results[$index])){
			$results[$index] = 0;
		}
		$results[$index]++;
		break;
	}
}

print_r($results);