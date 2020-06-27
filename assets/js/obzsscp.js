jQuery(document).ready(function($) {
	var cryptos = [
		{id: 'BTC', electrum: true, autopayment: true}, 
		{id: 'LTC', electrum: true, autopayment: true},
		{id: 'DOGE', electrum: false, autopayment: true},
		{id: 'ETH', electrum: false, autopayment: true},
		{id: 'XMR', electrum: false, autopayment: false},
		{id: 'ZEC', electrum: false, autopayment: true},
		{id: 'DASH', electrum: false, autopayment: true},
		{id: 'XRP', electrum: false, autopayment: true},
		{id: 'BCH', electrum: false, autopayment: true},
		{id: 'ONION', electrum: false, autopayment: true},
		{id: 'BLK', electrum: false, autopayment: true},
		{id: 'VRC', electrum: false, autopayment: false},
		{id: 'ETC', electrum: false, autopayment: true},
		{id: 'REP', electrum: false, autopayment: true},
		{id: 'BTG', electrum: false, autopayment: false},
		{id: 'EOS', electrum: false, autopayment: true},
		{id: 'BSV', electrum: false, autopayment: true},
		{id: 'VET', electrum: false, autopayment: false},
		{id: 'TRX', electrum: false, autopayment: true},
		{id: 'XLM', electrum: false, autopayment: true},
		{id: 'QTUM', electrum: true, autopayment: true},
		{id: 'ADA', electrum: false, autopayment: true},
		{id: 'XTZ', electrum: false, autopayment: true},
		{id: 'GNO', electrum: false, autopayment: true},
		{id: 'MLN', electrum: false, autopayment: true},

		{id: 'ONT', electrum: false, autopayment: false},
		{id: 'BAT', electrum: false, autopayment: true},
		{id: 'BCD', electrum: false, autopayment: false},
		{id: 'BCN', electrum: false, autopayment: false},
		{id: 'BNB', electrum: false, autopayment: true},
		{id: 'DCR', electrum: false, autopayment: false},
		{id: 'DGB', electrum: false, autopayment: false},
		{id: 'HOT', electrum: false, autopayment: true},
		{id: 'LINK', electrum: false, autopayment: true},
		{id: 'LSK', electrum: false, autopayment: false},
		{id: 'MIOTA', electrum: false, autopayment: false},
		{id: 'MKR', electrum: false, autopayment: true},
		{id: 'OMG', electrum: false, autopayment: true},
		{id: 'POT', electrum: false, autopayment: false},
		{id: 'WAVES', electrum: false, autopayment: false},
		{id: 'XEM', electrum: false, autopayment: false},
		{id: 'ZRX', electrum: false, autopayment: true},
		{id: 'GUSD', electrum: false, autopayment: true}
	];

	var cryptoPaymentEnabledId = '#woocommerce_obzsscp_gateway_enabled';
	var disabledWarningMessage = 'Cryptocurrency payments are disabled!';

	// add label to hide/unhide later, if crypto is disabled show warning message
	if ($(cryptoPaymentEnabledId).is(":checked")) {
		$(cryptoPaymentEnabledId)
			.parent()
			.parent()
			.append($('<label id="crypto_disabled_message" style="display:none;">')
			.css('color', 'red')
			.html(disabledWarningMessage));
	}
	else {
		$(cryptoPaymentEnabledId)
			.parent()
			.parent()
			.append($('<label id="crypto_disabled_message">')
			.css('color', 'red')
			.html(disabledWarningMessage));
	}

	$(cryptoPaymentEnabledId).on('change', function () {
		$('#crypto_disabled_message').toggle();
	});

	var hideRow = function (crypto, settingName) {
		var elementId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_' + settingName;
		$(elementId).parents('tr').hide();
	}
	var showRow = function (crypto, settingName) {
		var elementId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_' + settingName;
		$(elementId).parents('tr').show();
	}
	var isCryptoEnabled = function (crypto) {
		var cryptoEnabledId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_enabled';
		return $(cryptoEnabledId).is(':checked');
	}
	var isCryptoElectrumEnabled = function (crypto) {
		var cryptoElectrumEnabledId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_electrum_enabled';
		return $(cryptoElectrumEnabledId).is(':checked');
	}
	var isCryptoAutoPaymentEnabled = function (crypto) {
		var cryptoAutoPaymentEnabledId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_autopayment_enabled';
		return $(cryptoAutoPaymentEnabledId).is(':checked');
	}
	var isCryptoCarouselEnabled = function (crypto) {
		var cryptoMultiAddressEnabledId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_carousel_enabled';
		return $(cryptoMultiAddressEnabledId).is(':checked');
	}

	var setElectrumEnabled = function(crypto, isEnabled) {
		var elementId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_electrum_enabled';
		$(elementId).prop("checked", isEnabled);
	}

	var setAutoPaymentEnabled = function(crypto, isEnabled) {
		var elementId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_autopayment_enabled';
		$(elementId).prop("checked", isEnabled);
	}

	var setCarouselEnabled = function(crypto, isEnabled) {
		var elementId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_carousel_enabled';
		$(elementId).prop("checked", isEnabled);
	}

	var hideCarouselSettings = function (crypto) {
		hideRow(crypto, 'address2');
		hideRow(crypto, 'address3');
		hideRow(crypto, 'address4');
		hideRow(crypto, 'address5');	
	}

	var showCarouselSettings = function (crypto) {
		showRow(crypto, 'address2');
		showRow(crypto, 'address3');
		showRow(crypto, 'address4');
		showRow(crypto, 'address5');
	}

	var hideElectrumSettings = function (crypto) {
		hideRow(crypto, 'electrum_mpk');
		hideRow(crypto, 'electrum_percent_to_process');
		hideRow(crypto, 'electrum_required_confirmations');
		hideRow(crypto, 'electrum_order_cancellation_time_hr');
	}

	var showElectrumSettings = function (crypto) {
		showRow(crypto, 'electrum_mpk');
		showRow(crypto, 'electrum_percent_to_process');
		showRow(crypto, 'electrum_required_confirmations');
		showRow(crypto, 'electrum_order_cancellation_time_hr');
	}

	var hideAutoPaymentSettings = function (crypto) {
		hideRow(crypto, 'autopayment_percent_to_process');
		hideRow(crypto, 'autopayment_required_confirmations');
		hideRow(crypto, 'autopayment_order_cancellation_time_hr');
	}

	var showAutoPaymentSettings = function (crypto) {
		showRow(crypto, 'autopayment_percent_to_process');
		showRow(crypto, 'autopayment_required_confirmations');
		showRow(crypto, 'autopayment_order_cancellation_time_hr');
	}

	var hideAllRows = function (crypto) {
		var supportsElectrum = crypto.electrum;
		var supportsAutoPayment = crypto.autopayment;

		hideRow(crypto, 'address');

			if (supportsElectrum) {
				hideRow(crypto, 'electrum_enabled');
				hideElectrumSettings(crypto);
			}

			if (supportsAutoPayment) {
				hideRow(crypto, 'autopayment_enabled');
				hideAutoPaymentSettings(crypto);
			}

			hideRow(crypto, 'carousel_enabled');
			hideCarouselSettings(crypto);
	}

	// initially hide rows that should not be displayed
	cryptos.forEach(function (crypto) {		
		var supportsElectrum = crypto.electrum;
		var supportsAutoPayment = crypto.autopayment;
		
		// If the crypto is disabled hide everything
		if (!isCryptoEnabled(crypto)) {
			hideAllRows(crypto);
		}

		if (isCryptoEnabled(crypto)) {

			if (supportsElectrum && supportsAutoPayment) {
				if (!isCryptoElectrumEnabled(crypto) && !isCryptoAutoPaymentEnabled(crypto)) {
					hideElectrumSettings(crypto);
					hideAutoPaymentSettings(crypto);
				}
				else if (isCryptoElectrumEnabled(crypto) && !isCryptoAutoPaymentEnabled(crypto)) {					
					hideAutoPaymentSettings(crypto);
					hideRow(crypto, 'carousel_enabled');
					hideRow(crypto, 'address');
					hideRow(crypto, 'autopayment_enabled');
				}
				else if (!isCryptoElectrumEnabled(crypto) && isCryptoAutoPaymentEnabled(crypto)) {					
					hideElectrumSettings(crypto);
				}
			}
			else if (supportsElectrum) {
				if (!isCryptoElectrumEnabled(crypto)) {
					hideElectrumSettings(crypto);
				}
			}
			else if (supportsAutoPayment) {
				if (!isCryptoAutoPaymentEnabled(crypto)) {
					hideAutoPaymentSettings(crypto);
				}
			}
			
			if (!isCryptoCarouselEnabled(crypto)) {
				hideCarouselSettings(crypto);
			}
		}
	});

	// hook up on-change handler to checkboxes
	cryptos.forEach(function (crypto) {
		var cryptoEnabledId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_enabled';

		var supportsElectrum = crypto.electrum;
		var supportsAutoPayment = crypto.autopayment;

		// When a crypto is enabled/disabled
		$(cryptoEnabledId).on('change', function () {
			var cryptoEnabled = $(this).is(':checked');

			// If admin enabled crypto
			if (!cryptoEnabled) {
				hideAllRows(crypto);
			}
			else {
				showRow(crypto, 'address');
				showRow(crypto, 'carousel_enabled');

				if (supportsElectrum && supportsAutoPayment) {
					if (!isCryptoElectrumEnabled(crypto) && !isCryptoAutoPaymentEnabled(crypto)) {
						showRow(crypto, 'electrum_enabled');
						showRow(crypto, 'autopayment_enabled');
					}
					else if (isCryptoElectrumEnabled(crypto) && !isCryptoAutoPaymentEnabled(crypto)) {
						//showRow(crypto, 'electrum_enabled');
						hideRow(crypto, 'autopayment_enabled');
						hideRow(crypto, 'address');
						hideRow(crypto, 'carousel_enabled');
						showElectrumSettings(crypto);
					}
					else if (isCryptoAutoPaymentEnabled(crypto)) {
						showRow(crypto, 'autopayment_enabled');
						//hideRow(crypto, 'electrum_enabled');
						showAutoPaymentSettings(crypto);
					}
				}
				else if (supportsElectrum && !supportsAutoPayment) {
					showRow(crypto, 'electrum_enabled');

					if (isCryptoElectrumEnabled(crypto)) {
						
						showElectrumSettings(crypto);

						hideRow(crypto, 'address');
						hideRow(crypto, 'carousel_enabled');
					}					
				}
				else if (supportsAutoPayment && !supportsElectrum) {
					showRow(crypto, 'autopayment_enabled');
					
					if (isCryptoAutoPaymentEnabled(crypto)) {
						showAutoPaymentSettings(crypto);
					}
				}

				if (isCryptoCarouselEnabled(crypto)) {
					showCarouselSettings(crypto);
				}
			}			
		});

		var cryptoCarouselEnabledId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_carousel_enabled';

		// Whenever admin enables/disables carousel mode
		$(cryptoCarouselEnabledId).on('change', function() {
			var carouselEnabled = $(this).is(':checked');

			if (carouselEnabled) {
				showCarouselSettings(crypto);
			}
			else {
				hideCarouselSettings(crypto);
			}
		});

		if (supportsElectrum) {
			var cryptoElectrumEnabledId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_electrum_enabled';

			// Whenever admin enables/disables electrum mode
			$(cryptoElectrumEnabledId).on('change', function() {
				var electrumEnabled = $(this).is(':checked');

				if (electrumEnabled) {
					showElectrumSettings(crypto);
					setCarouselEnabled(crypto, false);
					hideRow(crypto, 'address');
					hideRow(crypto, 'carousel_enabled');					
					hideCarouselSettings(crypto);
					// If the crypto supports both electrum and autopayment, hide the autopayment mode when electrum is enabled
					if (supportsAutoPayment) {
						setAutoPaymentEnabled(crypto, false);
						hideRow(crypto, 'autopayment_enabled');
						hideAutoPaymentSettings(crypto);
					}
				}
				else {
					hideElectrumSettings(crypto);
					showRow(crypto, 'carousel_enabled');
					showRow(crypto, 'autopayment_enabled');
					showRow(crypto, 'address');
				}
			});
		}

		if (supportsAutoPayment) {
			var cryptoAutoPaymentEnabledId = '#woocommerce_obzsscp_gateway_' + crypto.id + '_autopayment_enabled';

			// Whenever admin enables/disables autopayment mode
			$(cryptoAutoPaymentEnabledId).on('change', function () {
				var autoPaymentEnabled = $(this).is(':checked');

				if (autoPaymentEnabled) {
					showAutoPaymentSettings(crypto);
					showRow(crypto, 'address');
					if (supportsElectrum) {
						setElectrumEnabled(crypto, false);
						//hideRow(crypto, 'electrum_enabled');
						hideElectrumSettings(crypto);
						showRow(crypto, 'carousel_enabled');
					}
				}
				else {
					hideAutoPaymentSettings(crypto);
					//showRow(crypto, 'electrum_enabled');
				}
			});
		}


	});
});
