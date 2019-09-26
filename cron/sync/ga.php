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

// Create the DateRange object.
$dateRange = new Google_Service_AnalyticsReporting_DateRange();
$dateRange->setStartDate($date);
$dateRange->setEndDate($date);

// Create the Metrics object.
$sessions = new Google_Service_AnalyticsReporting_Metric();
$sessions->setExpression("ga:users");

//Create the Dimensions object.
$dimensions = [
	new Google_Service_AnalyticsReporting_Dimension(),
	new Google_Service_AnalyticsReporting_Dimension(),
	new Google_Service_AnalyticsReporting_Dimension(),
	new Google_Service_AnalyticsReporting_Dimension(),
	new Google_Service_AnalyticsReporting_Dimension(),
];
$dimensions[0]->setName("ga:transactionId");
$dimensions[1]->setName("ga:source");
$dimensions[2]->setName("ga:medium");
$dimensions[3]->setName("ga:campaign");
$dimensions[4]->setName("ga:pagePath");

// Create the ReportRequest object.
$request = new Google_Service_AnalyticsReporting_ReportRequest();
$request->setViewId("146856754");
$request->setDateRanges($dateRange);
$request->setDimensions($dimensions);
$request->setMetrics([$sessions]);

$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
$body->setReportRequests([$request]);
$response = $analytics->reports->batchGet($body);
var_dump($response);
die();

$request = new Google_Service_AnalyticsReporting_ReportRequest([
	'viewId' => '146856754',
	'dateRanges' => [
		'startDate' => $date,
		'endDate' => $date,
	],
	'metrics' => [['expression' => 'ga:users']],
//	'metrics' => [['expression' => 'ga:users']],
	'dimensions' => [
		['name' => 'ga:transactionId'],
		['name' => 'ga:source'],
		['name' => 'ga:medium'],
		['name' => 'ga:campaign'],
		['name' => 'ga:pagePath'],
	],
	'orderBys' => [
		['fieldName' => 'ga:transactionId', "sortOrder" => "DESCENDING"]
	]
]);

$requests = new Google_Service_AnalyticsReporting_GetReportsRequest();
$requests->setReportRequests([$request]);

$response = $analytics->reports->batchGet($requests);

var_dump($response);