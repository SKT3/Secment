<?php
/********************************************************************
 *  @(#)UniversalPluginCheckoutMerchantProcessPARes.php             *
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
 // The purpose of the UniversalPluginCheckoutMerchantProcessPARes.php is to
 // pass the PARes that the ACS returned back into the Commerce Gateway so that
 // the transaction can continue (By default).  The transaction is completed and
 // if there are no errors the user is redirected to the
 // UniversalPluginCheckoutReceipt.php page.
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
	$CGPipe->setTransactionType("MPIPayerAuthentication");
	$CGPipe->setVersion("1");
    $CGPipe->setResourcePath($resourcePath);
	$CGPipe->setTerminalAlias($terminalAlias);

	//
	//  Get and Set the MD and PARes for the MPIPayerAuthentication Transaction
	//
	$PARes 			= $_REQUEST['PaRes'];
	$paymentid      = $_REQUEST['MD'];
	$_SESSION['PARes'] = $PARes;

	$CGPipe->set("pares",     $PARes);
	$CGPipe->set("paymentid", $paymentid);

	//
	// IMPORTANT: A REAL Merchant would COMMENT OUT THIS LINE TO ENABLE THE DEFAULT SSL CONNECTION
	//
    $CGPipe->setProtocol("");

	$CGPipe->performTransaction();

	$error  = $CGPipe->get("error_code_tag");
	$errortext  = $CGPipe->get("error_text");
	$result = $CGPipe->get("result");

	if (!strcmp($error, "")) {
		$trackid       = $CGPipe->get("trackid");
		$PARes         = $CGPipe->get("pares");
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
		$Config->set($paymentID . '.pares',     $PARes);
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

	} else {
	    // Failure.. Re-Use the UniversalPluginCheckoutFailure.php screen
		header( "Location: " . $currentContext . "UniversalPluginCheckoutFailure.php?error=" .  $error . "&errortext=" .  $errortext);
		exit;
	}
?>

