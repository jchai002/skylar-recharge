<?php
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

$cut_on_date = '2019-12-16T08:00:00Z';
$page_size = 250;
$page = 0;
$updates = [];
do {
	$page++;
	// Get held orders
	/* @var $res JsonAwareResponse */
	$res = $cc->get('SalesOrders', [
		'query' => [
			'fields' => implode(',', ['id', 'reference', 'logisticsStatus', 'freightDescription', 'deliveryPostalCode', 'lineItems']),
			'where' => "LogisticsStatus = '9' AND createdDate >= '$cut_on_date'",
			'order' => 'CreatedDate ASC',
			'rows' => $page_size,
			'page' => $page,
		],
	]);
	sleep(1);

	$cc_orders = $res->getJson();

	$stmt = $db->prepare("SELECT * FROM orders WHERE number=?");
	foreach($cc_orders as $index=>$cc_order){
		echo "Checking order ".$cc_order['reference']."... ";
		if($cc_order['logisticsStatus'] != 9){
			if(!empty($row['cancelled_at'])){
				echo "logisticsStatus is ".$cc_order['logisticsStatus'].", skipping cin7 id: ".$cc_order['id'].PHP_EOL;
				continue;
			}
		}
		if(empty($cc_order['freightDescription'])){
			echo "Skipping, empty freight description".PHP_EOL;
			continue;
		}
		$order_number = str_ireplace('#sb','',$cc_order['reference']);
		$stmt->execute([$order_number]);
		if($stmt->rowCount() == 0){
			echo "Couldn't find order in DB, must not be Shopify";
			continue;
		}
		$row = $stmt->fetch();
		if(!empty($row['cancelled_at'])){
			echo "Order cancelled in Shopify, skipping cin7 id: ".$cc_order['id'].PHP_EOL;
			continue;
		}
		if(strpos($row['tags'], 'HOLD:') !== false){
			echo "Order held in Shopify, skipping".PHP_EOL;
			continue;
		}
		$cc_order['logisticsStatus'] = 1;
//		$cc_order['branchId'] = calc_branch_id($cc_order);
		$updates[] = $cc_order;
		echo "Added to update queue [".count($updates)."]".PHP_EOL;
		if(count($updates) == $page_size){
			echo "! Queue hit $page_size, sending w/ branch id ".$cc_order['branchId']."... ";
			$res = send_cc_updates($cc, $updates);
			$updates = [];
			echo "Done".PHP_EOL;
			sleep(1);
		}
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
function calc_branch_id($cc_order){
	global $east_zip_prefixes;
	if(empty($cc_order['deliveryPostalCode'])){
		print_r($cc_order);
		die();
	}
	$zip_prefix = substr($cc_order['deliveryPostalCode'], 0, 3);
	if(!in_array($zip_prefix, $east_zip_prefixes)){
		return 3;
	}
	if(count($cc_order['lineItems']) > 1){
		return 3;
	}
	if($cc_order['lineItems'][0]['code'] != '10450506-101'){
		return 3;
	}
	if($cc_order['lineItems'][0]['qty'] != 1){
		return 3;
	}
	return 23755;
}