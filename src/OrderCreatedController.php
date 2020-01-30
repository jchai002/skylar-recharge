<?php

class OrderCreatedController {

	// TODO: Probably GWP state shouldn't be in here, figure how to make more atomic
	public static function are_gwps_valid($order){
		$has_hand_cream = false;
		$has_orly_gwp = false;
		$has_salt_air = false;
		$has_salt_air_gwp = false;
		foreach($order['line_items'] as $line_item){
			if(in_array('Hand Cream', $line_item['product']['tags'])){
				$has_hand_cream = true;
			}
			if($line_item['product_id'] == 4042122756183){
				$has_orly_gwp = true;
			}
			if($line_item['product_id'] == 4348558901335){
				$has_salt_air = true;
			}
			if($line_item['product_id'] == 4391067418711){
				$has_salt_air = true;
			}
			if($line_item['product_id'] == 4396952223831){
				$has_salt_air_gwp = true;
			}
			// Coral hand cream
			if($line_item['product_id'] == 4312664113239 && $order['total_line_items_price'] < 70){
				return false;
			}
			// Travel bag
			if($line_item['product_id'] == 4325189648471 && $order['total_line_items_price'] < 60){
				return false;
			}
			// arrow rollie
			if($line_item['product_id'] == 4345453445207 && $order['total_line_items_price'] < 80){
				return false;
			}
		}
		if(
			($has_orly_gwp && (!$has_hand_cream || $order['shipping_address']['country_code'] != 'US'))
			|| ($has_salt_air_gwp && !$has_salt_air)
		){
			return false;
		}
		return true;
	}

	public static function are_gwps_free($line_items){
		foreach($line_items as $line_item){
			if($line_item['product']['type'] == 'GWP' && $line_item['price'] != $line_item['total_discount']){
				return false;
			}
		}
		return true;
	}

	public static function shipping_looks_wrong($order){
		if(empty($order['discount_codes'])){
			return false;
		}
		switch(strtoupper($order['discount_codes'][0]['code'])){
			default:
				return false;
			case 'JUSTINTIME':
				return $order['shipping_lines'][0]['code'] != 'US Next Day' && $order['shipping_address']['country_code'] == 'US';
				break;
			case 'HAPPYSHIP':
				return $order['shipping_lines'][0]['code'] != 'US 2 Day' && $order['shipping_address']['country_code'] == 'US';
				break;
		}
	}

}