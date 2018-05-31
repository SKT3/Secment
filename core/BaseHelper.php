<?php

class BaseHelper {
	protected $_smarty_register = array(
			'functions' => array(),
			'modifiers' => array(),
	);

	/**
	 * Current controller
	 *
	 * @var object
	 * @access protected
	 */
	protected $controller = null;

	/**
	 * Current controller name
	 *
	 * @var string
	 * @access protected
	 */
	protected $controller_name = null;

	/**
	 * Current action name
	 *
	 * @var string
	 * @access protected
	 */
	protected $action_name = null;


	/**
	 * Current controller DataBase table
	 *
	 * @var string
	 * @access protected
	 */
	protected $model_name = null;

	/**
	 * Stores the registered template functions
	 *
	 * @var array
	 */
	protected static $template_functions = array();

	/**
     *  Set some class variables
     *
     *  @uses $controller
     *  @uses $controller_name
     *  @uses $model_name
     *  @uses Inflector
     *  @uses Registry
     *
     */
	function __construct() {
		if(isset(Registry()->controller)) {

			$this->controller = Registry()->controller;
			$pars = $this->controller->get_action_params();
			$this->current_id = $pars['id'];
			$this->action_name = $this->controller->get_action_name();
			$this->controller_name = $this->controller->get_controller_name();
			if(isset($this->controller->table)) {
				$this->model_name = Inflector::modulize($this->controller->table);
			}
			else {
				$this->model_name = Inflector::modulize($this->controller_name);
			}
		}

		foreach($this->_smarty_register['functions'] as $method_name) {
		    self::$template_functions[$method_name] = true;
			Registry()->tpl->register_function($method_name, array($this, $method_name . '_smarty'));
		}

		foreach($this->_smarty_register['modifiers'] as $method_name) {
			Registry()->tpl->register_modifier($method_name, array($this, $method_name . '_modifier'));
		}

		foreach(get_class_methods($this) as $method_name) {
		    if(substr($method_name, -7) == '_smarty') {
				$method_name_smarty = substr($method_name, 0, - 7);
				if (!isset(self::$template_functions[$method_name_smarty])) {
					Registry()->tpl->register_function($method_name_smarty, array($this, $method_name));
					self::$template_functions[$method_name_smarty] = true;
				}
			}
		}
	}

	/**
	 * Init tinymce wysiwyg editor
	 *
	 * @param array $params
	 * @param Smarty $smarty
	 * @return string
	 */
	function tinymce_smarty(array $params, $smarty) {
		$_root = Config()->COOKIE_PATH;

		$files_browser = false;
		if(array_key_exists('controller', $params)) {
			$image_upload_folder = Config()->UPLOADED_IMAGES_ROOT . Inflector::tableize($params['controller']) . '/';
			$files_upload_folder = Config()->UPLOADED_IMAGES_ROOT . 'filemanager/';
			if (!is_dir($image_upload_folder)) Files::make_dir($image_upload_folder);
			if (!is_dir($files_upload_folder)) Files::make_dir($files_upload_folder);
			$files_browser = true;
		}
		$extra_params = '';
		foreach($params AS $key=>$val) {
			if( $key != 'controller' && $key != 'plugins' && $key != 'theme_advanced_buttons2' && $key != 'theme_advanced_buttons3') {
				$extra_params .= chr(13) . chr(9) . $key . ': "' . $val . '", ';
			}
		}

		$plugins = array_key_exists('plugins', $params) ? $params['plugins'] : '';
		$theme_advanced_buttons2 = $params['theme_advanced_buttons2'] ? ',separator,' . $params['theme_advanced_buttons2'] : "";
		$theme_advanced_buttons3 = $params['theme_advanced_buttons3'] ? $params['theme_advanced_buttons3'] : "";

		$files_browser_options_string = '';
		$files_browser_plugin = '';
		if ($files_browser == true) {
			$files_browser_options = array();
			$files_browser_options[] = ',';
			$files_browser_options[] = 'imagemanager_path : "'.$image_upload_folder.'",';
			$files_browser_options[] = 'imagemanager_rootpath : "'.$image_upload_folder.'",';
			$files_browser_options[] = 'imagemanager_remember_last_path : false,';
			$files_browser_options[] = 'filemanager_path : "'.$files_upload_folder.'",';
			$files_browser_options[] = 'filemanager_rootpath : "'.$files_upload_folder.'",';
			$files_browser_options[] = 'filemanager_remember_last_path : false';
			$files_browser_options_string = join("\n", $files_browser_options);
			$files_browser_plugin = 'imagemanager, filemanager,';
		}

		$tiny_mces = array();
		$tiny_mces[] = '<script src="'.Config()->PUBLIC_URL.'js/tinymce4/tinymce.min.js?'.time().'" type="text/javascript"></script>';
		$tiny_mces[] = '
			<script type="text/javascript">
			var styles = [
				{title : \'Заглавие на страница(h1)\', block : \'h1\', classes :\'heading\'},
				{title : \'Заглавие/под-заглавие h3\', block : \'h3\'},
				{title : \'Заглавие на секция за начална страница(h3)\', block: \'h3\', classes : \'heading-normal\'},
				{title : \'Заглавие на секция с подчертавка(h4)\', block : \'h4\', classes : \'heading-normal\'},
				{title : \'Заглавие дясна колона с подчертавка(h5)\', block : \'h5\', classes : \'heading-normal\'},
				{title : \'Съдържание\', block : \'div\', classes : \'text\'},
				{title : \'Довършителни работи - Услуги - Черно към бяло\', block : \'span\', classes : \'hoverable\'},
				{title : \'Оцветен параграф\', block : \'p\', classes : \'emphasis\'}
			];

			tinymce.init({
				selector: \'textarea.rich-text-new, textarea.rich-text-full, textarea.rich-text\',
				convert_urls: false,
				width : "74%",
				plugins: [
					\'advlist autolink link image lists charmap anchor\',
					\'searchreplace wordcount visualblocks visualchars code nonbreaking\',
					\'save table contextmenu directionality template paste media textcolor\'
				],
				statusbar: false,
				image_advtab: true,
				toolbar: \'styleselect | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link image media | forecolor backcolor | code\',
				theme: \'modern\',
				style_formats: styles,
				templates: [
					{
						title: \'Project contacts template\',
						url: \'' . Config()->COOKIE_PATH . 'apps/views/tinymce/project_contacts.htm?' . time() . '\',
						description: \'\'
					},
					{
						title: \'Project description template\',
						url: \'' . Config()->COOKIE_PATH . 'apps/views/tinymce/project_description.htm?' . time() . '\',
						description: \'\'
					}
				]
			});
			</script>';

		return join("\n", $tiny_mces);
	}

	/**
	 * Returns an empty HTML tag of type name which by default is XHTML compliant.
	 * Setting open to true will create an open tag compatible with HTML 4.0 and below.
	 * Add HTML attributes by passing an attributes hash to options. For attributes with no value like
	 * (disabled and readonly), give it a value of true in the options hash. You can use symbols or strings
	 * for the attribute names.
	 *
	 * <code>
	 * tag("br");
	 * => <br />
	 * tag("br", null, true);
	 * => <br>
	 * tag("input", array("type" => "text", "disabled" => true))
	 * => <input type="text" disabled="disabled" />
	 * </code>
	 *
	 * @param name string
	 * @param options array
	 * @param open boolean
	 * @return string
	 *
	 * @uses tag_options
	 */
	public function tag($name, $options, $open = false) {
		$html  = "<$name ";
		$html .= $this->tag_options($options);
		$html .= $open ? " >" : " />";
		$html .= "\n";

		return $html;
	}

	/**
	 * Convert array into HTML tag attributes
	 *
	 * <code>
	 * tag_options(array("name"=>"name" "id"=>"id"))
	 * => name="name" id="id"
	 * </code>
	 *
	 * @param array $options
	 * @return string
	 *
	 * @uses implode
	 * @uses is_array
	 * @uses htmlspecialchars
	 */
	public function tag_options($options) {
		if(is_array($options))
		{
			$html = array();
			foreach($options as $key => $value)
			{
				if ($key == 'disabled'
					|| $key == 'multiple'
					|| $key == 'readonly') {
					$html[] = "$key=\"".htmlspecialchars($key)."\"";
				}
				else {
					$html[] = "$key=\"".htmlspecialchars($value)."\"";
				}
			}
			sort($html);

			return implode(" ", $html);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Returns an HTML block tag of type name surrounding the content. Add HTML attributes by passing an attributes hash to options. For attributes with no value like (disabled and readonly), give it a value of true in the options hash. You can use symbols or strings for the attribute names.
	 *
	 * <code>
  	 * content_tag("p", "Hello world!");
  	 * => <p>Hello world!</p>
  	 * content_tag("div", content_tag("p", "Hello world!"), array("class" => "strong"));
  	 * => <div class="strong"><p>Hello world!</p></div>
  	 * content_tag("select", $options, array("multiple" => true);
  	 * => <select multiple="multiple">...$options...</select>
   	 * </code>
   	 *
	 * @param string $name
	 * @param string $content
	 * @param array $options
	 * @return string
	 * @uses tag_options
	 */
	public function content_tag($name, $content, $options = array()) {
		$html  = "<$name ";
		$html .= $this->tag_options($options);
		$html .= ">".$content."</$name>";
		//$html .= "\n";

		return $html;
	}
}

?>