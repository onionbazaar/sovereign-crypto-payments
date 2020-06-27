<?php
// Dummy plain object
class OBZSSCP_Cryptocurrency {

	private $id;
	private $name;
	private $roundPrecision;
	private $logoFilePath;
	private $updateInterval;
	private $symbol;
	private $hasElectrum;
	private $paymentVerification;
	private $needsConfirmations;

	public function __construct($id, $name, $roundPrecision, $logoFilePath, $updateInterval, $symbol, $hasElectrum, $paymentVerification, $needsConfirmations) {
		$this->id = $id;
		$this->name = $name;
		$this->roundPrecision = $roundPrecision;
		$this->logoFilePath = $logoFilePath;
		$this->updateInterval = $updateInterval;
		$this->symbol = $symbol;
		$this->hasElectrum = $hasElectrum;
		$this->paymentVerification = $paymentVerification;
		$this->needsConfirmations = $needsConfirmations;
	}

	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function get_round_precision() {
		return $this->roundPrecision;
	}

	public function get_logo_file_path() {
		return OBZSSCP_PLUGIN_DIR . '/assets/img/' . $this->logoFilePath;
	}

	public function get_update_interval() {
		return $this->updateInterval;
	}

	public function get_symbol() {
		return $this->symbol;
	}
	public function has_electrum() {
		return $this->hasElectrum;
	}

	public function has_payment_verification() {
		return $this->paymentVerification;
	}

	public function needs_confirmations() {
		return $this->needsConfirmations;
	}
}

?>
