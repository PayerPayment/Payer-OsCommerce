<?php

require_once("pr_api/payread_post_api.php");
/*
  $Id: pr_cc.php,v 1.1 2015/04/22 11:57:42 bihla Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2008 osCommerce

  Released under the GNU General Public License
 */
define("PR_VERSION", "1.0.9");

class pr_cc {

	var $code, $title, $description, $enabled;

// class constructor
	function pr_cc() {
		global $order, $currency;

		$this->code = 'pr_cc';
		$this->title = MODULE_PAYMENT_PR_CC_TEXT_TITLE;
		$this->description = MODULE_PAYMENT_PR_CC_TEXT_DESCRIPTION;
		$this->sort_order = MODULE_PAYMENT_PR_CC_SORT_ORDER;
		$this->enabled = ((MODULE_PAYMENT_PR_CC_STATUS == 'True') ? true : false);
		$this->name = "Credit Card";
		$this->type = "CARD";

		if ((int) MODULE_PAYMENT_PR_CC_ORDER_STATUS_ID > 0) {
			$this->order_status = MODULE_PAYMENT_PR_CC_NEW_ORDER_STATUS_ID;
		}

		if ($this->code != "pr_" . "cc")
			if ($currency != "SE" && $currency != "se" && $currency != "SEK" && $currency != "sek")
				$this->enabled = false;

		if (is_object($order))
			$this->update_status();

		$thePostApi = new payread_post_api();
		$this->form_action_url = $thePostApi->get_server_url();
	}

// class methods
	function update_status() {
		global $order;

		if (($this->enabled == true) && ((int) MODULE_PAYMENT_PR_CC_ZONE > 0)) {
			$check_flag = false;
			$check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PR_CC_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
			while ($check = tep_db_fetch_array($check_query)) {
				if ($check['zone_id'] < 1) {
					$check_flag = true;
					break;
				} elseif ($check['zone_id'] == $order->billing['zone_id']) {
					$check_flag = true;
					break;
				}
			}

			if ($check_flag == false) {
				$this->enabled = false;
			}
		}
	}

	function javascript_validation() {
		return false;
	}

	function selection() {
		return array('id' => $this->code,
			'module' => '<img src="/images/payer/p_card.png" width="121" height="41" />');
	}

	function pre_confirmation_check() {
		global $cartID, $cart;

		if (empty($cart->cartID)) {
			$cartID = $cart->cartID = $cart->generate_cart_id();
		}

		if (!tep_session_is_registered('cartID')) {
			tep_session_register('cartID');
		}
	}

	function confirmation() {
		global $cartID, $cart_PAYER_ID, $customer_id, $languages_id, $order, $order_total_modules;
		$insert_order = false;

		if (tep_session_is_registered('cart_PAYER_ID')) {
			$order_id = substr($cart_PAYER_ID, strpos($cart_PAYER_ID, '-') + 1);

			$curr_check = tep_db_query("select currency from " . TABLE_ORDERS . " where orders_id = '" . (int) $order_id . "'");
			$curr = tep_db_fetch_array($curr_check);

			if (($curr['currency'] != $order->info['currency']) || ($cartID != substr($cart_PAYER_ID, 0, strlen($cartID)))) {
				$check_query = tep_db_query('select orders_id from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int) $order_id . '" limit 1');

				if (tep_db_num_rows($check_query) < 1) {
					tep_db_query('delete from ' . TABLE_ORDERS . ' where orders_id = "' . (int) $order_id . '"');
					tep_db_query('delete from ' . TABLE_ORDERS_TOTAL . ' where orders_id = "' . (int) $order_id . '"');
					tep_db_query('delete from ' . TABLE_ORDERS_STATUS_HISTORY . ' where orders_id = "' . (int) $order_id . '"');
					tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS . ' where orders_id = "' . (int) $order_id . '"');
					tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . ' where orders_id = "' . (int) $order_id . '"');
					tep_db_query('delete from ' . TABLE_ORDERS_PRODUCTS_DOWNLOAD . ' where orders_id = "' . (int) $order_id . '"');
				}

				$insert_order = true;
			}
		} else {
			$insert_order = true;
		}

		if ($insert_order == true) {
			$order_totals = array();
			if (is_array($order_total_modules->modules)) {
				reset($order_total_modules->modules);
				while (list(, $value) = each($order_total_modules->modules)) {
					$class = substr($value, 0, strrpos($value, '.'));
					if ($GLOBALS[$class]->enabled) {
						for ($i = 0, $n = sizeof($GLOBALS[$class]->output); $i < $n; $i++) {
							if (tep_not_null($GLOBALS[$class]->output[$i]['title']) && tep_not_null($GLOBALS[$class]->output[$i]['text'])) {
								$order_totals[] = array('code' => $GLOBALS[$class]->code,
									'title' => $GLOBALS[$class]->output[$i]['title'],
									'text' => $GLOBALS[$class]->output[$i]['text'],
									'value' => $GLOBALS[$class]->output[$i]['value'],
									'sort_order' => $GLOBALS[$class]->sort_order);
							}
						}
					}
				}
			}

			$sql_data_array = array('customers_id' => $customer_id,
				'customers_name' => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
				'customers_company' => $order->customer['company'],
				'customers_street_address' => $order->customer['street_address'],
				'customers_suburb' => $order->customer['suburb'],
				'customers_city' => $order->customer['city'],
				'customers_postcode' => $order->customer['postcode'],
				'customers_state' => $order->customer['state'],
				'customers_country' => $order->customer['country']['title'],
				'customers_telephone' => $order->customer['telephone'],
				'customers_email_address' => $order->customer['email_address'],
				'customers_address_format_id' => $order->customer['format_id'],
				'delivery_name' => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
				'delivery_company' => $order->delivery['company'],
				'delivery_street_address' => $order->delivery['street_address'],
				'delivery_suburb' => $order->delivery['suburb'],
				'delivery_city' => $order->delivery['city'],
				'delivery_postcode' => $order->delivery['postcode'],
				'delivery_state' => $order->delivery['state'],
				'delivery_country' => $order->delivery['country']['title'],
				'delivery_address_format_id' => $order->delivery['format_id'],
				'billing_name' => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
				'billing_company' => $order->billing['company'],
				'billing_street_address' => $order->billing['street_address'],
				'billing_suburb' => $order->billing['suburb'],
				'billing_city' => $order->billing['city'],
				'billing_postcode' => $order->billing['postcode'],
				'billing_state' => $order->billing['state'],
				'billing_country' => $order->billing['country']['title'],
				'billing_address_format_id' => $order->billing['format_id'],
				'payment_method' => $order->info['payment_method'],
				'cc_type' => $order->info['cc_type'],
				'cc_owner' => $order->info['cc_owner'],
				'cc_number' => $order->info['cc_number'],
				'cc_expires' => $order->info['cc_expires'],
				'date_purchased' => 'now()',
				'orders_status' => $order->info['order_status'],
				'currency' => $order->info['currency'],
				'currency_value' => $order->info['currency_value']);

			tep_db_perform(TABLE_ORDERS, $sql_data_array);

			$insert_id = tep_db_insert_id();

			$sql_data_array = array('orders_id' => $insert_id,
				'orders_status_id' => MODULE_PAYMENT_PR_CC_NEW_ORDER_STATUS_ID,
				'date_added' => 'now()',
				'customer_notified' => '0',
				'comments' => "Payer (" . $this->type . "): New order " . (MODULE_PAYMENT_PR_CC_TESTMODE == 'True' ? MODULE_PAYMENT_PR_CC_TEXT_TESTMODE : ""));

			tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);


			for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
				$sql_data_array = array('orders_id' => $insert_id,
					'title' => $order_totals[$i]['title'],
					'text' => $order_totals[$i]['text'],
					'value' => $order_totals[$i]['value'],
					'class' => $order_totals[$i]['code'],
					'sort_order' => $order_totals[$i]['sort_order']);

				tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
			}

			for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
				$sql_data_array = array('orders_id' => $insert_id,
					'products_id' => tep_get_prid($order->products[$i]['id']),
					'products_model' => $order->products[$i]['model'],
					'products_name' => $order->products[$i]['name'],
					'products_price' => $order->products[$i]['price'],
					'final_price' => $order->products[$i]['final_price'],
					'products_tax' => $order->products[$i]['tax'],
					'products_quantity' => $order->products[$i]['qty']);

				tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

				$order_products_id = tep_db_insert_id();

				$attributes_exist = '0';
				if (isset($order->products[$i]['attributes'])) {
					$attributes_exist = '1';
					for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
						if (DOWNLOAD_ENABLED == 'true') {
							$attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename
                                       from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                       left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                       on pa.products_attributes_id=pad.products_attributes_id
                                       where pa.products_id = '" . $order->products[$i]['id'] . "'
                                       and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "'
                                       and pa.options_id = popt.products_options_id
                                       and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "'
                                       and pa.options_values_id = poval.products_options_values_id
                                       and popt.language_id = '" . $languages_id . "'
                                       and poval.language_id = '" . $languages_id . "'";
							$attributes = tep_db_query($attributes_query);
						} else {
							$attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . $order->products[$i]['id'] . "' and pa.options_id = '" . $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $languages_id . "' and poval.language_id = '" . $languages_id . "'");
						}
						$attributes_values = tep_db_fetch_array($attributes);

						$sql_data_array = array('orders_id' => $insert_id,
							'orders_products_id' => $order_products_id,
							'products_options' => $attributes_values['products_options_name'],
							'products_options_values' => $attributes_values['products_options_values_name'],
							'options_values_price' => $attributes_values['options_values_price'],
							'price_prefix' => $attributes_values['price_prefix']);

						tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

						if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
							$sql_data_array = array('orders_id' => $insert_id,
								'orders_products_id' => $order_products_id,
								'orders_products_filename' => $attributes_values['products_attributes_filename'],
								'download_maxdays' => $attributes_values['products_attributes_maxdays'],
								'download_count' => $attributes_values['products_attributes_maxcount']);

							tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
						}
					}
				}
			}

			$cart_PAYER_ID = $cartID . '-' . $insert_id;
			tep_session_register('cart_PAYER_ID');
		}

		return false;
	}

	function process_button() {
		global $cart_PAYER_ID, $order, $currencies, $customer_id;

		// CHANGED
		$thePostApi = new payread_post_api();

		$round_to = $currencies->get_decimal_places($this->get_currency_code());

		// LOOP - add all the items
		$check_for_discounts_or_fees = 0;
		for ($i = 0; $i < sizeof($order->products); $i++) {
			$product_price_excl_vat = $order->info['currency_value'] * $order->products[$i]['final_price'];
			$product_vat = $product_price_excl_vat * $this->get_tax($i) / 100;
			$product_price_incl_vat = tep_round($product_price_excl_vat + $product_vat, $round_to);

			$thePostApi->add_freeform_purchase(
					$i + 1, $order->products[$i]['name'], $product_price_incl_vat, $this->get_tax($i), $order->products[$i]['qty']);

			$check_for_discounts_or_fees+=$product_price_incl_vat * $order->products[$i]['qty'];
		}
		$i++;

		// Add the shipping cost (if supplied)
		if ($order->info['shipping_method'] != "") {
			$i++;
			$shipping_vat = 25;
			$shipping_price_vat = $order->info['currency_value'] * $order->info['shipping_cost'];
			// $shipping_vat = $shipping_price_excl_vat * $shipping_vat/100;
			// $shipping_price_incl_vat =  tep_round($shipping_price_excl_vat + $shipping_vat, $round_to);

			$thePostApi->add_freeform_purchase(
					$i, $order->info['shipping_method'], $shipping_price_vat, $shipping_vat, 1);

			$check_for_discounts_or_fees += $shipping_price_vat;
		}
		/* add the discount - if explicit text and amount is provided */
		if (isset($order->info['discount_txt'])) {
			$i++;
			$thePostApi->add_freeform_purchase(
					$i, $order->info['discount_txt'], - $order->info['discount'], 25, 1);
		} else {

			/* otherwise - if we have a mismatch between "total" and the added amounts of item and shipping */
			/* we have either a discount OR an added fee generated by some arcane module - we handle the difference anyway */
			if (round($check_for_discounts_or_fees, 0) < round($order->info['total'], 0)) {
				$i++;
				// if the calculated sum is less then the "total" then the total has been recalculated as an increase in price - we call it "Avgift"
				$thePostApi->add_freeform_purchase(
						$i, "Avgift", $order->info['total'] - $check_for_discounts_or_fees, 25, 1);
			}
			if (round($check_for_discounts_or_fees, 0) > round($order->info['total'], 0)) {
				$i++;
				// if the calculated sum is greater then the "total" then the total has been recalculated as an decrease in price - we call it "Kundrabatt"
				$thePostApi->add_freeform_purchase(
						$i, "Kundrabatt", $order->info['total'] - $check_for_discounts_or_fees, 25, 1);
			}
		}

		$thePostApi->set_debug_mode('verbose');

		if (true) {
			// do we have a "personnummer" field is database ? Use it !
			$find_personnummer_field_query = tep_db_query("show columns from " . TABLE_CUSTOMERS);

			$has_personnummer = false;

			while ($fields = tep_db_fetch_array($find_personnummer_field_query))
				if ($fields['Field'] == "customers_personnummer")
					$has_personnummer = true;

			if ($has_personnummer) {
				$customer_query = tep_db_query("select customers_personnummer from " .
						TABLE_CUSTOMERS . " where customers_id = '" . (int) $customer_id . "'");
				$customer = tep_db_fetch_array($customer_query);

				$personnummer = $customer['customers_personnummer'];
			}
		}

		$thePostApi->add_buyer_info($order->customer['firstname'], $order->customer['lastname'], $order->customer['street_address'], $order->customer['suburb'], $order->customer['postcode'], $order->customer['city'], $order->customer['country']['iso_code_2'], $order->customer['telephone'], '', /* phone work */ '', /* phone mobile */ $order->customer['email_address'], $order->customer['company'], "$personnummer", "$customer_id");


		$thePostApi->set_currency($this->get_currency_code());
		$thePostApi->set_test_mode(MODULE_PAYMENT_PR_CC_TESTMODE == "True");
		$thePostApi->add_payment_method($this->type);
		$thePostApi->set_description("Order:" . $cart_PAYER_ID);
		$thePostApi->set_reference_id($cart_PAYER_ID);
		$thePostApi->set_message($order->info['comments']);

		$storeid = 1;

		$thePostApi->set_success_redirect_url(htmlentities(tep_href_link(FILENAME_CHECKOUT_PROCESS)));
		$thePostApi->set_authorize_notification_url(htmlentities(tep_href_link("ext/modules/payment/payer/authorize_callback.php")) . "?storeid=$storeid");
		$thePostApi->set_settle_notification_url(htmlentities(tep_href_link("ext/modules/payment/payer/settle_callback.php")) . "?transaction_type=$this->type&orderid=" . $cart_PAYER_ID . "&customerid=" . $customer_id . "&storeid=$storeid");
		$thePostApi->set_redirect_back_to_shop_url(htmlentities(tep_href_link("checkout_confirmation.php"))); // TODO

		$process_button_string = "\n" .
				tep_draw_hidden_field('payer_agentid', $thePostApi->get_agentid()) . "\n" .
				tep_draw_hidden_field('payer_xml_writer', $thePostApi->get_api_version()) . "\n" .
				tep_draw_hidden_field('payer_data', $thePostApi->get_xml_data()) . "\n" .
				tep_draw_hidden_field('payer_checksum', $thePostApi->get_checksum()) . "\n";

		return $process_button_string;
	}

	function before_process() {
		global $customer_id, $order, $order_totals, $sendto, $billto, $languages_id, $payment, $currencies, $cart;
		global $$payment;
		// initialized for the email confirmation
		$products_ordered = '';

		for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
// Stock Update - Joao Correia
			if (STOCK_LIMITED == 'true') {
				if (DOWNLOAD_ENABLED == 'true') {
					$stock_query_raw = "SELECT products_quantity, pad.products_attributes_filename 
                            FROM " . TABLE_PRODUCTS . " p
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                             ON p.products_id=pa.products_id
                            LEFT JOIN " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                             ON pa.products_attributes_id=pad.products_attributes_id
                            WHERE p.products_id = '" . tep_get_prid($order->products[$i]['id']) . "'";
// Will work with only one option for downloadable products
// otherwise, we have to build the query dynamically with a loop
					$products_attributes = (isset($order->products[$i]['attributes'])) ? $order->products[$i]['attributes'] : '';
					if (is_array($products_attributes)) {
						$stock_query_raw .= " AND pa.options_id = '" . (int) $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . (int) $products_attributes[0]['value_id'] . "'";
					}
					$stock_query = tep_db_query($stock_query_raw);
				} else {
					$stock_query = tep_db_query("select products_quantity from " . TABLE_PRODUCTS . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
				}
				if (tep_db_num_rows($stock_query) > 0) {
					$stock_values = tep_db_fetch_array($stock_query);
// do not decrement quantities if products_attributes_filename exists
					if ((DOWNLOAD_ENABLED != 'true') || (!$stock_values['products_attributes_filename'])) {
						$stock_left = $stock_values['products_quantity'] - $order->products[$i]['qty'];
					} else {
						$stock_left = $stock_values['products_quantity'];
					}
					tep_db_query("update " . TABLE_PRODUCTS . " set products_quantity = '" . (int) $stock_left . "' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
					if (($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false')) {
						tep_db_query("update " . TABLE_PRODUCTS . " set products_status = '0' where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");
					}
				}
			}

// Update products_ordered (for bestsellers list)
			tep_db_query("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

			$sql_data_array = array('orders_id' => $insert_id,
				'products_id' => tep_get_prid($order->products[$i]['id']),
				'products_model' => $order->products[$i]['model'],
				'products_name' => $order->products[$i]['name'],
				'products_price' => $order->products[$i]['price'],
				'final_price' => $order->products[$i]['final_price'],
				'products_tax' => $order->products[$i]['tax'],
				'products_quantity' => $order->products[$i]['qty']);
			tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);
			$order_products_id = tep_db_insert_id();

//------insert customer choosen option to order--------
			$attributes_exist = '0';
			$products_ordered_attributes = '';
			if (isset($order->products[$i]['attributes'])) {
				$attributes_exist = '1';
				for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
					if (DOWNLOAD_ENABLED == 'true') {
						$attributes_query = "select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix, pad.products_attributes_maxdays, pad.products_attributes_maxcount , pad.products_attributes_filename 
                               from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa 
                               left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                on pa.products_attributes_id=pad.products_attributes_id
                               where pa.products_id = '" . (int) $order->products[$i]['id'] . "' 
                                and pa.options_id = '" . (int) $order->products[$i]['attributes'][$j]['option_id'] . "' 
                                and pa.options_id = popt.products_options_id 
                                and pa.options_values_id = '" . (int) $order->products[$i]['attributes'][$j]['value_id'] . "' 
                                and pa.options_values_id = poval.products_options_values_id 
                                and popt.language_id = '" . (int) $languages_id . "' 
                                and poval.language_id = '" . (int) $languages_id . "'";
						$attributes = tep_db_query($attributes_query);
					} else {
						$attributes = tep_db_query("select popt.products_options_name, poval.products_options_values_name, pa.options_values_price, pa.price_prefix from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa where pa.products_id = '" . (int) $order->products[$i]['id'] . "' and pa.options_id = '" . (int) $order->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . (int) $order->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . (int) $languages_id . "' and poval.language_id = '" . (int) $languages_id . "'");
					}
					$attributes_values = tep_db_fetch_array($attributes);

					$sql_data_array = array('orders_id' => $insert_id,
						'orders_products_id' => $order_products_id,
						'products_options' => $attributes_values['products_options_name'],
						'products_options_values' => $attributes_values['products_options_values_name'],
						'options_values_price' => $attributes_values['options_values_price'],
						'price_prefix' => $attributes_values['price_prefix']);
					tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

					if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values['products_attributes_filename']) && tep_not_null($attributes_values['products_attributes_filename'])) {
						$sql_data_array = array('orders_id' => $insert_id,
							'orders_products_id' => $order_products_id,
							'orders_products_filename' => $attributes_values['products_attributes_filename'],
							'download_maxdays' => $attributes_values['products_attributes_maxdays'],
							'download_count' => $attributes_values['products_attributes_maxcount']);
						tep_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);
					}
					$products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . $attributes_values['products_options_values_name'];
				}
			}
//------insert customer choosen option eof ----
			$products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['tax'], $order->products[$i]['qty']) . $products_ordered_attributes . "\n";
		}

// lets start with the email confirmation
		$email_order = STORE_NAME . "\n" .
				EMAIL_SEPARATOR . "\n" .
				EMAIL_TEXT_ORDER_NUMBER . ' ' . $insert_id . "\n" .
				EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $insert_id, 'SSL', false) . "\n" .
				EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n\n";
		if ($order->info['comments']) {
			$email_order .= tep_db_output($order->info['comments']) . "\n\n";
		}
		$email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
				EMAIL_SEPARATOR . "\n" .
				$products_ordered .
				EMAIL_SEPARATOR . "\n";

		for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
			$email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
		}

		if ($order->content_type != 'virtual') {
			$email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
					EMAIL_SEPARATOR . "\n" .
					tep_address_label($customer_id, $sendto, 0, '', "\n") . "\n";
		}

		$email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
				EMAIL_SEPARATOR . "\n" .
				tep_address_label($customer_id, $billto, 0, '', "\n") . "\n\n";
		if (is_object($$payment)) {
			$email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
					EMAIL_SEPARATOR . "\n";
			$payment_class = $$payment;
			$email_order .= $order->info['payment_method'] . "\n\n";
			if (isset($payment_class->email_footer)) {
				$email_order .= $payment_class->email_footer . "\n\n";
			}
		}
		tep_mail($order->customer['firstname'] . ' ' . $order->customer['lastname'], $order->customer['email_address'], EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

// send emails to other people
		if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
			tep_mail('', SEND_EXTRA_ORDER_EMAILS_TO, EMAIL_TEXT_SUBJECT, $email_order, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);
		}

		$cart->reset(true);

// unregister session variables used during checkout
		tep_session_unregister('sendto');
		tep_session_unregister('billto');
		tep_session_unregister('shipping');
		tep_session_unregister('payment');
		tep_session_unregister('comments');
		tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));
	}

	function after_process() {
		return true;
	}

	function get_error() {
		global $HTTP_GET_VARS;

		$error = array('title' => MODULE_PAYMENT_PR_CC_TEXT_ERROR,
			'error' => ((isset($HTTP_GET_VARS['error'])) ? stripslashes(urldecode($HTTP_GET_VARS['error'])) : "Default kortfel!"));

		return $error;
	}

	function check() {
		if (!isset($this->_check)) {
			$check_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_PR_CC_STATUS'");
			$this->_check = tep_db_num_rows($check_query);
		}
		return $this->_check;
	}

	function install() {

		$this->remove();

		$base = "insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) ";

		tep_db_query("$base values ('Enable Payer Module', 'MODULE_PAYMENT_PR_CC_STATUS', 'True', 'Do you want to accept $this->name payments with Payer?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', null,now())");

		tep_db_query("$base values ('Enable Payer TestMode', 'MODULE_PAYMENT_PR_CC_TESTMODE', 'True', 'Do you want to use TestMode? (No real $this->name debits will be made)', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', null,now())");

		tep_db_query("$base values ('Sort order of display.', 'MODULE_PAYMENT_PR_CC_SORT_ORDER', '4', 'Sort order of display. Lowest is displayed first.', '6', '2' ,null, null, now())");

		tep_db_query("$base values ('Payment Zone', 'MODULE_PAYMENT_PR_CC_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '3', 'tep_cfg_pull_down_zone_classes(', 'tep_get_zone_class_title', now())");

		tep_db_query("$base values ('Set New Order Status', 'MODULE_PAYMENT_PR_CC_NEW_ORDER_STATUS_ID', '0', 'Set new orders to this status (not yet paid)', '6', '4', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");

		tep_db_query("$base values ('Set Payed Order Status', 'MODULE_PAYMENT_PR_CC_ORDER_STATUS_ID', '0', 'Set this status to orders that has been paid', '6', '5', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
	}

	function remove() {
		tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
	}

	function keys() {
		return array('MODULE_PAYMENT_PR_CC_STATUS', 'MODULE_PAYMENT_PR_CC_TESTMODE', 'MODULE_PAYMENT_PR_CC_ORDER_STATUS_ID', 'MODULE_PAYMENT_PR_CC_ZONE', 'MODULE_PAYMENT_PR_CC_SORT_ORDER', 'MODULE_PAYMENT_PR_CC_NEW_ORDER_STATUS_ID');
	}

	function get_total_in_currency() {
		global $order, $currencies;
		$rate = isset($order->info['currency_value']) ?
				$order->info['currency_value'] : 1;

		return(tep_round($rate * $order->info['total'], $currencies->get_decimal_places($this->get_currency_code())));
	}

	function get_tax($product) {
		global $order;

		$tax = $order->products[$product]['tax'];
		return $tax;
	}

	function get_currency_code() {
		global $order;

		$code = isset($order->info['currency']) ?
				$order->info['currency'] : "SEK";

		return($code);
	}

}

?>
