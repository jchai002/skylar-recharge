<?php

class ScentClubSchedule {

	public static function input_to_month($input){
		if(is_numeric($input) && $input <= 12){
			return date("Y-$input-01");
		}
		if(is_numeric($input)){
			return date('Y-m-01',$input);
		}
		return date('Y-m-01',strtotime($input));
	}

	public static function get_ship_date(PDO $db, $month){
		// Get it from monthly scent
		// If it's not there, calculate, save to db, save to monthly scent cache
		return self::calculate_ship_date($month);
	}

	public static function get_public_launch(PDO $db, $month){
		return self::calculate_ship_date($month);
	}

	public static function get_member_launch(PDO $db, $month){
		return self::calculate_ship_date($month);
	}

	public static function calculate_ship_date($month){
		return offset_date_skip_weekend(strtotime(self::input_to_month($month)));
	}

	public static function calculate_public_launch($month){
		return offset_date_skip_weekend(strtotime('-7 days', self::calculate_ship_date($month)), true);
	}

	public static function calculate_member_launch($month){
		return offset_date_skip_weekend(strtotime('-4 days', self::calculate_public_launch($month)), true);
	}

}