<?php

// Class that communicates with various exchanges via HTTP
class OBZSSCP_Exchange {
	// this function converts other WooCommerce currencies to USD because the crypto exchanges only have prices in USD
	public static function get_order_total_in_usd($total, $fromCurr) {
		if ($fromCurr === 'USD') {
			return $total;
		}
		$transientKey = $fromCurr . '_to_USD';
		$conversionRate = get_transient( $transientKey );
		if ($conversionRate !== false) {
			return $total / $conversionRate;
		}
		$response = wp_remote_get('https://api.exchangeratesapi.io/latest?base=USD&symbols=' . $fromCurr);
		if ( is_wp_error( $response ) || $response['response']['code'] !== 200) {
			throw new \Exception( esc_html__( 'Could not reach the currency conversion service. Please try again.', 'sovereign-crypto-payments' ) );
		}
		$body = json_decode($response['body']);
		//$conversionRate = $body->{'results'}->{$curr . '_USD'}->{'val'};
		$conversionRate = $body->{'rates'}->{$fromCurr};
		set_transient($transientKey, $conversionRate, 3600);
		$priceInUsd = $total / $conversionRate;
		return $priceInUsd;
	}

	// gets crypto to USD conversion from an API
	public static function get_cryptocompare_price($cryptoId, $updateInterval) {
		$transientKey = 'cryptocompare_' . $cryptoId . '_price';
		$cryptocomparePrice = get_transient($transientKey);
		// if transient is found in database just return it
		if ($cryptocomparePrice !== false) {
			return $cryptocomparePrice;
		}
		// if no transient is found we need to hit the api again
		$response = wp_remote_get('https://min-api.cryptocompare.com/data/price?fsym=' . $cryptoId . '&tsyms=USD');
		if ( is_wp_error( $response ) || $response['response']['code'] !== 200) {
			OBZSSCP_Util::log(__FILE__, __LINE__, print_r($response, true));
			return 0;
		}
		$responseBody = json_decode( $response['body'] );
		$cryptocomparePrice = (float) $responseBody->{'USD'};
		//cache value for X min to reduce api calls	
		set_transient($transientKey, $cryptocomparePrice, $updateInterval);
		return $cryptocomparePrice;
	}

	// gets crypto to USD conversion from an API
	public static function get_hitbtc_price($cryptoId, $updateInterval) {
		$transientKey = 'hitbtc_' . $cryptoId . '_price';
		$hitbtcPrice = get_transient($transientKey);
		if ($hitbtcPrice !== false) {
			return $hitbtcPrice;
		}
		$response = wp_remote_get('https://api.hitbtc.com/api/2/public/ticker/' . $cryptoId . 'USD');
		if ( is_wp_error( $response ) || $response['response']['code'] !== 200) {
			return 0;
		}
		$responseBody = json_decode( $response['body']);
		$hitbtcPrice = (float) $responseBody->{'last'};
		set_transient($transientKey, $hitbtcPrice, $updateInterval);
		return $hitbtcPrice;
	}

	// gets crypto to USD conversion from an API
	public static function get_gateio_price($cryptoId, $updateInterval) {
		$transientKey = 'gateio_' . $cryptoId . '_price';
		$gateioPrice = get_transient($transientKey);
		if ($gateioPrice !== false) {
			return $gateioPrice;
		}
		$response = wp_remote_get('https://data.gateio.io/api2/1/ticker/' . strtolower($cryptoId) . '_usdt');
		if ( is_wp_error( $response ) || $response['response']['code'] !== 200) {
			return 0;
		}
		$responseBody = json_decode( $response['body'] );
		$gateioPrice = (float) $responseBody->{'last'};
		set_transient($transientKey, $gateioPrice, $updateInterval);
		return $gateioPrice;
	}

	// gets crypto to USD conversion from an API
	public static function get_bittrex_price($cryptoId, $updateInterval) {
		$transientKey = 'bittrex_' . $cryptoId . '_price';
		$bittrexPrice = get_transient($transientKey);
		if ($bittrexPrice !== false) {
			return $bittrexPrice;
		}
		$response = wp_remote_get('https://bittrex.com/api/v1.1/public/getticker?market=USDT-' . $cryptoId);
		if ( is_wp_error( $response ) || $response['response']['code'] !== 200) {
			return 0;
		}
		$responseBody = json_decode( $response['body']);
		$bittrexPrice = (float) $responseBody->{'result'}->{'Last'};
		set_transient($transientKey, $bittrexPrice, $updateInterval);
		return $bittrexPrice;
	}

	// gets crypto to USD conversion from an API
	public static function get_poloniex_price($cryptoId, $updateInterval) {
		$transientKey = 'poloniex_' . $cryptoId . '_price';
		$poloniexPrice = get_transient($transientKey);
		if ($poloniexPrice !== false) {
			return $poloniexPrice;
		}
		$endTime = time();

		// time interval we fetch trades for
		$duration = 1800;
		$startTime = $endTime - $duration;
		$response = wp_remote_get('https://poloniex.com/public?command=returnTradeHistory&currencyPair=USDT_' . $cryptoId . '&start=' . $startTime . '&end=' . $endTime);
		if ( is_wp_error( $response ) || $response['response']['code'] !== 200) {
			return 0;
		}
		$responseBody = json_decode($response['body']);

		// average all trades fetched
		$totalTradeValue = 0;
		$numTrades = 0;
		foreach ($responseBody as $trade) {
			if ($trade->{'type'} === 'sell') {
				$totalTradeValue += (float) $trade->{'rate'};		
				$numTrades++;
			}
		}

		// if no trades are return 0 so the price is not used
		if ($numTrades === 0) {
			return 0;
		}
		$poloniexPrice = $totalTradeValue / $numTrades;
		set_transient($transientKey, $poloniexPrice, $updateInterval);
		return $poloniexPrice;
	}
}

?>
