<?php

function OBZSSCP_change_cancelled_email_note_subject_line($subject, $order) {
	$subject = sprintf( esc_html__( 'Order %s has been cancelled due to non-payment.', 'sovereign-crypto-payments' ), $order->get_id() );
	return $subject;
}

function OBZSSCP_change_cancelled_email_heading($heading, $order) {
	$heading = esc_html__( 'Your order has been cancelled. Do not send any cryptocurrency to the payment address.', 'sovereign-crypto-payments' );
	return $heading;
}

function OBZSSCP_change_partial_email_note_subject_line($subject, $order) {
	$subject = sprintf( esc_html__( 'Partial payment received for Order %s.', 'sovereign-crypto-payments' ), $order->get_id() );
	return $subject;
}

function OBZSSCP_change_partial_email_heading($heading, $order) {
	$heading = 	sprintf( esc_html__( 'Partial payment received for Order %s.', 'sovereign-crypto-payments' ), $order->get_id() );
	return $heading;
}

function OBZSSCP_update_database_when_admin_changes_order_status( $orderId, $postData ) {
	$oldOrderStatus = $postData->post_status;
	$newOrderStatus = sanitize_text_field($_POST['order_status']);
	$paymentAmount = 0.0;
	foreach ($_POST['meta'] as $customAttribute) {
		if ($customAttribute['key'] === 'crypto_amount') {
			$paymentAmount = $customAttribute['value'];
		}
		if ($customAttribute['key'] === 'wallet_address') {
			$walletAddress = $customAttribute['value'];
		}
	}

	// this order was not made by us
	if ($paymentAmount == 0.0) {
		return;
	}

	if (file_exists(WP_CONTENT_DIR.'/qrcodes/'.$walletAddress.'-'.$orderId.'.png')) unlink(WP_CONTENT_DIR.'/qrcodes/'.$walletAddress.'-'.$orderId.'.png');
	$paymentRepo = new OBZSSCP_Payment_Repo();
	$obzsscp_is_payment_repo = $paymentRepo->get_is_payment_repo($orderId);
	if ( $obzsscp_is_payment_repo ) {

		// If admin updates from needs-payment to has-payment, stop looking for matching transactions
		if ($oldOrderStatus === 'wc-pending' && $newOrderStatus === 'wc-processing') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'paid');
		}
		if ($oldOrderStatus === 'wc-pending' && $newOrderStatus === 'wc-completed') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'paid');
		}
		if ($oldOrderStatus === 'wc-on-hold' && $newOrderStatus === 'wc-processing') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'paid');
		}
		if ($oldOrderStatus === 'wc-on-hold' && $newOrderStatus === 'wc-completed') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'paid');
		}

		// If admin updates from needs-payment to cancelled, stop looking for matching transactions
		if ($oldOrderStatus === 'wc-pending' && $newOrderStatus === 'wc-cancelled') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'cancelled');
		}
		if ($oldOrderStatus === 'wc-pending' && $newOrderStatus === 'wc-failed') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'cancelled');
		}
		if ($oldOrderStatus === 'wc-on-hold' && $newOrderStatus === 'wc-cancelled') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'cancelled');
		}
		if ($oldOrderStatus === 'wc-on-hold' && $newOrderStatus === 'wc-failed') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'cancelled');
		}

		// If admin updates from cancelled to needs-payment, start looking for matching transactions
		if ($oldOrderStatus === 'wc-cancelled' && $newOrderStatus === 'wc-on-hold') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'unpaid');
			$paymentRepo->set_ordered_at($orderId, $paymentAmount, time());
		}
		if ($oldOrderStatus === 'wc-cancelled' && $newOrderStatus === 'wc-pending') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'unpaid');
			$paymentRepo->set_ordered_at($orderId, $paymentAmount, time());
		}
		if ($oldOrderStatus === 'wc-failed' && $newOrderStatus === 'wc-on-hold') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'unpaid');
			$paymentRepo->set_ordered_at($orderId, $paymentAmount, time());
		}
		if ($oldOrderStatus === 'wc-failed' && $newOrderStatus === 'wc-pending') {
			$paymentRepo->set_status($orderId, $paymentAmount, 'unpaid');
			$paymentRepo->set_ordered_at($orderId, $paymentAmount, time());
		}
	}
	else
	{
		// If admin updates from needs-payment to has-payment, stop looking for matching transactions
		if ($oldOrderStatus === 'wc-pending' && $newOrderStatus === 'wc-processing') {
			$paymentRepo->set_status_electrum($walletAddress, 'complete');
		}
		if ($oldOrderStatus === 'wc-pending' && $newOrderStatus === 'wc-completed') {
			$paymentRepo->set_status_electrum($walletAddress, 'complete');
		}
		if ($oldOrderStatus === 'wc-on-hold' && $newOrderStatus === 'wc-processing') {
			$paymentRepo->set_status_electrum($walletAddress, 'complete');
		}
		if ($oldOrderStatus === 'wc-on-hold' && $newOrderStatus === 'wc-completed') {
			$paymentRepo->set_status_electrum($walletAddress, 'complete');
		}

		// If admin updates from needs-payment to cancelled, stop looking for matching transactions
		if ($oldOrderStatus === 'wc-pending' && $newOrderStatus === 'wc-cancelled') {
			$paymentRepo->set_status_electrum($walletAddress, 'ready');
		}
		if ($oldOrderStatus === 'wc-pending' && $newOrderStatus === 'wc-failed') {
			$paymentRepo->set_status_electrum($walletAddress, 'ready');
		}
		if ($oldOrderStatus === 'wc-on-hold' && $newOrderStatus === 'wc-cancelled') {
			$paymentRepo->set_status_electrum($walletAddress, 'ready');
		}
		if ($oldOrderStatus === 'wc-on-hold' && $newOrderStatus === 'wc-failed') {
			$paymentRepo->set_status_electrum($walletAddress, 'ready');
		}

		// If admin updates from cancelled to needs-payment, start looking for matching transactions
		if ($oldOrderStatus === 'wc-cancelled' && $newOrderStatus === 'wc-on-hold') {
			$paymentRepo->set_status_electrum($walletAddress, 'assigned');
		}
		if ($oldOrderStatus === 'wc-cancelled' && $newOrderStatus === 'wc-pending') {
			$paymentRepo->set_status_electrum($walletAddress, 'assigned');
		}
		if ($oldOrderStatus === 'wc-failed' && $newOrderStatus === 'wc-on-hold') {
			$paymentRepo->set_status_electrum($walletAddress, 'assigned');
		}
		if ($oldOrderStatus === 'wc-failed' && $newOrderStatus === 'wc-pending') {
			$paymentRepo->set_status_electrum($walletAddress, 'assigned');
		}
	}
}

?>
