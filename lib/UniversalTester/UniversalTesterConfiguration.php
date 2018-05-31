<?php
/********************************************************************
 *  @(#)UniversalPluginDemoConfiguration.php                        *
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
 // The purpose of the UniversalPluginDemoConfiguration.php is to allow
 // this database-less demo to persist it's secure resource settings
 // for all pages to use.
 //

	require_once('../Universal/SecureResourceManager.php');
	require_once "./PHPUtils/Configuration.php";

	$savedMessage = "";
	$files = null;
	if ((isset($_REQUEST['Save'])) && (!strcmp($_REQUEST['Save'], "Save"))) { // ***ZZ*** 2015.06.04
		$resourcePath = $_REQUEST['resourcePath'];
		
		$alias        = $_REQUEST['alias'];
		// Save Settings.
		$Config = new Configuration('UniversalPluginDemoConfiguration.txt');
		$Config->set('settings.resourcePath', $resourcePath);
		$Config->set('settings.alias',        $alias);
		$Config->save();
		$savedMessage = "Configuration Saved Successfully.";
	} else if ((isset($_REQUEST['TestResource'])) && (strcmp($_REQUEST['TestResource'], ""))) { // ***ZZ*** 2015.06.04
		$resourcePath = $_REQUEST['resourcePath'];
		$terminalAlias = "";	// Unknown at this time
		$xmlMapFileName = "";   // Unknown at this time.
		if (strlen($resourcePath) > 0) {
			$srm = new SecureResourceManager($terminalAlias, $resourcePath, $xmlMapFileName);
			$files = $srm->getTerminalAliases();
			$resourcePath = $srm->getResourcePath();
		}                       
	} else {
		// Load the Settings.
		$Config = new Configuration('UniversalPluginDemoConfiguration.txt');
		$resourcePath = $Config->get('settings.resourcePath');
		$alias = $Config->get('settings.alias');
	}
	//
	$terminalAlias = "";	// ***ZZ*** 2015.06.04
	$xmlMapFileName = "";   // ***ZZ*** 2015.06.04
	//
	if (strlen($resourcePath) > 0) {
		$srm = new SecureResourceManager($terminalAlias, $resourcePath, $xmlMapFileName);
		$files = $srm->getTerminalAliases();
	}

	function cleanupResourcePath ($resourcePath) {
		$resourcePath = $_REQUEST['resourcePath'];
		while (strpos($resourcePath, "\\")) {
			$resourcePath = str_replace("\\", "/", $resourcePath);
		}
		while (strpos($resourcePath, "//")) {
			$resourcePath = str_replace("//", "/", $resourcePath);
		}
		return $resourcePath;
	}


?>
<html>
<head>
	<title>Universal Plugin Demo Website</title>
	<style type="text/css">
	<?php include "styles/style.css" ?>
	</style>
</head>
<body>
<form name="ConfigurationForm" action="UniversalTesterConfiguration.php" method="post">
<table width="100%" cellspacing="0" cellpadding="0" border = "0">
	<tr>
		<td colspan="2">
		  <table width="100%" cellspacing="0" cellpadding="0" border="0">
		    <tr>
		      <td><image src="images/acilogo.gif" border="0"></td><td align="Right"><H1 style="color:black;">Universal Plugin PHP Examples</H1></td>
		    </tr>
		    <tr>
		      <td colspan="2"><hr height="4" style="color:darkred;"/></td>
		    </tr>
		  </table>
		</td>
	</tr>
	<tr>
		<td colspan="2">Configuration Settings:</td>
	</tr>
	<tr>
		<th colspan="2"><hr/></th>
	</tr>
	<tr>
		<td align="right">Resource Path:</td>
		<td ><input name="resourcePath" type="input" size="80" value="<?php echo $resourcePath ?>"/><input type="submit" name="TestResource" value="Test"/></td>
	</tr>
	<tr>
		<td align="right">Alias:</td>
		<td >
		
<?php	
		if ($files == null) {
			echo "			<input name=\"alias\" type=\"input\" size=\"40\" value=\"" .$alias . "\"/>";
        }  else {
			echo "			<select name=\"alias\"/>";
			$firstFileFound = "";
			foreach ($files as $key => $value) {
				if (!strcmp($value, $alias)) {
					$selected = "selected";
				} else {
				    $selected = "";
				}
				echo "			    <option value=\"" . $value . "\" " . $selected . ">" . $value . "</option>";
			}
			$alias = $firstFileFound;
			echo "			</select>";

 		}
?>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input type="button" name="back" value="Return to Index" onClick="location.href='index.php'" class="checkoutButton">
			<input type="submit" name="Save" value="Save" class="checkoutButton"/>
		</td>
	</tr>
	<tr>
		<td align="center" colspan="2"><?php echo $savedMessage ?></td>
	</tr>
</table>
</form>
</body>
</html>