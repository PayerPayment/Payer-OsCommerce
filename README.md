# Payer OsCommerce Module

This is the payment module to get started with Payers payment services in OsCommerce.

For more information about our payment services, please visit [www.payer.se](http://www.payer.se).

## Requirements

  * [OsCommerce](https://www.oscommerce.com): Version 2.3.4
  * [Payer Configuration](https://payer.se) - Missing the configuration file? Contact the [Customer Service](mailto:kundtjanst@payer.se).

## Installation

  1. Copy the files from the `catalog` directory into the corresponding folder in your OsCommerce installation.
  2. In the `Modules` section in OsCommerce, choose `Payment` to list the payment methods available and click `Install`.

## Configuration

You need to have your `PayReadConf` file available. Replace that file with the placeholder in the `catalog/includes/modules/payment/pr_api` folder.

## Environment

You can switch between the `test` and `live` environment in the payment method interface through the `Payment Modules` section in VirtueMart. 

**NOTICE** Remember to turn off the test environment before you go in production mode.

## Support

For questions regarding your payment module integration, please contact the Payer [Technican Support](mailto:teknik@payer.se). 