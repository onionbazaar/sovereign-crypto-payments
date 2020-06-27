<?php

class OBZSSCP_Payment_Repo {
	private $tableName;	

	public function __construct() {
		global $wpdb;
		$this->tableName = $wpdb->prefix . 'obzsscp_payments';
	}

	public function get_is_payment_repo($orderId) {
		global $wpdb;
		$query = "SELECT `order_id` FROM `$this->tableName` WHERE `order_id` = '$orderId'";
		$results = $wpdb->get_results($query);
		foreach ( $results as $result )
		{
			if ( $result->order_id == $orderId ) return true;
		}
		return false;
	}

	public function insert($address, $cryptocurrency, $orderId, $paymentAmount, $status) {
		OBZSSCP_Util::log(__FILE__, __LINE__, 'inserting ' . $address . ' into db as ' . $status . ' with order amount of: ' . $paymentAmount);
		global $wpdb;
		$currentTime = time();
		$query = "INSERT INTO `$this->tableName`
					(`address`,  `cryptocurrency`,  `order_id`, `order_amount`, `status`, `ordered_at`) VALUES
					('$address', '$cryptocurrency', '$orderId', '$paymentAmount', '$status', '$currentTime')";
		$wpdb->query($query);
	}

	public function get_unpaid() {
		global $wpdb;
		$query = "SELECT `address`,
						 `cryptocurrency`,
						 `order_id`,
						 `order_amount`,
						 `status`,
						 `ordered_at`
				  FROM `$this->tableName`
				  WHERE `status` = 'unpaid'";
		$results = $wpdb->get_results($query, ARRAY_A);
		return $results;
	}

	public function get_distinct_unpaid_addresses() {
		global $wpdb;
		$query = "SELECT DISTINCT `address`, `cryptocurrency` FROM `$this->tableName` WHERE `status` = 'unpaid'";
		$results = $wpdb->get_results($query, ARRAY_A);
		return $results;
	}

	public function get_unpaid_for_address($cryptoId, $address) {
		global $wpdb;
		$query = "SELECT `cryptocurrency`,
						 `order_id`,
						 `order_amount`,
						 `status`,
						 `ordered_at`
				  FROM `$this->tableName`
				  WHERE `status` = 'unpaid'
				  AND `address` = '$address'
				  AND `cryptocurrency` = '$cryptoId'";
		$results = $wpdb->get_results($query, ARRAY_A);
		return $results;
	}

	public function set_status($orderId, $orderAmount, $status) {
		global $wpdb;
		OBZSSCP_Util::log(__FILE__, __LINE__, 'updating ' . $orderId . ' to ' . $status);
		$query = "UPDATE `$this->tableName`
				  SET `status` = '$status'
				  WHERE `order_amount` = '$orderAmount'
				  AND `order_id` = '$orderId'";
		$wpdb->query($query);
	}

	public function set_status_electrum($address, $status) {
		global $wpdb;
		$obzsscp_electrum_table = $wpdb->prefix . 'obzsscp_electrum_addresses';
		OBZSSCP_Util::log(__FILE__, __LINE__, 'Updating ' . $address . ' to ' . $status);
		if ( $status == 'ready' ) $obzsscp_order_amount_sql = ", `order_amount` = '0.0'"; else $obzsscp_order_amount_sql = null;
		$query = "UPDATE `$obzsscp_electrum_table` SET `status` = '$status'".$obzsscp_order_amount_sql." WHERE `address` = '$address'";
		$wpdb->query($query);
	}

	public function set_hash($orderId, $orderAmount, $hash) {
		global $wpdb;
		$query = "UPDATE `$this->tableName`
				  SET `tx_hash` = '$hash'
				  WHERE `order_amount` = '$orderAmount'
				  AND `order_id` = '$orderId'";
		$wpdb->query($query);
	}

	public function set_ordered_at($orderId, $orderAmount, $orderedAt) {
		global $wpdb;
		$query = "UPDATE `$this->tableName`
				  SET `ordered_at` = '$orderedAt'
				  WHERE `order_amount` = '$orderAmount'
				  AND `order_id` = '$orderId'";
		$wpdb->query($query);
	}
}

?>
