<?php
require_once(__DIR__.'/../includes/config.php');

$fh = fopen(__DIR__."/order_id_numbers.csv", 'r');

$titles = array_map('strtolower',fgetcsv($fh));

$order = [];
$order_id = 0;

$stmt = $db->prepare("UPDATE orders SET number=? WHERE shopify_id=?");

$index = 0;
$start_line = 165760;
$total = 254267;
$start_time = time();
while($row = fgetcsv($fh)){
	if($index > 0 && $index % 100 == 0){
		echo "$index of ".$total." ".round($index/$total*100)."%".PHP_EOL;
	}
	$index++;

	if($index < $start_line){
		continue;
	}

	$stmt->execute([$row[1], $row[0]]);

	echo $row[1]." ".$row['0'].PHP_EOL;
}
