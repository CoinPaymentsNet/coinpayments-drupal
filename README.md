#Drupal Commerce

IMPORTANT NOTE:

This is only for use with: https://alpha.coinpayments.net/

NOT for use with https://coinpayments.net

Demonstration Website Disclaimer:   The information presented on alpha.coinpayments.net (the "Demo Site") is for demonstration purposes only. All content on the Demo Site is considered “in development” and should be used at your own risk. CoinPayments Inc. assumes no responsibility or liability for any errors or omissions in the content of the Demo Site. The information contained in the Demo Site is provided on an "as is" basis with no guarantees of completeness, accuracy, usefulness or timeliness and without any warranties of any kind whatsoever, express or implied. CoinPayments Inc. does not warrant that the Demo Site and any information or material downloaded from the Demo Site, will be uninterrupted, error-free, omission-free or free of viruses or other harmful items.

In no event will CoinPayments Inc. or its directors, officers, employees, shareholders, service providers or agents, be liable to you, or anyone else, for any decision(s) made or action(s) taken in reliance upon the information contained in the Demo Site, nor for any direct, indirect, incidental, special, exemplary, punitive, consequential, or other damages whatsoever (including, but not limited to, liability for loss of use, funds, data or profits) whether in an action of contract, statute, tort or otherwise, relating to the use of the Demo Site.

Commerce Coinpayments
=====================
CoinPayments.net Payments Module for Drupal Commerce, which accepts all
cryptocurrencies for payments in your drupal site. To check the supported
coins visit - https://www.coinpayments.net/supported-coins

Installation:
=============
  Using drush:
  - To download module use command
  
    `drush dl commerce_coinpayments`
  - To install module use command
  
    `drush en commerce_coinpayments`

  Manual:
  - Extract the files in your module directory (web/modules/commerce_coinpayments).

  Administrator interface:
  - Go to Administration Extend aria and press "Install new module"
  - Install for url https://www.drupal.org/project/commerce_coinpayments
  - Upload a module archive to install


  After:
  - Visit the modules page and enable the module.
  - From the modules page you will find links to
    - help

This module is safe to use on a production site.

How to Configure Module:
========================
 - Go to Commerce Store, Configuration, Payment gateways.
 - Click on "Add payment gateway" button to add new payment gateway.
 - Insert Name & Display name for new payment gateway & select
   "CoinPayments.net - Pay with Bitcoin, Litecoin, and other
   cryptocurrencies (Off-site redirect)" plugin from the given list.
 - Enter your Client ID
 - Enable Webhooks and enter Client Secret to receive CoinPayments.net webhooks
 - And you should be all set to use this module.
 - Click Save.

How to Test Module:
===================
 - First you need to create store (https://www.example.com/admin/commerce/config/stores)
   before creating any commerce product in drupal site.
 - Then create a new product(https://www.example.com/admin/commerce/products).
 - Add newly created product to cart using “add to cart” button on product page.
 - Goto the cart page - https://www.example.com/cart & click on checkout button & proceed to checkout.
 - Fill up the details & click on “continue to review” button & then click on “pay & complete purchase”.
 - User will be redirected to coinpayments page & after selecting currency barcode will generate.
 - After successful payment you will see the order status completed on
   link “https://www.example.com/admin/commerce/orders”
