<?php
/********************************************************************
 *  @(#)ACIPHPUtils.php                                             *
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

class ACIPHPUtils {
//    public static function getContextPath($HTTP_SERVER_VARS) {
    public static function getContextPath($SERVER) {
	if ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == "on" ){
   		$protocol = "https://";
	} else {
		$protocol = "http://";
	}


	$uri = $_SERVER ["REQUEST_URI"];
	$host = $_SERVER ['HTTP_HOST'];
	$port = $_SERVER ['SERVER_PORT'];
	$uri = substr($uri, 0, strrpos($uri, "/"));
	if ($port == 80) {
	    $port = "";
	} else {
	    $port = ":" . $port;
	}

	return $protocol . $host . $port . $uri . "/";
	}
}

?>
