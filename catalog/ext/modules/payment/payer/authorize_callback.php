<?php

/*
  $Id: authorize_callback.php,v 1.1 2015/04/22 11:57:42 bihla Exp $

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
	die("FALSE:" . $_SERVER["REMOTE_ADDR"]);
}

if (!($postAPI->validate_callback_url(get_request_url()) == true)) {
	die("FALSE:Checksum error");
}

die("TRUE:Proceed to settle");
?>