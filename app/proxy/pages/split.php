<?php
global $db;

$test_id = $test_id ?? 1;
$test_id--;

$split_tests = [
	[
		'experiment_id' => 'P2nm02EvRI2_P53yPBkMcA',
		'variants' => [
			[
				'url' => 'https://get.skylar.com/sample-palette-a',
				'weight' => '50'
			],
			[
				'url' => 'https://skylar.com/pages/sample-palette-a',
				'weight' => '100'
			],
		]
	]
];

$get_vars = $_GET;
// Unset shopify vars
foreach([
	'shop', 'path_prefix', 'timestamp', 'signature'
] as $unset_key){
	if(array_key_exists($unset_key, $get_vars)){
		unset($get_vars[$unset_key]);
	}
}


if(empty($split_tests[$test_id])){
//	var_dump($split_tests);
//	var_dump($test_id);
	header("Location: /");
	die();
}

$test = $split_tests[$test_id];
$total_weight = array_sum(array_column($test['variants'], 'weight'));
$weight = rand(1, $total_weight);
$get_vars['exp_id'] = $test['experiment_id'];

if(!empty($_REQUEST['test'])){
	echo "$weight of $total_weight".PHP_EOL;
}

foreach($test['variants'] as $index => $variant){
	$weight -= $variant['weight'];
	if($weight > 0){
		continue;
	}
	if(!empty($_REQUEST['test'])){
		echo "Variant: ".($index+1);
		die();
	}
	$url = $variant['url'];
	if(!empty($get_vars)){
		$url .= strpos($variant['url'], '?') === false ? '?' : '&';
		$url .= http_build_query($get_vars);
	}
	header("Location: $url");
	die();
}