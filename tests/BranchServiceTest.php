<?php

use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;

// TODO: Use Mock DB results
class BranchServiceTest extends TestCase{

	private static $db;

	public static function setUpBeforeClass(): void {
		$dotenv = new Dotenv(__DIR__.'/..');
		$dotenv->load();
		self::$db = new PDO("mysql:host=".$_ENV['DB_HOST'].";dbname=".$_ENV['DB_NAME'].";charset=UTF8", $_ENV['DB_USER'], $_ENV['DB_PASS']);
		self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		BranchService::init(self::$db);
	}

	public function test_calc_branch_id(){
		// Must have zip code
		$this->assertEquals(-1, BranchService::calc_branch_id(self::$db, [
			'deliveryPostalCode' => false,
		]));

		// Must have location available
		$this->assertEquals(-3, BranchService::calc_branch_id(self::$db, [
			'deliveryPostalCode' => '90094',
			'deliveryCountry' => 'USA',
			'lineItems' => [
				// Isle candle, out of stock
				['code' => '11902901-105', 'qty' => 1],
			]
		]));
	}

	public function test_get_available_locations(){
		// Doesn't ship international from east
		$available_locations = BranchService::get_available_locations([
			'deliveryCountry' => 'FRANCE',
		]);
		$this->assertArrayHasKey(23755, $available_locations);
		$this->assertFalse($available_locations[23755]);

		// Prefers east branch for east zip
		$this->assertEquals([
			23755 => true,
			3 => true,
		], BranchService::get_available_locations([
			'deliveryCountry' => 'USA',
			'deliveryPostalCode' => '00411',
		]));

		// Prefers west branch for west zip
		$this->assertEquals([
			3 => true,
			23755 => true,
		], BranchService::get_available_locations([
			'deliveryCountry' => 'USA',
			'deliveryPostalCode' => '90094',
		]));

	}

	public function test_can_23755_ship_quantity(){
		// Ignore kits
		$this->assertTrue(
			BranchService::can_23755_ship_quantity([
				['code' => '70331003-100', 'qty' => 1]
			])
		);
		// Ignore Sample
		$this->assertTrue(
			BranchService::can_23755_ship_quantity([
				['code' => '99238701-112', 'qty' => 1]
			])
		);

		// Can ship 2 rollies
		$this->assertTrue(
			BranchService::can_23755_ship_quantity([
				['code' => '10213901-109', 'qty' => 1],
				['code' => '10213902-110', 'qty' => 1],
			])
		);

		// Can't ship 2 non-rollies
		$this->assertFalse(
			BranchService::can_23755_ship_quantity([
				['code' => '10450504-105', 'qty' => 1],
				['code' => '11902901-105', 'qty' => 1],
			])
		);

		// Can't ship 2 rollies + 1 non-rollie
		$this->assertFalse(
			BranchService::can_23755_ship_quantity([
				['code' => '10213901-109', 'qty' => 2],
				['code' => '11902901-105', 'qty' => 1],
			])
		);
	}

	public function test_branch_can_fill_sku(){
		// Isle full size in stock
		$this->assertTrue(
			BranchService::branch_can_fill_sku(self::$db, 3, '10450504-105')
		);
		// Isle candle out of stock
		$this->assertFalse(
			BranchService::branch_can_fill_sku(self::$db, 3, '11902901-105')
		);

		// Secondary location sample in stock
		$this->assertTrue(
			BranchService::branch_can_fill_sku(self::$db, 23755, '10450506-101')
		);
		// Secondary location candle out of stock
		$this->assertFalse(
			BranchService::branch_can_fill_sku(self::$db, 23755, '11902901-105')
		);

		// Ignore kits
		$this->assertTrue(
			BranchService::branch_can_fill_sku(self::$db, 3, '70331003-100')
		);

	}

}