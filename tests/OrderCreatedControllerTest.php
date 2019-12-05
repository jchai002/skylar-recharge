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
				'final_price' => 0,
				'product' => ['type' => 'GWP']
			]])
		);
		// Coral invalid
		$this->assertFalse(
			OrderCreatedController::are_gwps_free([[
				'product_id' => 4312664113239,
				'final_price' => 20,
				'product' => ['type' => 'GWP']
			]])
		);
	}

}