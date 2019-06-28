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
$cookie_session = '.eJwdjz1rwzAUAP9KeXMHWXaGGjoUHAkH9EyCVKG3hJQqqfURip2QViH_vabrDcfdHfbHyc9f0B4PafbPsB8_ob3D0we0QJqyK9sKS8rYxRq5iC5Ehvp0o7BlpDeRtCsumELZcBViRdatBuv4YE1BKxJ2ilH3xkgaTnn9S11fo055YRUFMWJZM5Xfgwp9Q5mCkiIPUi2u0w2lq0mKqKxZKYuJpGuU3oxkdxF5_6PC4g27jLJ_hcfS_u2nfDj78wXay3Rdbq6zn_6PgL80nMPjD_TfTfw.D_Rf3A.bRTLgDleNQgG-5Ho1drMuaQVYN8';
$last_charge_id = 0;
$rownum = 0;
$starttime = microtime(true);
while($row = fgetcsv($f)){
	$rownum++;
	$row = array_combine($headers, $row);

	// Check if the charge is scent club
    if(!in_array($row['item sku'],[
        '10450503-101',
        '10450502-103',
        '10450502-105',
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