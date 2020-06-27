<?php

// Helper class to be used when validating postback data in admin settings page
class OBZSSCP_Postback_Settings_Helper {
	private $id;
	private $postData;
	private $cryptos;

	public function __construct ($id, $cryptos, $postData) {
		$this->id = $id;
		$this->postData = $postData;
		$this->cryptos = $cryptos;
	}

	public function has_one_enabled_pricing_options() {
		$ccChosen = $this->is_checkbox_selected('use_crypto_compare');
        $hitbtcChosen = $this->is_checkbox_selected('use_hitbtc');
        $gateioChosen = $this->is_checkbox_selected('use_gateio');
        $bittrexChosen = $this->is_checkbox_selected('use_bittrex');
        $poloniexChosen = $this->is_checkbox_selected('use_poloniex');
        return $ccChosen || $hitbtcChosen || $gateioChosen || $bittrexChosen || $poloniexChosen;
	}

	public function has_one_enabled_crypto() {
		$isCryptoSelected = false;
        foreach ($this->cryptos as $crypto) {
            if (! $isCryptoSelected) {
                $isCryptoSelected = $this->is_checkbox_selected($crypto->get_id() . '_enabled');
            }
        }
        return $isCryptoSelected;
	}

	public function is_crypto_enabled($cryptoId) {
		return $this->is_checkbox_selected($cryptoId . '_enabled');
	}

	public function is_electrum_enabled($cryptoId) {
		return $this->is_checkbox_selected($cryptoId . '_electrum_enabled');
	}

	public function crypto_has_valid_wallet($cryptoId) {
		if ( $this->is_text_empty($cryptoId . '_address')) {
			return false;
		}
		$address = $this->get_value($cryptoId . '_address');
		if (OBZSSCP_Cryptocurrencies::is_valid_wallet_address($cryptoId, $address)) {
				return true;
		}
		return false;
	}

	public function crypto_has_valid_electrum_mpk($cryptoId) {
		if ($this->is_text_empty($cryptoId . '_electrum_mpk')) {
			return false;
		}
		$mpk = $this->get_value($cryptoId . '_electrum_mpk');
		if(OBZSSCP_Electrum::is_valid_mpk($mpk)) {
			return true;
		}
		return false;
	}

	private function is_checkbox_selected($optionName) {
		$isOptionSet = array_key_exists('woocommerce_' . $this->id . '_' . $optionName, $this->postData);
		return $isOptionSet;
	}

	private function is_text_empty($optionName) {
		return empty($this->postData['woocommerce_' .  $this->id . '_' . $optionName]);
	}

	private function get_value($optionName) {
		return $this->postData['woocommerce_' . $this->id . '_' . $optionName];
	}
}

?>
