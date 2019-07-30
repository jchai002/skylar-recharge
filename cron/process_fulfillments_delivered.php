<?php
require_once('../includes/config.php');

$stmt = $db->prepare("SELECT * FROM fulfillments WHERE delivered_at >= ? AND delivery_process_at IS NULL");
$stmt->execute([date('Y-m-d H:i:s', time()-(60*60*24*7))]);

foreach($stmt->fetchAll() as $fulfillment){

}
