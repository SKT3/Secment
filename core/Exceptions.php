<?php

	/**
	 * Sweboo Error Handler
	 *
	 * @param int $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 */
	function SwebooErrorHandler($errno, $errstr, $errfile, $errline) {
		switch ($errno) {
			case E_STRICT:
			case E_ERROR:
			case E_PARSE:
			case E_CORE_ERROR:
			case E_USER_ERROR:
			case E_USER_WARNING:
			case E_RECOVERABLE_ERROR:
			case E_COMPILE_ERROR:
				throw new SwebooException($errstr, $errno, $errfile, $errline);
				break;
		}
	}
	$error_handler = set_error_handler("SwebooErrorHandler");

	/**
	 * Sweboo Exception Class
	 * All exceptions must extend this class
	 * 
	 * @package Sweboo
	 *
	 */
	class SwebooException extends Exception {
	
		/**
		 * Error generated output
		 *
		 * @var string
		 */
		private $dump = null;
		
		/**
		 * Constructor
		 *
		 * @param string $message
		 * @param int $code
		 * @param string $errfile
		 * @param int $errline
		 */
		function __construct($message, $code = 0, $errfile = null, $errline = null) {
			parent::__construct($message, $code);
			if (!is_null($errfile)) $this->file = $errfile;
			if (!is_null($errline)) $this->line = $errline;
			$this->report();
		}
		
		/**
		 * Dump the error
		 *
		 * @return string
		 */
		function dump() {
			return $this->dump;
		}
		
		/**
		 * Report the error according to set debug mode
		 * 
		 * @param void
		 * @return void
		 *
		 */
		private function report() {
			// if debug output set to screen
			if ((Config()->DEVELOPMENT && strpos(Config()->DEBUG_MODE, 'screen') !== false) || in_array($_SERVER['REMOTE_ADDR'],Config()->DEBUG_IPS)) {
				ob_start();
		    	echo $this->output_html();
		    	echo '<pre style="margin: .5em 0;font: 80%/15pt Verdana, Tahoma, Arial, sans-serif;">';
		    	debug_print_backtrace();
		    	echo '</pre>';
		    	$report = ob_get_clean();
		    	$this->dump = $report;
			}
			else {
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: http://'.$_SERVER['HTTP_HOST']);
			}
			
			// if debug output set to mail
			if (strpos(Config()->DEBUG_MODE, 'mail') !== false) {
				// TODO !!!
			}
			// if debug output set to sms
			if (strpos(Config()->DEBUG_MODE, 'sms') !== false) {
				// TODO !!!
			}
			if(Registry()->db) {
				Registry()->db->close();
			}
		}
		
		/**
		 * Plain output of the error
		 *
		 * @param void
		 * @return string
		 */
		private function output_plain() {
		    $message  = 'Oops! There seems to be a problem: ';
		    $message .= $this->getMessage().'\n\n';
		    $message .= 'The error occurred on line '.$this->getLine().' of '.$this->getFile().'.';
		    return nl2br($message);
		}
	
		/**
		 * HTML formated output of the error
		 *
		 * @param void
		 * @return string
		 */
		private function output_html() {
		    $message  = '<div style="border: 1px solid #2C658F; padding: 1px; margin: .5em 0;font: 80%/15pt Verdana, Tahoma, Arial, sans-serif; color: #232C8F;">';
		    $message .= '<p style="background: #95C9EF; padding: .5em; margin: 0;">';
		    $message .= 'Oops! ';
		    $message .= $this->getMessage().'<br />';
		    $message .= 'The error occurred on line <strong>'.$this->getLine().'</strong> of <em>'.$this->getFile().'</em>';
		    $message .= '</p></div>';
	
		    return $message;
		}
	}
	
	/**
	 * File Exception class
	 * Manage all file exceptions raised by application
	 *
	 * @package Sweboo
	 * @subpackage SwebooException
	 */
	class FileException extends SwebooException {
		function __construct($error) {
			parent::__construct($error);
		}
	}	

	/**
	 * Action Exception class
	 * Manage all action missing exceptions raised by application
	 *
	 * @package Sweboo
	 * @subpackage SwebooException
	 * 
	 */
	class ActionException extends SwebooException {
		function __construct($controller, $action) {
			$error = "Missing method <strong><em>{$action}</em></strong> in controller <strong><em>{$controller}</em></strong>.";
			parent::__construct($error);
		}
	}
	
	/**
	 * Class Missing Exception class
	 * Manage all class file exceptions raised by application
	 *
	 * @package Sweboo
	 * @subpackage SwebooException
	 * 
	 */	
	class ClassMissingException extends FileException {
		function __construct($class_name) {
			$error = "Classname <strong><em>{$class_name}</em></strong> is missing.";
			parent::__construct($error);
		}
	}

	/**
	 * File Missing Exception class
	 * Manage all missing file exceptions raised by application
	 * 
	 * @package Sweboo
	 * @subpackage SwebooException
	 * 
	 */	
	class FileMissingException extends FileException {
		function __construct($file_name) {
			$error = "Filename <strong><em>{$file_name}</em></strong> is missing.";
			parent::__construct($error);
		}
	}
	
	/**
	 * Class Problems Exception class
	 * Manage all errors raised by classes in the application
	 *
	 * @package Sweboo
	 * @subpackage SwebooException
	 * 
	 */		
	class ClassProblems extends SwebooException {
		function __construct($error) {
			parent::__construct($error);
		}		
	}

	/**
	 * Undefined Variable Exception class
	 * Manage errors raised by classes, when some trying to access a variable which is not defined, in the application
	 *
	 * @package Sweboo
	 * @subpackage SwebooException
	 * 
	 */		
	class UndefinedVariable extends ClassProblems {
		function __construct($class, $key) {
			$string = "Variable <strong><em>{$key}</em></strong> is not defined in class <strong><em>{$class}</em></strong>";
			parent::__construct($string);
		}
	}
	
	/**
	 * Active Record Exception class
	 * Manage all errors raised by ActiveRecord in the application
	 *
	 * @package Sweboo
	 * @subpackage SwebooException
	 * 
	 */
	class ActiveRecordException extends SwebooException {
		function __construct( $error, $error_no ) {
			parent::__construct( $error, $error_no );
		}
	}

?>