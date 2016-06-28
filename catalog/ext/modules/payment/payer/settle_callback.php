<?php

/*
  $Id: settle_callback.php,v 1.1 2015/04/22 11:57:42 bihla Exp $

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2007 osCommerce

  Released under the GNU General Public License
 */
require_once("../../../../includes/modules/payment/pr_api/payread_post_api.php");

function get_request_url() {
	if (isset($_SERVER["REQUEST_URI"])) {
		return ($_SERVER["SERVER_PORT"] == "80" ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
	} else {
		return ($_SERVER["SERVER_PORT"] == "80" ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"] . "?" . $_SERVER["QUERY_STRING"];
	}
}

$postAPI = new payread_post_api();

if ($postAPI->is_valid_ip() == false) {
	die("FALSE:IP:" . $_SERVER["REMOTE_ADDR"]);
}

if (!($postAPI->validate_callback_url(get_request_url()) == true)) {
	die("FALSE:Checksum error");
}

/*
 * Everything is validated ok - proceed
 */

chdir('../../../../');
require('includes/application_top.php');
global $customer_id, $order, $order_totals, $sendto, $billto, $languages_id, $payment, $currencies, $cart;
$order_id = substr($_GET['orderid'], strpos($_GET['orderid'], '-') + 1);
if (
		isset($order_id) && is_numeric($order_id) &&
		isset($_GET['customerid']) && is_numeric($_GET['customerid'])
) {
	switch ($_GET['payer_payment_type']) {
		/*
		  http://192.168.100.168/oscommerce/v2_3_1/includes/pr_callback/authorize.php
		  ?payer_testmode=true
		  &payer_payment_type=card
		  &payer_callback_type=auth
		  &payread_payment_id=_T_D3@TESTINSTAL4n4arf5n
		  &md5sum=7D32492551A6B8AC70CE238AA32B4189
		 */

		case 'card':
			define(MODULE_PAYMENT_PR_COMMON_NEW_ORDER_STATUS_ID, MODULE_PAYMENT_PR_CC_NEW_ORDER_STATUS_ID);
			define(MODULE_PAYMENT_PR_COMMON_ORDER_STATUS_ID, MODULE_PAYMENT_PR_CC_ORDER_STATUS_ID);
			break;

		case 'bank':
			define(MODULE_PAYMENT_PR_COMMON_NEW_ORDER_STATUS_ID, MODULE_PAYMENT_PR_DB_NEW_ORDER_STATUS_ID);
			define(MODULE_PAYMENT_PR_COMMON_ORDER_STATUS_ID, MODULE_PAYMENT_PR_DB_ORDER_STATUS_ID);
			break;

		case 'invoice':
			define(MODULE_PAYMENT_PR_COMMON_NEW_ORDER_STATUS_ID, MODULE_PAYMENT_PR_IV_NEW_ORDER_STATUS_ID);
			define(MODULE_PAYMENT_PR_COMMON_ORDER_STATUS_ID, MODULE_PAYMENT_PR_IV_ORDER_STATUS_ID);
			break;

		case 'enter':
			define(MODULE_PAYMENT_PR_COMMON_NEW_ORDER_STATUS_ID, MODULE_PAYMENT_PR_EN_NEW_ORDER_STATUS_ID);
			define(MODULE_PAYMENT_PR_COMMON_ORDER_STATUS_ID, MODULE_PAYMENT_PR_EN_ORDER_STATUS_ID);
			break;

		case 'phone':
			define(MODULE_PAYMENT_PR_COMMON_NEW_ORDER_STATUS_ID, MODULE_PAYMENT_PR_TL_NEW_ORDER_STATUS_ID);
			define(MODULE_PAYMENT_PR_COMMON_ORDER_STATUS_ID, MODULE_PAYMENT_PR_TL_ORDER_STATUS_ID);
			break;

		default:
			die("FALSE: Unknown payment type:" . $_GET['payer_payment_type']);
	}

	echo "1:";
	$order_query = tep_db_query("select orders_status, currency, currency_value from " . TABLE_ORDERS . " where orders_id = '" . (int) $order_id . "' and customers_id = '" . (int) $_GET['customerid'] . "'");
	echo "2:";
	if (tep_db_num_rows($order_query) > 0) {
		$order = tep_db_fetch_array($order_query);
		echo "3:";
		if ($order['orders_status'] == MODULE_PAYMENT_PR_CC_NEW_ORDER_STATUS_ID) {
			if (true) {
				echo "4:";
				$total_query = tep_db_query("select value from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int) $order_id . "' and class = 'ot_total' limit 1");
				echo "5:";
				$total = tep_db_fetch_array($total_query);
				echo "6:";
				$comment_status = "Payer (" . $_GET['payer_payment_type'] . ') [' . $_GET['payread_payment_id'] . ']';
				echo "7:";

				$order_status_id = (MODULE_PAYMENT_PR_CC_ORDER_STATUS_ID > 0 ? (int) MODULE_PAYMENT_PR_CC_ORDER_STATUS_ID : (int) DEFAULT_ORDERS_STATUS_ID);
				echo "8:";
				tep_db_query("update " . TABLE_ORDERS . " set orders_status = '" . $order_status_id . "', last_modified = now() where orders_id = '" . (int) $order_id . "'");
				echo "9:";
				echo "10:";
				$notified = (SEND_EMAILS == 'true') ? '1' : '0';
				tep_db_query('INSERT INTO ' . TABLE_ORDERS_STATUS_HISTORY . '(orders_id,orders_status_id,date_added,customer_notified,comments) VALUES(' . $order_id . ',' . $order_status_id . ',now(),' . $notified . ',"' . $comment_status . '")');
				echo "11:";
			}
			echo "12:";
		}
		echo "13:";
	}
	echo "14:";
} else {
	die("FALSE: Incorrect parameters received.");
}
echo "15:";

echo ":TRUE:order_id=$order_id:";
/* stop here - we are ready */
require('includes/application_bottom.php');
?>