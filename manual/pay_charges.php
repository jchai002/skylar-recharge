<?php
require_once(__DIR__.'/../includes/config.php');

$page = 0;
$scent = null;


$cookie_token = '29422|7cbe63b01b7aec3588970e96fec94d101db71f8b0f72623bd7d3d2b87095ca3fcc767803605372a39b2ec76d62438ca4fa918f7b2ee735e6af4efb3505169b49';
$cookie_session = 'eJw1j1FrgzAURv_KyHMf6l2zbsIeBmFD4V5piZPkRVqXqjFxI1qmlv73dYO9nKfvwHcurDwFMzQsHsPZrFjZfrD4wu6OLGYkmk4L5xSknpaXBSG1KOqZYAfav_us2HuUHSdJrbbUaK8WBfmMdsczmXZK1px8zlG4FqVzJPMIl3qNQm1Q7O3NcShfWyySiN4S0LKa1HLby-SbvG6yIue0pG0m1b2WasJCd2hdRwJ5JqpZ29TqIgfl8Zldb9-_TPCH3vTjf00wVXMItSl7M43l0Qy_OH0Gw-KIP2xhvYXHaMXOgwl_4QyeNgDs-gNWeVvh.EH_CiQ.Xj9IAxK8ioOVx6RCG-_UtwyezOM';

$start_date = date('Y-m-d', strtotime('-1 day'));
$end_date = date('Y-m-d', strtotime('+1 day'));
$charge_ids = [];
$charges = [];
$start_time = microtime(true);
do {
	$page++;
	// Load month's upcoming queued charges
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $end_date,
		'status' => 'QUEUED',
		'limit' => 250,
		'page' => $page,
//        'address_id' => '29806558',
	]);
	if(empty($res['charges'])){
		print_r($res);
		sleep(5);
		$page--;
	}
	foreach($res['charges'] as $charge){
		if(strtotime($charge['scheduled_at']) != strtotime(date('Y-m-d'))){
			echo "Skipping charge ".$charge['id'].", scheduled for ".$charge['scheduled_at'].PHP_EOL;
			continue;
		}
		$charges[] = $charge;
	}
	echo "Adding ".count($res['charges'])." to array - total: ".count($charges).PHP_EOL;
	echo "Rate: ".(count($charges) / (microtime(true) - $start_time))." charges/s".PHP_EOL;
} while(count($res['charges']) == 250);
echo "Total: ".count($charges).PHP_EOL;


$starttime = microtime(true);
foreach($charges as $rownum => $charge){
	$charge_id = $charge['id'];
	echo $charge_id.": ";

	$ch = curl_init("https://maven-and-muse.shopifysubscriptions.com/charge/$charge_id/pay");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER =>  true,
		CURLOPT_HTTPHEADER => [
			"cookie: _hjid=4dd9b0cd-1e6d-404d-89e7-e4426b237b53; _ga=GA1.2.394335229.1570649314; _gid=GA1.2.74339139.1570649314; _gat_gtag_UA_96176346_2=1; session=.eJw1j01rAjEUAP9KybmHmpqDCz0UQssG3gvKS0NykWq3u_kSWZW6Ef97l0LvMzBzY9vvsTsNrDmPl-6RbcMXa27sYccahnJIXubsuCpYXytwFUH2E_I19-WjaLspQEkgYfARB19cddxMENdCk0qOeoHFCJA5AOWMZBZQ-yeQbglyE2cnA70FsO0C31vuaX91deap_cHiB22NwKqCJvfsyV3B-gQxJ5QgtNxPPqroreGuwAu7z-3Hbiyfh-5w_r-5nLrx74jx1ZJzdv8FDtlO8g.EH_GkA.23OFTvDLhe_IiUNNvxPyiWJlT8E; intercom-session-cpej2sb2=SEU1TC9iSUFKbDN6VGlqNVRnU3BiUWRFY0tBanAxK1dTbDYyTHRKWWM4aEJwWXhydnBOUlVBS280akswTzh0ci0tYVN2bE5YK0xRWjVZZ1lsMFg3aWpmdz09--d8382334a04cf79d212925bc388864fcd9625942",
			"user_type: STORE_ADMIN",
			"sec-fetch-mode: cors",
			"sec-fetch-site: same-origin",
		],
	]);
	$res = curl_exec($ch);
	$res_parse = json_decode($res, true);
	if(empty($res_parse['msg'])){
		echo $res;
	} else {
		echo $res_parse['msg'].PHP_EOL;
	}

	if($rownum % 20 == 0 && $rownum > 0){
		echo "Row: $rownum. Time: ".(microtime(true)-$starttime)." seconds | ";
		echo "Pace: ".($rownum / (microtime(true)-$starttime))." rows/sec".PHP_EOL;
	}
}