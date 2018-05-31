<?php
/********************************************************************
 *  @(#)UniversalPluginCheckoutSuccess.php                          *
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
 // The purpose of the UniversalPluginCheckoutSuccess.php is to provide
 // a way for the Commerce Gateway to tell the merchant, outside of the
 // consumer flow, the status of the payment after all authentication is complete.
 // This page allows the merchant to decide, based on the hosts reply where to
 // redirect the user.
 //
 // This page can be implemented as a servlet and should always return ONE line,
 // without any carriage-return/linefeed containing the URL the merchant wants
 // the user to be redirected to prededed by the command string "REDIRECT="
 //
 // Because this page is performed using a DIFFERENT SESSION than the consumer,
 // the $_SESSION object may not be used to store the information.  Some other form of
 // persistance is required and to allow matching up of the data acquired here when the
 // consumer is redirected, a unique value (we use the paymentid here as an example) should be
 // used.
 //
 // An example of a value reply (without any carriage-return/linefeed) is:
 //
 // REDIRECT=http://yourdemobank/UniversalTester/UniversalPluginCheckoutReceipt.php?paymentid=2060005351572270
 //

	require_once "PHPUtils/ACIPHPUtils.php";
	require_once "PHPUtils/Configuration.php";
	//
	$HTTP_SERVER_VARS = $_SERVER; // ***ZZ*** 2015.06.01
	//
	$currentContext = ACIPHPUtils::getContextPath($HTTP_SERVER_VARS);
	//
	if (isset($_REQUEST['paymentid'])) { // ***ZZ*** 2015.06.01
		$paymentID  = $_REQUEST['paymentid'];
	}
	else {
		$paymentID  = '';
	}
	if (isset($_REQUEST['Error'])) { // ***ZZ*** 2015.06.01
		$error      = $_REQUEST['Error'];			// The Notification servlet/page, unlike the Universal Servlet's replies, still uses: Error instead of error_code_tag
	}
	else {
		$error  = "";
	}
	
	if (isset($_REQUEST['Error'])) { // ***ZZ*** 2015.06.01
		$errortext  = $_REQUEST['ErrorText'];       // The Notification servlet/page, unlike the Universal Servlet's replies, still uses: ErrorText instead of error_text
    }
	else {
		$errortext  = '';
		//if (isset($_REQUEST['result']) && ($_REQUEST['result'] == 'HOST TIMEOUT')) {
		//	$errortext  =  'HOST TIMEOUT';
		//}
	}
	//
	try {
		// You Would NOT EVER DO THIS IN PRODUCTION as it is a security concern.. instead, merchants should serialize the order data to a database.
		if (!strcmp($error, "")) {
			$Config = new Configuration('orders.lst');
			if (isset($_REQUEST['result'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.result',    $_REQUEST['result']);
			}
			else {
				$Config->set($paymentID . '.result',    '');
			}
			if (isset($_REQUEST['error'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.error',     $_REQUEST['error']);
			}
			else {
				$Config->set($paymentID . '.error',     '-1');
			}
			if (isset($_REQUEST['errortext'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.errortext', $_REQUEST['errortext']);
			}
			else {
				$Config->set($paymentID . '.errortext',     'Unknown error');
			}
			if (isset($_REQUEST['ref'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.ref',       $_REQUEST['ref']);
			}
			else {
				$Config->set($paymentID . '.ref',       '');
			}
			if (isset($_REQUEST['responsecode'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.responsecode', $_REQUEST['responsecode']);
			}
			else {
				$Config->set($paymentID . '.responsecode', '');
			}
			if (isset($_REQUEST['cvv2response'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.cvv2response', $_REQUEST['cvv2response']);
			}
			else {
				$Config->set($paymentID . '.cvv2response', '');
			}
			if (isset($_REQUEST['postdate'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.postdate',  $_REQUEST['postdate']);
			}
			else
			{
				$Config->set($paymentID . '.postdate',  '');
			}
			if (isset($_REQUEST['udf1'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.udf1',      $_REQUEST['udf1']);
			}
			else {
				$Config->set($paymentID . '.udf1',      '');
			}
			if (isset($_REQUEST['udf2'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.udf2',      $_REQUEST['udf2']);
			}
			else {
				$Config->set($paymentID . '.udf2',      '');
			}
			if (isset($_REQUEST['udf3'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.udf3',      $_REQUEST['udf3']);
			}
			else {
				$Config->set($paymentID . '.udf3',      '');
			}
			if (isset($_REQUEST['udf4'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.udf4',      $_REQUEST['udf4']);
			}
			else {
				$Config->set($paymentID . '.udf4',      '');
			}
			if (isset($_REQUEST['udf5'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.udf5',      $_REQUEST['udf5']);
			}
			else {
				$Config->set($paymentID . '.udf5',      '');
			}
			if (isset($_REQUEST['tranid'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.tranid',    $_REQUEST['tranid']);
			}
			else {
				$Config->set($paymentID . '.tranid',    '');
			}
			if (isset($_REQUEST['auth'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.auth',      $_REQUEST['auth']);
			}
			else {
				$Config->set($paymentID . '.auth',      '');
			}
			if (isset($_REQUEST['avr'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.avr',       $_REQUEST['avr']);
			}
			else {
				$Config->set($paymentID . '.avr',       '');
			}
			if (isset($_REQUEST['trackid'])) { // ***ZZ*** 2015.06.01 
				$Config->set($paymentID . '.trackid',   $_REQUEST['trackid']);
			}
			else {
				$Config->set($paymentID . '.trackid',   $_REQUEST['trackid']);
			}
			
			$Config->save();

			$reply      = "REDIRECT=" . $currentContext . "UniversalPluginCheckoutReceipt.php?paymentid=" . $paymentID;
	    } else {
	        $reply      = "REDIRECT=" . $currentContext . "UniversalPluginCheckoutFailure.php?error=" .  $error . "&errortext=" .  $errortext;
	    }
    } catch (Exception $e) {
		$reply      = "Error Occurred During Notification: " . $e;
    }

	// Now reply with the redirection value.
	echo $reply;

	// Note: There is no Carriage Return after this block as Commerce Gateway does not handle Carriage Returns in the REDIRECT instruction well.
 ?>