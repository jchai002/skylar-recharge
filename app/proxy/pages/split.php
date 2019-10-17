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

$get_vars = $_GET;
unset($get_vars['id']);

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
	$url = $variant['url'];
	if(!empty($get_vars)){
		$url .= strpos($variant['url'], '?') === false ? '?' : '&';
		$url .= http_build_query($get_vars);
	}
	header("Location: $url");
	die();
}