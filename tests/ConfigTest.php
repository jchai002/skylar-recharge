<?php

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase {

	public function test_is_discount_allowed_in_account(){
		include_once(__DIR__.'/../includes/config.php');
		// Test ok code
		$this->assertTrue(is_discount_allowed_in_account('MYPICK20'));
		// Test disallowed code
		$this->assertFalse(is_discount_allowed_in_account('TRYSCENTCLUB'));
		$this->assertFalse(is_discount_allowed_in_account('SURPRISE15'));
		// Test disallowed prefixes
		$this->assertFalse(is_discount_allowed_in_account('RS-20-ABCD'));
		$this->assertFalse(is_discount_allowed_in_account('GS-50-ABCD'));
	}

	public function test_match_email(){
		include_once(__DIR__.'/../includes/config.php');
		$test_emails = [
			'tim@skylar.com',
			'julie@skylar.com',
			'jhoang@avisan.com',
		];
		// Test exact match
		$this->assertTrue(match_email('tim@skylar.com', $test_emails));
		// Test + match
		$this->assertTrue(match_email('julie+123@skylar.com', $test_emails));
		// Test non-match
		$this->assertFalse(match_email('adrian@skylar.com', $test_emails));
	}

}