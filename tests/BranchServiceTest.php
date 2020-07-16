<?php

use PHPUnit\Framework\TestCase;
use Pseudo\Result;

class BranchServiceTest extends TestCase{

	/**
	 * @var $db Pseudo\Pdo
	 */
	private static $db;

	public static function setUpBeforeClass(): void {
		self::$db = new Pseudo\Pdo();
		self::$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

		self::$db->mock("SELECT v.sku FROM variants v
LEFT JOIN products p ON v.product_id=p.id
LEFT JOIN variant_attribute_codes vac ON vac.variant_id=v.shopify_id
WHERE (vac.format = 'rollie' AND vac.product_type='fragrance')
OR (p.type = 'Scent Club Month' AND p.published_at IS NOT NULL AND p.deleted_at IS NULL)
GROUP BY v.sku;", [
			['sku' => '10213901-109'],
			['sku' => '10213902-110'],
			['sku' => '10213903-111'],
			['sku' => '10213904-112'],
			['sku' => '10213905-113'],
			['sku' => '10213906-114'],
			['sku' => '10213907-115'],
			['sku' => '10213908-116'],
			['sku' => '10213909-117'],
			['sku' => '10213910-118'],
			['sku' => '10213910-119'],
			['sku' => '10213910-120'],
			['sku' => '10213910-121'],
			['sku' => '10213910-122'],
			['sku' => '10213910-123'],
			['sku' => '10213910-124'],
			['sku' => '10213910-125'],
			['sku' => '10450505-102'],
			['sku' => '10450505-103'],
			['sku' => '10450505-104'],
			['sku' => '10450505-105'],
			['sku' => '10450505-106'],
			['sku' => '10450505-107'],
			['sku' => '10450505-112'],
		]);
		BranchService::init(self::$db);
	}

	public function test_calc_branch_id(){
		self::$db->mock("
SELECT csu.available+csu.virtual AS available FROM cin_stock_units csu
LEFT JOIN cin_product_options cpo ON cpo.id=csu.cin_product_option_id
LEFT JOIN cin_products cp ON cp.id=cpo.cin_product_id
LEFT JOIN cin_branches cb ON cb.id=csu.cin_branch_id
WHERE cpo.sku = :sku
AND cin_branch_id = :branch_id;", [['available' => 1432]]);
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
		global $_stmt_cache;
		$query = "
SELECT csu.available+csu.virtual AS available FROM cin_stock_units csu
LEFT JOIN cin_product_options cpo ON cpo.id=csu.cin_product_option_id
LEFT JOIN cin_products cp ON cp.id=cpo.cin_product_id
LEFT JOIN cin_branches cb ON cb.id=csu.cin_branch_id
WHERE cpo.sku = :sku
AND cin_branch_id = :branch_id;";
		$result = new Result([['available' => 100]]);
		$result->setAffectedRowCount(1);
		$_stmt_cache['cin_branch_stock_check'] = self::$db->prepare($query);
		self::$db->mock($query, $result);

		// Isle full size in stock
		$result = new Result([['available' => 100]]);
		$result->setAffectedRowCount(1);
		$_stmt_cache['cin_branch_stock_check']->setResult($result);
		$this->assertTrue(
			BranchService::branch_can_fill_sku(self::$db, 3, '10450504-105'),
			"In stock should return true"
		);
		// Isle candle out of stock
		$result = new Result([['available' => 0]]);
		$result->setAffectedRowCount(1);
		$_stmt_cache['cin_branch_stock_check']->setResult($result);
		$this->assertFalse(
			BranchService::branch_can_fill_sku(self::$db, 3, '11902901-105'),
			"Out of stock should return false"
		);

		// Secondary location sample in stock
		$result = new Result([['available' => 100]]);
		$result->setAffectedRowCount(1);
		$_stmt_cache['cin_branch_stock_check']->setResult($result);
		$this->assertTrue(
			BranchService::branch_can_fill_sku(self::$db, 23755, '10450506-101'),
			"In stock should return true, 2nd location"
		);
		// Secondary location candle out of stock
		$result = new Result([['available' => 0]]);
		$result->setAffectedRowCount(1);
		$_stmt_cache['cin_branch_stock_check']->setResult($result);
		$this->assertFalse(
			BranchService::branch_can_fill_sku(self::$db, 23755, '11902901-105'),
			"Out of stock should return false, 2nd location"
		);

		// Ignore kits
		$this->assertTrue(
			BranchService::branch_can_fill_sku(self::$db, 3, '70331003-100'),
			"Should ignore kits, returning true"
		);

		// Multiple quantity can fulfill
		$result = new Result([['available' => 100]]);
		$result->setAffectedRowCount(1);
		$_stmt_cache['cin_branch_stock_check']->setResult($result);
		$this->assertTrue(
			BranchService::branch_can_fill_sku(self::$db, 3, '10450504-105', 5),
			"Quantity 5 should have been fulfillable"
		);
		// Multiple quantity can't fulfill
		$result = new Result([['available' => 5]]);
		$result->setAffectedRowCount(1);
		$_stmt_cache['cin_branch_stock_check']->setResult($result);
		$this->assertFalse(
			BranchService::branch_can_fill_sku(self::$db, 3, '10450504-105', 10),
			"Quantity 15 shouldn't have been fulfillable"
		);

	}

}