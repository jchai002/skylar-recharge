<?php
require_once('../includes/config.php');
require_once('../includes/class.RechargeClient.php');


// get $charge from webhook
foreach($charge['line_item'] as $line_item){
	if($line_item['shopify_product_id'] == 738567323735){
		exit;
	}
}

$discount_factors = calculate_discount_factors($charge);
$discount_amount = calculate_discount_amount($charge, $discount_factors);
if(empty($discount_factors)){
	exit;
}

$rc = new RechargeClient();
$code = get_charge_discount_code($rc, $discount_amount);
apply_discount_code($rc, $charge, $code);