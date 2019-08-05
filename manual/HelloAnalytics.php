<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();
$order = $sc->get('/admin/orders/1066783244375.json');

$views = [
	'all_web_data' => '146856754',
	'littledata' => '178268161',
	'test' => '199478891',
	'testuid' => '199553029',
];

$analytics = initializeAnalytics();

$dateRange = new Google_Service_AnalyticsReporting_DateRange();
$dateRange->setStartDate("today");
$dateRange->setEndDate("today");

$sessions = new Google_Service_AnalyticsReporting_Metric();
$sessions->setExpression("ga:sessions");
$sessions->setAlias("sessions");

$request = new Google_Service_AnalyticsReporting_SearchUserActivityRequest();
$request->setViewId($views['all_web_data']);
$user = new Google_Service_AnalyticsReporting_User();
$user->setType('USER_ID');
$user->setUserId($order['customer']['id']);
$request->setUser($user);

$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
$body->setReportRequests( array( $request) );
$response = $analytics->reports->batchGet( $body );
var_dump($response->getReports());


die();
$response = getReport($analytics);
//printResults($response);
var_dump($response->getReports());


/**
 * Initializes an Analytics Reporting API V4 service object.
 *
 * @return Google_Service_AnalyticsReporting An authorized Analytics Reporting API V4 service object.
 */
function initializeAnalytics()
{

  // Use the developers console and download your service account
  // credentials in JSON format. Place them in this directory or
  // change the key file location if necessary.
  $KEY_FILE_LOCATION = __DIR__ . '/googlekey.json';

  // Create and configure a new client object.
  $client = new Google_Client();
  $client->setApplicationName("Hello Analytics Reporting");
  $client->setAuthConfig($KEY_FILE_LOCATION);
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_AnalyticsReporting($client);

  return $analytics;
}


/**
 * Queries the Analytics Reporting API V4.
 *
 * @param Google_Service_AnalyticsReporting An authorized Analytics Reporting API V4 service object.
 * @return Google_Service_AnalyticsReporting_GetReportsResponse Analytics Reporting API V4 response.
 */
function getReport(Google_Service_AnalyticsReporting $analytics) {

  // Replace with your view ID, for example XXXX.
  $VIEW_ID = "146856754";

  // Create the DateRange object.
  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
  $dateRange->setStartDate("7daysAgo");
  $dateRange->setEndDate("today");

  // Create the Metrics object.
  $sessions = new Google_Service_AnalyticsReporting_Metric();
  $sessions->setExpression("ga:sessions");
  $sessions->setAlias("sessions");

  // Create the ReportRequest object.
  $request = new Google_Service_AnalyticsReporting_ReportRequest();
  $request->setViewId($VIEW_ID);
  $request->setDateRanges($dateRange);
  $request->setMetrics(array($sessions));

  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
  $body->setReportRequests( array( $request) );
  return $analytics->reports->batchGet( $body );
}


/**
 * Parses and prints the Analytics Reporting API V4 response.
 *
 * @param Google_Service_AnalyticsReporting_GetReportsResponse An Analytics Reporting API V4 response.
 */
function printResults(Google_Service_AnalyticsReporting_GetReportsResponse $reports) {
  for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
    $report = $reports[ $reportIndex ];
    $header = $report->getColumnHeader();
    $dimensionHeaders = $header->getDimensions();
    $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
    $rows = $report->getData()->getRows();

    for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
      $row = $rows[ $rowIndex ];
      $dimensions = $row->getDimensions();
      $metrics = $row->getMetrics();
      for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
        print($dimensionHeaders[$i] . ": " . $dimensions[$i] . "\n");
      }

      for ($j = 0; $j < count($metrics); $j++) {
        $values = $metrics[$j]->getValues();
        for ($k = 0; $k < count($values); $k++) {
          $entry = $metricHeaders[$k];
          print($entry->getName() . ": " . $values[$k] . "\n");
        }
      }
    }
  }
}
