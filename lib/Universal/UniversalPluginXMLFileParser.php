<?php
/********************************************************************
 *  @(#)Universal/UniversalPluginXMLFileParser.php                  *
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

require_once "TransactionData.php";

class UniversalPluginXMLFileParser {
	private $xml_parser = null;
	private $attrs;
	private $tranData;
	private $currObjPtr;
	private $tranDataRequest;
	private $tranDataResponse;
	private $field;
	private $err;

	function __construct() {
		$this->tranData = new TransactionData();
		$this->currObjPtr =& $this->tranData;
		$this->xml_parser = xml_parser_create();
	    xml_set_object($this->xml_parser,$this);
	    xml_set_character_data_handler($this->xml_parser, 'dataHandler');
	    xml_set_element_handler($this->xml_parser, "startHandler", "endHandler");
	    xml_parser_set_option($this->xml_parser, XML_OPTION_CASE_FOLDING, 1);
	}

	function getErrorText() {
	    return $this->err;
	}

    function getTransactionData() {
		if (isset($this->tranData)) {
			return $this->tranData;
		}
    }

    function parse($data) {
		//
        if (!xml_parse($this->xml_parser, $data)) {
        	$this->err = (sprintf("XML error: %s at line %d",
            xml_error_string(xml_get_error_code($this->xml_parser)),
            xml_get_current_line_number($this->xml_parser)));
            xml_parser_free($this->xml_parser);
        	return false;
        }
		return true;
    }

	function startHandler($parser, $name, $attribs) {
		//
		if (!strcmp($name, "TRANSACTION")) {
			if (isset($attribs['NAME'])) {
				$this->tranData->name = $attribs['NAME'];
			}
			else {
				$this->tranData->name = '';
			}
			if (isset($attribs['CLASS'])) {
			$this->tranData->className = $attribs['CLASS'];
			}
			else {
				$this->tranData->className = '';
			}
			if (isset($attribs['METHOD'])) {
				$this->tranData->method = $attribs['METHOD'];
			}
			else {
				$this->tranData->method = '';
			}
			if (isset($attribs['VERSION'])) {
				$this->tranData->version = $attribs['VERSION'];
			}
			else {
				$this->tranData->version = '';
			}
		} else if (!strcmp($name, "REQUEST")) {
			$this->tranDataRequest = new TransactionDataRequest();
			if (isset($attribs['ACTION'])) {
				$this->tranDataRequest->action = $attribs['ACTION'];
			}
			else {
				$this->tranDataRequest->action = '';
			}
		 	$this->currObjPtr->request = $this->tranDataRequest;
		 	$tmp = $this->tranData->request;
		 	$this->currObjPtr =& $this->tranDataRequest;  // Set the pointer to the request object.
		} else if (!strcmp($name, "RESPONSE")) {
			$this->tranDataResponse = new TransactionDataResponse();
			//
			if (isset($attribs['TYPE'])) {
				$this->tranDataResponse->type = $attribs['TYPE'];
			}
			else {
				$this->tranDataResponse->type = '';
			}
			//
		 	array_push($this->tranData->responses, $this->tranDataResponse);
		 	$this->currObjPtr =& $this->tranDataResponse;  // Set the pointer to the request object.
		} else if (!strcmp($name, "FIELD")) {
		 	$this->field = new TransactionDataField();
			if (isset($attribs['ID'])) {
				$this->field->id = $attribs['ID'];
			}
			else {
				$this->field->id = '';
			}
			if (isset($attribs['REFID'])) {
				$this->field->refID = $attribs['REFID'];
			}
			else {
				$this->field->refID = '';
			}
			if (isset($attribs['TYPE'])) {
				$this->field->type = $attribs['TYPE'];
			}
			else {
				$this->field->type = '';
			}
			if (isset($attribs['REQUIRED'])) {
				$this->field->required = $attribs['REQUIRED'];
			}
			else {
				$this->field->required = '';
			}
			if (isset($attribs['TESTVALUE'])) {
				$this->field->testValue = $attribs['TESTVALUE'];
			}
			else {
				$this->field->testValue = '';
			}
		 	$tmp = get_class($this->currObjPtr);
			array_push($this->currObjPtr->fields, $this->field);
		} else {
			$this->err = "Unknown tag name in startHandler(): $name";
			return;
		}
   }

   function dataHandler($parser, $data){

   }

   function endHandler($parser, $name){

   }

}
?>
