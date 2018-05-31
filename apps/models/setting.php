<?php

class Setting extends ActiveRecord {
	
	/**
	 * 
	 * @param string $key
	 * @return string|null
	 */
	public function getValueByKey($key) {
		$setting = $this->find_first(sprintf('setting_key = "%s"', filter_var($key, FILTER_SANITIZE_STRING)));
		
		if ($setting && $setting instanceof Setting) {
			return $setting->val;
		}
		
		return null;
	}

}

?>