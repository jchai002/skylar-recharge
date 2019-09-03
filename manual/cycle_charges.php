<?php
require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');
require_once(__DIR__.'/../includes/class.RechargeClient.php');

$rc = new RechargeClient();
$sc = new ShopifyPrivateClient();

$page = 0;
$scent = null;

$start_date = date('Y-m-t');
$end_date = date('Y-m', strtotime('+2 months')).'-01';

$f = fopen(__DIR__.'/charges.csv', 'r');

$headers = fgetcsv($f);

$cookie_token = '29422|7cbe63b01b7aec3588970e96fec94d101db71f8b0f72623bd7d3d2b87095ca3fcc767803605372a39b2ec76d62438ca4fa918f7b2ee735e6af4efb3505169b49';
$cookie_session = '.eJxdkEtrwzAQhP9K2XMgtlzXiaEHg2LTgCQoso10MU6s-O0WOcGPkP9etZBLL8uyzCzzzR2yi1ZjBf5V39QGsroA_w4vJ_BBoHhmuOopD1wZ0Zpi4hBeLiyilVg_FtKELUvFQnDYEZT0Rr_QNXZEGjsMC0visBbrwexhK5uioWnSEfzZyp52FHcV4cSi0bEh_FgxfF7N_4li2csoqRkPZoqIxSKBZHqYKG9thsvJeFzSJ41sglmsocmU1ITH7_Aw2b-V7vNBDdcnjVbnKtelygY1myNs86IwtKMat2jvIsfbIfinyk5q_B2XL63At903D1ke2tkbuI1K_9UDaP-KEDx-AFExZyw.EEtAbQ.kBuDMnIfSd8v3Sx6YdoOFrjLC8E';
$last_charge_id = 0;
$rownum = 0;
$starttime = microtime(true);
while($row = fgetcsv($f)){
	$rownum++;
	$row = array_combine($headers, $row);

	// Check if the charge is scent club
    if(!in_array($row['item sku'],[
        '10450501-105',
        '10450501-106',
    ])){
        continue;
    }
	if($last_charge_id == $row['recharge charge id']){
		continue;
	}
	$last_charge_id = $row['recharge charge id'];
	echo $row['recharge charge id'].": ";

	$ch = curl_init("https://maven-and-muse.shopifysubscriptions.com/charge/$last_charge_id/regenerate_charge/");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER =>  true,
		CURLOPT_HTTPHEADER => ["cookie: session=$cookie_session"],
//		CURLOPT_HTTPHEADER => ["cookie: remember_token=$cookie_token"],
	]);
	$res = curl_exec($ch);
	$res_lines = preg_split("/\r\n|\n|\r/", $res);
	$skus = array_values(array_filter($res_lines, function($item){
		return strpos($item, "u'sku'") !== false;
	}));
	$skus = array_map("trim", $skus);
	echo implode('', $skus).PHP_EOL;

	if($rownum % 20 == 0){
		echo "Row: $rownum. Time: ".(microtime(true)-$starttime)." seconds | ";
		echo "Pace: ".($rownum / (microtime(true)-$starttime))." rows/sec".PHP_EOL;
	}
}