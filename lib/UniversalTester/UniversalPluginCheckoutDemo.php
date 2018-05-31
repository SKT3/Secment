<?php
/********************************************************************
 *  @(#)UniversalPluginCheckoutDemo.php                             *
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
 // simulate a typical online shopping card page that the
 // merchant might use for allowing consumers to enter checkout and
 // subsequently enter the Commerce Gateway.
 //

	require_once "../Universal/UniversalPlugin.php";
	require_once "../Universal/UniversalPluginXMLFileParser.php";
	require_once "../Universal/Framework.php";
	require_once "PHPUtils/ACIPHPUtils.php";
	require_once "PHPUtils/Configuration.php";
	//
	if (isset($_REQUEST['destination'])) { // ***ZZ*** 2015.06.01
		$destination = $_REQUEST['destination'];
	}
	else {
		$destination = '';
	}
	if (!strcmp($destination, "Merchant")) {
	    $kickoffURL = "UniversalPluginCheckoutMerchantPaymentPage.php";
	} else {
	    $kickoffURL = "UniversalPluginCheckoutPaymentInit.php";
	}
	//
	$Config = new Configuration('UniversalPluginDemoConfiguration.txt');
	$resourcePath = $Config->get('settings.resourcePath');
	$terminalAlias = $Config->get('settings.alias');
	$tranType = "TranPortal";
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
<form name="transactionForm" action="<?php echo $kickoffURL ?>" method="POST" >
<table width="80%">
	<tr>
		<th colspan="2">Shopping Cart Checkout</th>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
	<tr>
		<td colspan="2">
			  <table cellspacing="0" cellpadding="0">
			    <tr>
			      <td rowspan="5"><image src="images/jacket.gif"/></td><td>Manufacturer: Applied Communications Inc.</td>
			    </tr>
			    <tr>
			      <td>Mfg Part#: SDMX3-533GR</td>
			    </tr>
			    <tr>
			      <td>aciworldwide.com Sku: 2024470129</td>
			    </tr>
			    <tr>
			      <td>Item#: F3PXLS</td>
			    </tr>
			    <tr>
			      <td>Unit Price: $12.34 (Was <span style="color:red; text-decoration: line-through;">$22.00</span>)</td>
			    </tr>
			  </table>
		</td>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
	<tr>
		<td align="right">Quantity:</td>
		<td><input type="text" name="quantity" size="5" value="1"/></td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<input  name="TranType" value="<?php echo $tranType ?>">
			<input type="hidden" name="TerminalAlias" value="<?php echo $terminalAlias?>">
			<input type="hidden" name="ResourcePath" value="<?php echo $resourcePath ?>">
		</td>
	</tr>
	<tr>
		<th colspan="2">&nbsp;</th>
	</tr>
	<tr>
		<td colspan="2">
		<input type="button" name="back" value="Return to Index" onClick="location.href='index.php'" class="checkoutButton">
		<input type="submit" name="proceed" value="Proceed to Checkout" class="checkoutButton">
		</td>
	</tr>
</table>
</form>
</center>
</body>
</html>
