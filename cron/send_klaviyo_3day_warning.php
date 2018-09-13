<?php
require_once('../includes/config.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$klaviyo = new Klaviyo($_ENV['KLAVIYO_API_KEY']);
$ch = curl_init("https://a.klaviyo.com/api/v1/email-template/HHsipD/send");
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_POST => true,
	CURLOPT_POSTFIELDS => [
		'api_key' => 'pk_4c31e0386c15cca46c19dac063c013054c',
		'from_email' => 'hello@skylar.com',
		'from_name' => 'Skylar',
		'subject' => 'Order Reminder Test',
		'to' => json_encode([
			['email' => 'j.tim.nolan@gmail.com', 'name' => 'Tim Nolan',]
		]),
		'context' => json_encode([
			'test' => 'Test',
			'date' => time()+(24*60*60*3),
			'line_items' => [
				['title' => 'Isle - Full size (1.7oz)', 'quantity'=> 1, 'price' => 78],
				['title' => 'Meadow - Full size (1.7oz)', 'quantity'=> 2, 'price' => 78],
			],
		]),
	]
]);
$res = curl_exec($ch);
$res = json_decode($res);
var_dump($res);

die();

$res = $klaviyo->track(
	'Upcoming Charge: 3 Days',
	['$email' => 'j.tim.nolan@gmail.com'],
	['line_items'=>[
		['title' => 'Isle - Full size (1.7oz)', 'quantity'=> 1, 'price' => 78],
		['title' => 'Meadow - Full size (1.7oz)', 'quantity'=> 2, 'price' => 78],
	]]
);
var_dump($res);