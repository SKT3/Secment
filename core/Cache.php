<?php

	class CacheFactory {

		public static function factory() {
			$config = explode('://',Config()->CACHE_DRIVER);
	        $driver_name = $config[0].'Cache';
	        return new $driver_name($config[1]);
		}
	}

	abstract class Cache {

		protected $connection = null;	

		function __construct(){}

	    abstract function exists($key);

	    abstract function expire($key);


	    abstract function push($content,$key=null);

	    abstract function pull($key);

	}


	class FilesystemCache extends Cache
	{
		private $cache_location = null;

		function __construct(){
			$this->cache_location = Config()->ROOT_PATH.'cache/fs/';
			parent::__construct();
		}

	    function exists($key){
			return is_file($this->key_to_file($key));
	    }

	    function expire($key){
			if($this->exists($key)) {
				@unlink($this->key_to_file($key));
				clearstatcache();
			}
	    }

	    function push($content,$key=null){
	    	$key ? $key : $key = md5(microtime(true).'_'.rand(1,1000000));
	    	$file_name = $this->key_to_file($key);
	    	if(Files::file_put_contents($file_name, $content)) {
	    		return $key;
	    	} else {
	    		return false;
	    	}
	    }

	    function pull($key){	    	
	    	if($this->exists($key)){
	    		$file = $this->key_to_file($key);
	    		return file_get_contents($file);
	    	}
	    }

		/* Todo - delete all caches */
	    function flush_cache(){

	    }

	    private function key_to_file($key) {
	    	return $this->cache_location . join('/' , array_slice(str_split($key,2),0,2)).'/'.$key;
	    }
	}

	class MemcacheCache extends FilesystemCache
	{
		protected $connection = null;
		private $is_connected = false;
		function __construct($connection_info){
			parent::__construct();
			if (class_exists('Memcached', false))
			{
				$info = explode(':', $connection_info);
				$this->connection = new Memcached;
				$this->connection->addServer($info[0],$info[1]);
				$this->connection->setOption(Memcached::OPT_PREFIX_KEY, $info[2]);
				$this->connection->setOption(Memcached::OPT_COMPRESSION, true); // Enable compression on strings > 100 bytes
				if($this->connection) {
					$this->is_connected = true;
				}
			}
		}

	    function expire($key){
	    	if($this->is_connected) {
		    	$this->connection->delete($key);
		    }
	    	parent::expire($key);
	    }

	    function push($content,$key=null){
	    	$key = parent::push($content,$key);
	    	if($this->is_connected) {
	    		$this->connection->set($key,$content);
	    	}
	    	return $key;
	    	
	    }

	    function pull($key){
	    	$content = '';
	    	if($this->is_connected) {
		    	$content = $this->connection->get($key);
		    	if ($this->connection->getResultCode() == Memcached::RES_NOTFOUND) {
			        $content = parent::pull($key);
			        if($content) {
			       		$this->connection->set($key,$content);
			        }
			    }
			} else {
				$content = parent::pull($key);
			}
			return $content;
	    }

	    /* ToDo - Flush only current Namespace !!! */
	    function flush_cache(){
	    	if($this->is_connected) {
		    	//$this->connection->flush();
		    }
	    }

	    function __destruct()
		{
			if($this->is_connected) {
				$this->connection->quit();
			}
		}
	}


?>