<?php

class FlashMessageHelper extends BaseHelper implements ArrayAccess, IteratorAggregate {
	private $array_object;

	private $session;

	function __construct() {
		parent::__construct();
		$this->session = Registry()->session;
		$this->array_object = isset($this->session->flash_messages) ? unserialize($this->session->flash_messages) : array();
	}

	function offsetExists($offset) {
		return isset($this->array_object[$offset]);
	}

	function offsetGet($offset) {
		return $this->offsetExists($offset) ? $this->array_object[$offset] : false;
	}

	function offsetSet($offset, $value) {
		if ($offset) {
			$this->array_object[$offset] = $value;
		} else {
			$this->array_object[] = $value;
		}

		$this->session->flash_messages = serialize($this->array_object);
		return true;
	}

	function offsetUnset($offset) {
		unset($this->array_object[$offset]);
	}

	function getIterator() {
		return new ArrayIterator($this->array_object);
	}

	function show_flash_messages_smarty($params, $smarty) {
		$html = array();

		if($this->array_object) {
			$html[] = '<div id="flash_messages" style="display: none;">';
			$html[] = '<ul>';
			$html[] = '<li class="close">Close</li>';

			foreach($this as $key=>$val) {
				$html[] = '<li>' . $val . '</li>';
			}

			$html[] = '</ul>';
			$html[] = '</div>';
		}

		unset($this->session->flash_messages);
		return join("\n", $html);
	}

    function show_flash_messages_public_smarty($params, $smarty) {

	    $content = '';
	    $messages = array();

	    if ($this->array_object) {
            foreach($this as $key => $val) {
                $messages[] = addslashes(trim(preg_replace('/\s+/u', ' ',  Localizer($this->controller_name)->get_label('FLASH_MESSAGES', $val))));
            }
        }

        if ($messages) {
            $content = "$(document).ready(function(){toastr.success('" . implode('<br>', $messages) . "');});";
            $content = $this->content_tag('script', $content, array(
                'type' => 'text/javascript',
            ));
            unset($this->session->flash_messages);
        }

        return $content;
    }

	function flash_messages_smarty($params, $smarty) {
		$html = array();

		if($this->array_object) {
			$html[] = '<div id="flash_messages">';
			$html[] = '<ul>';

			foreach($this as $key=>$val) {
				$html[] = '<li>' . $val . '</li>';
			}

			$html[] = '</ul>';
			$html[] = '</div>';
		}

		unset($this->session->flash_messages);
		return join("\n", $html);
	}

	function js_flash_messages_smarty($params, $smarty) {
		$html = array();
		$sticky = false;

		if($this->array_object) {
			$html[] = '<script type="text/javascript">';
			$html[] = '$(document).ready(function(){';

			foreach($this as $key=>$val) {
				$html[] = 'add_flash_message("'.$val.'");';
			}
			$html[] = '});';
			$html[] = '</script>';
		}

		unset($this->session->flash_messages);
		return join("\n", $html);
	}

	function redirect_offer_url_smarty($params, $smarty) {
		$url = Registry()->session->offer_redirect;
		unset(Registry()->session->offer_redirect);
		return $url;
	}
}

?>