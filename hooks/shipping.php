<?php

require_once(__DIR__.'/../includes/config.php');
require_once(__DIR__.'/../includes/class.ShopifyClient.php');

$sc = new ShopifyClient();

$data .= file_get_contents('php://input');
$data = json_decode($data, true);

$rate = $data['rate'];

$headers = getallheaders();
$shop_url = $headers['X-Shopify-Shop-Domain'];
if(empty($shop_url)){
	die();
}
$is_test = false;
foreach($rate['items'] as $item){
	if(empty($item['properties'])){
		continue;
	}
	foreach($item['properties'] as $key=>$value){
		if($key == 'test' && $value == 1){
			$is_test = true;
			break 2;
		}
	}
}
log_event($db, 'SHIPPING_RATES', '', 'REQUESTED', json_encode($rate), json_encode($headers));
if(!$is_test){
	echo json_encode(["rates"=>$_RATES]);
	die();
}

$total_weight = array_sum(array_column($rate['items'],'grams'))/28.35; // Grams to ounces
$total_price = array_sum(array_column($rate['items'], 'price'))/100;

$stmt = $db->query("SELECT sku FROM variants WHERE shopify_id IN(".implode(',',array_column($ids_by_scent, 'variant')).")");
$fullsize_skus = $stmt->fetchAll(PDO::FETCH_COLUMN);
$has_fullsize = !empty(array_intersect(array_column(['items'], 'sku'), $fullsize_skus));

$stmt = $db->query("SELECT DISTINCT sku FROM sc_product_info");
$sc_skus = $stmt->fetchAll(PDO::FETCH_COLUMN);
$has_sc = !empty(array_intersect(array_column(['items'], 'sku'), $sc_skus));

$_RATES = [];
switch($rate['destination']['country']){
	case 'US':
		if($has_sc){
			if($total_weight <= 15){
				$_RATES[] = [
					'service_name' => 'Standard Shipping (3-7 business days)',
					'service_code' => 'DHL Ground',
					'total_price' => 0,
					'description' => 'Free for Scent Club Members!',
					'currency' => 'USD',
				];
			} else {
				$_RATES[] = [
					'service_name' => 'Standard Shipping (3-7 business days)',
					'service_code' => 'FedEx SmartPost',
					'total_price' => 0,
					'description' => 'Free for Scent Club Members!',
					'currency' => 'USD',
				];
			}
		} else {
			if($total_price >= 40){
				if($total_weight <= 15){
					$_RATES[] = [
						'service_name' => 'Free Standard Shipping (3-7 business days)',
						'service_code' => 'DHL Ground',
						'total_price' => 0,
						'description' => '',
						'currency' => 'USD',
					];
				} else {
					$_RATES[] = [
						'service_name' => 'Free Standard Shipping (3-7 business days)',
						'service_code' => 'FedEx SmartPost',
						'total_price' => 0,
						'description' => '',
						'currency' => 'USD',
					];
				}
			} else {
				if($total_weight <= 15){
					$_RATES[] = [
						'service_name' => 'Standard Shipping (3-7 business days)',
						'service_code' => 'DHL Ground',
						'total_price' => 499,
						'description' => '',
						'currency' => 'USD',
					];
				} else {
					$_RATES[] = [
						'service_name' => 'Standard Shipping (3-7 business days)',
						'service_code' => 'FedEx SmartPost',
						'total_price' => 499,
						'description' => '',
						'currency' => 'USD',
					];
				}
			}
		}
		$_RATES[] = [
			'service_name' => '2-Day Shipping (2 business days)',
			'service_code' => 'FedEx 2-day',
			'total_price' => 1500,
			'description' => 'Order must be placed before noon PST Monday-Friday',
			'currency' => 'USD',
		];
		if(!in_array($rate['destination']['province'], ['HI', 'AK', 'AS', 'FM', 'GU', 'MH', 'MP', 'PW', 'PR', 'VI', 'AE', 'AA', 'AP'])){ // Exclude outside lower 48
			$_RATES[] = [
				'service_name' => 'Next Day Shipping (1 business day)',
				'service_code' => 'FedEx next day',
				'total_price' => 2500,
				'description' => 'Order must be placed before noon PST Monday-Friday. Excludes AK and HI',
				'currency' => 'USD',
			];
		}
		break;
	case 'CA':
		if($has_fullsize){
			$_RATES[] = [
				'service_name' => 'Expedited (3-5 business days)',
				'service_code' => 'UPS Standard to Canada',
				'total_price' => 2500,
				'description' => 'Duties and taxes are not included - All prices are in USD',
				'currency' => 'USD',
			];
		} else {
			/*
			if($total_weight <= 8){
				$_RATES[] = [
					'service_name' => 'Standard (7-14 days)',
					'service_code' => 'USPS FC International',
					'total_price' => 1000,
					'description' => 'Duties and taxes are not included - All prices are in USD',
					'currency' => 'USD',
				];
			}
			*/
			$_RATES[] = [
				'service_name' => 'Expedited (3-5 days)',
				'service_code' => 'DHL WW Express',
				'total_price' => 2000,
				'description' => 'Duties and taxes are not included - All prices are in USD',
				'currency' => 'USD',
			];
		}
		break;
	default: // Other international
		if($has_fullsize){
			$_RATES[] = [
				'service_name' => 'Expedited (3-5 business days) - Includes $23 Intl Airmail Surcharge',
				'service_code' => 'DHL WW Express',
				'total_price' => 2500,
				'description' => 'Duties and taxes are not included - All prices are in USD',
				'currency' => 'USD',
			];
		} else {
			/*
			if($total_weight <= 8){
				$_RATES[] = [
					'service_name' => 'Standard (7-14 days)',
					'service_code' => 'USPS FC International',
					'total_price' => 1200,
					'description' => 'Duties and taxes are not included - All prices are in USD',
					'currency' => 'USD',
				];
			}
			*/
			$_RATES[] = [
				'service_name' => 'Expedited (3-5 days)',
				'service_code' => 'DHL WW Express',
				'total_price' => 2000,
				'description' => 'Duties and taxes are not included - All prices are in USD',
				'currency' => 'USD',
			];
		}
		break;
}

if($is_test){
	foreach($_RATES as $index=>$v){
		$_RATES[$index]['service_name'] = '[TEST] '.$_RATES[$index]['service_name'];
	}
}

echo json_encode(["rates"=>$_RATES]);

/*
 * EXAMPLE:{
  "rate": {
    "origin": {
      "country": "CA",
      "postal_code": "K2P1L4",
      "province": "ON",
      "city": "Ottawa",
      "name": null,
      "address1": "150 Elgin St.",
      "address2": "",
      "address3": null,
      "phone": "16135551212",
      "fax": null,
      "email": null,
      "address_type": null,
      "company_name": "Jamie D's Emporium"
    },
    "destination": {
      "country": "CA",
      "postal_code": "K1M1M4",
      "province": "ON",
      "city": "Ottawa",
      "name": "Bob Norman",
      "address1": "24 Sussex Dr.",
      "address2": "",
      "address3": null,
      "phone": null,
      "fax": null,
      "email": null,
      "address_type": null,
      "company_name": null
    },
    "items": [{
      "name": "Short Sleeve T-Shirt",
      "sku": "",
      "quantity": 1,
      "grams": 1000,
      "price": 1999,
      "vendor": "Jamie D's Emporium",
      "requires_shipping": true,
      "taxable": true,
      "fulfillment_service": "manual",
      "properties": null,
      "product_id": 48447225880,
      "variant_id": 258644705304
    }],
    "currency": "USD",
    "locale": "en"
  }
}
 *
 */