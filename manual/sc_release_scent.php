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

do {
	// Load upcoming queued charges for may
	$res = $rc->get('/charges', [
		'date_min' => $start_date,
		'date_max' => $end_date,
		'status' => 'QUEUED',
		'limit' => 250,
		'page' => $page,
//		'address_id' => '29919072',
	]);
	$charges = $res['charges'];
	foreach($charges as $charge){
		// Check if the charge is scent club
		$scent_club_item = false;
		foreach($charge['line_items'] as $line_item){
			if(is_scent_club(get_product($db, $line_item['shopify_product_id']))){
				$scent_club_item = $line_item;
				break;
			}
		}
		if(empty($scent_club_item)){
			continue;
		}

		sc_swap_to_monthly($db, $rc, $charge['address_id'], strtotime($charge['scheduled_at']));
		sleep(2);

	}

} while(count($charges) == 250);