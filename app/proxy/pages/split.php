<?php
global $db;

$split_tests = [
	'1' => [
//		'experiment_id' => 'Y7uryreySJS5Fo6Cvao36A',
		'variants' => [
			[
//				'url' => 'https://get.skylar.com/sample-palette-a',
				'url' => 'https://skylar.com/pages/sample-palette-a',
				'weight' => '50'
			],
			[
				'url' => 'https://skylar.com/pages/sample-palette-a',
				'weight' => '100'
			],
		]
	],
];

$split_tests['holiday'] = [
	'variants' => [
		[
			'url' => 'https://skylar.com/collections/great-gifts',
			'weight' => '30'
		],
		[
			'url' => 'https://skylar.com',
			'weight' => '60'
		],
		[
			'url' => 'https://skylar.com/pages/cybermonday',
			'weight' => '10'
		],
	]
];


$test_id = $test_id ?? array_key_first($split_tests);
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
if(!empty($test['experiment_id'])){
	$get_vars['exp_id'] = $test['experiment_id'];
}

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