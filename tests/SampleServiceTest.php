<?php

use PHPUnit\Framework\TestCase;

class SampleServiceTest extends TestCase {

	public static function is_first_order(PDO $db, $email, $db_order_id){
		// TODO
	}

	public function test_order_has_sample(){
		$this->assertTrue(
			SampleService::order_has_sample([
				['code' => '10450506-101']
			])
		);
		$this->assertFalse(
			SampleService::order_has_sample([
				['code' => '10450505-112']
			])
		);
	}

	public function test_order_has_salt_air(){
		$this->assertTrue(
			SampleService::order_has_salt_air([
				['code' => '10450504-112']
			])
		);
		$this->assertFalse(
			SampleService::order_has_salt_air([
				['code' => '10450506-101']
			])
		);
	}

	public function test_order_has_sun_shower(){
		$this->assertTrue(
			SampleService::order_has_sun_shower([
				['code' => '10450504-121']
			])
		);
		$this->assertFalse(
			SampleService::order_has_sun_shower([
				['code' => '10450506-101']
			])
		);
	}

}