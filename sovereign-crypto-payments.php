<?php
/*
Plugin Name: Sovereign Crypto Payments
Plugin URI: https://wordpress.org/plugins/sovereign-crypto-payments/
Description: Cryptocurrency Payment Gateway for WooCommerce.
Version: 1.0.3
Author: OnionBazaar
Author URI: https://onionbazaar.org
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: sovereign-crypto-payments
*/

function obzsscp_load_plugin_textdomain() {
    load_plugin_textdomain( 'sovereign-crypto-payments' );
}
add_action( 'plugins_loaded', 'obzsscp_load_plugin_textdomain' );

function obzsscp_settings_link( $links )
{
	$_link = '<a href="admin.php?page=wc-settings&tab=checkout&section=obzsscp_gateway">'.esc_html__( 'Settings', 'sovereign-crypto-payments' ).'</a>';
	$links[] = $_link;
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'obzsscp_settings_link' );

add_action('plugins_loaded', 'OBZSSCP_init_gateways');
register_activation_hook(__FILE__, 'OBZSSCP_activate');
register_deactivation_hook(__FILE__, 'OBZSSCP_deactivate');
register_uninstall_hook(__FILE__, 'OBZSSCP_uninstall');

function OBZSSCP_init_gateways(){

	if (!class_exists('WC_Payment_Gateway')) {
		return;
	};

	define('OBZSSCP_PLUGIN_DIR', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)) . '/');
	define('OBZSSCP_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ));
	define('OBZSSCP_CRON_JOB_URL', plugins_url('', __FILE__) . '/src/OBZSSCP_Cron.php');
	define('OBZSSCP_VERSION', '1.0.3');
	define('OBZSSCP_LOGGING', false);

	// Vendor
	require_once(plugin_basename('src/vendor/OBZSSCP_bcmath_Utils.php'));
	require_once(plugin_basename('src/vendor/OBZSSCP_CurveFp.php'));
	require_once(plugin_basename('src/vendor/OBZSSCP_ElectrumHelper.php'));
	require_once(plugin_basename('src/vendor/OBZSSCP_gmp_Utils.php'));
	require_once(plugin_basename('src/vendor/OBZSSCP_NumberTheory.php'));
	require_once(plugin_basename('src/vendor/OBZSSCP_Point.php'));
	require_once(plugin_basename('src/vendor/OBZSSCP_CashAddress.php'));
	require_once(plugin_basename('src/vendor/OBZSSCP_phpqrcode.php'));

	// Http
	require_once(plugin_basename('src/OBZSSCP_Exchange.php'));
	require_once(plugin_basename('src/OBZSSCP_Blockchain.php'));

	// Database
	require_once(plugin_basename('src/OBZSSCP_Carousel_Repo.php'));
	require_once(plugin_basename('src/OBZSSCP_Electrum_Repo.php'));
	require_once(plugin_basename('src/OBZSSCP_Payment_Repo.php'));

	// Simple Objects
	require_once(plugin_basename('src/OBZSSCP_Cryptocurrency.php'));
	require_once(plugin_basename('src/OBZSSCP_Transaction.php'));

	// Business Logic
	require_once(plugin_basename('src/OBZSSCP_Cryptocurrencies.php'));
	require_once(plugin_basename('src/OBZSSCP_Carousel.php'));
	require_once(plugin_basename('src/OBZSSCP_Electrum.php'));
	require_once(plugin_basename('src/OBZSSCP_Payment.php'));

	// Misc
	require_once(plugin_basename('src/OBZSSCP_Util.php'));
	require_once(plugin_basename('src/OBZSSCP_Hooks.php'));
	require_once(plugin_basename('src/OBZSSCP_Cron.php'));
	require_once(plugin_basename('src/OBZSSCP_Postback_Settings_Helper.php'));

	// Core
	require_once(plugin_basename('src/OBZSSCP_Gateway.php'));

	add_filter ('cron_schedules', 'OBZSSCP_add_interval');

	add_action('OBZSSCP_cron_hook', 'OBZSSCP_do_cron_job');
	add_action( 'woocommerce_process_shop_order_meta', 'OBZSSCP_update_database_when_admin_changes_order_status', 10, 2 ); 

	if (!wp_next_scheduled('OBZSSCP_cron_hook')) {
		wp_schedule_event(time(), 'minutes_2', 'OBZSSCP_cron_hook');
	}
}

function OBZSSCP_add_interval ($schedules)
{
	$schedules['seconds_5'] = array('interval'=>5, 'display'=>'debug');
	$schedules['seconds_30'] = array('interval'=>30, 'display'=>'Bi-minutely');
	$schedules['minutes_1'] = array('interval'=>60, 'display'=>'Once every 1 minute');
	$schedules['minutes_2'] = array('interval'=>120, 'display'=>'Once every 2 minutes');

	return $schedules;
}

function OBZSSCP_activate() {
	if (!wp_next_scheduled('OBZSSCP_cron_hook')) {
		wp_schedule_event(time(), 'minutes_2', 'OBZSSCP_cron_hook');
	}

	OBZSSCP_create_mpk_address_table();
	OBZSSCP_create_payment_table();
	OBZSSCP_create_carousel_table();
}

function OBZSSCP_deactivate() {
	wp_clear_scheduled_hook('OBZSSCP_cron_hook');
}

function OBZSSCP_uninstall() {
	OBZSSCP_drop_mpk_address_table();
	OBZSSCP_drop_payment_table();
	OBZSSCP_drop_carousel_table();
}

function OBZSSCP_add_gateways($methods) {
	$methods[] = 'OBZSSCP_Gateway';
	return $methods;
}

function OBZSSCP_drop_mpk_address_table() {
	global $wpdb;
	$tableName = $wpdb->prefix . 'obzsscp_electrum_addresses';
	$query = "DROP TABLE IF EXISTS `$tableName`";
	$wpdb->query($query);
}

function OBZSSCP_drop_payment_table() {
	global $wpdb;
	$tableName = $wpdb->prefix . 'obzsscp_payments';
	$query = "DROP TABLE IF EXISTS `$tableName`";
	$wpdb->query($query);
}

function OBZSSCP_drop_carousel_table() {
	global $wpdb;
	$tableName = $wpdb->prefix . 'obzsscp_carousel';
	$query = "DROP TABLE IF EXISTS `$tableName`";
	$wpdb->query($query);
}

function OBZSSCP_create_mpk_address_table() {
	global $wpdb;
	$tableName = $wpdb->prefix . 'obzsscp_electrum_addresses';
	$query = "CREATE TABLE IF NOT EXISTS `$tableName` 
		(
			`id` bigint(12) unsigned NOT NULL AUTO_INCREMENT,
			`mpk` char(150) NOT NULL,
			`mpk_index` bigint(20) NOT NULL DEFAULT '0',
			`address` char(150) NOT NULL,
			`cryptocurrency` char(12) NOT NULL,
			`status` char(24)  NOT NULL DEFAULT 'error',
			`total_received` decimal( 16, 8 ) NOT NULL DEFAULT '0.00000000',
			`last_checked` bigint(20) NOT NULL DEFAULT '0',
			`assigned_at` bigint(20) NOT NULL DEFAULT '0',
			`order_id` bigint(10) NULL,
			`order_amount` decimal(16, 8) NOT NULL DEFAULT '0.00000000',
			PRIMARY KEY (`id`),
			UNIQUE KEY `address` (`address`),
			KEY `status` (`status`),
			KEY `mpk_index` (`mpk_index`),
			KEY `mpk` (`mpk`)
		);";

	$wpdb->query($query);
}

function OBZSSCP_create_payment_table() {
	global $wpdb;
	$tableName = $wpdb->prefix . 'obzsscp_payments';
	$query = "CREATE TABLE IF NOT EXISTS `$tableName`
		(
			`id` bigint(12) unsigned NOT NULL AUTO_INCREMENT,
			`address` char(150) NOT NULL,
			`cryptocurrency` char(12) NOT NULL,
			`status` char(24)  NOT NULL DEFAULT 'error',
			`ordered_at` bigint(20) NOT NULL DEFAULT '0',
			`order_id` bigint(10) NOT NULL DEFAULT '0',
			`order_amount` decimal(32, 18) NOT NULL DEFAULT '0.000000000000000000',
			`tx_hash` char(150) NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `unique_payment` (`order_id`, `order_amount`),
			KEY `status` (`status`)
		);";

	$wpdb->query($query);
}

function OBZSSCP_create_carousel_table() {
	global $wpdb;
	$tableName = $wpdb->prefix . 'obzsscp_carousel';
	$query = "CREATE TABLE IF NOT EXISTS `$tableName`
		(
			`id` bigint(12) unsigned NOT NULL AUTO_INCREMENT,
			`cryptocurrency` char(12) NOT NULL,
			`current_index` bigint(20) NOT NULL DEFAULT '0',
			`buffer` text NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `cryptocurrency` (`cryptocurrency`)
		);";

	$wpdb->query($query);

	require_once( plugin_basename( 'src/OBZSSCP_Cryptocurrency.php' ) );
	require_once( plugin_basename( 'src/OBZSSCP_Carousel_Repo.php' ) );
	require_once( plugin_basename( 'src/OBZSSCP_Util.php' ) );
	require_once( plugin_basename( 'src/OBZSSCP_Cryptocurrencies.php' ) );

	OBZSSCP_Carousel_Repo::init();

	$cryptos = OBZSSCP_Cryptocurrencies::get();

	$settings = get_option('woocommerce_obzsscp_gateway_settings');

	// if we find settings here we need to initialize the databases with the admin options for carousels
	if ($settings) {
		foreach ($cryptos as $crypto) {
			if (!$crypto->has_electrum()) {
				if (array_key_exists($crypto->get_id() . '_carousel_enabled', $settings)) {
					if ($settings[$crypto->get_id() . '_carousel_enabled'] === 'yes') {
							$buffer = array();
							$buffer[] = $settings[$crypto->get_id() . '_address'];
							$buffer[] = $settings[$crypto->get_id() . '_address2'];
							$buffer[] = $settings[$crypto->get_id() . '_address3'];
							$buffer[] = $settings[$crypto->get_id() . '_address4'];
							$buffer[] = $settings[$crypto->get_id() . '_address5'];
							$repo = new OBZSSCP_Carousel_Repo();
							$repo->set_buffer($crypto->get_id(), $buffer);
					}
				}
			}
		}
	}
}

add_filter('woocommerce_payment_gateways', 'OBZSSCP_add_gateways');

?>
