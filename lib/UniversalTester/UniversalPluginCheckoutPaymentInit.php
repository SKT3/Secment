<?php
/********************************************************************
 *  @(#)UniversalPluginCheckoutPaymentInit.php                      *
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
 // The purpose of the UniversalPluginCheckoutPaymentInit.php is to show
 // an example (for PHP) on how to use the Universal Servlet to call the
 // PaymentInit transaction and show how to handle the return values from it.
 //
 //

	require_once "../Universal/UniversalPlugin.php";
	require_once "../Universal/UniversalPluginXMLFileParser.php";
	require_once "../Universal/Framework.php";
	require_once "PHPUtils/ACIPHPUtils.php";
	require_once "PHPUtils/Configuration.php";
	
	$HTTP_SERVER_VARS = $_SERVER; // ***ZZ*** 
	session_start();
	
	$Config = new Configuration('UniversalPluginDemoConfiguration.txt');
	$resourcePath = $Config->get('settings.resourcePath');
	$terminalAlias = $Config->get('settings.alias');

	$CGPipe = new UniversalPlugin(false);

	// Get and Store the quantity and price per unit and total price in session for the reciept page.
	if (isset($_REQUEST['quantity'])) {
		$quantity = $_REQUEST['quantity'];
	}
	else
	{
		$quantity = 1;
	}
	$pricePerUnit = 12.34;
	$price = $quantity * $pricePerUnit;
	$_SESSION['quantity'] = $quantity;
	$_SESSION['unitPrice'] = $pricePerUnit;
	$_SESSION['totalPrice'] = $price;

	// Get the current Context Path for redirects to return
	$currentContext = ACIPHPUtils::getContextPath($HTTP_SERVER_VARS);

    //  
    //$CGPipe->setProtocol("");
	$CGPipe->set("action", "1");	// 1 - Purchase, 4 - Authorization
	$CGPipe->set("amt", $price);
	$CGPipe->set("currencycode", "840");
	$CGPipe->set("trackid", "1029309");
	$CGPipe->set("langid", "en_US");
	$CGPipe->set("responseurl", $currentContext . "UniversalPluginCheckoutNotification.php");
	$CGPipe->set("errorurl", $currentContext . "UniversalPluginCheckoutFailure.php");


	$CGPipe->setResourcePath($resourcePath);
	$CGPipe->setTerminalAlias($terminalAlias);

	$CGPipe->setTransactionType("PaymentInit");
	$CGPipe->setVersion("1");
	
	$CGPipe->performTransaction();
	//
	// Determine if this is a 3D Secure Transaction, if so, redirect the browser to
	// the ACS.
	//
	// ***ZZ*** 2015.06.17 START
	if(isset($_REQUEST['TranType'])) {
		$type = $_REQUEST['TranType'];
	}
	else {
		$type = '';
	}
	if (!strcmp($type, "VPAS")) {
        $vpasTran = true;
    } else {
        $vpasTran = false;
    }
	// ***ZZ*** 2015.06.17 END
	//
	$respArray = $CGPipe->getResponseFields();
	
	//***ZZ*** 2015.06.03 START
	if (isset($respArray["ERROR_CODE_TAG"])) {	// ***ZZ*** 15.07.2015
		$error = $respArray["ERROR_CODE_TAG"];
	}
	else {
		$error = '';
	}
	if (isset($respArray["ERROR_TEXT"])) {	// ***ZZ*** 15.07.2015
		$errortext = $respArray["ERROR_TEXT"]; 
	}
	else {
		$errortext = '';
	}
	// ***ZZ*** 2015.06.03 END
	//
	if (!empty($error)) {
        echo "<h2>Error code: $error</h2>\r\n";
        echo "<h2>Error message: $errortext</h2>\r\n"; // ***ZZ*** 15.07.2015
        if (!strcmp($error, "CM90100")) {
        	echo "Unable to invoke requested Command.<br/>\r\n";
        }
    } else {
    	if(isset($respArray['PAYMENTPAGE']) && isset($respArray['PAYMENTID'])) {		// ***ZZ*** 15.07.2015 
    		performGatewayRedirect($respArray['PAYMENTPAGE'], $respArray['PAYMENTID']);
    	}
        exit;
    }
	//
	function performGatewayRedirect($url, $paymentId) {
		// Get the current Context Path for redirects to return
		$currentContext = ACIPHPUtils::getContextPath($_SERVER);
		//
		$termURL = $currentContext . "TermURL.php";

    // Begin HTML CODE
?>
<html>
<head>
	<style type="text/css">
	<?php include "styles/style.css" ?>
	</style>
    <META HTTP-EQUIV="PRAGMA" CONTENT="NO-CACHE">
</head>
<body OnLoad="OnLoadEvent();">
    <form action="<?php echo $url ?>" method="post" name="form1" autocomplete="off">
        <input type="hidden" name="PaymentID" value="<?php echo $paymentId ?>"  /> 
    </form>
    <script language="JavaScript">

    function OnLoadEvent() {
       document.form1.submit();
       timVar = setTimeout("procTimeout()",300000);
    }

    function procTimeout() {
       	location = 'http://enter.a.timeout.url.here';
    }

    //
    // disable page duplication -> CTRL-N key
    //
    if (document.all) {
        document.onkeydown = function () {
            if (event.ctrlKey && event.keyCode == 78) {
                return false;
            }
        }
    }
    </script>
</body>
</html>
<?php
        // End of HTML CODE

    } //end of function performVPASRedirect()

?>

