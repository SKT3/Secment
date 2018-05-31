<?php
/********************************************************************
 *  @(#)UniversalPluginCheckoutMerchantProcessPayment.php           *
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
 // The purpose of the UniversalPluginCheckoutMerchantProcessPayment.php is to
 // Show post-Confirmation processing for the first leg of the MPI Transaction Flow.
 // In this leg the MPIVerifyEnrollMent is performed, and if successful will return the
 // Enrollment status of the consumer.
 //
 // If the user is ENROLLED then this page redirects the consumer browser into the
 // Commerce Gateway via the returned url which will allow the consumer to authenticate with the
 // ACS.
 //
 // If the user is NOT ENROLLED then by default the Commerce Gateway will have performed
 // the requested transaction as a Credit Card and returned the status of the purchase (aka the flow is done).
 // In this case the consumer is forwarded to the UniversalPluginCheckoutReceipt.php page.
 //

 	require_once "../Universal/UniversalPlugin.php";
	require_once "../Universal/UniversalPluginXMLFileParser.php";
	require_once "../Universal/Framework.php";
	require_once "PHPUtils/ACIPHPUtils.php";
	require_once "PHPUtils/Configuration.php";

 	session_start();

	// Get the current Context Path for redirects to return
	$currentContext = ACIPHPUtils::getContextPath($HTTP_SERVER_VARS);

	$Config = new Configuration('UniversalPluginDemoConfiguration.txt');
	$resourcePath = $Config->get('settings.resourcePath');
	$terminalAlias = $Config->get('settings.alias');

 	//
	// Now create and populate the plugin for transaction processing.
	//
	$CGPipe = new UniversalPlugin(false);

	//
    // Populate the Plugin to perform the MPIVerifyEnrollment version 1 transaction
	//
	$CGPipe->setTransactionType("MPIVerifyEnrollment");
	$CGPipe->setVersion("1");
    $CGPipe->setResourcePath($resourcePath);
	$CGPipe->setTerminalAlias($terminalAlias);

	$quantity = $_SESSION['quantity'];
	$unitPrice = $_SESSION['unitPrice'];
	$totalPrice = $_SESSION['totalPrice'];

    //
    // Populate the MPIVerifyEnrollment Transaction details
    //
	$CGPipe->set("action",       "1");    // 1 - Purchase, 4 - Authorization
	$CGPipe->set("type",         "VPAS");
	$CGPipe->set("card",         $_SESSION['cardNumber']);
	$CGPipe->set("addr",         $_SESSION['address']);
	$CGPipe->set("zip",          $_SESSION['zip']);
	$CGPipe->set("expyear",      $_SESSION['expiryYear']);
	$CGPipe->set("expmonth",     $_SESSION['expiryMonth']);
	$CGPipe->set("expday",       "");
	$CGPipe->set("amt",          $totalPrice);
	$CGPipe->set("currencycode", "840");
	$CGPipe->set("trackid",      "1029309");
	$CGPipe->set("cvv2",         $_SESSION['cvv']);
	// $CGPipe->set("udf1",         "");
	// $CGPipe->set("udf2",         "");
	// $CGPipe->set("udf3",         "");
	// $CGPipe->set("udf4",         "");
	// $CGPipe->set("udf5",         "");
	$CGPipe->set("member",       $_SESSION['cardholder']);

	//
	// IMPORTANT: A REAL Merchant would COMMENT OUT THIS LINE TO ENABLE THE DEFAULT SSL CONNECTION
	//
    $CGPipe->setProtocol("");

	$CGPipe->performTransaction();

	$error  = $CGPipe->get("error_code_tag");
	$errortext  = $CGPipe->get("error_text");
	$result = $CGPipe->get("result");
	if (!strcmp($error, "")) {
		// Success.. If the Consumer was ENROLLED, Then we have a PAReq.
		//   If we have a PAReq, we will redirect the user to the Commerce Gateway to Authenticate with the ACS.
		if (!strcmp($result, "ENROLLED")) {
			$acsURL       = $CGPipe->get("url");
			$PAReq        = $CGPipe->get("PAReq");
			// currentContextPath + "vbvresponse.jsp?resourcePath=" + resourcePath + "&resourceAlias=" + resourceAlias + "&";
			$TermUrl      = $currentContext . "\UniversalPluginCheckoutMerchantProcessPARes.php";
			$paymentid    = $CGPipe->get("paymentid");

			// In case the Merchant needs it the track id is returned also.
			$trackid      = $CGPipe->get("trackid");
?>
<html>
<head>
<title>Commerce Gateway Redirect</title>
</head>
<body onLoad="document.form1.submit();">
<form name="form1" method="POST" action="<?php echo $acsURL ?>">
	<input type=hidden name="PaReq" value="<?php echo $PAReq ?>">
	<input type=hidden name="TermUrl" value="<?php echo $TermUrl ?>">
	<input type=hidden name="MD" value="<?php echo $paymentid ?>">
	<input type="submit" name="submitButton" value="Submit" onClick="document.form1.submitButton.disabled = true;">
</form>
</body>
</html>
<?php
		} else {
			//
		    // The user is not enrolled.  By Default the Commerce Gateway will automatically process the payment as a
		    // Credit Card transaction so read the reply data to populate the orders.lst/Database with the results.  Then forward on to the Receipt page.
		    //
			$trackid       = $CGPipe->get("trackid");
			$paymentID     = $CGPipe->get("paymentid");
		    $ref           = $CGPipe->get("ref");
		    $auth          = $CGPipe->get("auth");
		    $avr           = $CGPipe->get("avr");
		    $postdate      = $CGPipe->get("postdate");
		    $transactionid = $CGPipe->get("tranid");
		    $udf1          = $CGPipe->get("udf1");
		    $udf2          = $CGPipe->get("udf2");
		    $udf3          = $CGPipe->get("udf3");
		    $udf4          = $CGPipe->get("udf4");
		    $udf5          = $CGPipe->get("udf5");
		    $responsecode  = $CGPipe->get("responsecode");
		    $cvv2response  = $CGPipe->get("cvv2response");

			$Config = new Configuration('orders.lst');
			$Config->set($paymentID . '.result',    $result);
			$Config->set($paymentID . '.error',     $error);
			$Config->set($paymentID . '.errortext', $errortext);
			$Config->set($paymentID . '.ref',       $ref);
			$Config->set($paymentID . '.responsecode', $responsecode);
			$Config->set($paymentID . '.cvv2response', $cvv2response);
			$Config->set($paymentID . '.postdate',  $postdate);
			$Config->set($paymentID . '.udf1',      $udf1);
			$Config->set($paymentID . '.udf2',      $udf2);
			$Config->set($paymentID . '.udf3',      $udf3);
			$Config->set($paymentID . '.udf4',      $udf4);
			$Config->set($paymentID . '.udf5',      $udf5);
			$Config->set($paymentID . '.tranid',    $transactionid);
			$Config->set($paymentID . '.auth',      $auth);
			$Config->set($paymentID . '.avr',       $avr);
			$Config->set($paymentID . '.trackid',   $trackid);
			$Config->save();

			// Now redirect the user to the Reciept Page..
			header( "Location: " . $currentContext . "UniversalPluginCheckoutReceipt.php?paymentid=" . $paymentID);
			exit;
		}


	} else {
	    // Failure.. Re-Use the UniversalPluginCheckoutFailure.php screen
		header( "Location: " . $currentContext . "UniversalPluginCheckoutFailure.php?error=" .  $error . "&errortext=" .  $errortext);
		exit;
	}
?>
