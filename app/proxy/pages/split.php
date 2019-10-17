<?php
global $db;

$split_tests = [
	[
		'variants' => [
			[
				'url' => 'https://try.skylar.com/sample-palette-a',
				'weight' => '50'
			],
			[
				'url' => 'https://skylar.com/pages/sample-palette-a',
				'weight' => '50'
			],
		]
	]
];

if(empty($split_tests[$_REQUEST['id']])){
	header("Location: /");
	die();
}

$test = $split_tests[$_REQUEST['id']];
$total_weight = array_sum(array_column($test['variants'], 'weight'));
$weight = rand(0, $total_weight);

foreach($test['variants'] as $variant){
	$weight -= $variant['weight'];
	if($weight > 0){
		continue;
	}
	header("Location: ".$variant['url']);
	die();
}