<?php

die();

require_once(__DIR__.'/../includes/config.php');

$east_zip_prefixes = [
	'004',
	'005',
	'010',
	'011',
	'012',
	'013',
	'014',
	'015',
	'016',
	'017',
	'018',
	'019',
	'020',
	'021',
	'022',
	'023',
	'024',
	'025',
	'026',
	'027',
	'028',
	'029',
	'030',
	'031',
	'032',
	'033',
	'034',
	'035',
	'036',
	'037',
	'038',
	'039',
	'040',
	'041',
	'042',
	'043',
	'044',
	'045',
	'046',
	'047',
	'048',
	'049',
	'050',
	'051',
	'052',
	'053',
	'054',
	'055',
	'056',
	'057',
	'058',
	'059',
	'060',
	'061',
	'062',
	'063',
	'064',
	'065',
	'066',
	'067',
	'068',
	'069',
	'070',
	'071',
	'072',
	'073',
	'074',
	'075',
	'076',
	'077',
	'078',
	'079',
	'080',
	'081',
	'082',
	'083',
	'084',
	'085',
	'086',
	'087',
	'088',
	'089',
	'100',
	'101',
	'102',
	'103',
	'104',
	'105',
	'106',
	'107',
	'108',
	'109',
	'110',
	'111',
	'112',
	'113',
	'114',
	'115',
	'116',
	'117',
	'118',
	'119',
	'120',
	'121',
	'122',
	'123',
	'124',
	'125',
	'126',
	'127',
	'128',
	'129',
	'130',
	'131',
	'132',
	'133',
	'134',
	'135',
	'136',
	'137',
	'138',
	'139',
	'140',
	'141',
	'142',
	'143',
	'144',
	'145',
	'146',
	'147',
	'148',
	'149',
	'150',
	'151',
	'152',
	'153',
	'154',
	'155',
	'156',
	'157',
	'158',
	'159',
	'160',
	'161',
	'162',
	'163',
	'164',
	'165',
	'166',
	'167',
	'168',
	'169',
	'170',
	'171',
	'172',
	'173',
	'174',
	'175',
	'176',
	'177',
	'178',
	'179',
	'180',
	'181',
	'182',
	'183',
	'184',
	'185',
	'186',
	'187',
	'188',
	'189',
	'190',
	'191',
	'192',
	'193',
	'194',
	'195',
	'196',
	'197',
	'198',
	'199',
	'200',
	'201',
	'202',
	'203',
	'204',
	'205',
	'206',
	'207',
	'208',
	'209',
	'210',
	'211',
	'212',
	'213',
	'214',
	'215',
	'216',
	'217',
	'218',
	'219',
	'220',
	'221',
	'222',
	'223',
	'224',
	'225',
	'226',
	'227',
	'228',
	'229',
	'230',
	'231',
	'232',
	'233',
	'234',
	'235',
	'236',
	'237',
	'238',
	'239',
	'240',
	'241',
	'242',
	'243',
	'244',
	'245',
	'246',
	'247',
	'248',
	'249',
	'250',
	'251',
	'252',
	'253',
	'254',
	'255',
	'256',
	'257',
	'258',
	'259',
	'260',
	'261',
	'262',
	'263',
	'264',
	'265',
	'266',
	'267',
	'268',
	'269',
	'270',
	'271',
	'272',
	'273',
	'274',
	'275',
	'276',
	'277',
	'278',
	'279',
	'280',
	'281',
	'282',
	'283',
	'284',
	'285',
	'286',
	'287',
	'288',
	'289',
	'290',
	'291',
	'292',
	'293',
	'294',
	'295',
	'296',
	'297',
	'298',
	'299',
	'300',
	'301',
	'302',
	'303',
	'304',
	'305',
	'306',
	'307',
	'308',
	'309',
	'310',
	'311',
	'312',
	'313',
	'314',
	'315',
	'316',
	'317',
	'318',
	'319',
	'320',
	'321',
	'322',
	'323',
	'324',
	'325',
	'326',
	'327',
	'328',
	'329',
	'330',
	'331',
	'332',
	'333',
	'334',
	'335',
	'336',
	'337',
	'338',
	'339',
	'341',
	'342',
	'343',
	'344',
	'345',
	'346',
	'347',
	'348',
	'349',
	'350',
	'351',
	'352',
	'353',
	'354',
	'355',
	'356',
	'357',
	'358',
	'359',
	'360',
	'361',
	'362',
	'363',
	'364',
	'365',
	'366',
	'367',
	'368',
	'369',
	'370',
	'371',
	'372',
	'373',
	'374',
	'375',
	'376',
	'377',
	'378',
	'379',
	'380',
	'381',
	'382',
	'383',
	'384',
	'385',
	'386',
	'387',
	'388',
	'389',
	'390',
	'391',
	'392',
	'393',
	'394',
	'395',
	'396',
	'397',
	'398',
	'399',
	'400',
	'401',
	'402',
	'403',
	'404',
	'405',
	'406',
	'407',
	'408',
	'409',
	'410',
	'411',
	'412',
	'413',
	'414',
	'415',
	'416',
	'417',
	'418',
	'420',
	'421',
	'422',
	'423',
	'424',
	'425',
	'426',
	'427',
	'428',
	'429',
	'430',
	'431',
	'432',
	'433',
	'434',
	'435',
	'436',
	'437',
	'438',
	'439',
	'440',
	'441',
	'442',
	'443',
	'444',
	'445',
	'446',
	'447',
	'448',
	'449',
	'450',
	'451',
	'452',
	'453',
	'454',
	'455',
	'456',
	'457',
	'458',
	'459',
	'461',
	'462',
	'463',
	'464',
	'465',
	'466',
	'467',
	'468',
	'469',
	'470',
	'471',
	'472',
	'473',
	'474',
	'475',
	'476',
	'477',
	'478',
	'479',
	'480',
	'481',
	'482',
	'483',
	'484',
	'485',
	'486',
	'487',
	'488',
	'489',
	'490',
	'491',
	'492',
	'493',
	'494',
	'495',
	'496',
	'497',
	'498',
	'499',
	'500',
	'501',
	'502',
	'503',
	'504',
	'506',
	'507',
	'509',
	'520',
	'521',
	'522',
	'523',
	'524',
	'525',
	'526',
	'527',
	'528',
	'529',
	'530',
	'531',
	'532',
	'533',
	'534',
	'535',
	'536',
	'537',
	'538',
	'539',
	'540',
	'541',
	'542',
	'543',
	'544',
	'545',
	'546',
	'547',
	'548',
	'549',
	'550',
	'551',
	'552',
	'553',
	'554',
	'555',
	'556',
	'557',
	'558',
	'559',
	'560',
	'562',
	'563',
	'564',
	'565',
	'566',
	'567',
	'582',
	'600',
	'601',
	'602',
	'603',
	'604',
	'605',
	'606',
	'607',
	'608',
	'609',
	'610',
	'611',
	'612',
	'613',
	'614',
	'615',
	'616',
	'617',
	'618',
	'619',
	'620',
	'621',
	'622',
	'623',
	'624',
	'625',
	'626',
	'627',
	'628',
	'629',
	'630',
	'631',
	'632',
	'633',
	'634',
	'635',
	'636',
	'637',
	'638',
	'639',
	'650',
	'651',
	'652',
	'653',
	'654',
	'655',
	'700',
	'701',
	'702',
	'703',
	'704',
	'705',
	'706',
	'707',
	'708',
	'709',
	'712',
	'713',
	'714',
	'715',
	'716',
	'717',
	'719',
	'720',
	'721',
	'722',
	'723',
	'724',
	'725',
	'728',
	'776',
	'777',
	'004',
	'005',
	'010',
	'011',
	'012',
	'013',
	'014',
	'015',
	'016',
	'017',
	'018',
	'019',
	'020',
	'021',
	'022',
	'023',
	'024',
	'025',
	'026',
	'027',
	'028',
	'029',
	'030',
	'031',
	'032',
	'033',
	'034',
	'035',
	'036',
	'037',
	'038',
	'039',
	'040',
	'041',
	'042',
	'043',
	'044',
	'045',
	'046',
	'047',
	'048',
	'049',
	'050',
	'051',
	'052',
	'053',
	'054',
	'055',
	'056',
	'057',
	'058',
	'059',
	'060',
	'061',
	'062',
	'063',
	'064',
	'065',
	'066',
	'067',
	'068',
	'069',
	'070',
	'071',
	'072',
	'073',
	'074',
	'075',
	'076',
	'077',
	'078',
	'079',
	'080',
	'081',
	'082',
	'083',
	'084',
	'085',
	'086',
	'087',
	'088',
	'089',
	'100',
	'101',
	'102',
	'103',
	'104',
	'105',
	'106',
	'107',
	'108',
	'109',
	'110',
	'111',
	'112',
	'113',
	'114',
	'115',
	'116',
	'117',
	'118',
	'119',
	'120',
	'121',
	'122',
	'123',
	'124',
	'125',
	'126',
	'127',
	'128',
	'129',
	'130',
	'131',
	'132',
	'133',
	'134',
	'135',
	'136',
	'137',
	'138',
	'139',
	'140',
	'141',
	'142',
	'143',
	'144',
	'145',
	'146',
	'147',
	'148',
	'149',
	'150',
	'151',
	'152',
	'153',
	'154',
	'155',
	'156',
	'157',
	'158',
	'159',
	'160',
	'161',
	'162',
	'163',
	'164',
	'165',
	'166',
	'167',
	'168',
	'169',
	'170',
	'171',
	'172',
	'173',
	'174',
	'175',
	'176',
	'177',
	'178',
	'179',
	'180',
	'181',
	'182',
	'183',
	'184',
	'185',
	'186',
	'187',
	'188',
	'189',
	'190',
	'191',
	'192',
	'193',
	'194',
	'195',
	'196',
	'197',
	'198',
	'199',
	'200',
	'201',
	'202',
	'203',
	'204',
	'205',
	'206',
	'207',
	'208',
	'209',
	'210',
	'211',
	'212',
	'213',
	'214',
	'215',
	'216',
	'217',
	'218',
	'219',
	'220',
	'221',
	'222',
	'223',
	'224',
	'225',
	'226',
	'227',
	'228',
	'229',
	'230',
	'231',
	'232',
	'233',
	'234',
	'235',
	'236',
	'237',
	'238',
	'239',
	'240',
	'241',
	'242',
	'243',
	'244',
	'245',
	'246',
	'247',
	'248',
	'249',
	'250',
	'251',
	'252',
	'253',
	'254',
	'255',
	'256',
	'257',
	'258',
	'259',
	'260',
	'261',
	'262',
	'263',
	'264',
	'265',
	'266',
	'267',
	'268',
	'269',
	'270',
	'271',
	'272',
	'273',
	'274',
	'275',
	'276',
	'277',
	'278',
	'279',
	'280',
	'281',
	'282',
	'283',
	'284',
	'285',
	'286',
	'287',
	'288',
	'289',
	'290',
	'291',
	'292',
	'293',
	'294',
	'295',
	'296',
	'297',
	'298',
	'299',
	'300',
	'301',
	'302',
	'303',
	'304',
	'305',
	'306',
	'307',
	'308',
	'309',
	'310',
	'311',
	'312',
	'313',
	'314',
	'315',
	'316',
	'317',
	'318',
	'319',
	'320',
	'321',
	'322',
	'323',
	'324',
	'325',
	'326',
	'327',
	'328',
	'329',
	'330',
	'331',
	'332',
	'333',
	'334',
	'335',
	'336',
	'337',
	'338',
	'339',
	'341',
	'342',
	'343',
	'344',
	'345',
	'346',
	'347',
	'348',
	'349',
	'350',
	'351',
	'352',
	'353',
	'354',
	'355',
	'356',
	'357',
	'358',
	'359',
	'360',
	'361',
	'362',
	'363',
	'364',
	'365',
	'366',
	'367',
	'368',
	'369',
	'370',
	'371',
	'372',
	'373',
	'374',
	'375',
	'376',
	'377',
	'378',
	'379',
	'380',
	'381',
	'382',
	'383',
	'384',
	'385',
	'386',
	'387',
	'388',
	'389',
	'390',
	'391',
	'392',
	'393',
	'394',
	'395',
	'396',
	'397',
	'398',
	'399',
	'400',
	'401',
	'402',
	'403',
	'404',
	'405',
	'406',
	'407',
	'408',
	'409',
	'410',
	'411',
	'412',
	'413',
	'414',
	'415',
	'416',
	'417',
	'418',
	'420',
	'421',
	'422',
	'423',
	'424',
	'425',
	'426',
	'427',
	'428',
	'429',
	'430',
	'431',
	'432',
	'433',
	'434',
	'435',
	'436',
	'437',
	'438',
	'439',
	'440',
	'441',
	'442',
	'443',
	'444',
	'445',
	'446',
	'447',
	'448',
	'449',
	'450',
	'451',
	'452',
	'453',
	'454',
	'455',
	'456',
	'457',
	'458',
	'459',
	'461',
	'462',
	'463',
	'464',
	'465',
	'466',
	'467',
	'468',
	'469',
	'470',
	'471',
	'472',
	'473',
	'474',
	'475',
	'476',
	'477',
	'478',
	'479',
	'480',
	'481',
	'482',
	'483',
	'484',
	'485',
	'486',
	'487',
	'488',
	'489',
	'490',
	'491',
	'492',
	'493',
	'494',
	'495',
	'496',
	'497',
	'498',
	'499',
	'500',
	'501',
	'502',
	'503',
	'504',
	'506',
	'507',
	'509',
	'520',
	'521',
	'522',
	'523',
	'524',
	'525',
	'526',
	'527',
	'528',
	'529',
	'530',
	'531',
	'532',
	'533',
	'534',
	'535',
	'536',
	'537',
	'538',
	'539',
	'540',
	'541',
	'542',
	'543',
	'544',
	'545',
	'546',
	'547',
	'548',
	'549',
	'550',
	'551',
	'552',
	'553',
	'554',
	'555',
	'556',
	'557',
	'558',
	'559',
	'560',
	'562',
	'563',
	'564',
	'565',
	'566',
	'567',
	'582',
	'600',
	'601',
	'602',
	'603',
	'604',
	'605',
	'606',
	'607',
	'608',
	'609',
	'610',
	'611',
	'612',
	'613',
	'614',
	'615',
	'616',
	'617',
	'618',
	'619',
	'620',
	'621',
	'622',
	'623',
	'624',
	'625',
	'626',
	'627',
	'628',
	'629',
	'630',
	'631',
	'632',
	'633',
	'634',
	'635',
	'636',
	'637',
	'638',
	'639',
	'650',
	'651',
	'652',
	'653',
	'654',
	'655',
	'700',
	'701',
	'702',
	'703',
	'704',
	'705',
	'706',
	'707',
	'708',
	'709',
	'712',
	'713',
	'714',
	'715',
	'716',
	'717',
	'719',
	'720',
	'721',
	'722',
	'723',
	'724',
	'725',
	'728',
	'776',
	'777',
];

$inventory_pulls = [];
// TODO: Alert for high volume of orders not shipped

$cut_on_date = '2019-12-16T08:00:00Z';
$page_size = 250;
$page = 0;
$updates = [];
$last_send_time = time();
$buffer_date = gmdate('Y-m-d\TH:i:s\Z', strtotime('-5 minutes'));

$stmt_get_prev_order = $db->prepare("SELECT 1 FROM orders WHERE email = :email AND id != :id LIMIT 1");
$stmt_get_order_skus = $db->prepare("SELECT sku FROM order_line_items WHERE order_id=?");

echo "Pulling $buffer_date to $cut_on_date".PHP_EOL;

do {
	$page++;
	// Get held orders
	/* @var $res JsonAwareResponse */
	$res = $cc->get('SalesOrders', [
		'query' => [
			'fields' => implode(',', ['id', 'email', 'status', 'reference', 'logisticsStatus', 'freightDescription', 'deliveryPostalCode', 'deliveryCountry', 'lineItems']),
			'where' => "LogisticsStatus = '9' AND createdDate >= '$cut_on_date' AND createdDate < '$buffer_date' AND status = 'APPROVED' AND stage = 'New'",
//			'where' => "id = 219878 AND LogisticsStatus = '9' AND createdDate >= '$cut_on_date' AND createdDate < '$buffer_date' AND status = 'APPROVED' AND stage = 'New'",
			'order' => 'CreatedDate DESC',
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	sleep(1);

	$cc_orders = $res->getJson();

	$stmt = $db->prepare("SELECT * FROM orders WHERE number=?");
	foreach($cc_orders as $index=>$cc_order){
		$send_updates = false;
		if(count($updates) == $page_size){
			echo "Updates hit $page_size, ";
			$send_updates = true;
		}
		if(count($updates) > 0 && time()-$last_send_time > 30){
			echo "It's been ".(time()-$last_send_time)."s since last update, ";
			$send_updates = true;
		}
		if($send_updates){
			echo "Sending updates... ";
			$res = send_cc_updates($cc, $updates);
			$updates = [];
			echo "Done".PHP_EOL;
			sleep(1);
			$last_send_time = time();
		}
		$order_number = str_ireplace('#sb','',$cc_order['reference']);
		$stmt->execute([$order_number]);
		if($stmt->rowCount() == 0){
			echo "Couldn't find order in DB, must not be Shopify";
			continue;
		}
		$db_order = $stmt->fetch();
		echo "Checking order ".$cc_order['reference']."... ";
		if($cc_order['logisticsStatus'] != 9){
			if(!empty($db_order['cancelled_at'])){
				echo "logisticsStatus is ".$cc_order['logisticsStatus'].", skipping cin7 id: ".$cc_order['id'].PHP_EOL;
				continue;
			}
		}
		if(empty($cc_order['freightDescription'])){
			echo "Order doesn't have freight description, skipping and alerting".PHP_EOL;
			print_r(send_alert($db, 15, "Order is being held because it doesn't have a freight description: https://go.cin7.com/Cloud/TransactionEntry/TransactionEntry.aspx?idCustomerAppsLink=800541&OrderId=".$cc_order['id'], 'Skylar Alert - No Freight Description on Order', ['tim@skylar.com', 'kristin@skylar.com'], [
				'smother_window' => date('Y-m-d H:i:s', strtotime('-48 hours')),
			]));
			continue;
		}
		if(!empty($db_order['cancelled_at'])){
			echo "Order cancelled in Shopify, skipping cin7 id: ".$cc_order['id'].PHP_EOL;
			continue;
		}
		if(strpos($db_order['tags'], 'HOLD:') !== false){
			echo "Order held in Shopify, skipping".PHP_EOL;
			continue;
		}
		$cc_order['logisticsStatus'] = 1;
		$cc_order['branchId'] = calc_branch_id($db, $cc_order);

		switch($cc_order['branchId']){
			default: break;
			case -1:
				echo "Order doesn't have zip code, skipping and alerting".PHP_EOL;
				print_r(send_alert($db, 13, "Order is being held because it doesn't have a shipping address zip: https://go.cin7.com/Cloud/TransactionEntry/TransactionEntry.aspx?idCustomerAppsLink=800541&OrderId=".$cc_order['id'], 'Skylar Alert - No Zip on Order', ['tim@skylar.com', 'kristin@skylar.com'], [
					'smother_window' => date('Y-m-d H:i:s', strtotime('-48 hours')),
				]));
				continue 2; // Switch statements are treated as loops
			case -2:
				echo "No branch can fulfill this order, skipping and alerting".PHP_EOL;
				print_r(send_alert($db, 14, "Order is being held because it doesn't have stock available: https://go.cin7.com/Cloud/TransactionEntry/TransactionEntry.aspx?idCustomerAppsLink=800541&OrderId=".$cc_order['id'], 'Skylar Alert - No Stock Available', ['tim@skylar.com'], [
					'smother_window' => date('Y-m-d H:i:s', strtotime('-48 hours')),
					'inventory_pulls' => $inventory_pulls,
				]));
				continue 2; // Switch statements are treated as loops
		}
		// Salt air sample add
		$add_salt_air = false;
		if(count(array_intersect([
			'70221408-100', // Scent experience
			'10450506-101', // Sample Palette
		], array_column($cc_order['lineItems'], 'code'))) > 0){
			$add_salt_air = true;
		} else{
			$stmt_get_prev_order->execute([
				'email' => $cc_order['email'],
				'id' => $db_order['id'],
			]);
			if($stmt_get_prev_order->rowCount() == 0){
				$add_salt_air = true;
			}
		}
		if($add_salt_air && !empty(array_intersect(array_column($cc_order['lineItems'], 'code'), [
			'99238701-112', // Peel
			'10450504-112', // full size
			'10450505-112', // rollie
		]))){
			$add_salt_air = false;
		}
		if($add_salt_air){
			$stmt_get_order_skus->execute([
				$db_order['id'],
			]);
			$order_skus = $stmt_get_order_skus->fetchAll(PDO::FETCH_COLUMN);
			$missing_skus = array_diff($order_skus, array_column($cc_order['lineItems'], 'code'));
			if(count($missing_skus) > 0){
				echo "Missing sku ".implode(',', $missing_skus).", sending alert" . PHP_EOL;
				print_r($cc_order['lineItems']);
				print_r(send_alert($db, 16, "Order is being held because it is missing line items that are in shopify (".implode(',', $missing_skus)."): https://go.cin7.com/Cloud/TransactionEntry/TransactionEntry.aspx?idCustomerAppsLink=800541&OrderId=" . $cc_order['id'] . " , https://skylar.com/admin/orders/" . $db_order['shopify_id'], 'Skylar Alert - Missing Line Items'));
				continue;
			}
			echo "Adding salt air to order... ";
			add_salt_air_sample($cc_order);
			$tags = explode(', ', $db_order['tags']);
			$tags[] = 'Added Salt Air Sample';
			$res = $sc->put('orders/'.$db_order['shopify_id'].'.json', ['order' => ['tags' => implode(', ', array_unique($tags))]]);
		} else {
			unset($cc_order['lineItems']);
		}
		$updates[] = $cc_order;
		echo "Added to update queue w/ branch id ".$cc_order['branchId']." [".count($updates)."]".PHP_EOL;
	}
} while(count($cc_orders) >= $page_size);

if(count($updates) > 0){
	echo "Sending last updates... ";
	$res = send_cc_updates($cc, $updates);
	echo "Done".PHP_EOL;
}

function send_cc_updates(GuzzleHttp\Client $cc, $updates){
	$res = $cc->put('SalesOrders',[
		'http_errors' => false,
		'json' => $updates,
	]);
	if($res->getStatusCode() != 200){
		echo "Error! ".$res->getStatusCode().": ".$res->getReasonPhrase()." ";
		print_r($res->getBody());
	}
	return $res;
}
function calc_branch_id(PDO $db, $cc_order){
	global $east_zip_prefixes;
	if(empty($cc_order['deliveryPostalCode'])){
		return -1;
	}

	$line_items = $cc_order['lineItems'];

	// Shipper logic
	if(array_sum(array_column($line_items, 'qty')) > 1){
		return 3;
	}

	if(empty($cc_order['deliveryCountry']) || !in_array(strtoupper($cc_order['deliveryCountry']), ['UNITED STATES', 'US', 'USA'])){
		$preferred_location_id = 3;
	} else {
		$zip_prefix = substr($cc_order['deliveryPostalCode'], 0, 3);
		$preferred_location_id = in_array($zip_prefix, $east_zip_prefixes) ? 23755 : 3;
	}

	// Check if preferred location can fulfill order
	if(branch_can_fill_items($db, $preferred_location_id, $line_items)){
		return $preferred_location_id;
	}

	// Check if backup location can fulfill order
	$backup_location_id = $preferred_location_id == 3 ? 23755 : 3;
	if(branch_can_fill_items($db, $backup_location_id, $line_items)){
		return $backup_location_id;
	}

	return -2;
}

function branch_can_fill_items(PDO $db, $branch_id, $line_items){
	foreach($line_items as $line_item){
		if(!branch_can_fill_sku($db, $branch_id, $line_item['code'], $line_item['qty'])){
			return false;
		}
	}
	return true;
}
function branch_can_fill_sku(PDO $db, $branch_id, $sku, $quantity = 1){
	global $_stmt_cache, $inventory_pulls;
	if(empty($_stmt_cache['cin_branch_stock_check'])){
		$_stmt_cache['cin_branch_stock_check'] = $db->prepare("
SELECT csu.available FROM cin_stock_units csu
LEFT JOIN cin_product_options cpo ON cpo.id=csu.cin_product_option_id
LEFT JOIN cin_products cp ON cp.id=cpo.cin_product_id
LEFT JOIN cin_branches cb ON cb.id=csu.cin_branch_id
WHERE cpo.sku = :sku
AND cin_branch_id = :branch_id;");
	}
	$_stmt_cache['cin_branch_stock_check']->execute([
		'sku' => $sku,
		'branch_id' => $branch_id,
	]);
	if($_stmt_cache['cin_branch_stock_check']->rowCount() == 0){
		$inventory_pulls["$branch_id.$sku"] = [
			'rowcount' => $_stmt_cache['cin_branch_stock_check']->rowCount(),
			'errorinfo' => $db->errorInfo(),
		];
		return false;
	}
	$res = $_stmt_cache['cin_branch_stock_check']->fetchColumn();
	$inventory_pulls["$branch_id.$sku"] = [
		'res' => $res,
		'rowcount' => $_stmt_cache['cin_branch_stock_check']->rowCount(),
		'errorinfo' => $db->errorInfo(),
	];
	return $res >= $quantity;
}

function add_salt_air_sample(&$cc_order){
	// Make sure it doesn't already have salt air
	$sort = array_reduce($cc_order['lineItems'], function($carry, $item){
		return $item['sort'] > $carry ? $item['sort'] : $carry;
	}, 1);
	$sort++;
	$cc_order['lineItems'][] = [
		'transactionId' => $cc_order['id'],
		'productId' => 1494,
		'productOptionId' => 1495,
		'sort' => $sort,
		'code' => '99238701-112',
		'name' => 'Scent Peel Back Salt Air',
		'qty' => 1,
		'styleCode' => '99238701-112',
		'lineComments' => 'Auto-added by API',
	];
	return $cc_order;
}