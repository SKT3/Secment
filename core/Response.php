<?php

	class Response {
	    
		/**
		 * Reference to the current instance of the Request object
		 *
		 * @var object
		 * @access private
		 */
		private static $instance = null;		
		
	    /** 
	     * @var string $content 
	     */
	    private $content;
	
	    /**
	     * Status codes
	     *
	     * @var array
	     */
		private $status_codes = array (
			100 => "HTTP/1.1 100 Continue",
			101 => "HTTP/1.1 101 Switching Protocols",
			200 => "HTTP/1.1 200 OK",
			201 => "HTTP/1.1 201 Created",
			202 => "HTTP/1.1 202 Accepted",
			203 => "HTTP/1.1 203 Non-Authoritative Information",
			204 => "HTTP/1.1 204 No Content",
			205 => "HTTP/1.1 205 Reset Content",
			206 => "HTTP/1.1 206 Partial Content",
			300 => "HTTP/1.1 300 Multiple Choices",
			301 => "HTTP/1.1 301 Moved Permanently",
			302 => "HTTP/1.1 302 Found",
			303 => "HTTP/1.1 303 See Other",
			304 => "HTTP/1.1 304 Not Modified",
			305 => "HTTP/1.1 305 Use Proxy",
			307 => "HTTP/1.1 307 Temporary Redirect",
			400 => "HTTP/1.1 400 Bad Request",
			401 => "HTTP/1.1 401 Unauthorized",
			402 => "HTTP/1.1 402 Payment Required",
			403 => "HTTP/1.1 403 Forbidden",
			404 => "HTTP/1.1 404 Not Found",
			405 => "HTTP/1.1 405 Method Not Allowed",
			406 => "HTTP/1.1 406 Not Acceptable",
			407 => "HTTP/1.1 407 Proxy Authentication Required",
			408 => "HTTP/1.1 408 Request Time-out",
			409 => "HTTP/1.1 409 Conflict",
			410 => "HTTP/1.1 410 Gone",
			411 => "HTTP/1.1 411 Length Required",
			412 => "HTTP/1.1 412 Precondition Failed",
			413 => "HTTP/1.1 413 Request Entity Too Large",
			414 => "HTTP/1.1 414 Request-URI Too Large",
			415 => "HTTP/1.1 415 Unsupported Media Type",
			416 => "HTTP/1.1 416 Requested range not satisfiable",
			417 => "HTTP/1.1 417 Expectation Failed",
			500 => "HTTP/1.1 500 Internal Server Error",
			501 => "HTTP/1.1 501 Not Implemented",
			502 => "HTTP/1.1 502 Bad Gateway",
			503 => "HTTP/1.1 503 Service Unavailable",
			504 => "HTTP/1.1 504 Gateway Time-out"
		);
	    
	    /**
	     * Holds the headers to be sent
	     *
	     * @var array
	     */
	    private $headers = array();
	    
	    /**
	     * Holds the sent headers
	     *
	     * @var unknown_type
	     */
	    private $headers_sent = array();
	    
	    /**
	     * Status code
	     *
	     * @var int
	     */
	    private $status = 200;
	    
	   	/**
		 * Returns an instance of the Request object
		 *
		 * @return Request
		 */		
	    static function getInstance() {
	    	if(is_null(self::$instance)) {
	    		self::$instance = new Response();
	    		Registry::getInstance()->response = self::$instance;
	    	}
	    	return self::$instance;
	    }

		/**
		 * Constructor
		 *
		 * @access private
		 */	    
	    private function __construct() {
		}		

		/**
		 * Clonning of Response is disallowed.
		 *
		 */
		public function __clone() {
			trigger_error(__CLASS__ . ' can\'t be cloned! It is singleton.', E_USER_ERROR);
		}		
	    
	    /**
	     * Set the content
	     * Will discard all the changes made on the buffer so far
	     * 
	     * @param mixed $content
	     */
	    public function set_content($content) {
	        $this->content = $content;
	    }
	
	    /** 
	     * Add content on the buffer
	     *
	     * @param mixed $content
	     */
	    public function append($content) {
	        $this->content .= $content;
	    }
	
	    /** 
	     * It gets the content
	     * @return string the content that we push so far on to this Response
	     */
	    public function get_content() {
	        return $this->content;
	    }
	
	    /** 
	     * Echos the content (buffer)
	     */
	    public function dump() {
	        echo $this->content;
	    }
	    
	    /**
	     * Sets the status code
	     * 
	     * @param int $code
	     */
	    public function set_status($code) {
	    	$this->status = (int)$code;
	    }
	    
	    /**
	     * Output the response
	     */
	    public function output() {
	    	$this->send_headers();
	    	echo $this->content;
	    }
	    
	    /**
	     * Sets the header
	     * 
	     *
	     * @param $header mixxed
	     * @param $options string
	     */
	    public function add_header($header = null, $options = null) {
	        if(!empty($options)){
	            $this->headers[$header] = $options;
	        }elseif (!empty($header) && is_array($header)){
	            $this->headers = array_merge($this->headers, $header);
	        }elseif (!empty($header)){
	            $this->headers[] = $header;
	        }
	    }    
	    
	    /**
	     * It gets the Response headers
	     *
	     * <code>
	     * $response->set_header("X-Foo", "Bar");
	     * $response->set_header("X-Framework", "Sweboo");
	     * $response->get_headers(); // array("X-Foo"=>"Bar", "X-Framework"=>"Sweboo");
	     * </code>
	     */
		public function get_headers() {
			return $this->headers;
		}
	
		/**
		 * Get header by name
		 *
		 * @param string $name
		 * @return mixed
		 */
		public function get_header($name) {
			return isset($this->headers[trim($name)]) ? trim($this->headers[trim($name)]) : false;
		}
	    
		/**
		 * Checks if header exists
		 *
		 * @param string $name
		 * @return boolean
		 */
		public function has_header($name) {
			return in_array(trim($name), $this->headers);
		}
	
	    /**
	     * Sets the content-type header
	     *
	     * @param string the content type
	     */
	    public function set_content_type($type) {
	        return $this->set_header('Content-type', $type);
	    }

	    /**
	    * Redirects to given $url
	    *
	    * @param string $url
	    */
	    public function redirect($url) {
	        if(!empty($this->headers['Status']) && substr($this->headers['Status'],0,3) != '301'){
	            $this->headers['Status'] = 302;
	        }
	        $this->add_header('Location', $url);
	        $this->send_headers();
	    }
	    
	    
	    private function send_headers($terminate_if_redirected = true) {
	    	/**
	         * Fix a problem with IE 6.0 on opening downloaded files:
	         * If Cache-Control: IE removes the file it just downloaded from
	         * its cache immediately
	         * after it displays the "open/save" dialog, which means that if you
	         * hit "open" the file isn't there anymore when the application that
	         * is called for handling the download is run, so let's workaround that
	         */
	        if(isset($this->headers['Cache-Control']) && $this->headers['Cache-Control'] == 'no-cache'){
	            $this->headers['Cache-Control'] = 'private';
	        }
	        if (empty($this->headers['Status'])) {
	            $this->headers['Status'] = $this->status;
	        }
	
	        $status = $this->status_codes[$this->headers['Status']];
	        array_unshift($this->headers,  $status ? $status : (strstr('HTTP/1.1 '.$this->headers['Status'], 'HTTP') ? $this->headers['Status'] : 'HTTP/1.1 '.$this->headers['Status']));
	        unset($this->headers['Status']);
	
	        $headers_sent = headers_sent();
			$redirected = false;
			$has_content_type = false;
	        if(!empty($this->headers) && is_array($this->headers)) {
	            $this->add_header('Connection: close');
	            foreach ($this->headers as $key => $value){
	                $header = trim((!is_numeric($key) ? $key . ': ' : '') . $value);
	                $this->headers_sent[] = $header;
	                if(strtolower(substr($header,0,9)) == 'location:'){
	                    $header = str_replace(array("\n","\r"), '', $header);
	                    $redirected = true;
	                    $javascript_redirection = $headers_sent ? '<title>Redirecting...</title><script type="text/javascript">location = "'.substr($header,9).'";</script>' : '';
	                }
	                if(strtolower(substr($header,0,13)) == 'content-type:'){
	                    $has_content_type = true;
	                }
	                header($header);
	            }
	        }
	
	        if(!$has_content_type && !$redirected || ($redirected && $javascript_redirection)){
	            header('Content-Type: text/html; charset=UTF-8');
	            $this->headers_sent[] = 'Content-Type: text/html; charset=UTF-8';
	        }
	
	        if($redirected && $headers_sent){
	        	echo $javascript_redirection;
	        }
	
	        $terminate_if_redirected ? ($redirected ? exit() : null) : null;
	    }
	
	}

?>