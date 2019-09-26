<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once(__DIR__.'/../../includes/config.php');

$KEY_FILE_LOCATION = __DIR__ . '/../../'. $_ENV['GOOGLE_API_FILE'];

// Create and configure a new client object.
$client = new Google_Client();
$client->setApplicationName("Hello Analytics Reporting");
$client->setAuthConfig($KEY_FILE_LOCATION);
$client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
$analytics = new Google_Service_AnalyticsReporting($client);

$date = date('Y-m-d');

$request = new Google_Service_AnalyticsReporting_GetReportsRequest([
	'viewId' => '146856754',
	'dateRange' => [
		'startDate' => $date,
		'endDate' => $date,
	],
	'metrics' => [['expression' => 'ga:users']],
	'dimensions' => [
		['expression' => 'ga:transactionId'],
		['expression' => 'ga:source'],
		['expression' => 'ga:medium'],
		['expression' => 'ga:campaign'],
		['expression' => 'ga:pagePath'],
	],
	'orderBys' => [
		['fieldName' => 'ga:transactionId', "sortOrder" => "DESCENDING"]
	]
]);

$response = $analytics->reports->batchGet($request);

var_dump($response);