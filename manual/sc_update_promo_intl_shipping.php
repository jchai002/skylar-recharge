<?php
require_once(__DIR__.'/../includes/config.php');

$f = fopen(__DIR__.'/promos.csv', 'r');

$headers = fgetcsv($f);

$rownum = 0;
while($row = fgetcsv($f)) {
    $rownum++;
    $row = array_combine($headers, $row);
    print_r($row);
    $row['order_id'] = $row['recharge shipping id'];
    $row['address_id'] = $row['recharge purchase id'];
    if(trim($row['country']) == 'United States'){
        continue;
    }

}