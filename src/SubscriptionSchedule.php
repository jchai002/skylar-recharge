<?php

class SubscriptionSchedule {

	private $db;
	private $rc;
	private $rc_customer_id;
	private $max_time;
	private $schedule = [];
	private $orders = [];
	private $subscriptions = [];
	private $charges = [];

	public function __construct(PDO $db, RechargeClient $rc, $rc_customer_id, $max_time = null){
		$this->db = $db;
		$this->rc = $rc;
		$this->rc_customer_id = $rc_customer_id;
		$this->max_time = empty($max_time) ? strtotime('+12 months') : $max_time;
	}

	public function get(){
		if(empty($this->schedule)){
			$this->generate();
		}
		return $this->schedule;
	}

	public function orders($orders_to_set = null){
		if(!is_null($orders_to_set)){
			$this->orders = [];
			foreach($orders_to_set as $order){
				$this->orders[$order['id']] = $this->normalize_order($order);
			}
		}
		return $this->orders;
	}

	public function charges($charges_to_set = null){
		if(!is_null($charges_to_set)){
			$this->charges = [];
			foreach($charges_to_set as $charge){
				$this->charges[$charge['id']] = $this->normalize_charge($charge);
			}
		}
		return $this->charges;
	}

	public function subscriptions($subs_to_set = null){
		if(!is_null($subs_to_set)){
			$this->subscriptions = [];
			foreach($subs_to_set as $sub){
				$this->subscriptions[$sub['id']] = $this->normalize_subscription($sub);
			}
		}
		return $this->subscriptions;
	}

	public function max_time($new_max_time = null){
		if(!is_null($new_max_time) && $this->max_time != $new_max_time){
			$this->max_time = $new_max_time;
			$this->schedule = [];
		}
		return $this->max_time;
	}

	private function load(){
		if(empty($this->subscriptions)){
			$res = $this->rc->get('/subscriptions', [
				'customer_id' => $this->rc_customer_id,
				'status' => 'ACTIVE',
			]);
			if(!empty($res['subscriptions'])){
				$this->subscriptions($res['subscriptions']);
			}
		}
		if(empty($this->orders)){
			$res = $this->rc->get('/orders', [
				'customer_id' => $this->rc_customer_id,
				'status' => 'QUEUED',
			]);
			if(!empty($res['orders'])){
				$this->orders($res['orders']);
			}
		}
		if(empty($this->charges)){
			$res = $this->rc->get('/charges', [
				'customer_id' => $this->rc_customer_id,
				'date_min' => date('Y-m-d'),
			]);
			if(!empty($res['charges'])){
				$this->charges($res['charges']);
			}
		}
	}

	private function generate(){
		$this->load();
		$this->schedule = [];

		foreach($this->orders as $order){
			foreach($order['line_items'] as $item){
				$this->add_item_to_schedule($item);
			}
		}

		foreach($this->charges as $charge){
			if($charge['status'] != 'QUEUED' && $charge['status'] != 'SKIPPED'){
				continue;
			}
			foreach($charge['line_items'] as $item){
				$this->add_item_to_schedule($item);
			}
			if(!empty($charge['discount_codes'])){
				$this->schedule[date('Y-m-d', $charge['scheduled_at_time'])]['addresses'][$charge['address_id']]['discounts'] = $charge['discount_codes'];
			}
		}

		foreach($this->subscriptions as $subscription){
			$next_charge_time = $charge_time = strtotime($subscription['next_charge_scheduled_at']);
			if(empty($charge_time)){
				continue;
			}
			// Iterate through months, adding subscription as sub as individual items to each one
			$subscription_index = 0;
			while($next_charge_time < $this->max_time){
				$item = $subscription;
				$item['scheduled_at'] = date('Y-m-d', $next_charge_time);
				$item['scheduled_at_time'] = $next_charge_time;
				$item['index'] = $subscription_index;
				$this->add_item_to_schedule($item);

				$subscription_index++;
				$next_charge_time = $this->get_subscription_time_by_index($subscription_index, $next_charge_time, $subscription['order_interval_frequency'], $subscription['order_interval_unit'], $subscription['order_interval_index']);
				if($subscription_index > 100){
					throw new Exception('Too many loops');
				}
			}
		}

		return $this->schedule;
	}

	private function add_item_to_schedule($item){
		if(empty($item['scheduled_at_time'])){ // Recharge bug where items come in with 0 time
			return false;
		}
		if($item['scheduled_at_time'] > $this->max_time){
			return false;
		}
		if($item['scheduled_at_time'] < time()){
			return false;
		}

		// Build out array structure
		$date = date('Y-m-d', $item['scheduled_at_time']);
		if(empty($this->schedule[$date])){
			$this->schedule[$date] = [
				'addresses' => [],
				'ship_date_time' => strtotime($date),
			];
		}
		$address_id = $item['address_id'];
		if(empty($this->schedule[$date]['addresses'][$address_id])){
			$this->schedule[$date]['addresses'][$address_id] = [
				'items' => [],
				'charge_id' => null,
				'ship_date_time' => strtotime($date), // compatibility TODO Remove
				'total' => 0,
			];
		}

		// Check if duplicate
		foreach($this->schedule[$date][$address_id]['items'] as $index => $scheduled_item){
			if($scheduled_item['subscription_id'] == $item['subscription_id']){
				// Duplicate, merge in information then skip
				if(!empty($item['skipped'])){
					$scheduled_item['skipped'] = true;
				}
				if(!empty($item['charge_id'])){
					$scheduled_item['charge_id'] = $item['charge_id'];
					$this->schedule[$date][$address_id]['charge_id'] = $item['charge_id'];
				}
				$this->schedule[$date][$address_id]['items'][$index] = $scheduled_item;
				return true;
			}
		}

		// Check if we should swap in SC Monthly
		if(is_scent_club(get_product($this->db, $item['shopify_product_id']))){
			$swap = sc_get_monthly_scent($this->db, $this->schedule[$date]['ship_date_time'], is_admin_address($item['address_id']));
			$item['swap'] = $swap;
			if(!empty($swap)){
				$item['handle'] = $swap['handle'];
				$item['shopify_product_id'] = $swap['shopify_product_id'];
				$item['shopify_variant_id'] = $swap['shopify_variant_id'];
				$item['product_title'] = $swap['product_title'];
				$item['variant_title'] = $swap['variant_title'];
			}
		}

		$this->schedule[$date]['addresses'][$address_id]['items'][] = $item;
		return true;
	}

	private function normalize_order($order){
		$order['next_charge_scheduled_at'] = $order['scheduled_at'];
		$order['scheduled_at_time'] = strtotime($order['scheduled_at']);
		foreach($order['line_items'] as $index => $item){
			$item['id'] = $item['subscription_id'];
			$item['type'] = 'order';
			$item['order_id'] = $order['id'];
			$item['scheduled_at'] = $order['scheduled_at'];
			$item['scheduled_at_time'] = $order['scheduled_at_time'];
			$item['address_id'] = $order['address_id'];
			$order['line_items'][$index] = $item;
		}
		return $order;
	}

	private function normalize_charge($charge){
		$charge['next_charge_scheduled_at'] = $charge['scheduled_at'];
		$charge['scheduled_at_time'] = strtotime($charge['scheduled_at']);
		foreach($charge['line_items'] as $index => $item){
			$item['id'] = $item['subscription_id'];
			$item['type'] = 'charge';
			$item['charge_id'] = $charge['id'];
			$item['scheduled_at'] = $charge['scheduled_at'];
			$item['scheduled_at_time'] = $charge['scheduled_at_time'];
			$item['address_id'] = $charge['address_id'];
			$item['skipped'] = $charge['status'] == 'SKIPPED';

			if(empty($item['product_title']) && !empty($item['title'])){
				$item['product_title'] = $item['title'];
			}

			$charge['line_items'][$index] = $item;
		}
		return $charge;
	}

	private function normalize_subscription($subscription){
		$subscription['subscription_id'] = $subscription['id'];
		$subscription['type'] = 'subscription';
		$subscription['scheduled_at'] = $subscription['next_charge_scheduled_at'];
		$subscription['scheduled_at_time'] = strtotime($subscription['scheduled_at']);
		if(!empty($subscription['order_day_of_month'])){
			$subscription['order_interval_index'] = $subscription['order_day_of_month'];
		} else if(!empty($subscription['order_day_of_week'])){
			$subscription['order_interval_index'] = $subscription['order_day_of_week'];
		} else {
			$subscription['order_interval_index'] = false;
		}

		return $subscription;
	}

	private function get_subscription_time_by_index($index, $start_time, $order_interval_frequency, $order_interval_unit, $order_interval_index = false){
		if($order_interval_unit == 'month'){
			// PHP doesn't count months well, do it manually
			$date_year = date('Y', $start_time);
			$date_month = date('m', $start_time);
			$date_day = date('d', $start_time);
			$date_month += $order_interval_frequency*$index;
			while($date_month > 12){
				$date_month -= 12;
				$date_year += 1;
			}
			$max_day = date('T', implode('-',[$date_year,$date_month,'01']));
			if(!empty($order_interval_index)){
				$date_day = $order_interval_index;
			}
			if($date_day > $max_day){
				$date_day = $max_day;
			}
			$next_time = strtotime(implode('-', [$date_year, $date_month, $date_day]));
		} else { // week
			$next_time = strtotime('+'.(7*$index).' days', $start_time);
			if($order_interval_index !== false){
				$next_day_of_week = date('N', $next_time);
				$order_interval_index++; // Recharge 0 = monday, php 1 = monday
				$offset = $order_interval_index - $next_day_of_week;
				if($offset != 0){
					$next_time = strtotime(($offset > 0 ? '+' : '-').$offset.' days', $next_time);
				}
			}
		}
		return $next_time;
	}

}