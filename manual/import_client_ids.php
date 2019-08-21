<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once(__DIR__.'/../includes/config.php');

$stmt_insert_client = $db->prepare("INSERT INTO ga_clients (ga_id) VALUES (:ga_id) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
$stmt_insert_client_date = $db->prepare("INSERT INTO ga_client_dates (ga_client_id, date) VALUES (:ga_client_id, :date)");

$dir = new DirectoryIterator(dirname(__DIR__.'/ga_exports'));
foreach ($dir as $fileinfo) {
	if($fileinfo->isDot()) {
		continue;
	}
	if(!($fh = fopen($fileinfo->getFilename(), 'r'))){
		echo "Error opening file: ".$fileinfo->getFilename();
		continue;
	}
	$i = 0;
	while(($row = fgetcsv($fh)) !== false){
		$i++;
		if($i == 4){
			// # 20190814-20190820
			$dates = explode('-',trim(str_replace('#','', $row[0])));
			$start_time = strtotime($dates[0]);
			$end_time = strtotime($dates[1]);
		}
		if($i <= 7){
			continue;
		}
		$stmt_insert_client->execute([
			'ga_id' => $row[0],
		]);
		$client_id = $db->lastInsertId();
		$insert_time = $start_time;
		do {
			$stmt_insert_client_date->execute([
				'ga_client_id' => $client_id,
				'date' => date('Y-m-d', $insert_time),
			]);
			$insert_time += 60*60*24;
		} while($insert_time <= $end_time);
	}
}