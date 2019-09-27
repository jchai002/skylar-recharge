<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
require_once(__DIR__.'/../../includes/config.php');

echo "Loading order nums from csv... ";
$fh = fopen(__DIR__."/../../manual/order_id_numbers.csv", 'r');
fgetcsv($fh); // Headers

$order_ids_by_number = [];
while($row = fgetcsv($fh)){
	$order_ids_by_number[$row[1]] = $row[0];
}
echo "loaded!".PHP_EOL;

$KEY_FILE_LOCATION = __DIR__ . '/../../'. $_ENV['GOOGLE_API_FILE'];

// Create and configure a new client object.
$client = new Google_Client();
$client->setApplicationName("Hello Analytics Reporting");
$client->setAuthConfig($KEY_FILE_LOCATION);
$client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
$analytics = new Google_Service_AnalyticsReporting($client);

$datetime = time();
$datetime = strtotime('2018-12-14'); // TODO: Remove
$stmt_get_order_id = $db->prepare("SELECT id FROM orders WHERE number = ?");
$stmt_insert_update_source = $db->prepare("INSERT INTO order_transaction_sources (order_id, ga_transaction_id, source, medium, campaign, page, ga_date) VALUES (:order_id, :ga_transaction_id, :source, :medium, :campaign, :page, :ga_date) ON DUPLICATE KEY UPDATE order_id=order_id");

// Loop here
do {
	$datetime = strtotime('yesterday', $datetime);
	$date = date('Y-m-d', $datetime);

	echo "Building GA query for $date... ";
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
	$request->setSamplingLevel("LARGE");

	$body = new Google_Service_AnalyticsReporting_GetReportsRequest();
	$body->setReportRequests([$request]);
	echo "Sending query... ";
	$response = $analytics->reports->batchGet($body);
	echo "Sent!".PHP_EOL;

	$report = $response->getReports()[0];
	$dimension_headers = $report->getColumnHeader()->getDimensions();
	$rows = $report->getData()->getRows();
	$is_sampled = !empty($report->getData()->getSamplesReadCounts());
	echo $is_sampled ? "Sampled!" : "Not sampled".PHP_EOL;
	foreach($rows as $row){
		/* @var $row Google_Service_AnalyticsReporting_ReportRow */
		$row_data = array_combine($dimension_headers,$row->getDimensions());
		/*
		*/
		$order_id = $order_ids_by_number[$row_data['ga:transactionId']] ?? null;
		if(empty($order_id)){
			$stmt_get_order_id->execute([$row_data['ga:transactionId']]);
			$order_id = $stmt_get_order_id->rowCount() > 0 ? $stmt_get_order_id->fetch(PDO::FETCH_COLUMN) : null;
		}
		$stmt_insert_update_source->execute([
			'order_id' => $order_id,
			'ga_transaction_id' => $row_data['ga:transactionId'],
			'source' => $row_data['ga:source'],
			'medium' => $row_data['ga:medium'],
			'campaign' => $row_data['ga:campaign'],
			'page' => $row_data['ga:pagePath'],
			'ga_date' => $date,
		]);
		echo "Stored ".$row_data['ga:transactionId']." as $order_id [".$row_data['ga:source']."/".$row_data['ga:medium']."]".PHP_EOL;
	}
} while(date('Y', $datetime) >= 2018);