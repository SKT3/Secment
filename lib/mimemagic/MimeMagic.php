<?php

class MimeMagic {
	/**
	 * Method for determing the mimetype of a file
	 *
	 * @param string $file
	 * @access public
	 */
	static function mimetype($file) {
		$mime_type = null;
		if(is_file($file)) {
			// the best way
			// -- use the PECL extension fileinfo which is built in since PHP 5.3.0
			// (our own magic.mime file due to some restrictions on shared hosting servers)
			// NB!!! - use this only if not running on phpsuexec server - they have some problems
			// with fileinfo extension and it's not working correctly
			if(false && (substr(php_sapi_name(), 0, 3) !== 'cgi') && function_exists("finfo_open")) {
				$finfo = finfo_open(FILEINFO_MIME, Config()->LIB_PATH . 'mimemagic/magic');
				$mime_type = finfo_file($finfo, $file);
				return $mime_type;
			}
			// the better way
			// -- use the OS (assuming *NIX) file -bi command but only if the EXEC function is available
			else if (false && !in_array('exec', explode(',',ini_get('disable_functions')))) {
				$mime_type = exec(Config()->FILE_ROOT . 'file -bi "' . $file . '"');
				return $mime_type;
			}
			// the 'old-school' way
			// -- use the provided database to determine file mimetype based on file signatures
			// (note that this database has only most common file types in web included)
			else {
				require(Config()->LIB_PATH . 'mimemagic/magic.php');
				$handle = fopen($file, 'r');
				foreach ($mimemagic as $mimetype => $properties) {
					$mime_type = self::mimecheck($handle, $mimetype, $properties);
					if ($mime_type !== null) {
						break;
					}
				}
				fclose($handle);
				return $mime_type;
			}
		}
		return $mime_type;
	}

	/**
	 * Internal method for checking file content against given signatures
	 *
	 * @access private
	 * @param resource $handle
	 * @param string $mimetype
	 * @param array $properties
	 */
	static private function mimecheck($handle, $mimetype, $properties) {
		// we want all $properties to be array so let's ensure proper type
		if (!is_array(current($properties))) {
			$properties = array($properties);
		}
		foreach ($properties as $property) {
			// set the pointer at the specific location
			fseek($handle, $property['start'], SEEK_SET);
			// if in the database is given string to find in the file...
			if ($property['type'] == 'string') {
				if (strtolower(fread($handle, strlen($property['value']))) == strtolower($property['value'])) {
					// if we have some extra values to check for this mimetype
					if (array_key_exists('dependencies', $property) && self::mimecheck($handle, $mimetype, $property['dependencies'])) {
						return $mimetype;
					}
					// if we don't have to do extra checking return the mimetype found
					else {
						return $mimetype;
					}
				}
			}
			// if in the database is given hex value to find in the file...
			if ($property['type'] == 'hex') {
				if (strtolower(bin2hex(fread($handle, strlen($property['value']) / 2))) == strtolower($property['value'])) {
					// if we have some extra values to check for this mimetype
					if (array_key_exists('dependencies', $property) && self::mimecheck($handle, $mimetype, $property['dependencies'])) {
						return $mimetype;
					}
					// if we don't have to do extra checking return the mimetype found
					else {
						return $mimetype;
					}
				}
			}
			// if in the database is given octal to find in the file...
			if ($property['type'] == 'oct') {
				$oct = explode('\\', $property['value']);
				$hex = join('', array_slice(array_map('dechex', array_map('octdec', $oct)), 1, count($oct)));
				if (strtolower(bin2hex(fread($handle, count($oct) - 1))) == strtolower($hex)) {
					// if we have some extra values to check for this mimetype
					if (array_key_exists('dependencies', $property) && self::mimecheck($handle, $mimetype, $property['dependencies'])) {
						return $mimetype;
					}
					// if we don't have to do extra checking return the mimetype found
					else {
						return $mimetype;
					}
				}
			}
		}
		// if we still haven't find anything return NULL
		return null;
	}
}

?>