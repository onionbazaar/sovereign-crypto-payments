<?php

class OBZSSCP_Gateway extends WC_Payment_Gateway {
	private $cryptos;
	public function __construct() {
		$cryptoArray = OBZSSCP_Cryptocurrencies::get();

		// electrum at the top, then currencies with auto-payment, then the rest
		$keys = array_map(function($val) {
				return $val->get_name();
		}, $cryptoArray);
		array_multisort($keys, $cryptoArray);
		$this->cryptos = $cryptoArray;
		$obzsscp_gateway_settings = get_option( 'woocommerce_obzsscp_gateway_settings' );
		if ( isset ($obzsscp_gateway_settings['BTC_payment_title'] ) ) $obzsscp_payment_title = $obzsscp_gateway_settings['BTC_payment_title']; else $obzsscp_payment_title = 'Bitcoin';
		$this->id = 'obzsscp_gateway';
		$this->icon = OBZSSCP_PLUGIN_DIR . '/assets/img/bitcoin_logo_small.png';
		$this->title = $obzsscp_payment_title;
		$this->has_fields = true;
		$this->method_title = esc_html__( 'Cryptocurrency', 'sovereign-crypto-payments' );
		$this->method_description = esc_html__( 'Take payments in cryptocurrency.', 'sovereign-crypto-payments' );
		$this->init_form_fields();
		$this->init_settings();
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thank_you_page'));
	}

	public function admin_options() {
		?>
		<h2><?php esc_html_e( 'Sovereign Crypto Payments', 'sovereign-crypto-payments' ); ?></h2>
		<div class="obzsscp-options">
			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table><!--/.form-table-->
		</div>
		<?php
	}

	// WooCommerce Admin Payment Method Settings
	public function init_form_fields() {
		// general settings
		$generalSettings = array(
			'general_settings' => array(
				'title' => esc_html__( 'General settings', 'sovereign-crypto-payments' ),
				'type' => 'title',
				'class' => 'section-title',
			),
			'enabled' => array(
				'title' => esc_html__( 'Enable/Disable', 'sovereign-crypto-payments' ), 'woocommerce',
				'type' => 'checkbox',
				'label' => esc_html__( 'Enable Cryptocurrency Payments', 'sovereign-crypto-payments' ), 'woocommerce',
				'default' => 'no',
				'class' => 'obzsscp-setting',
			)
		);
		$cryptoSettings = array();
		$cryptoSettings['crypto wallets'] = array(
			'title' => esc_html__( 'Cryptocurrency Options', 'sovereign-crypto-payments' ),
			'type' => 'title',		  
			'description' => esc_html__( 'Set up your wallet and define payment options.', 'sovereign-crypto-payments' ),
			'class' => 'section-title',
		);
		foreach ($this->cryptos as $crypto) {
			if ($crypto->get_id()=='BTC') {
				$cryptoSettings[$crypto->get_name() . ' Options'] = array(
					'title' => $crypto->get_name() . ' (' . $crypto->get_id() . ')',
					'type' => 'title',
					'class' => 'crypto-title',
				);
				if (!$crypto->has_electrum() && !$crypto->has_payment_verification()) {
					$cryptoSettings[$crypto->get_name() . ' Options']['description'] = $crypto->get_name() . ' ' . esc_html__( 'does not support automatic order processing. Please reconcile these orders manually.', 'sovereign-crypto-payments' );
				}
				$cryptoSettings[$crypto->get_id() . '_enabled'] = array(
					'title' => esc_html__( 'Enable/Disable', 'sovereign-crypto-payments' ),
					'type' => 'checkbox',
					'label' => 'Enable ' . $crypto->get_name(),
				);
				$cryptoSettings[$crypto->get_id() . '_payment_title'] = array(
					'title' => esc_html__( 'Payment Title', 'sovereign-crypto-payments' ),
					'type' => 'text',
					'default' => esc_html__( 'Bitcoin', 'sovereign-crypto-payments' ),
				);
				$cryptoSettings[$crypto->get_id() . '_payment_description'] = array(
					'title' => esc_html__( 'Payment Description', 'sovereign-crypto-payments' ),
					'type' => 'text',
					'default' => esc_html__( 'Pay with Bitcoin (BTC)', 'sovereign-crypto-payments' ),
				);
				
				if ($crypto->has_electrum()) {
					$cryptoSettings[$crypto->get_id() . '_electrum_enabled'] = array(
						'title' => esc_html__( 'MPK Mode', 'sovereign-crypto-payments' ),
						'type' => 'checkbox',
						'default' => 'no',
						'label' => esc_html__( 'MPK Mode enabled', 'sovereign-crypto-payments' ),
					);
					$cryptoSettings[$crypto->get_id() . '_electrum_mpk'] = array(
						'title' => esc_html__( 'Master Public Key (xpub)', 'sovereign-crypto-payments' ),
						'type' => 'text',
						'description' => esc_html__( 'Your Master Public Key. (Legacy seed-type only)', 'sovereign-crypto-payments' ),
					);
					$cryptoSettings[$crypto->get_id() . '_electrum_percent_to_process'] = array (
						'title' => esc_html__( 'MPK Auto-Confirm Percentage', 'sovereign-crypto-payments' ),
						'type' => 'number',
						'default' => '0.995',
						'description' => esc_html__( 'Orders confirm after reaching this percentage of the total amount. (0.995 = 99.5%)', 'sovereign-crypto-payments' ),
						'custom_attributes' => array(
							'min'  => 0.001,
							'max'  => 1.000,
							'step' => 0.001,
						),
					);
					$cryptoSettings[$crypto->get_id() . '_electrum_order_cancellation_time_hr'] = array (
						'title' => esc_html__( 'MPK Order Cancellation Timer (hr)', 'sovereign-crypto-payments' ),
						'type' => 'number',
						'default' => 3,
						'custom_attributes' => array(
							'min'  => 0.01,
							'max'  => 24 * 7, // 7 days
							'step' => 0.01,
						),
						'description' => esc_html__( 'Orders are cancelled automatically after this amount of time in hours. (1.5 = 1 hour 30 minutes)', 'sovereign-crypto-payments' ),
					);
					
					if ($crypto->get_id()=='BTC') {
						$cryptoSettings[$crypto->get_id() . '_electrum_required_confirmations'] = array (
							'type' => 'hidden',
							'default' => '1',
							'custom_attributes' => array(
								'min'  => 0,
								'max'  => 9999,
								'step' => 1,
							),
						);
					}
					else {
						$cryptoSettings[$crypto->get_id() . '_electrum_required_confirmations'] = array (
							'title' => esc_html__( 'Required Confirmations', 'sovereign-crypto-payments' ),
							'type' => 'number',
							'default' => '1',
							'custom_attributes' => array(
								'min'  => 0,
								'max'  => 9999,
								'step' => 1,
							),
							'description' => esc_html__( 'This is the number of confirmations a payment needs to receive before it is considered a valid payment.', 'sovereign-crypto-payments' ),
						);
					}
				}
				$cryptoSettings[$crypto->get_id() . '_address'] = array(
					'title' => $crypto->get_name() . ' ' . esc_html__( 'Wallet Address', 'sovereign-crypto-payments' ),
					'type' => 'text',
				);
				$cryptoSettings[$crypto->get_id() . '_carousel_enabled'] = array(
					'title' => esc_html__( 'Carousel Mode', 'sovereign-crypto-payments' ),
					'type' => 'checkbox',
					'default' => 'no',
					'label' => esc_html__( 'Carousel Addresses Enabled', 'sovereign-crypto-payments' )
				);
				$cryptoSettings[$crypto->get_id() . '_address2'] = array(
					'title' => $crypto->get_name() . ' ' . esc_html__( 'Address', 'sovereign-crypto-payments' ) . ' 2',
					'type' => 'text',
				);
				$cryptoSettings[$crypto->get_id() . '_address3'] = array(
					'title' => $crypto->get_name() . ' ' . esc_html__( 'Address', 'sovereign-crypto-payments' ) . ' 3',
					'type' => 'text',
				);
				$cryptoSettings[$crypto->get_id() . '_address4'] = array(
					'title' => $crypto->get_name() . ' ' . esc_html__( 'Address', 'sovereign-crypto-payments' ) . ' 4',
					'type' => 'text',
				);
				$cryptoSettings[$crypto->get_id() . '_address5'] = array(
					'title' => $crypto->get_name() . ' ' . esc_html__( 'Address', 'sovereign-crypto-payments' ) . ' 5',
					'type' => 'text',
				);
				if ($crypto->has_payment_verification()) {
					$cryptoSettings[$crypto->get_id() . '_autopayment_enabled'] = array (
						'title' => esc_html__( 'Auto-Payment Mode', 'sovereign-crypto-payments' ),
						'type' => 'Checkbox',
						'default' => 'no',
						'label' => esc_html__( 'Enable Auto-Payment Confirmation/Cancellation', 'sovereign-crypto-payments' ),
					);
					$cryptoSettings[$crypto->get_id() . '_autopayment_percent_to_process'] = array (
						'title' => esc_html__( 'Auto-Confirm Percentage', 'sovereign-crypto-payments' ),
						'type' => 'number',
						'default' => '0.995',
						'description' => esc_html__( 'Orders confirm after reaching this percentage of the total amount. (0.995 = 99.5%)', 'sovereign-crypto-payments' ),
						'custom_attributes' => array(
							'min'  => 0.001,
							'max'  => 1.000,
							'step' => 0.001,
						),
					);
					$cryptoSettings[$crypto->get_id() . '_autopayment_order_cancellation_time_hr'] = array (
						'title' => esc_html__( 'Order Cancellation Timer (hr)', 'sovereign-crypto-payments' ),
						'type' => 'number',
						'default' => 3,
						'custom_attributes' => array(
							'min'  => 0.01,
							'max'  => 24 * 7, // 7 days
							'step' => 0.01,
						),
						'description' => esc_html__( 'Orders are cancelled automatically after this amount of time in hours. (1.5 = 1 hour 30 minutes)', 'sovereign-crypto-payments' ),
					);
					if ($crypto->get_id()=='BTC') {
						if ($crypto->needs_confirmations()) {
							$cryptoSettings[$crypto->get_id() . '_autopayment_required_confirmations'] = array (
								'type' => 'hidden',
								'default' => '1',
								'custom_attributes' => array(
									'min'  => 0,
									'max'  => 9999,
									'step' => 1,
								),
							);
						}
					}
					else {
						if ($crypto->needs_confirmations()) {
							$cryptoSettings[$crypto->get_id() . '_autopayment_required_confirmations'] = array (
								'title' => esc_html__( 'Required Confirmations', 'sovereign-crypto-payments' ),
								'type' => 'number',
								'default' => '1',
								'custom_attributes' => array(
									'min'  => 0,
									'max'  => 9999,
									'step' => 1,
								),
								'description' => esc_html__( 'This is the number of confirmations a payment needs to receive before it is considered a valid payment.', 'sovereign-crypto-payments' ),
							);
						}
					}
				}
			}
		}
		$pricingSettings = array(
			'pricing options' => array(
				'title' => esc_html__( 'Pricing Sources', 'sovereign-crypto-payments' ),
				'type' => 'title',
				'description' => esc_html__( 'The cryptocurrency price is the average of selected sources. If your store currency is not USD, exchange rates are retrieved from exchangeratesapi.io', 'sovereign-crypto-payments' ),
				'class' => 'section-title',
			),
			/*
			'use_crypto_compare' => array(
				'title' => 'cryptocompare.com',
				'type' => 'checkbox',
				'default' => 'no',
				'description' => 'Use Cryptocompare via https://min-api.cryptocompare.com',			
			),
			'use_hitbtc' => array(
				'title' => 'hitbtc.com',
				'type' => 'checkbox',
				'default' => 'no',
				'description' => 'Use HitBTC via https://api.hitbtc.com',
			),
			*/
			'use_bittrex' => array(
				'title' => 'bittrex.com',
				'type' => 'checkbox',
				'default' => 'yes',
				'description' => esc_html__( 'Use Bittrex via https://bittrex.com', 'sovereign-crypto-payments' ),
			),
			'use_poloniex' => array(
				'title' => 'poloniex.com',
				'type' => 'checkbox',
				'default' => 'yes',
				'description' => esc_html__( 'Use Poloniex via https://poloniex.com', 'sovereign-crypto-payments' ),
			),
			'use_gateio' => array(
				'title' => 'gateio.io',
				'type' => 'checkbox',
				'default' => 'no',
				'description' => esc_html__( 'Use GateIO via https://data.gateio.io', 'sovereign-crypto-payments' ),
			),
		);

		$this->form_fields = array_merge($generalSettings, $cryptoSettings, $pricingSettings);
		$cssPath = OBZSSCP_PLUGIN_DIR . 'assets/css/obzsscp.css';
		$jsPath = OBZSSCP_PLUGIN_DIR . 'assets/js/obzsscp.js';
		wp_enqueue_style('obzsscp-styles', $cssPath);
		wp_enqueue_script('obzsscp-scripts', $jsPath, array('jquery'), OBZSSCP_VERSION);
	}

	public function process_admin_options() {
		parent::process_admin_options();
		foreach ($this->cryptos as $crypto) {
				//if (!$crypto->has_electrum()) {
				if ($crypto->get_id()=='BTC') {
				$buffer = array();
				$buffer[] = $this->settings[$crypto->get_id() . '_address'];
				$buffer[] = $this->settings[$crypto->get_id() . '_address2'];
				$buffer[] = $this->settings[$crypto->get_id() . '_address3'];
				$buffer[] = $this->settings[$crypto->get_id() . '_address4'];
				$buffer[] = $this->settings[$crypto->get_id() . '_address5'];
				$sendWarningMessage = true;
				for ($i = 2; $i <= 5; $i++) {
					$address = $this->settings[$crypto->get_id() . '_address' . $i];
					$addressValid = OBZSSCP_Cryptocurrencies::is_valid_wallet_address($crypto->get_id(), $address);
					if ($addressValid) {
						$sendWarningMessage = false;
					}   
				}
				if ($sendWarningMessage && $this->crypto_is_enabled($crypto) && $this->crypto_has_carousel_enabled($crypto)) {
					WC_Admin_Settings::add_error( sprintf(
					esc_html__( 'Carousel mode was activated for %s but no valid carousel addresses were saved, falling back to static address.', 'sovereign-crypto-payments' ),
					$crypto->get_name() ) );
				}
				OBZSSCP_Util::log(__FILE__, __LINE__, 'saving buffer to database with count of: ' . count($buffer));
				$carouselRepo = new OBZSSCP_Carousel_Repo();
				$carouselRepo->set_buffer($crypto->get_id(), $buffer);
			}
			//}
		}
	}

	// This is called whenever the user saves the woocommerce admin settings, for some reason AFTER validate_enabled_field is called
	public function validate_BTC_electrum_enabled_field($key, $value) {
		$post_data = $this->get_post_data();
		$gatewaySettings = new OBZSSCP_Postback_Settings_Helper($this->id, $this->cryptos, $post_data);
		$validMpk = $gatewaySettings->crypto_has_valid_electrum_mpk('BTC');		
		if (! $value) {
			return 'no';
		}
		if (!$validMpk) {
			WC_Admin_Settings::add_error( sprintf( esc_html__( 'MPK Mode was enabled for %1$s but the Master Public Key is invalid. Disabling MPK Mode for %1$s.', 'sovereign-crypto-payments' ), 'Bitcoin' ) );
			return 'no';
		}
		return 'yes';
	}

	// This is called whenever the user saves the woocommerce admin settings, for some reason AFTER validate_enabled_field is called
	public function validate_LTC_electrum_enabled_field($key, $value) {
		$post_data = $this->get_post_data();
		$gatewaySettings = new OBZSSCP_Postback_Settings_Helper($this->id, $this->cryptos, $post_data);
		$validMpk = $gatewaySettings->crypto_has_valid_electrum_mpk('LTC');
		//$validAddress = $gatewaySettings->crypto_has_valid_wallet('BTC');
		if (! $value) {
			return 'no';
		}
		if (!$validMpk) {
			WC_Admin_Settings::add_error( sprintf( esc_html__( 'MPK Mode was enabled for %1$s but the Master Public Key is invalid. Disabling MPK Mode for %1$s.', 'sovereign-crypto-payments' ), 'Litecoin' ) );
			return 'no';
		}
		return 'yes';
	}

	// This is called whenever the user saves the woocommerce admin settings, for some reason AFTER validate_enabled_field is called
	public function validate_QTUM_electrum_enabled_field($key, $value) {
		$post_data = $this->get_post_data();
		$gatewaySettings = new OBZSSCP_Postback_Settings_Helper($this->id, $this->cryptos, $post_data);
		$validMpk = $gatewaySettings->crypto_has_valid_electrum_mpk('QTUM');
		if (! $value) {
			return 'no';
		}
		if (!$validMpk) {
			WC_Admin_Settings::add_error( sprintf( esc_html__( 'MPK Mode was enabled for %1$s but the Master Public Key is invalid. Disabling MPK Mode for %1$s.', 'sovereign-crypto-payments' ), 'QTUM' ) );
			return 'no';
		}
		return 'yes';
	}

	// This is called whenever the user saves the woocommerce admin settings, server side validation based around the enable/disable plugin field
	public function validate_enabled_field($key, $value) {

		// if the gateway is not enabled do not do any validation
		if (! $value) {
			return 'no';
		}
		$result = 'yes';
		$post_data = $this->get_post_data();
		$gatewaySettings = new OBZSSCP_Postback_Settings_Helper($this->id, $this->cryptos, $post_data);

		// fail if no pricing options are selected
		if (! $gatewaySettings->has_one_enabled_pricing_options()) {
			WC_Admin_Settings::add_error( esc_html__( 'You must select at least one pricing source.', 'sovereign-crypto-payments' ) );
			$result = 'no';
		}

		// fail if no cryptos are enabled
		if (! $gatewaySettings->has_one_enabled_crypto()) {
			WC_Admin_Settings::add_error( esc_html__( 'You must enable at least one cryptocurrency.', 'sovereign-crypto-payments' ) );
			$result = 'no';
		}

		// validation for each crypto
		foreach ($this->cryptos as $crypto) {
			$cryptoId = $crypto->get_id();
			$cryptoEnabled = $gatewaySettings->is_crypto_enabled($cryptoId);
			$electrumEnabled = $gatewaySettings->is_electrum_enabled($cryptoId);
			$validMpk = $gatewaySettings->crypto_has_valid_electrum_mpk($cryptoId);
			$validAddress = $gatewaySettings->crypto_has_valid_wallet($cryptoId);

			// fall back to regular address but let user know
			if ($cryptoEnabled && $electrumEnabled && (!$validMpk) && $validAddress) {

				// code in validate_BTC_enabled_field handles disabling electrum
				$errorMessage = sprintf( esc_html__('Invalid Master Public Key for %s. Falling back to regular wallet address.', 'sovereign-crypto-payments'), $crypto->get_name() );

				// EVEN THOUGH WE THROW AN ERROR WE DO NOT DISABLE THE PLUGIN
				WC_Admin_Settings::add_error($errorMessage);
			}
			if ($cryptoEnabled && $electrumEnabled && (!$validMpk) && (!$validAddress)) {
				$errorMessage = sprintf( esc_html__('Invalid wallet address for %s... Plug-in will be disabled until each enabled cryptocurrency has a valid wallet address.', 'sovereign-crypto-payments'), $crypto->get_name() );
				// code in validate_BTC_enabled_field handles disabling electrum
				WC_Admin_Settings::add_error($errorMessage);
				$result = 'no';
			}
			if ($cryptoEnabled && (!$electrumEnabled) && (!$validAddress)) {
				$errorMessage = sprintf( esc_html__( 'Invalid wallet address for %s... Plug-in will be disabled until each enabled cryptocurrency has a valid wallet address', 'sovereign-crypto-payments' ), $crypto->get_name() );
				WC_Admin_Settings::add_error($errorMessage);
				$result = 'no';
			}
		}
		return $result;
	}

	// This runs when the user hits the checkout page
	// We load our crypto select with valid crypto currencies
	public function payment_fields() {
		$validCryptos = $this->cryptos_with_valid_settings();
		foreach ($validCryptos as $crypto) {
			if ($crypto->has_electrum() && $this->crypto_has_electrum_enabled($crypto)) {
				$mpk = $this->get_crypto_electrum_mpk($crypto);
				$electrumRepo = new OBZSSCP_Electrum_Repo($crypto->get_id(), $mpk);
				$count = $electrumRepo->count_ready();
				if ($count < 1) {
					try {
						OBZSSCP_Electrum::force_new_address($crypto->get_id(), $mpk);
					}
					catch ( \Exception $e) {
						OBZSSCP_Util::log(__FILE__, __LINE__, 'Unable to generate MPK Address for ' . $crypto->get_name() . '. Removing this crypto from the payment options.');
						unset($validCryptos[$crypto->get_id()]);
					}
				}
			}
		}
		$selectOptions = $this->get_select_options_for_cryptos($validCryptos);

		/*
		woocommerce_form_field(
			'obzsscp_currency_id', array(
				'type'	 => 'select',				
				'label'	=> 'Choose a Cryptocurrency',
				'required' => true,
				'default' => 'ZRX',
				'options'  => $selectOptions,
			)
		); 
		*/ 

		$obzsscp_gateway_settings = get_option( 'woocommerce_obzsscp_gateway_settings' );
		if ( isset ($obzsscp_gateway_settings['BTC_payment_description'] ) ) $obzsscp_payment_description = $obzsscp_gateway_settings['BTC_payment_description']; else $obzsscp_payment_description = 'Pay with Bitcoin (BTC)';
		echo $obzsscp_payment_description;
	}

	// return list of cryptocurrencies that have valid settings
	private function cryptos_with_valid_settings() {
		$cryptosWithValidSettings = array();
		foreach ($this->cryptos as $crypto) {
			/*
			if ( $this->crypto_has_valid_settings($crypto) ) {
				$cryptosWithValidSettings[$crypto->get_id()] = $crypto;
			}
			*/
			$cryptosWithValidSettings['BTC'] = $crypto;
		}
		return $cryptosWithValidSettings;
	}

	// check if crypto has valid settings
	private function crypto_has_valid_settings($crypto) {
		if (! $this->crypto_is_enabled($crypto)) {
			return false;
		}
		$electrumValid = $this->crypto_has_electrum_enabled($crypto) && $this->crypto_has_electrum_mpk($crypto);
		if ($electrumValid || $this->crypto_has_wallet_address($crypto)) {
			return true;
		}
		return false;
	}

	// This runs when the user selects Place Order, before process_payment, has nothing to do with the other validation methods
	public function validate_fields() {
		// if the currently selected gateway is this gateway we set transients related to conversions and if something goes wrong we prevent the customer from hitting the thank you page  by throwing the WooCommerce Error Notice.
		if (WC()->session->get('chosen_payment_method') === $this->id) {
			try {
				//$chosenCryptoId = $_POST['obzsscp_currency_id'];
				$chosenCryptoId = 'BTC';
				$crypto = $this->cryptos[$chosenCryptoId];
				$curr = get_woocommerce_currency();
				$cryptoPerUsd = $this->get_crypto_value_in_usd($crypto->get_id(), $crypto->get_update_interval());

				// this is just a check to make sure we can hit the currency exchange if we need to
				$usdTotal = OBZSSCP_Exchange::get_order_total_in_usd(1.0, $curr);
			}
			catch ( \Exception $e) {
				OBZSSCP_Util::log(__FILE__, __LINE__, $e->getMessage());
				wc_add_notice($e->getMessage(), 'error');
			}
		}
	}

	// This is called when the user clicks Place Order, after validate_fields
	public function process_payment($order_id) {
		$order = new WC_Order($order_id);
		//$selectedCryptoId = $_POST['obzsscp_currency_id'];
		$selectedCryptoId = 'BTC';
		//WC()->session->set('chosen_crypto_id', $selectedCryptoId);
		WC()->session->set('chosen_crypto_id', 'BTC');
		return array(
				'result' => 'success',
				'redirect'  => $this->get_return_url( $order ),
				);
	}

	// This is called after process payment, when the customer places the order
	public function thank_you_page($order_id) {
		try {
			$walletCheck = get_post_meta($order_id, 'wallet_address');

			// if we already set this then we are on a page refresh, so handle refresh
			if (count($walletCheck) > 0) {
				$this->handle_thank_you_refresh(
					get_post_meta($order_id, 'crypto_type_id', true),
					$order_id,
					get_post_meta($order_id, 'wallet_address', true),
					get_post_meta($order_id, 'crypto_amount', true));
				return;
			}
			$chosenCryptoId = WC()->session->get('chosen_crypto_id');
			$order = new WC_Order($order_id);
			$crypto = $this->cryptos[$chosenCryptoId];

			// get current price of crypto
			$cryptoPerUsd = $this->get_crypto_value_in_usd($crypto->get_id(), $crypto->get_update_interval());

			// handle different woocommerce currencies and get the order total in USD
			$curr = get_woocommerce_currency();
			$usdTotal = OBZSSCP_Exchange::get_order_total_in_usd($order->get_total(), $curr);
			
			// order total in cryptocurrency
			$cryptoTotal = round($usdTotal / $cryptoPerUsd, $crypto->get_round_precision(), PHP_ROUND_HALF_UP);

			// format the crypto amount based on crypto
			$formattedCryptoTotal = OBZSSCP_Cryptocurrencies::get_price_string($crypto->get_id(), $cryptoTotal);

			OBZSSCP_Util::log(__FILE__, __LINE__, 'Crypto total: ' . $cryptoTotal . ' Formatted Total: ' . $formattedCryptoTotal);

			$electrumEnabled = $crypto->has_electrum() && $this->crypto_has_electrum_enabled($crypto);

			// if electrum is enabled we have stuff to do
			if ($electrumEnabled) {
				$mpk = $this->get_crypto_electrum_mpk($crypto);
				$electrumRepo = new OBZSSCP_Electrum_Repo($crypto->get_id(), $mpk);

				// get fresh electrum wallet
				$walletAddress = $electrumRepo->get_oldest_ready();

				// if we couldnt find a fresh one, force a new one
				if (!$walletAddress) {
					try {
						OBZSSCP_Electrum::force_new_address($crypto->get_id(), $mpk);
						$walletAddress = $electrumRepo->get_oldest_ready();
					}
					catch ( \Exception $e) {
						throw new \Exception( sprintf( esc_html__( 'Unable to get payment address for order. This order has been cancelled. Please try again or contact us. Exception: %s', 'sovereign-crypto-payments' ), $e->getMessage() ) );
					}
				}

				// set electrum wallet address to get later
				WC()->session->set('electrum_wallet_address', $walletAddress);

				// update the database
				$electrumRepo->set_status($walletAddress, 'assigned');
				$electrumRepo->set_order_id($walletAddress, $order_id);
				$electrumRepo->set_order_amount($walletAddress, $formattedCryptoTotal);
				$orderNote = sprintf(
					esc_html__( 'MPK wallet address %1$s is awaiting payment of %2$s', 'sovereign-crypto-payments' ),
					$walletAddress,
					$formattedCryptoTotal);
			}
			// Electrum is not enabled, just handle static wallet or carousel mode
			else {
				$walletAddress = $this->get_crypto_wallet_address($crypto);
				// handle payment verification feature
				if ($crypto->has_payment_verification() && $this->settings[$crypto->get_id() . '_autopayment_enabled'] === 'yes') {
					$paymentRepo = new OBZSSCP_Payment_Repo();
					$paymentRepo->insert($walletAddress, $crypto->get_id(), $order_id, $formattedCryptoTotal, 'unpaid');
				}
				$orderNote = sprintf(
					esc_html__( 'Awaiting payment of %1$s %2$s to payment address %3$s.', 'sovereign-crypto-payments' ),
					$formattedCryptoTotal,
					$crypto->get_id(),
					$walletAddress );
			}

			// For email
			WC()->session->set($crypto->get_id() . '_amount', $formattedCryptoTotal);

			// For customer reference and to handle refresh of thank you page
			update_post_meta($order_id, 'crypto_amount', $formattedCryptoTotal);
			update_post_meta($order_id, 'wallet_address', $walletAddress);
			update_post_meta($order_id, 'crypto_type_id', $crypto->get_id());

			// Emails are fired once we update status to on-hold, so hook additional email details here
			add_action('woocommerce_email_order_details', array( $this, 'additional_email_details' ), 10, 4);
			$order->update_status('wc-on-hold', $orderNote);

			// Output additional thank you page html
			$this->output_thank_you_html($crypto, $order_id, $walletAddress, $formattedCryptoTotal);
		}
		catch ( \Exception $e ) {
			$order = new WC_Order($order_id);

			// cancel order if something went wrong
			$order->update_status('wc-failed', 'Error Message: ' . $e->getMessage());
			OBZSSCP_Util::log(__FILE__, __LINE__, 'Something went wrong during checkout: ' . $e->getMessage());
			echo '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">';
			echo '<ul class="woocommerce-error">';
			echo '<li>';
			echo esc_html__( 'Something went wrong.', 'sovereign-crypto-payments' ) . '<br>';
			echo $e->getMessage();
			echo '</li>';
			echo '</ul>';
			echo '</div>';
		}
	}

	public function additional_email_details( $order, $sent_to_admin, $plain_text, $email ) {
		$chosenCrypto = WC()->session->get('chosen_crypto_id');
		$crypto =  $this->cryptos[$chosenCrypto];
		$orderCryptoTotal = WC()->session->get($crypto->get_id() . '_amount');
		$electrumEnabled = $crypto->has_electrum() && $this->crypto_has_electrum_enabled($crypto);
		if ($electrumEnabled) {
			// electrum wallet that was selected/generated on thank you page
			$walletAddress = WC()->session->get('electrum_wallet_address');
		}
		else {
			$walletAddress = get_post_meta($order->get_id(), 'wallet_address', true);
			OBZSSCP_Util::log(__FILE__, __LINE__, 'getting wallet address from post meta: ' . $walletAddress);
		}
		$qrCode = $this->get_qr_code($crypto->get_name(), $order->get_id(), $walletAddress, $orderCryptoTotal);
		?>
		<h2><?php esc_html_e( 'Additional Details', 'sovereign-crypto-payments' ); ?></h2>
		<p>
			<?php esc_html_e( 'Cryptocurrency', 'sovereign-crypto-payments' ); ?>: <?php echo '<img src="' . $crypto->get_logo_file_path() . '" alt="" style="display: inline;" />' . $crypto->get_name(); ?>
		</p>
		<p>
			<?php esc_html_e( 'Wallet Address', 'sovereign-crypto-payments' ); ?>: <?php echo $walletAddress ?>
		</p>
		<p>
			<?php esc_html_e( 'Total', 'sovereign-crypto-payments' ); ?>: <?php echo OBZSSCP_Cryptocurrencies::get_price_string($crypto->get_id(), $orderCryptoTotal) . ' ' . $crypto->get_id(); ?>	 
		</p>
		<p><?php esc_html_e( 'QR Code', 'sovereign-crypto-payments' ); ?>: </p>
		<div style="margin-bottom:12px;">
			<img  src=<?php echo $qrCode; ?> />
		</div>


		<?php
	}

	// check admin settings to see if crypto is enabled
	private function crypto_is_enabled($crypto) {
		$enabledSetting = $crypto->get_id() . '_enabled';
		return $this->settings[$enabledSetting] === 'yes';
	}

	// check admin settings to see if crypto has a wallet address
	private function crypto_has_wallet_address($crypto) {
		$walletSetting = $crypto->get_id() . '_address';
		return ! empty( $this->settings[$walletSetting] );
	}

	private function get_crypto_wallet_address($crypto) {
		$walletSetting = $crypto->get_id() . '_address';

		// we dont offer carousel mode for electrum cryptos so just return regular wallet address
		//if ($crypto->has_electrum()) {
		//	return $this->settings[$walletSetting];
		//}

		if (!$this->crypto_has_carousel_enabled($crypto)) {
			return $this->settings[$walletSetting];
		}
		else {
			$carousel = new OBZSSCP_Carousel($crypto->get_id());
			return $carousel->get_next_address();
		}
	}

	private function crypto_has_electrum_enabled($crypto) {
		$electrumEnabledSetting = $crypto->get_id() . '_electrum_enabled';
		if (! array_key_exists($electrumEnabledSetting, $this->settings)) {
			return false;
		}
		return $this->settings[$electrumEnabledSetting] === 'yes';
	}

	private function crypto_has_electrum_mpk($crypto) {
		$electrumMpkSetting = $crypto->get_id() . '_electrum_mpk';
		if ( !array_key_exists($electrumMpkSetting, $this->settings)) {
			return false;
		}
		return ! empty($this->settings[$electrumMpkSetting]);
	}

	private function get_crypto_electrum_mpk($crypto) {
		$electrumMpkSetting = $crypto->get_id() . '_electrum_mpk';
		return $this->settings[$electrumMpkSetting];
	}

	private function crypto_has_carousel_enabled($crypto) {
		$carouselEnabledSetting = $crypto->get_id() . '_carousel_enabled';
		return $this->settings[$carouselEnabledSetting] === 'yes';
	}

	// convert array of cryptos to option array
	private function get_select_options_for_cryptos($cryptos) {
		$selectOptionArray = array();
		foreach ($cryptos as $crypto) {
			$selectOptionArray[$crypto->get_id()] = $crypto->get_name();
		}
		return $selectOptionArray;
	}

	private function get_qr_code($cryptoName, $OrderID, $walletAddress, $cryptoTotal) {
		/*
		$endpoint = 'https://api.qrserver.com/v1/create-qr-code/?data=';
		$formattedName = strtolower(str_replace(' ', '', $cryptoName));
		$qrData = $formattedName . ':' . $walletAddress . '?amount=' . $cryptoTotal;
		return $endpoint . $qrData;
		*/
		if (!file_exists(WP_CONTENT_DIR.'/qrcodes')) {
			mkdir(WP_CONTENT_DIR.'/qrcodes', 0777, true);
			file_put_contents(WP_CONTENT_DIR.'/qrcodes/index.html', null);
		}
		$dirWrite = WP_CONTENT_DIR.'/qrcodes/';
		$formattedName = strtolower(str_replace(' ', '', $cryptoName));
		$qrData = $formattedName . ':' . $walletAddress . '?amount=' . $cryptoTotal;
		try {
			obzsscp_QRcode::png($qrData, $dirWrite . $walletAddress . '-' . $OrderID . '.png', OBZSSCP_QR_ECLEVEL_H);			
		}
		catch (\Exception $e) {
			NMM_Util::log(__FILE__, __LINE__, 'QR code generation failed');
			return null;
		}
		$dirRead = content_url().'/qrcodes/';
		return $dirRead . $walletAddress . '-' . $OrderID .'.png';
	}

	private function output_thank_you_html($crypto, $OrderID, $walletAddress, $cryptoTotal) {
		$formattedPrice = OBZSSCP_Cryptocurrencies::get_price_string($crypto->get_id(), $cryptoTotal);
		$qrCode = $this->get_qr_code($crypto->get_name(), $OrderID, $walletAddress, $formattedPrice);
		$wallet_text_length = strlen($walletAddress) * 14;	
		?>
		<p><?php esc_html_e( 'Here are your cryptocurrency payment details. Please send the exact amount as stated below.', 'sovereign-crypto-payments' ); ?></p>
		<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
			<li>
				<p><?php esc_html_e( 'Cryptocurrency' ); ?>: 
					<strong>
						<span class="woocommerce-Price-amount amount">
							<?php echo '<img src="' . $crypto->get_logo_file_path() . '" style="display: inline; vertical-align: -7px;" />' . ' &nbsp;' . $crypto->get_name() ?>
						</span>
					</strong>
				</p>
			</li>
			<li>
				<p style="word-wrap: break-word;"><?php esc_html_e( 'Wallet Address', 'sovereign-crypto-payments' ); ?>:<br>
					<input type="text" value="<?php echo $walletAddress ?>" class="obzsscp_paymentfield" style="width: <?php echo $wallet_text_length; ?>px;" onclick="this.select()">
				</p>
			</li>
			<li>
				<p style="word-wrap: break-word;"><?php esc_html_e( 'Total', 'sovereign-crypto-payments' ); ?> (<?php echo $crypto->get_id(); ?>):<br>
					<input type="text" value="<?php echo $formattedPrice ?>" class="obzsscp_paymentfield" style="width: 150px;" onclick="this.select()">
				</p>
			</li>
			<li class="woocommerce-order-overview__qr-code">
				<p style="word-wrap: break-word;"><?php esc_html_e( 'QR Code', 'sovereign-crypto-payments' ); ?>:</p>
				<strong>
					<span class="woocommerce-Price-amount amount">
						<img style="width: 200px; height: 200px; margin-top: 3px;" src=<?php echo $qrCode; ?> />
					</span>
				</strong>
			</li>
		</ul>
		<?php
	}

	private function handle_thank_you_refresh($chosenCrypto, $OrderID, $walletAddress, $cryptoTotal) {
		$this->output_thank_you_html($this->cryptos[$chosenCrypto], $OrderID, $walletAddress, $cryptoTotal);
	}

	// this function hits all the crypto exchange APIs that the user selected, then averages them and returns a conversion rate for USD
	// if the user has selected no exchanges to fetch data from it instead takes the average from all of them
	private function get_crypto_value_in_usd($cryptoId, $updateInterval) {
		$prices = array();
		/*
		if ($this->settings['use_crypto_compare'] === 'yes') {
			$ccPrice = OBZSSCP_Exchange::get_cryptocompare_price($cryptoId, $updateInterval);
			if ($ccPrice > 0) {
				$prices[] = $ccPrice;
			}
		}

		if ($this->settings['use_hitbtc'] === 'yes') {
			$hitbtcPrice = OBZSSCP_Exchange::get_hitbtc_price($cryptoId, $updateInterval);
			if ($hitbtcPrice > 0) {
				$prices[] = $hitbtcPrice;
			}
		}
		*/
		if ($this->settings['use_bittrex'] === 'yes') {
			$bittrexPrice = OBZSSCP_Exchange::get_bittrex_price($cryptoId, $updateInterval);
			if ($bittrexPrice > 0) {
				$prices[] = $bittrexPrice;  
			}
		}
		if ($this->settings['use_poloniex'] === 'yes') {
			$poloniexPrice = OBZSSCP_Exchange::get_poloniex_price($cryptoId, $updateInterval);

			// if there were no trades do not use this pricing method
			if ($poloniexPrice > 0) {
				$prices[] = $poloniexPrice;
			}
		}
		if ($this->settings['use_gateio'] === 'yes') {
			$gateioPrice = OBZSSCP_Exchange::get_gateio_price($cryptoId, $updateInterval);
			if ($gateioPrice > 0) {
				$prices[] = $gateioPrice;
			}
		}
		$sum = 0;
		$count = count($prices);
		if ($count === 0) {
			throw new \Exception( esc_html__( 'No cryptocurrency exchanges could be reached, please try again.', 'sovereign-crypto-payments' ) );
		}

		foreach ($prices as $price) {
			$sum += $price;
		}
		$average_price = $sum / $count;
		return $average_price;
	}
}

?>
