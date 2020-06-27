<?php

// This is the business logic for the Carousel Mode Feature
// We have a hard coded count of five maximum wallets that we cycle through. 0 -> 1 -> 2 -> 3 -> 4

// The database table is: 
//		'cryptocurrency' unique string,
//		'current_index' string,
//      'buffer' string            --Address array

// We store the address array whenever admin settings are saved
// Current index is maintained by this class

// We do inline validation and just skip over any invalid wallets
class OBZSSCP_Carousel {
	private $buffer;
	private $cryptoId;
	private $currentIndex;
	private $carouselSeats = 5;

	public function __construct($cryptoId) {
		$this->cryptoId = $cryptoId;
		$carouselRepo = new OBZSSCP_Carousel_Repo();
		$this->buffer = $carouselRepo->get_buffer($cryptoId);
		$this->currentIndex = $carouselRepo->get_index($cryptoId);
		$this->carouselSeats = 5;
	}

	public function get_next_address() {
		// Set the next address
		$nextAddress = $this->buffer[$this->currentIndex];
		// If we have an invalid address, increment the index and try the next one
		while (!OBZSSCP_Cryptocurrencies::is_valid_wallet_address($this->cryptoId, $nextAddress)) {
			$this->increment_current_index();
			$nextAddress = $this->buffer[$this->currentIndex];
		}
		// increment once after we have found a valid address so we start at the correct index next time
		$this->increment_current_index();
		$carouselRepo = new OBZSSCP_Carousel_Repo();
		// update the index in the database
		$carouselRepo->set_index($this->cryptoId, $this->currentIndex);
		return $nextAddress;
	}

	private function increment_current_index() {
		// increment by one if we aren't at the last index
		if ($this->currentIndex >= 0 && $this->currentIndex < ($this->carouselSeats - 1)) {
			$this->currentIndex = $this->currentIndex + 1;
		}
		// if we are at the last index then start over
		elseif ($this->currentIndex == ($this->carouselSeats - 1)) {
			$this->currentIndex = 0;
		}
		else {
			OBZSSCP_Util::log(__FILE__, __LINE__, 'Invalid current index! Something went wrong, please contact plug-in support.');
		}
	}
}

?>
