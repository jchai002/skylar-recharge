<?php
require_once(__DIR__.'/../includes/config.php');

$sc = new ShopifyClient();

if(!empty($_REQUEST['id'])){
    $customer = $sc->call('GET', '/admin/customers/'.intval($_REQUEST['id']).'.json');
} else {
	respondOK();
    $data = file_get_contents('php://input');
    $customer = json_decode($data, true);
}
if(empty($customer)){
    die('no data');
}

echo insert_update_customer($db, $customer);