<?php
/********************************************************************
 *  @(#)UniversalPluginCheckoutMerchantPaymentPage.php              *
 *                                                                  *
 *  Copyright (c) 2000 - 2007 by ACI Worldwide Inc.                 *
 *  330 South 108th Avenue, Omaha, Nebraska, 68154, U.S.A.          *
 *  All rights reserved.                                            *
 *                                                                  *
 *  This software is the confidential and proprietary information   *
 *  of ACI Worldwide Inc ("Confidential Information").  You shall   *
 *  not disclose such Confidential Information and shall use it     *
 *  only in accordance with the terms of the license agreement      *
 *  you entered with ACI Worldwide Inc.                             *
 ********************************************************************/

 //
 // The purpose of the UniversalPluginCheckoutMerchantPaymentPage.php is to
 // show an example (for PHP) on how to collect the card information and then
 // use the Universal Servlet to call the MPIVerifyEnrollmentInit transaction
 // and show how to handle the return values from it.
 //
 //

	require_once "PHPUtils/ACIPHPUtils.php";
	
	$HTTP_SERVER_VARS = $_SERVER;
	
	session_start();

	// Get the current Context Path for redirects to return
	$currentContext = ACIPHPUtils::getContextPath($HTTP_SERVER_VARS);

	//
	// Get and Store the quantity and price per unit and total price in session for the reciept page.
	// Warning: Because the page is re-entrant, we must only save this off when it's submitted.
	//
	if (strcmp($_REQUEST['quantity'], "")) {
		$quantity = $_REQUEST['quantity'];

		$pricePerUnit = 12.34;
		$price = $quantity * $pricePerUnit;

		$_SESSION['quantity'] = $quantity;
		$_SESSION['unitPrice'] = $pricePerUnit;
		$_SESSION['totalPrice'] = $price;
	}

	// Set Defaults for the form
	$defaultExpiryMonth     = "mm";
	$defaultExpiryYear      = "yyyy";
	$creditcard_expiryMonth = $defaultExpiryMonth;
	$creditcard_expiryYear  = $defaultExpiryYear;

	//
	// Page submits to itself for validating before forwarding...
	//
	$errorMessage = "";
	
	if ((!isset($_REQUEST['Proceed'])) || (!strcmp($_REQUEST['Proceed'], "Proceed"))) {
		if (isset($_REQUEST['creditcard_cardNumber'])) {
			$creditcard_cardNumber      = $_REQUEST['creditcard_cardNumber'];
		}
		else {
			$creditcard_cardNumber      = '';
		}
		if (isset($_REQUEST['creditcard_expiryMonth'])) {
			$creditcard_expiryMonth     = $_REQUEST['creditcard_expiryMonth'];
		}
		else {
			$creditcard_expiryMonth      = '';
		}
		if (isset($_REQUEST['creditcard_cardNumber'])) {
			$creditcard_expiryYear      = $_REQUEST['creditcard_expiryYear'];
		}
		else {
			$creditcard_expiryYear      = '';
		}
		if (isset($_REQUEST['creditcard_cvv'])) {
			$creditcard_cvv             = $_REQUEST['creditcard_cvv'];
		}
		else {
			$creditcard_cvv      = '';
		}
		if (isset($_REQUEST['creditcard_cardholder'])) {
			$creditcard_cardholder      = $_REQUEST['creditcard_cardholder'];
		}
		else {
			$creditcard_cardholder      = '';
		}
		if (isset($_REQUEST['billing_address1'])) {
			$billing_address1           = $_REQUEST['billing_address1'];
		}
		else {
			$billing_address1      = '';
		}
		if (isset($_REQUEST['billing_address2'])) {
			$billing_address2           = $_REQUEST['billing_address2'];
		}
		else {
			$billing_address2      = '';
		}
		if (isset($_REQUEST['billing_city'])) {
			$billing_city               = $_REQUEST['billing_city'];
		}
		else {
			$billing_city      = '';
		}
		if (isset($_REQUEST['billing_state'])) {
			$billing_state              = $_REQUEST['billing_state'];
		}
		else {
			$billing_state       = '';
		}
		if (isset($_REQUEST['billing_zip'])) {
			$billing_zip                = $_REQUEST['billing_zip'];
		}
		else {
			$billing_zip         = '';
		}
		if (isset($_REQUEST['billing_phone'])) {
			$billing_phone              = $_REQUEST['billing_phone'];
		}
		else {
			$billing_phone         = '';
		}

		//
		// Commerce Gateway does not need this information, but it's here for example purposes
		//
		$shipping_recipient = $creditcard_cardholder;
		$shipping_address1 = $billing_address1;
		$shipping_address2 = $billing_address2;
		$shipping_city = $billing_city;
		$shipping_state = $billing_state;
		$shipping_zip = $billing_zip;
		$shipping_phone = $billing_phone;
		//  If the shipping address is different, get those values
		if (isset($_REQUEST['billing_phone'])) {
			$choice_billingOrShipping = $_REQUEST['choice_billingOrShipping'];
		}
		else {
			$choice_billingOrShipping         = '';
		}
		
		if (!strcmp($choice_billingOrShipping, "shipping")) {
			$shipping_recipient = $_REQUEST['shipping_recipient'];
			$shipping_address1 = $_REQUEST['shipping_address1'];
			$shipping_address2 = $_REQUEST['shipping_address2'];
			$shipping_city = $_REQUEST['shipping_city'];
			$shipping_state = $_REQUEST['shipping_state'];
			$shipping_zip = $_REQUEST['shipping_zip'];
			$shipping_phone = $_REQUEST['shipping_phone'];
		}

		//
		// Store the submitted data in session so we can pull it out on the processing page.
		//
		$_SESSION['cardNumber']  = $creditcard_cardNumber;
		$_SESSION['expiryMonth'] = $creditcard_expiryMonth;
		$_SESSION['expiryYear']  = $creditcard_expiryYear;
		$_SESSION['cvv']         = $creditcard_cvv;
		$_SESSION['cardholder']  = $creditcard_cardholder;

		$_SESSION['address']     = $billing_address1 . " " . $billing_address2;
		$_SESSION['city']        = $billing_city;
		$_SESSION['state']       = $billing_state;
		$_SESSION['zip']         = $billing_zip;

		//
		//  Also store the shipping address for the confirmation page
		//
		$_SESSION['shipping_recipient'] = $shipping_recipient;
		$_SESSION['shipping_address1']  = $shipping_address1;
		$_SESSION['shipping_address2']  = $shipping_address2;
		$_SESSION['shipping_city']      = $shipping_city;
		$_SESSION['shipping_state']     = $shipping_state;
		$_SESSION['shipping_zip']       = $shipping_zip;
		$_SESSION['shipping_phone']     = $shipping_phone;

		//
		//  Now validate that required fields are not absent
		//
		if (strlen($billing_zip) == 0) {
		    $errorMessage = "Billing Zip Required.";
		}
		if (strlen($billing_state) == 0) {
		    $errorMessage = "Billing State Required.";
		}
		if (strlen($billing_city) == 0) {
		    $errorMessage = "Billing City Required.";
		}
		if (strlen($billing_address1) == 0) {
		    $errorMessage = "Billing Address Required.";
		}
		if (strlen($creditcard_cardholder) == 0) {
		    $errorMessage = "Card Name Required.";
		}
		if (strlen($creditcard_cvv) == 0) {
		    $errorMessage = "Card Verification Value Required.";
		}
		if (strlen($creditcard_expiryYear) == 0 || !strcmp($creditcard_expiryYear, $defaultExpiryYear)) {
		    $errorMessage = "Card Expiration Year Required.";
		}
		if (strlen($creditcard_expiryMonth) == 0 || !strcmp($creditcard_expiryMonth, $defaultExpiryMonth)) {
		    $errorMessage = "Card Expiration Month Required.";
		}
		if (strlen($creditcard_cardNumber) == 0) {
		    $errorMessage = "Credit Card Number Required.";
		}

		//
		//  If all required fields are present go to the Confirmation Page.
		//
		if (strlen($errorMessage) == 0) {
			header( 'Location: ' . $currentContext . 'UniversalPluginCheckoutMerchantConfirmation.php' ) ;
			exit;
		}

	}
?>
<html>
<head>
<title>Universal Plugin Checkout Demo</title>
	<style type="text/css">
	<?php include "styles/style.css" ?>
	</style>
</head>
<body>
<script language="javascript">
	function showShipping(bShow) {

		var tableObj = document.getElementById("shipping.table.id");
	    if (bShow) {
	        tableObj.style.display = '';
	    } else {
	        tableObj.style.display = 'none';
	    }

	}
</script>
<center>
<table width="80%" style="border: 1px solid darkred;">
	<tr>
		<th><h1><font color="darkred">Your </font>Merchant Logo</h1></th>
	</tr>
</table>
<form name="transactionForm" action="UniversalPluginCheckoutMerchantPaymentPage.php" method="POST" >
<table width="80%">
	<tr>
		<th colspan="2">Payment Details</th>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
<?php
	if (strlen($errorMessage) > 0) {
	    echo "	<tr>\r\n";
	    echo "		<td colspan=\"2\"><h4><font color=\"red\">" . $errorMessage . "</font></h4></td>\r\n";
	    echo "	</tr>\r\n";
	}
?>
	<tr>
		<td colspan="2">
			<fieldset>
				<legend>Payment Instrument</legend>

				<table width="50%" align="center">
					<tr>
					   <td width="50%" align="" nowrap="true">Credit card number:</td>
					   <td colspan="2"><input type="text" name="creditcard_cardNumber" size="33" maxlength="23" value="<?php echo $creditcard_cardNumber?>" autocomplete="off" onfocus="select()"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">Expiration date:</td>
					   <td><input type="text" name="creditcard_expiryMonth" size="3" maxlength="2" value="<?php echo $creditcard_expiryMonth ?>" autocomplete="off" onfocus="select()">
							&nbsp;/&nbsp;
							<input type="text" name="creditcard_expiryYear" size="4" maxlength="4" value="<?php echo $creditcard_expiryYear ?>" autocomplete="off" onfocus="select()">
					   </td>
					   <td>CVC: <input type="text" name="creditcard_cvv" size="4" maxlength="4" value="<?php echo $creditcard_cvv ?>" autocomplete="off"></td>
					</tr>
					<tr>
					   <td width="50%" align="" nowrap="true">Cardholder name:</td>
					   <td colspan="2"><input type="text" name="creditcard_cardholder" size="33"  value="<?php echo $creditcard_cardholder ?>" autocomplete="off" onfocus="select()"></td>
					</tr>
				</table>
			</fieldset>
		</td>
	</tr>
	<tr>
		<th colspan="2">&nbsp;</th>
	</tr>
	<tr>
		<td colspan="2">
			<fieldset>
				<legend>Delivery:</legend>
				<table width="50%" align="center">
					<tr>
					   <td width="50%" nowrap="true">Billing address:</td>
					   <td><input type="text" name="billing_address1" size="33" value="<?php echo $billing_address1 ?>"></td>
					</tr>
					<tr>
					   <td></td>
					   <td><input type="text" name="billing_address2" size="33" value="<?php echo $billing_address2 ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">City / Town:</td>
					   <td><input type="text" name="billing_city" size="33" value="<?php echo $billing_city ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">State / Province:</td>
					   <td><input type="text" name="billing_state" size="33" value="<?php echo $billing_state ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">Zip:</td>
					   <td><input type="text" name="billing_zip" size="33" value="<?php echo $billing_zip ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">Phone number:</td>
					   <td><input type="text" name="billing_phone" size="33" value="<?php echo $billing_phone ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">My shipping address is:</td>
					   <td>
					   		<input type="radio" name="choice_billingOrShipping" value="billing" onClick="showShipping(false);" checked="true"> My Billing Address<br/>
					   </td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true"></td>
					   <td>
					   		<input type="radio" name="choice_billingOrShipping" value="shipping" onClick="showShipping(true);"> A different address
						</td>
					</tr>
				</table>
				<table width="50%" align="center" name="shipping.table" id="shipping.table.id" style="display:none;">
					<tr>
					   <td width="50%" nowrap="true">Recipient's name:</td>
					   <td><input type="text" name="shipping_recipient" size="33" value="<?php echo $shipping_recipient ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">Shipping address:</td>
					   <td><input type="text" name="shipping_address1" size="33" value="<?php echo $shipping_address1 ?>"></td>
					</tr>
					<tr>
					   <td></td>
					   <td><input type="text" name="shipping_address2" size="33" value="<?php echo $shipping_address2 ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">City / Town:</td>
					   <td><input type="text" name="shipping_city" size="33" value="<?php echo $shipping_city ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">State / Province:</td>
					   <td><input type="text" name="shipping_state" size="33" value="<?php echo $shipping_state ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">Zip:</td>
					   <td><input type="text" name="shipping_zip" size="33" value="<?php echo $shipping_zip ?>"></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">Phone number:</td>
					   <td><input type="text" name="shipping_phone" size="33" value="<?php echo $shipping_phone ?>"></td>
					</tr>
				</table>


			</fieldset>
		</td>
	</tr>
	<tr>
		<td colspan="2">
		<input type="button" name="back" value="Cancel" onClick="location.href='index.php'" class="checkoutButton">
		<input type="submit" name="Proceed" value="Proceed" class="checkoutButton">
		</td>
	</tr>
</table>
</form>
</center>
</body>
</html>
