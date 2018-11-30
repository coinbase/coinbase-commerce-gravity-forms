**Coinbase Commerce Payment Gateway For Gravity Forms**
-------
* Requires at least: 4.0
* Tested up to: 5.0
* License: GPLv2 or later
* License URI: http://www.gnu.org/licenses/gpl-2.0.html

Coinbase Commerce is a service that enables merchants to accept multiple cryptocurrencies directly into a user-controlled wallet.

### Description

Coinbase Commerce is the digital payment service that allows merchants to accept payments in a variety of digital currencies like Bitcoin, Bitcoin Cash, Ethereum and Litecoin. These payments are received directly into a merchant-controlled wallet.

Features:

* Fast — Go live in minutes, not days
* Free — No fees to accept crypto
* Global — Tap into a global customer base
* No Middlemen — Be your own bank
* Irreversible — Chargebacks are a thing of the past

**Requirements**
-------
* [Gravity Forms](https://www.gravityforms.com/) plugin 2.0 or later.

### Installation

This section describes how to install and setup the Gravity Forms Coinbase Commerce Add-On. Be sure to follow *all* of the instructions in order for the Add-On to work properly.

### Requirements

Requires at least WordPress 4.0, PHP 5.3, [Gravity Forms](https://rocketgenius.pxf.io/c/1212782/445235/7938) 1.9.

### Steps

1. Make sure you have your own copy of [Gravity Forms](https://www.gravityforms.com/). This plugin does not include Gravity Forms.

2. You'll also need a Coinbase Commerce account. To signup visit https://commerce.coinbase.com/

3. Upload the plugin to your WordPress site /wp-content/plugins folder.

4. Visit the **Plugins** menu in your WordPress dashboard, find `Coinbase Commerce Payments For Gravity Forms` in your plugin list, and click the Activate link.

5. Visit the **Forms->Settings** menu, select the Coinbase Commerce Tab, and add your API Key, Shared Secret Key from Coinbase Commerce Dashboard (https://commerce.coinbase.com/dashboard/settings).

6. Copy Webhook Notification Url from Coinbase Commerce Tab to Settings/Webhook subscription https://commerce.coinbase.com/dashboard/settings

7. Create new Gravity form and add Coinbase Commerce Feed.

### Frequently Asked Questions

**Do I need to have SSL?**  
Yes, for webhook notifications you need SSL.

**Does Coinbase Commerce integrate with other e-commerce platforms?**  
Yes, a list of all offical integrations can be found here: [Coinbase Commerce Integrations](https://commerce.coinbase.com/integrate).

### Changelog

**1.0.0**

* Initial release version
