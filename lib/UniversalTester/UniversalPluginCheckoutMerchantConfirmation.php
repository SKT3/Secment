<?php
/********************************************************************
 *  @(#)UniversalPluginCheckoutMerchantConfirmation.php             *
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
 // The purpose of the UniversalPluginCheckoutMerchantConfirmation.php is to
 // simulate a typical confirmation page that the merchant might use during an
 // MPI Transaction Flow.
 //

	session_start();

	//
	//  For showing on the confirmation, pull out the amount, quantity and total
	//
	$quantity = $_SESSION['quantity'];
	$unitPrice = $_SESSION['unitPrice'];
	$totalPrice = $_SESSION['totalPrice'];

	//
	//  Harvest submitted customer card and shipping address information
	//
	$creditcard_cardNumber = $_SESSION['cardNumber'];
	$creditcard_expiryMonth = $_SESSION['expiryMonth'];
	$creditcard_expiryYear = $_SESSION['expiryYear'];
	$creditcard_cvv = $_SESSION['cvv'];
	$creditcard_cardholder = $_SESSION['cardholder'];

	//
	// Now mask the card to only show the last 4 digits
	//
	$len = strlen($creditcard_cardNumber);
	for ($i = 0; $i < ($len - 4); $i = $i + 1 ){
		$asterixes .= "*";
	}
	$creditcard_cardNumber_masked = $asterixes . substr($creditcard_cardNumber, $len - 4);

	//
	// Commerce Gateway does not need this information, but it's here for example purposes
	//
	$shipping_recipient             = $_SESSION['shipping_recipient'];
	$shipping_address1              = $_SESSION['shipping_address1'];
	$shipping_address2              = $_SESSION['shipping_address2'];
	$shipping_city                  = $_SESSION['shipping_city'];
	$shipping_state                 = $_SESSION['shipping_state'];
	$shipping_zip                   = $_SESSION['shipping_zip'];
	$shipping_phone                 = $_SESSION['shipping_phone'];

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
<form name="transactionForm" action="UniversalPluginCheckoutMerchantProcessPayment.php" method="POST" >
<table width="80%">
	<tr>
		<th colspan="2">Confirmation</th>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>

	<tr>
		<td colspan="2">
			<fieldset>
				<legend>Item Summary</legend>
				<table width="50%" align="center">
					<tr style="background-color: lightblue;">
						<td>Mfg Part#</td>
						<td>Sku #</td>
						<td>Item#</td>
						<td>Quantity</td>
						<td>Unit Price</td>
						<td>Total</td>
					</tr>
					<tr>
						<td>SDMX3-533GR</td>
						<td>2024470129</td>
						<td>F3PXLS</td>
						<td><?php echo $quantity ?>&nbsp;</td>
						<td><?php echo $unitPrice ?>&nbsp;</td>
						<td><?php echo $totalPrice ?>&nbsp;</td>
					</tr>
				</table>
			</fieldset>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<fieldset>
				<legend>Payment Details</legend>

				<table width="50%" align="center">
					<tr>
					   <td width="50%" align="" nowrap="true">Credit card number:</td>
					   <td colspan="2"><?php echo $creditcard_cardNumber_masked ?></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">Expiration date:</td>
					   <td><?php echo $creditcard_expiryMonth ?>&nbsp;/&nbsp;<?php echo $creditcard_expiryYear ?></td>
					   <td>CVC: <?php echo $creditcard_cvv ?></td>
					</tr>
					<tr>
					   <td width="50%" align="" nowrap="true">Cardholder name:</td>
					   <td colspan="2"><?php echo $creditcard_cardholder ?></td>
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
				<legend>To be Delivered to:</legend>
				<table width="50%" align="center">
					<tr>
					   <td width="50%" nowrap="true">Recipient's name:</td>
					   <td><?php echo $shipping_address1 ?></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">address:</td>
					   <td><?php echo $shipping_address1 ?></td>
					</tr>
					<tr>
					   <td></td>
					   <td><?php echo $shipping_address2 ?></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">City / Town:</td>
					   <td><?php echo $shipping_city ?></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">State / Province:</td>
					   <td><?php echo $shipping_state ?></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">Zip:</td>
					   <td><?php echo $shipping_zip ?></td>
					</tr>
					<tr>
					   <td width="50%" nowrap="true">Phone number:</td>
					   <td><?php echo $shipping_phone ?></td>
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
		<input type="button" name="back" value="Back" onClick="history.back()" class="checkoutButton">
		<input type="submit" name="submit" value="Confirm" class="checkoutButton">
		</td>
	</tr>
</table>
</form>
</center>
</body>
</html>
