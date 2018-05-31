<?php
/********************************************************************
 *  @(#)UniversalPluginCheckoutFailure.php                          *
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
 // The purpose of the UniversalPluginCheckoutDemo.php is to
 // simulate a typical processing failure page that the
 // merchant might use for handling errors but also adds the error
 // number and error message returned from the Commerce Gateway, which
 // would not be typically shown to consumers.
 //
	if (isset($_REQUEST['error'])) {
		$error     = $_REQUEST['error'];
	}
	else {
		$error = -1;
	}
	if (isset($_REQUEST['errortext'])) {
		$errortext = $_REQUEST['errortext'];
	}
	else {
		$errortext = "";
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
<center>
<table width="80%" style="border: 1px solid darkred;">
	<tr>
		<th><h1><font color="darkred">Your </font>Merchant Logo</h1></th>
	</tr>
</table>
<form name="transactionForm" action="UniversalPluginCheckoutPaymentInit.php" method="POST" >
<table width="80%">
	<tr>
		<th colspan="2">Shopping Cart Checkout Error</th>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
	<tr>
		<td colspan="2"> An error occurred processing your payment.  Please Contact <font color="darkred">Your </font> Merchant for details.</td>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
	<tr>
		<td width="20%" align="right">Error:</td>
		<td><font color="red"><?php echo $error ?></font></td>
	</tr>
	<tr>
		<td width="20%"  align="right">Error Message:</td>
		<td><font color="red"><?php echo $errortext ?></font></td>
	</tr>
	<tr>
		<th colspan="2">&nbsp;</th>
	</tr>
	<tr>
		<td colspan="2">
		<input type="button" name="back" value="Return to Index" onClick="location.href='index.php'" class="checkoutButton">
		</td>
	</tr>
</table>
</form>
</center>
</body>
</html>
