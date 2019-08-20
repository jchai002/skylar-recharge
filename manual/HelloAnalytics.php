<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();
$order = $sc->get('/admin/orders/1332307787863.json');

$views = [
	'all_web_data' => '146856754',
	'littledata' => '178268161',
	'test' => '199478891',
];

$KEY_FILE_LOCATION = __DIR__ . '/../'. $_ENV['GOOGLE_API_FILE'];
echo $KEY_FILE_LOCATION.PHP_EOL;

// Create and configure a new client object.
$client = new Google_Client();
$client->setApplicationName("Hello Analytics Reporting");
$client->setAuthConfig($KEY_FILE_LOCATION);
$client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
$analytics = new Google_Service_AnalyticsReporting($client);

$request = new Google_Service_AnalyticsReporting_SearchUserActivityRequest([
	'user' => [
		'type' => 'CLIENT_ID',
		'userId' => '1085142703.1550250382',
	],
	'viewId' => '199478891'
]);
$response = $analytics->userActivity->search($request);
print_r($response);

die();