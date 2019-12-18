<?php

use PHPUnit\Framework\TestCase;

class OrderCreatedControllerTest extends TestCase{

	public function test_are_gwps_valid(){
		include_once(__DIR__.'/../includes/config.php');

		// Coral valid
		$this->assertTrue(
			OrderCreatedController::are_gwps_valid($order = [
				'line_items' => [
					[
						'product_id' => 4312664113239,
						'final_price' => 0,
						'product' => ['tags' => []]
					],
				],
				'total_line_items_price' => 70,
			])
		);
		// coral invalid
		$this->assertFalse(
			OrderCreatedController::are_gwps_valid($order = [
				'line_items' => [
					[
						'product_id' => 4312664113239,
						'final_price' => 0,
						'product' => ['tags' => []]
					],
				],
				'total_line_items_price' => 30,
			])
		);
	}

	public function test_are_gwps_free(){
		include_once(__DIR__.'/../includes/config.php');

		// Coral valid
		$this->assertTrue(
			OrderCreatedController::are_gwps_free([[
				'product_id' => 4312664113239,
				'price' => '16.00',
				'total_discount' => '16.00',
				'product' => ['type' => 'GWP']
			]])
		);
		// Coral invalid
		$this->assertFalse(
			OrderCreatedController::are_gwps_free([[
				'product_id' => 4312664113239,
				'price' => '16.00',
				'total_discount' => '10.00',
				'product' => ['type' => 'GWP']
			]])
		);
	}

	public function test_shipping_looks_wrong(){
		$order = [
			'discount_codes' => [],
			'shipping_lines' => [['code' => 'US Next Day']],
			'shipping_address' => ['country_code' => 'US'],
		];
		// No code is ok
		$this->assertFalse(OrderCreatedController::shipping_looks_wrong($order));

		// JUSTINTIME with next day is ok
		$order['discount_codes'] = [['code' => 'JUSTINTIME']];
		$this->assertFalse(OrderCreatedController::shipping_looks_wrong($order));
		// JUSTINTIME without next day is not ok
		$order['shipping_lines'][0]['code'] = 'US 2 Day';
		$this->assertTrue(OrderCreatedController::shipping_looks_wrong($order));

		// HAPPYSHIP with 2 day is ok
		$order['discount_codes'] = [['code' => 'HAPPYSHIP']];
		$this->assertFalse(OrderCreatedController::shipping_looks_wrong($order));
		// HAPPYSHIP without 2 day is not ok
		$order['shipping_lines'][0]['code'] = 'US Next Day';
		$this->assertTrue(OrderCreatedController::shipping_looks_wrong($order));
	}

}