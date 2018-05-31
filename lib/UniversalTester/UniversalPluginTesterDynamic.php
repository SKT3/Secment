<?php
/********************************************************************
 *  @(#)UniversalPluginTesterDynamic.php                            *
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
 // The purpose of UniversalPluginTesterDynamic.php is solely to provide
 // a Developer-centric test facility for testing out transactions using
 // the Universal Servlet.
 //

	require_once "../Universal/UniversalPlugin.php";
	require_once "../Universal/UniversalPluginXMLFileParser.php";
	require_once "../Universal/Framework.php";
	require_once "PHPUtils/Configuration.php";
	require_once "PHPUtils/ACIPHPUtils.php";
	
	$HTTP_SERVER_VARS = $_SERVER;
	
	$Config = new Configuration('UniversalPluginDemoConfiguration.txt');
	$resourcePath = $Config->get('settings.resourcePath');
	$terminalAlias = $Config->get('settings.alias');

	// Configuration
	if(isset($_REQUEST['transactionType'])) { // ***ZZ*** 2015.06.03
		$tranType = $_REQUEST['transactionType'];
	}
	else {
		$tranType = '';
	}
	if(isset($_REQUEST['paymentid'])) { // ***ZZ*** 2015.06.03
		$acceptedPaymentID = $_REQUEST['paymentid'];
	}
	else {
		$acceptedPaymentID = '';
	}
	//
	if (!strcmp($tranType, "")) {
		$tranType = "TranPortal";
	}
	$ver = "1";
	$submitURL = "UniversalPluginTesterDynamicAcceptor.php";
	$debugStr = "false";

	// Get the current Context Path for redirects to return
	$HTTP_SERVER_VARS = $_SERVER; // ***ZZ*** 2015.06.03
	$currentContext = ACIPHPUtils::getContextPath($HTTP_SERVER_VARS);
?>
<html>
<head>
<title>Universal Plugin Tester</title>
	<style type="text/css">
	<?php include "styles/style.css" ?>
	</style>


</head>
<body>
<form name="transactionForm" action="<?php echo $submitURL ?>" method="POST" >
<table>
	<tr>
		<th colspan="2">Dynamic Transaction Tester</th>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
	<tr>
		<td colspan="2">This test page reads the resource file for a selected transaction and dyanically renders an entry form for the fields belonging to that transaction by using the definition of the form from within the resource.
		<br/>
			<font color="red">
				Note: That this code should NOT BE USED AS-IS for production. (You should code only the transaction you want to allow on your PHP Pages.)
			</font>
		</td>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
	<tr>
		<td align="right">Transaction Definition:
		</td>
		<td>
			<select name="transactionType" onChange="javascript:refreshTransaction();">
<?php
	if (!strcmp($tranType, "CardManagement")) {
		echo "<option value=\"CardManagement\" selected>CardManagement</option>";
	} else {
		echo "<option value=\"CardManagement\">CardManagement</option>";
	}
	if (!strcmp($tranType, "TranPortal")) {
		echo "<option value=\"TranPortal\" selected>TranPortal</option>";
	} else {
		echo "<option value=\"TranPortal\">TranPortal</option>";
	}
	if (!strcmp($tranType, "PaymentInit")) {
		echo "<option value=\"PaymentInit\" selected>PaymentInit</option>";
	} else {
		echo "<option value=\"PaymentInit\">PaymentInit</option>";
	}
	if (!strcmp($tranType, "PaymentTran")) {
		echo "<option value=\"PaymentTran\" selected>PaymentTran</option>";
	} else {
		echo "<option value=\"PaymentTran\">PaymentTran</option>";
	}
	if (!strcmp($tranType, "MPIPayerAuthentication")) {
		echo "<option value=\"MPIPayerAuthentication\" selected>MPIPayerAuthentication</option>";
	} else {
		echo "<option value=\"MPIPayerAuthentication\">MPIPayerAuthentication</option>";
	}
	if (!strcmp($tranType, "MPIVerifyEnrollment")) {
		echo "<option value=\"MPIVerifyEnrollment\" selected>MPIVerifyEnrollment</option>";
	} else {
		echo "<option value=\"MPIVerifyEnrollment\">MPIVerifyEnrollment</option>";
	}
?>
			</select>
		</td>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<fieldset>
				<legend><h3>Transaction: <?php echo $tranType ?></h3></legend>
				<table width="50%" border="0" align="center">
					<tr>
						<td colspan="2"><input type=checkbox name="debug" value="true"> Show Debug Output? </td>
					</tr>
<?php

	$td = loadAndParseXMLFile($tranType, $ver, $resourcePath, $terminalAlias);

	$traceMessage  = "\r\n// The following is an example of the currently generated code: \r\n";
	$traceMessage .= "\$pipe = new UniversalPlugin($debugStr);\r\n";
	$traceMessage .= "\$pipe->setResourcePath($resourcePath);\r\n";
	$traceMessage .= "\$pipe->setTerminalAlias($terminalAlias);\r\n";
	$traceMessage .= "\$pipe->setTransactionType($tranType);\r\n";
	$traceMessage .= "\$pipe->setVersion(\"1\");\r\n";
	$traceMessage .= "\r\n";

	foreach ($td->request->fields as $field) {
		if (!strcmp( $field->id, "id") || !strcmp( $field->id, "password") || !strcmp( $field->id, "passwordhash")) {
		} else {
	        $refID = $field->refID;
	        $id = $field->id;
	        $testValue = $field->testValue;
	        $fieldType = $field->type;
	        $len = strlen($testValue) + 5;
	        if ($len == 5) {
	            $len = 30;
	        }

            //
            // Populate the responseurl and errorurl
            //
	        if (!strcmp($field->id, "responseurl")) {
	            $testValue = $currentContext . "UniversalPluginTesterDynamicNotification.php";
	        } else if (!strcmp($field->id, "errorurl")) {
	        	$testValue = $currentContext . "UniversalPluginCheckoutFailure.php";
	        }

            //
            // Since an Accepted Payment ID was received,
            //
			if ($acceptedPaymentID) {
			    if (!strcmp($field->id, "paymentid")) {
			        $testValue = $acceptedPaymentID;
			    }
			}

			$traceMessage .= "\r\n\$plug->set(\"" . $id . "\", \"value for $id\");";
	        echo "<tr>\r\n   <td>$field->id</td>\r\n   <td><input type=\"text\" name=\"tran_$id\" size=\"$len\" value=\"$testValue\"></td>\r\n</tr>\r\n";
		}
    }
	$traceMessage .= "\r\n";
	$traceMessage .= "\$pipe->performTransaction();\r\n";



	function loadAndParseXMLFile($tranType, $ver, $resourcePath, $terminalAlias) {
    	$parser = new UniversalPlugin(false);
   		$parser->setResourcePath($resourcePath);
		$parser->setTerminalAlias($terminalAlias);
		$parser->setTransactionType($tranType);
		$parser->setVersion($ver);
		return $parser->getTransactionDefinition();
	}

?>
					<tr align=center><td colspan="2" align="center""><input type="submit" name="submitTransaction" value="Submit Transaction" class="checkoutButton"></td></tr>
				</table>
			</fieldset>
			<input type="hidden" name="TranType" value="<?php echo $tranType ?>">
		</td>
	</tr>
	<tr>
		<td colspan="2"><input type="button" name="back" value="Return to Index" onClick="location.href='index.php'"  class="checkoutButton"></td>
	</tr>
</table>
<script language="javascript">
	function refreshTransaction(){
		var formObj = document.forms.transactionForm;
		if (formObj != null) {
			formObj.action = "";
			formObj.submit();
		}
	}
</script>

<!-- <?php echo $traceMessage ?> -->
</form>
</body>
</html>
