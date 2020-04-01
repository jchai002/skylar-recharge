<?php

use RollingCurl\Request;
use RollingCurl\RollingCurl;

require_once(__DIR__.'/../includes/config.php');

$page = 0;
$scent = null;


$cookie_token = '29422|7cbe63b01b7aec3588970e96fec94d101db71f8b0f72623bd7d3d2b87095ca3fcc767803605372a39b2ec76d62438ca4fa918f7b2ee735e6af4efb3505169b49';
$cookie_session = '.eJw1j8FqwzAQBX-l6NxDItuHGnoQyDUu7JqGTc3qElqqRpHlUJwEuQr596qFHt_hDTNXsfuc7cmJ-jxf7L3YHT5EfRV376IWTFBh2xVGm4DeOKNVBf45sHeOyU3QNhHIBSYVzcDfLF9HnLoCW66QVP6akfNmjx7TJoAe15i6ZKaXZIbt0pM7wIAeqFnAqxL0xoPsIspmQa0i0lPoaf_LXmeGQ9qu2MMqMyJ4SKD3Ra-5ZGoexS27f9l5ejva4_m_5nKy81-RkA-llOL2A-vuTpA.EWXPYg.t-PJt-SpdJ6VTy4hihc3OTizgkE';

$start_date = date('Y-m-d', strtotime('-1 day'));
$end_date = date('Y-m-d', strtotime('+1 day'));
$charge_ids = [];
$charges = [];
$start_time = microtime(true);
$total_charges = 0;
$starttime = microtime(true);
$rownum=0;
$retry = false;
do {
	$page++;
	// Load month's upcoming queued charges
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $end_date,
		'status' => 'QUEUED',
		'limit' => 250,
//		'page' => $page,
//        'address_id' => '29806558',
	]);
	if(empty($res['charges'])){
		if($retry){
			die("Empty charge response, must be done!".PHP_EOL);
		}
		print_r($res);
		sleep(5);
		$retry = true;
		$page--;
	}
	$retry = false;
	foreach($res['charges'] as $charge){
		if(strtotime($charge['scheduled_at']) != strtotime(date('Y-m-d'))){
			echo "Skipping charge ".$charge['id'].", scheduled for ".$charge['scheduled_at'].PHP_EOL;
			continue;
		}
		$charges[] = $charge;
		$total_charges++;
	}
	echo "Adding ".count($res['charges'])." to array - total: $total_charges".PHP_EOL;
	echo "Rate: ".($total_charges / (microtime(true) - $start_time))." charges/s".PHP_EOL;
	do_rolling_curl($charges, $cookie_session);
	$charges = [];
} while(count($res['charges']) == 250);
echo "Total: ".count($charges).PHP_EOL;
$charges = array_reverse($charges);

//do_rolling_curl($charges, $cookie_session);

die();

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

function do_rolling_curl($charges, $cookie_session) {
	$rolling_curl = new RollingCurl();

	foreach($charges as $charge){
		$charge_id = $charge['id'];
		$rolling_curl->get("https://maven-and-muse.shopifysubscriptions.com/charge/$charge_id/pay");
	}

	$rolling_curl
		->addOptions([
			CURLOPT_RETURNTRANSFER =>  true,
			CURLOPT_HTTPHEADER => [
				"cookie: session=$cookie_session",
				"user_type: STORE_ADMIN",
				"sec-fetch-mode: cors",
				"sec-fetch-site: same-origin",
			],
		])
		->setCallback(function(Request $request){
			global $starttime, $rownum;
			$rownum++;
			echo $request->getUrl().": ";
			$res_parse = json_decode($request->getResponseText(), true);
			if(empty($res_parse['msg'])){
				echo $request->getResponseText();
			} else {
				echo $res_parse['msg'].PHP_EOL;
			}

			if($rownum % 20 == 0 && $rownum > 0){
				echo "Row: $rownum. Time: ".(microtime(true)-$starttime)." seconds | ";
				echo "Pace: ".($rownum / (microtime(true)-$starttime))." rows/sec".PHP_EOL;
			}
		})
		->setSimultaneousLimit(5)
		->execute();
}