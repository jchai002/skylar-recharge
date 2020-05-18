<?php

class SubscriptionSchedule {

	public $hidden_subscription_ids = [];
	public $is_alias = false;

	private $db;
	private $rc;
	private $rc_customer_id;
	private $max_time;
	private $min_time;
	private $schedule = [];
	private $metadata = [];
	private $orders = [];
	private $subscriptions = [];
	private $onetimes = [];
	private $charges = [];
	private $prev_charges = [];

	public function __construct(PDO $db, RechargeClient $rc, $rc_customer_id, $max_time = null, $min_time = null){
		$this->db = $db;
		$this->rc = $rc;
		$this->rc_customer_id = $rc_customer_id;
		$this->max_time = $max_time ?? strtotime('+12 months');
		$this->min_time = $min_time ?? strtotime(date('Y-m-d'));
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

	public function prev_charges($charges_to_set = null){
		if(!is_null($charges_to_set)){
			$this->prev_charges = [];
			foreach($charges_to_set as $charge){
				if(!in_array($charge['status'], ['SUCCESS', 'REFUNDED', 'PARTIALLY_REFUNDED'])){
					continue;
				}
				$this->prev_charges[$charge['id']] = $this->normalize_charge($charge);
				foreach($charge['line_items'] as $line_item){
					if(empty($this->subscriptions[$line_item['subscription_id']])){
						continue;
					}
					// If SC gift checkout but start date is not checkout date, nothing shipped to don't count it
					$this->prev_charges[$charge['id']]['debug'] = [
						$charge['type'],
						get_oli_attribute($line_item, '_subscription_month'),
						$this->prev_charges[$charge['id']]['scheduled_at_time'],
						date('Y-m', $this->prev_charges[$charge['id']]['scheduled_at_time']),
					];
					if($charge['type'] == 'CHECKOUT' && get_oli_attribute($line_item, '_subscription_month') != date('Y-m', $this->prev_charges[$charge['id']]['scheduled_at_time'])){
						continue;
					}
					$this->subscriptions[$line_item['subscription_id']]['previous_charge_count']++;
				}
			}
		}
		return $this->prev_charges;
	}

	public function subscriptions($subs_to_set = null){
		if(!is_null($subs_to_set)){
			$this->subscriptions = [];
			foreach($subs_to_set as $sub){
				if(empty($sub['next_charge_scheduled_at'])){
					continue;
				}
				if(
					!$this->is_alias
					&& is_scent_club_gift(get_product($this->db, $sub['shopify_product_id']))
					&& empty(get_oli_attribute($sub, '_show_in_account'))
				){
					continue;
				}
				$this->subscriptions[$sub['id']] = $this->normalize_subscription($sub);
			}
		}
		return $this->subscriptions;
	}

	public function onetimes($onetimes_to_set = null){
		if(!is_null($onetimes_to_set)){
			$this->onetimes = [];
			foreach($onetimes_to_set as $onetime){
				if($onetime['status'] != 'ONETIME'){
					continue;
				}
				if(empty($onetime['next_charge_scheduled_at'])){
					continue;
				}
				$this->onetimes[$onetime['id']] = $this->normalize_onetime($onetime);
			}
		}
		return $this->onetimes;
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
		if(empty($this->onetimes)){
			$res = $this->rc->get('/onetimes', [
				'customer_id' => $this->rc_customer_id,
			]);
			if(!empty($res['onetimes'])){
				$this->onetimes($res['onetimes']);
			}
		}
		if(empty($this->orders)){
			$res = $this->rc->get('/orders', [
				'customer_id' => $this->rc_customer_id,
			]);
			if(!empty($res['orders'])){
				$this->orders($res['orders']);
			}
		}
		if(empty($this->charges)){
			$charges = [];
			$res = $this->rc->get('/charges', [
				'customer_id' => $this->rc_customer_id,
				'date_min' => date('Y-m-d', strtotime('-1 day')),
//				'date_min' => date('Y-m-d'),
				'status' => 'QUEUED',
			]);
			if(!empty($res['charges'])){
				$charges = $res['charges'];
			}
			$res = $this->rc->get('/charges', [
				'customer_id' => $this->rc_customer_id,
				'date_min' => date('Y-m-d', strtotime('-1 day')),
//				'date_min' => date('Y-m-d'),
				'status' => 'SKIPPED',
			]);
			if(!empty($res['charges'])){
				$charges = array_merge($charges, $res['charges']);
			}
			$this->charges($charges);
		}
		if($this->is_alias && empty($this->prev_charges)){
			$charges = [];
			$res = $this->rc->get('/charges', [
				'customer_id' => $this->rc_customer_id,
				'date_max' => date('Y-m-d', strtotime('+1 day')),
				'limit' => 250,
			]);
			if(!empty($res['charges'])){
				$charges = $res['charges'];
			}
			$this->prev_charges($charges);
		}
	}

	private function generate(){
		$this->load();
		$this->schedule = [];
		$this->metadata = [];

		foreach($this->orders as $order){
			if($order['status'] != 'QUEUED' && $order['status'] != 'SKIPPED' || $order['scheduled_at_time'] > $this->max_time || $order['scheduled_at_time'] < $this->min_time){
				continue;
			}
			foreach($order['line_items'] as $item){
				$this->add_item_to_schedule($item);
			}
		}

		foreach($this->charges as $charge){
			if($charge['status'] != 'QUEUED' && $charge['status'] != 'SKIPPED' || $charge['scheduled_at_time'] > $this->max_time || $charge['scheduled_at_time'] < $this->min_time){
				continue;
			}
			foreach($charge['line_items'] as $item){
				$this->add_item_to_schedule($item);
			}
			if(!empty($charge['discount_codes'])){
				$this->schedule[date('Y-m-d', $charge['scheduled_at_time'])]['addresses'][$charge['address_id']]['discounts'] = $charge['discount_codes'];
			}
		}

		foreach($this->onetimes as $onetime){
			if($onetime['scheduled_at_time'] > $this->max_time || $onetime['scheduled_at_time'] < $this->min_time){
				continue;
			}
			$this->add_item_to_schedule($onetime);
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
				$item['index'] = $subscription_index + $item['previous_charge_count'];
				if(!empty($item['expire_after_specific_number_of_charges']) && $item['index'] >= $item['expire_after_specific_number_of_charges']){
					break;
				}
				$this->add_item_to_schedule($item);

				$subscription_index++;
				$next_charge_time = self::get_subscription_time_by_index($subscription_index, $charge_time, $subscription['order_interval_frequency'], $subscription['order_interval_unit'], $subscription['order_interval_index']);
				if($subscription_index > 100){
					if(ENV_TYPE == 'LIVE'){
						break;
					}
					throw new Exception('Too many loops');
				}
			}

			if(!is_scent_club(get_product($this->db, $subscription['shopify_product_id']))){
				continue;
			}
			// Show skipped SC subscriptions (iterate backwards)
			$subscription_index = -1;
			$next_charge_time = self::get_subscription_time_by_index($subscription_index, $charge_time, $subscription['order_interval_frequency'], $subscription['order_interval_unit'], $subscription['order_interval_index']);
			$blackout_date_parts = explode('-', date('Y-m'), get_last_month($next_charge_time));
			while($next_charge_time >= $this->min_time){
				// If today, skip
				if(date('Y-m-d') == date('Y-m-d', $next_charge_time)){
					$subscription_index--;
					$next_charge_time = self::get_subscription_time_by_index($subscription_index, $charge_time, $subscription['order_interval_frequency'], $subscription['order_interval_unit'], $subscription['order_interval_index']);
					continue;
				}

				// Check if other scent club is in this month already
				$next_charge_date_parts = explode('-', date('Y-m-d', $next_charge_time));

				// Need to check historical orders
				foreach($this->orders as $order){
					$ship_date_parts = explode('-', date('Y-m', strtotime($order['scheduled_at'])));

					// Check if customer was in blackout
					if($subscription_index == -1 && $ship_date_parts[0] == $blackout_date_parts[0] && $ship_date_parts[1] == $blackout_date_parts[1]){
						$monthly_scent = sc_get_monthly_scent($this->db, $next_charge_time);
						if(!empty($monthly_scent)){
							foreach($order['line_items'] as $order_item){
								// Blackout will always be SC container with "this" month's sku
								if(is_scent_club(get_product($this->db, $order_item['shopify_product_id'])) && $order_item['sku'] == $monthly_scent['sku']){
									// In blackout means we can stop rewinding, as no SC before that
									break 3;
								}
							}
						}
					}

					if($ship_date_parts[0] != $next_charge_date_parts[0] || $ship_date_parts[1] != $next_charge_date_parts[1]){
						// Month or year doesn't match
						continue;
					}
					foreach($order['line_items'] as $order_item){
						if(is_scent_club_any(get_product($this->db, $order_item['shopify_product_id']))){
							$subscription_index--;
							$next_charge_time = self::get_subscription_time_by_index($subscription_index, $charge_time, $subscription['order_interval_frequency'], $subscription['order_interval_unit'], $subscription['order_interval_index']);
							continue 3;
						}
					}
				}

				// Check future as well
				foreach($this->schedule as $ship_date => $shipment_list){
					$ship_date_parts = explode('-', $ship_date);
					if($ship_date_parts[0] != $next_charge_date_parts[0] || $ship_date_parts[1] != $next_charge_date_parts[1]){
						// Month or year doesn't match
						continue;
					}
					foreach($shipment_list['addresses'] as $shipments){
						foreach($shipments['items'] as $shipment_item){
							if(
								!empty($shipment_item['properties']['_swap'])
								&& $shipment_item['properties']['_swap'] == $subscription['id']
							){
								$subscription_index--;
								$next_charge_time = self::get_subscription_time_by_index($subscription_index, $charge_time, $subscription['order_interval_frequency'], $subscription['order_interval_unit'], $subscription['order_interval_index']);
								continue 4;
							}
						}
					}
				}

				$item = $subscription;
				$item['scheduled_at'] = date('Y-m-d', $next_charge_time);
				$item['scheduled_at_time'] = $next_charge_time;
				$item['index'] = $subscription_index + $item['previous_charge_count'];
				$item['skipped'] = true;
				$item['skipped_via_iteration'] = true;
				$this->add_item_to_schedule($item);

				$subscription_index--;
				$next_charge_time = self::get_subscription_time_by_index($subscription_index, $charge_time, $subscription['order_interval_frequency'], $subscription['order_interval_unit'], $subscription['order_interval_index']);
				if(abs($subscription_index) > 100){
					if(ENV_TYPE == 'LIVE'){
						break;
					}
					break;
					throw new Exception('Too many loops');
				}
			}

		}

		$this->schedule = $this->sort($this->schedule);

		return $this->schedule;
	}

	private function sort($schedule){
		foreach($schedule as $date => $shipment_list){
			foreach($shipment_list['addresses'] as $address_id => $shipment){
				if(empty($shipment['items'])){
					continue;
				}
				usort($shipment['items'], function($a, $b){
					if($a['is_sc_any'] != $b['is_sc_any']){
						return $a['is_sc_any'] ? -1 : 1;
					}
					if($a['is_ac_followup'] != $b['is_ac_followup']){
						return $a['is_ac_followup'] ? -1 : 1;
					}
					return 0;
				});
				$schedule[$date]['addresses'][$address_id]['items'] = $shipment['items'];
			}
		}
		ksort($schedule);
		usort($schedule, function($a, $b){
			if(empty($a['has_ac_pending']) && empty($b['has_ac_pending'])){
				return 0;
			}
			if($a['has_ac_pending'] != $b['has_ac_pending']){
				return $a['has_ac_pending'] ? -1 : 1;
			}
			return 0;
		});
		return $schedule;
	}

	private function add_item_to_schedule($item){
		if(empty($item['scheduled_at_time'])){ // Recharge bug where items come in with 0 time
			return false;
		}
		if($item['scheduled_at_time'] > $this->max_time){
			return false;
		}
		if($item['scheduled_at_time'] < $this->min_time){
			return false;
		}
		if(in_array($item['subscription_id'], $this->hidden_subscription_ids)){
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
				'discounts' => [],
				'total' => 0,
			];
		}

		if(!empty($item['charge_id'])){
			$this->schedule[$date]['addresses'][$address_id]['charge_id'] = $item['charge_id'];
		}
		$this->schedule[$date]['has_ac_followup'] = $item['is_ac_followup'];
		$this->schedule[$date]['has_ac_pending'] = $item['is_ac_followup'] && empty($item['ac_delivered']) && empty($item['ac_pushed_up']);

		// Check if duplicate
		foreach($this->schedule[$date]['addresses'][$address_id]['items'] as $index => $scheduled_item){
			if($scheduled_item['subscription_id'] == $item['subscription_id']){
				// Duplicate, merge in information then skip
				$scheduled_item['types'][] = $item['type'];
				$scheduled_item[$item['type']] = $item;
				if(!empty($item['skipped'])){
					$scheduled_item['skipped'] = true;
				}
				foreach([
					'charge_id',
					'order_day_of_month',
					'order_interval_frequency',
					'order_interval_unit',
					'index',
					'expire_after_specific_number_of_charges',
				] as $duplicate_key){
					if(!empty($item[$duplicate_key])){
						$scheduled_item[$duplicate_key] = $item[$duplicate_key];
					}
				}
				$this->schedule[$date]['addresses'][$address_id]['items'][$index] = $scheduled_item;
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
		// Check if we should swap in container
		$item['notes'] = [
			'admin_address' => is_admin_address($item['address_id']),
			'sc_monthly' => is_scent_club_month(get_product($this->db, $item['shopify_product_id'])),
		];
		if(!is_admin_address($item['address_id']) && is_scent_club_month(get_product($this->db, $item['shopify_product_id']))){
			$variant = get_variant($this->db, $item['shopify_variant_id']);
			$stmt = $this->db->prepare("SELECT * FROM sc_product_info WHERE variant_id=?");
			$stmt->execute([
				$variant['id'],
			]);
			$row = $stmt->fetch();
			$item['notes']['row'] = $row;
			if(strtotime($row['member_launch']) > time()){
				$item['swap'] = $item;
				if(!empty($swap)){
					$item['handle'] = 'scent-club';
					$item['shopify_product_id'] = 2005573795927;
					$item['shopify_variant_id'] = 19787922014295;
					$item['product_title'] = 'Skylar Scent Club';
					$item['variant_title'] = '';
				}
			}
		}

		$this->schedule[$date]['addresses'][$address_id]['items'][] = $item;
		return true;
	}

	private function normalize_item($item){
		// Explode properties into associative format
		if(!empty($item['properties']) && array_keys($item['properties'])[0] == 0){
			$properties = [];
			foreach($item['properties'] as $property){
				$properties[$property['name']] = $property['value'];
			}
			$item['properties'] = $properties;
		}

		// If it's swapped in (e.g. SC month) use the order interval property from the original item
		if(!empty($item['properties']['_swap']) && !empty($this->subscriptions[$item['properties']['_swap']]) && !empty($this->subscriptions[$item['properties']['_swap']]['order_interval_frequency'])){
			$item['order_interval_frequency'] = $this->subscriptions[$item['properties']['_swap']]['order_interval_frequency'];
		}
		$item['index'] = $item['index'] ?? 0;
		$item['types'] = $item['types'] ?? [$item['type']];
		$item['skipped'] = $item['skipped'] ?? false;
		$item['is_sc_any'] = is_scent_club_any(get_product($this->db, $item['shopify_product_id']));
		$item['is_ac_followup'] = is_ac_followup_lineitem($item);
		$item['ac_delivered'] = is_ac_delivered($item);
		$item['ac_pushed_back'] = is_ac_pushed_back($item);
		$item['ac_pushed_up'] = is_ac_pushed_up($item);
		return $item;
	}

	private function normalize_order($order){
		$order['next_charge_scheduled_at'] = $order['scheduled_at'];
		$order['scheduled_at_time'] = strtotime($order['scheduled_at']);
		foreach($order['line_items'] as $index => $item){
			if(
				!$this->is_alias
				&& is_scent_club_gift(get_product($this->db, $item['shopify_product_id']))
				&& empty(get_oli_attribute($item, '_show_in_account'))
			){
				continue;
			}
			$item['id'] = $item['subscription_id'];
			$item['type'] = 'order';
			$item['order_id'] = $order['id'];
			$item['scheduled_at'] = $order['scheduled_at'];
			$item['scheduled_at_time'] = $order['scheduled_at_time'];
			$item['address_id'] = $order['address_id'];
			$order['line_items'][$index] = $this->normalize_item($item);
		}
		return $order;
	}

	private function normalize_charge($charge){
		$charge['next_charge_scheduled_at'] = $charge['scheduled_at'];
		$charge['scheduled_at_time'] = strtotime($charge['scheduled_at']);
		foreach($charge['line_items'] as $index => $item){
			if(
				!$this->is_alias
				&& is_scent_club_gift(get_product($this->db, $item['shopify_product_id']))
				&& empty(get_oli_attribute($item, '_show_in_account'))
			){
				continue;
			}
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

			$charge['line_items'][$index] = $this->normalize_item($item);
		}
		// RC doesn't give us applied discount value, so we need to calculate it :(
		if(!empty($charge['discount_codes'])){
			foreach($charge['discount_codes'] as $index => $discount){
				// Load all info on the discount
				$stmt = $this->db->prepare("SELECT * FROM rc_discounts WHERE code=?");
				$stmt->execute([
					$discount['code'],
				]);
				if($stmt->rowCount() != 0){
					$db_discount = $stmt->fetch();
				} else {
					$res = $this->rc->get('/discounts', ['discount_code' => $discount['code']]);
					if(!empty($res[0]['discount'])){
						$db_discount = $res[0]['discount'];
						insert_update_rc_discount($this->db, $db_discount);
					}
				}
				if(empty($db_discount)){
					continue;
				}
				$discount['details'] = $db_discount;
				// If no applies to resource, just leave as is
				if(empty($db_discount['applies_to_resource'])){
					$discount['applied_amount'] = $discount['amount'];
				} else {
					// If applies to product, filter lines to the product it applies to
					if($db_discount['applies_to_resource'] == 'shopify_product'){
						$applies_to_lines = array_filter($charge['line_items'], function($item) use($db_discount) {
							return $item['shopify_product_id'] == $db_discount['applies_to_id'];
						});
						$discount['applies_to_ids'] = [$db_discount['applies_to_id']];
					} else if($db_discount['applies_to_resource'] == 'shopify_collection_id'){
						// If if applies to a collection, filter lines to the products in that collection
						$stmt = $this->db->prepare("SELECT p.shopify_id FROM collections c
							LEFT JOIN collection_products cp ON c.id=cp.collection_id
							LEFT JOIN products p ON cp.product_id=p.id
							WHERE c.shopify_id=?");
						$stmt->execute([
							$db_discount['applies_to_id'],
						]);
						$applies_to_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
						$discount['applies_to_ids'] = $applies_to_ids;
						$applies_to_lines = array_filter($charge['line_items'], function($item) use($applies_to_ids) {
							return in_array($item['shopify_product_id'], $applies_to_ids);
						});
					}
					$discount['applies_to_lines'] = $applies_to_lines ?? [];
					if(empty($applies_to_lines)){
						$discount['applied_amount'] = 0;
					} else {
						// Now that we have the lines that the discount applies to, calculate the amount of the discount
						$total_lines_price = array_sum(array_column($applies_to_lines, 'price'));
						if($db_discount['type'] == 'fixed_amount'){
							$discount['applied_amount'] = $total_lines_price < $discount['amount'] ? $total_lines_price : $discount['amount'];
						} else if($db_discount['type'] == 'percentage'){
							$discount['applied_amount'] = $total_lines_price * ($discount['amount']/100);
						}
					}
				}
				$charge['discount_codes'][$index] = $discount;
			}
		}
		return $charge;
	}

	private function normalize_subscription($subscription){
		$subscription['subscription_id'] = $subscription['id'];
		$subscription['type'] = 'subscription';
		$subscription['scheduled_at'] = $subscription['next_charge_scheduled_at'];
		$subscription['scheduled_at_time'] = strtotime($subscription['scheduled_at']);
		$subscription['previous_charge_count'] = 0;
		if($subscription['shopify_product_id'] == 4313965625431 && $subscription['expire_after_specific_number_of_charges']%3 != 0){ // Scent club ships now TODO has to be a better way to do this
			$subscription['previous_charge_count']++;
			$subscription['expire_after_specific_number_of_charges']++;
		}
		if(!empty($subscription['order_day_of_month'])){
			$subscription['order_interval_index'] = $subscription['order_day_of_month'];
		} else if(!empty($subscription['order_day_of_week'])){
			$subscription['order_interval_index'] = $subscription['order_day_of_week'];
		} else {
			$subscription['order_interval_index'] = false;
		}

		return $this->normalize_item($subscription);
	}

	private function normalize_onetime($onetime){
		$onetime['subscription_id'] = $onetime['id'];
		$onetime['type'] = 'onetime';
		$onetime['scheduled_at'] = $onetime['next_charge_scheduled_at'];
		$onetime['scheduled_at_time'] = strtotime($onetime['scheduled_at']);

		return $this->normalize_item($onetime);
	}

	public static function get_subscription_time_by_index($index, $start_time, $order_interval_frequency, $order_interval_unit, $order_interval_index = false){
		if($order_interval_unit == 'month'){
			// PHP doesn't count months well, do it manually
			$date_year = date('Y', $start_time);
			$date_month = date('m', $start_time);
			$date_day = date('d', $start_time);
			$date_month += $order_interval_frequency*$index;
			while($date_month < 1){
				$date_month += 12;
				$date_year -= 1;
			}
			while($date_month > 12){
				$date_month -= 12;
				$date_year += 1;
			}
			$max_day = date('t', strtotime("$date_year-$date_month-01"));
			if(!empty($order_interval_index)){
				$date_day = $order_interval_index;
			}
			if($date_day > $max_day){
				$date_day = $max_day;
			}
			$next_time = strtotime("$date_year-$date_month-$date_day");
		} else { // week
			$next_time = strtotime(($index >= 0 ? '+ ' : '').(7*$index).' days', $start_time);
			if($order_interval_index !== false){
				$next_day_of_week = date('N', $next_time);
				$order_interval_index++; // Recharge 0 = monday, php 1 = monday
				$offset = $order_interval_index - $next_day_of_week;
				if($offset != 0){
					$next_time = strtotime(($offset > 0 ? '+' : '-').$offset.' days', $next_time);
				}
			}
		}
		return offset_date_skip_weekend($next_time);
	}

}