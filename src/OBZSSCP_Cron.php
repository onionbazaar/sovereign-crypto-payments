<?php

function OBZSSCP_do_cron_job() {
	global $wpdb;
	$options = get_option('woocommerce_obzsscp_gateway_settings');
	
	$electrumBufferAddressCount = 5;
	// Only look at transactions in the past two hours
	$autoPaymentTransactionLifetimeSec = 2 * 60 * 60;
	$startTime = time();
	OBZSSCP_Util::log(__FILE__, __LINE__, 'Starting Cron Job...');
	OBZSSCP_Util::version_upgrade();
	if (OBZSSCP_electrum_has_valid_settings($options, 'BTC')) {
		$mpkBtc = $options['BTC_electrum_mpk'];
		$electrumPercentToVerify = $options['BTC_electrum_percent_to_process'];	
		$electrumRequiredConfirmations = $options['BTC_electrum_required_confirmations'];
		$electrumOrderCancellationTimeHr = $options['BTC_electrum_order_cancellation_time_hr'];
		$electrumOrderCancellationTimeSec = round($electrumOrderCancellationTimeHr * 60 * 60, 0);
		OBZSSCP_Electrum::buffer_ready_addresses('BTC', $mpkBtc, $electrumBufferAddressCount);
		OBZSSCP_Electrum::check_all_pending_addresses_for_payment('BTC', $mpkBtc, $electrumRequiredConfirmations, $electrumPercentToVerify);
		OBZSSCP_Electrum::cancel_expired_addresses('BTC', $mpkBtc, $electrumOrderCancellationTimeSec);
	}
	/*
	if (OBZSSCP_electrum_has_valid_settings($options, 'LTC')) {
		$mpkLtc = $options['LTC_electrum_mpk'];
		$electrumPercentToVerify = $options['LTC_electrum_percent_to_process'];	
		$electrumRequiredConfirmations = $options['LTC_electrum_required_confirmations'];
		$electrumOrderCancellationTimeHr = $options['LTC_electrum_order_cancellation_time_hr'];
		$electrumOrderCancellationTimeSec = round($electrumOrderCancellationTimeHr * 60 * 60, 0);
		OBZSSCP_Electrum::buffer_ready_addresses('LTC', $mpkLtc, $electrumBufferAddressCount);
		OBZSSCP_Electrum::check_all_pending_addresses_for_payment('LTC', $mpkLtc, $electrumRequiredConfirmations, $electrumPercentToVerify);
		OBZSSCP_Electrum::cancel_expired_addresses('LTC', $mpkLtc, $electrumOrderCancellationTimeSec);
	}
	*/
	OBZSSCP_Payment::check_all_addresses_for_matching_payment($autoPaymentTransactionLifetimeSec);	
	OBZSSCP_Payment::cancel_expired_payments();
	OBZSSCP_Util::cleancqrcodes();
	OBZSSCP_Util::log(__FILE__, __LINE__, 'total time for cron job: ' . OBZSSCP_get_time_passed($startTime));
}

function OBZSSCP_get_time_passed($startTime) {
	return time() - $startTime;
}

function OBZSSCP_electrum_has_valid_settings($settings, $cryptoId) {
	if (is_array($settings)) {
		$mpkExists = array_key_exists($cryptoId . '_electrum_mpk', $settings);
		if (!$mpkExists) {
			return false;
		}
	}
	else {
		return false;
	}
	$mpk = $settings[$cryptoId . '_electrum_mpk'];
	$mpkValid = OBZSSCP_Electrum::is_valid_mpk($mpk);
	$percentToVerifyExists = array_key_exists($cryptoId . '_electrum_percent_to_process', $settings);
	$requiredConfirmationsExists = array_key_exists($cryptoId . '_electrum_required_confirmations', $settings);
	$cancellationTimeExists = array_key_exists($cryptoId . '_electrum_order_cancellation_time_hr', $settings);
	return $mpkValid && $percentToVerifyExists && $requiredConfirmationsExists && $cancellationTimeExists;
}

?>
