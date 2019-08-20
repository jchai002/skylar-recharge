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

?>
<table>
	<thead>
	<tr>
		<td>Time</td>
		<td>Type</td>
		<td>Source</td>
		<td>Medium</td>
		<td>Campaign</td>
		<td>Channel Grouping</td>
		<td>Ecommerce</td>
		<td>Event</td>
		<td>Goals</td>
	</tr>
	</thead>
	<tbody>
	<?php
	/* @var $session Google_Service_AnalyticsReporting_UserActivitySession
	 */
	foreach($response->getSessions() as $session){ ?>
		<tr>
			<td colspan="4">Session ID: <?= $session->getSessionId() ?> </td>
		</tr>
		<?php
		/* @var $activity Google_Service_AnalyticsReporting_Activity
		 */
		foreach($session->getActivities() as $activity){ ?>
			<tr>
				<td><?= $activity->getActivityTime() ?></td>
				<td><?= $activity->getActivityType() ?></td>
				<td><?= $activity->getSource() ?></td>
				<td><?= $activity->getMedium() ?></td>
				<td><?= $activity->getCampaign() ?></td>
				<td><?= $activity->getChannelGrouping() ?></td>
				<td><?= print_r($activity->getEcommerce()) ?></td>
				<td><?= print_r($activity->getEvent()) ?></td>
				<td><?= print_r($activity->getGoals()) ?></td>
				<!--<?php print_r($activity); ?> -->
			</tr>
		<?php } ?>
	<?php } ?>
	</tbody>
</table>
<pre><?php print_r($response); ?></pre>
