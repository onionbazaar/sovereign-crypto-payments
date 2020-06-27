=== Sovereign Crypto Payments ===
Contributors: OnionBazaar
Donate link: https://onionbazaar.org/?p=donation
Tags: woocommerce, cryptocurrency, crypto, bitcoin, btc, segwit, mpk, xpub, hd wallet, bech32, payment, crypto payments, bitcoin payments, cryptocurrency payments
Requires at least: 3.0.1
Tested up to: 5.4.2
Stable tag: 1.0.3
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Free cryptocurrency payment gateway for WooCommerce. Retrieve Bitcoin directly to your wallet. No signups, fees or 3rd parties.

== Description ==

Sovereign Crypto Payments is a simple and free payment gateway for WooCommerce. No need to register anywhere, payments are made directly to your wallet without redirecting through a 3rd party website. You can either add a list of Bitcoin addresses or enter a Master Public Key (MPK), deriving a unique address for each order. MPK-mode currently only supports Legacy-wallets (xpub), while the regular mode works with all address formats (Legacy, Segwit, Bech32). This plugin is Bitcoin-only for now, more cryptocurrencies will be added soon. Next on the list is Monero and Ethereum/ERC20.

Payments are verified in real-time via blockstream.info block explorer, orders are automatically set to processing after 1 confirmation. Bitcoin price is sourced from bittrex, poloniex and gate.io. Stores with non-USD currencies retrieve rates from exchangeratesapi.io.

Sovereign Crypto Payments is a fork of [Agile Cash](https://wordpress.org/plugins/agile-cash). We simplified the interface, fixed bugs, added local QR code generation and changed the blockchain source to blockstream (supporting all address formats).

For support, head over to the [WordPress Support Forum](https://wordpress.org/support/plugin/sovereign-crypto-payments) or [https://onionbazaar.org/?p=help](https://onionbazaar.org/?p=help) for direct support.

== Features ==

* 100% Free
* No 3rd Parties
* No Signups
* No Intermediaries
* Retrieve Bitcoin directly to your wallet
* All BTC address formats supported (Legacy, Segwit, Bech32)
* Bitcoin address derivation via MPK (xpub), preventing address reuse without having to add them manually
* Automatic payment processing
* Payment verification via blockstream block explorer
* Pricing through multiple exchange rate APIs
* Supports all fiat currencies
* Locally generated QR Code
* Noscript-friendly

== Installation ==

1. Upload the entire `/sovereign-crypto-payments` directory to the `/wp-content/plugins/` directory.
2. Activate Sovereign Crypto Payments through the 'Plugins' menu in WordPress.
3. Open `WooCommerce` -> `Settings` -> `Payment` -> `Cryptocurrency` -> `Manage` to configure the plugin.
4. Enter your wallet addresses or MPK and pick the exchange rate sources.
5. Make a test purchase to confirm everything works correctly.

== Frequently Asked Questions ==

**How to use a Master Public Key (MPK)?**

A Master Public Key is used to derive BTC addresses directly from your wallet, without having to add addresses manually and assuring each address is only used once. To use it, you need the wallet software Electrum (electrum.org). Create a Legacy-wallet and go to `Wallet` -> `Information` to get your MPK (starting with xpub).

Per default Electrum only displays the first 25 addresses. To increase that number (e.g. to 500), click `View` -> `Show Console` and enter the following commands:
 
wallet.change_gap_limit(500)
wallet.storage.write()

== Screenshots ==

1. Simple Address Setup
2. Carousel Mode
3. MPK Mode
4. Pricing Sources
5. Store: Select Payment Gateway
6. Store: Pay for your order

== Changelog ==

= 1.0.3 - 2020-06-27 =
* Update checkout-template

= 1.0.2 - 2020-05-08 =
* Bugfix for some database setups, various smaller edits

= 1.0.1 - 2020-04-22 =
* Bugfix currency-conversion, code-formatting change

= 1.0.0 - 2020-04-21 =
* Fork of Agile Cash 1.8.3. Changed prefixes, fixed bugs, switched blockchain source to blockstream.info and currency conversion to exchangeratesapi.io. Interface simplified and altcoins removed. External QR code changed to local generation. The management and development of the plugin is done by [OnionBazaar](https://onionbazaar.org)
