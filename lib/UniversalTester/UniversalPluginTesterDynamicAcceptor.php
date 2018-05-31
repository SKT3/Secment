
<?php
/********************************************************************
 *  @(#)UniversalPluginTesterDynamicAccessor.php                    *
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
 // The purpose of UniversalPluginTesterDynamicAccessor.php is solely
 // to provide a Developer-centric test facility for testing out
 // transactions using the Universal Servlet.  This page specifically performs
 // necessary redirection based on the replies from the Commerce Gateway and
 // in the case where a receipt page is needed, it re-uses the demo's
 // UniversalPluginCheckoutReceipt.php page.
 //

	require_once "../Universal/UniversalPlugin.php";
	require_once "../Universal/UniversalPluginXMLFileParser.php";
	require_once "../Universal/Framework.php";
	require_once "PHPUtils/Configuration.php";
	require_once "PHPUtils/ACIPHPUtils.php";

	$Config = new Configuration('UniversalPluginDemoConfiguration.txt');
	$resourcePath  = $Config->get('settings.resourcePath');
	$terminalAlias = $Config->get('settings.alias');


	// Get the current Context Path for redirects to return
	$HTTP_SERVER_VARS = $_SERVER;
	$currentContext = ACIPHPUtils::getContextPath($HTTP_SERVER_VARS);



	if (!$_REQUEST) {
        die("Please submit the transaction using the init or an html file.");
    }
	if (isset($_REQUEST['debug'])) { // ***ZZ*** 2015.06.03
		$debugStr = $_REQUEST['debug'];
	}
	else {
		$debugStr = ''; // ***ZZ*** 2015.06.03
	}
	if (!strcmp($debugStr, "true")) {
        $debug = true;
    } else {
        $debug = false;
    }

	$CGPipe = new UniversalPlugin($debug);

	foreach ($_REQUEST as $key => $value) {
		if (!strncmp( $key, "tran_", 5)) {
			$key = substr ( $key, 5);
			$CGPipe->set($key, $value);
		}
	}

    // Turn off ssl for this test.
    $CGPipe->setProtocol("");
    $CGPipe->setResourcePath($resourcePath);
	$CGPipe->setTerminalAlias($terminalAlias);
	$CGPipe->setTransactionType($_REQUEST['TranType']);
	$CGPipe->setVersion("1");

	$CGPipe->performTransaction();
	$errorText = $CGPipe->getErrorText();

	if (strlen($errorText) > 0) {
	    echo "Error: " . $errorText;
	}

	//
	// Determine if this is a 3D Secure Transaction, if so, redirect the browser to
	// the ACS.
	//

	$respArray = $CGPipe->getResponseFields();
	if (isset($respArray["error_code_tag"])) { // ***ZZ*** 2015.06.03
		$error = $CGPipe->get("error_code_tag");
	}
	else {
		$error = ''; // ***ZZ*** 2015.06.03
	}
	if (!empty($error)) {
        echo "<h2>Error: $error</h2>\r\n";
    } else {

    	// Check to see if this is a reply to PaymentInit
		//if (($respArray['PAYMENTPAGE'])) { // ***ZZ*** 2015.06.03
		if (isset($respArray['PAYMENTPAGE'])) {
			// Now we should redirect the user to this URL.
			performGatewayRedirect($respArray['PAYMENTPAGE'], $respArray['PAYMENTID']);
            // No need to do anything else on this page, so exit.
            exit;
		}
		//
		$url = $CGPipe->get("url");
        if ($url) {
            // 3D Secure, so redirect the browser.
            performVPASRedirect($respArray['URL'],
                                $respArray['PAREQ'],
                                $respArray['PAYMENTID']);

            // No need to do anything else on this page, so exit.
            exit;
        } else {
            echo "<h2>Successful Transaction</h2>\r\n";
        }
    }

	function performVPASRedirect($url, $pareq, $payid) {
		$termURL = "http://omanicoteron/TermURL.php";

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
        <input type="hidden" name="PaReq" value="<?php echo $pareq ?>"  />
        <input type="hidden" name="MD" value="<?php echo $payid ?>"  />
        <input type="hidden" name="TermUrl" value="<?php echo $termURL ?>" />
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

	function performGatewayRedirect($paymentPage, $paymentId) {
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
    <form action="<?php echo $paymentPage ?>" method="post" name="form1" autocomplete="off">
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

    } //end of function performGatewayRedirect()
?>
<html>
<head>
<title>Commerce Gateway Dynamic Acceptor Results</title>
</head>
<body">
<form name="form1" method="POST" action="">
<table>
	<tr>
		<td>Field</td>
		<td>Value</td>
	</tr>
<?php
	$traceString = "";
	foreach ($respArray as $key => $value) {
		echo "	<tr>\r\n";
		echo "	    <td>$key</td>\r\n";
		echo "	    <td>$value</td>\r\n";
		echo "	</tr>\r\n";

		$traceString .= "\r\n " . $key . "Value = \$plug->get(\"" . $key . "\");";
	}

?>
	<tr>
		<td colspan="2"><input type="button" name="back" value="Return to Index" onClick="location.href='index.php'"  class="checkoutButton"></td>
	</tr>
</table>
<!-- <?php echo $traceString ?> -->
</form>
</body>
</html>

