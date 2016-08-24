# Payer OsCommerce Module

This is the payment module to get started with Payers payment services in OsCommerce.

For more information about our payment services, please visit [www.payer.se](http://www.payer.se).

## Requirements

  * [OsCommerce](https://www.oscommerce.com): Version 2.3.4
  * [Payer Credentials](https://payer.se) - Missing credentials? Contact the [Customer Service](mailto:kundtjanst@payer.se).

## Installation

  1. Copy the files from the `catalog` directory into the corresponding folder in your OsCommerce installation.
  2. Configure your Payer Credentials. See the `Configuration` section below for more details.
  3. In the `Modules` section in OsCommerce, choose `Payment` to list the payment methods available and click `Install`.

## Configuration

Each module has to be configured correctly with your unique Payer Credentials before it can be used in production. The credentials corresponds to the following parameters:

  * `AGENT ID`
  * `KEY 1`
  * `KEY 2`

The key values can be found under the `Settings/Account` section in [Payer Administration](https://secure.payer.se/adminweb/inloggning/inloggning.php).

Setup the module by replacing the placeholders in the `PayReadConf.php` file with these values. The configuration file can be found in the `includes/modules/payment/pr_api` folder in the root of the directory. And that's it!

## Support

For questions regarding your payment module integration, please contact the Payer [Technican Support](mailto:teknik@payer.se). 