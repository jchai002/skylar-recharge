<?php

class SampleService {

	private static $stmt_get_prev_order;

	public static function is_first_order(PDO $db, $email, $db_order_id){
		if(empty(self::$stmt_get_prev_order)){
			self::$stmt_get_prev_order = $db->prepare("SELECT 1 FROM orders WHERE email = :email AND id != :id LIMIT 1");
		}
		self::$stmt_get_prev_order->execute([
			'email' => $email,
			'id' => $db_order_id,
		]);
		return self::$stmt_get_prev_order->rowCount() == 0;
	}

	public static function order_has_sample($line_items){
		return count(array_intersect([
			'70221408-100', // Scent experience
			'10450506-101', // Sample Palette
		], array_column($line_items, 'code'))) > 0;
	}

	public static function order_has_salt_air($line_items){
		return !empty(array_intersect(array_column($line_items, 'code'), [
			'99238701-112', // Peel
			'10450504-112', // full size
			'10450505-112', // rollie
		]));
	}

	public static function order_has_sun_shower($line_items){
		return !empty(array_intersect(array_column($line_items, 'code'), [
			'99238701-121', // Peel
			'10450504-121', // full size
			'10213910-121', // rollie
			'13200311-121', // Hand sani
		]));
	}

	public static function order_needs_peel_salt_air(PDO $db, $cc_order, $db_order_id){
		if(self::order_has_salt_air($cc_order['lineItems'])){
			return false;
		}
		return SampleService::order_has_sample($cc_order['lineItems']) || SampleService::is_first_order($db, $cc_order['email'], $db_order_id);
	}

	public static function order_needs_peel_sun_shower(PDO $db, $cc_order, $db_order_id){
		if(self::order_has_sun_shower($cc_order['lineItems'])){
			return false;
		}
		return true;
		// Phase 2 logic
//		return SampleService::order_has_sample($cc_order['lineItems']) || SampleService::is_first_order($db, $cc_order['email'], $db_order_id);
	}

	public static function add_peel_salt_air(&$cc_order){
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

	public static function add_peel_sun_shower(&$cc_order){
		// Make sure it doesn't already have salt air
		$sort = array_reduce($cc_order['lineItems'], function($carry, $item){
			return $item['sort'] > $carry ? $item['sort'] : $carry;
		}, 1);
		$sort++;
		$cc_order['lineItems'][] = [
			'transactionId' => $cc_order['id'],
			'productId' => 1727,
			'productOptionId' => 1728,
			'sort' => $sort,
			'code' => '99238701-121',
			'name' => 'Scent Peel Back Sun Shower',
			'qty' => 1,
			'styleCode' => '99238701-121',
			'lineComments' => 'Auto-added by API',
		];
		return $cc_order;
	}

}