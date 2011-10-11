<?php

namespace lhtml;

define(__NAMESPACE__.'\LHTML_VAR_REGEX', "/{([\w:|.\,\(\)\/\-\% \[\]\?'=]+?)}/");
define(__NAMESPACE__.'\LHTML_VAR_REGEX_SPECIAL', "/{(\%[\w:|.\,\(\)\[\]\/\-\% ]+?)}/");
define(__NAMESPACE__.'\LHTML_STATIC_DMN', "static.momentumapp.co");

class InterfaceObj {
	
	/**
	 * Tag names as keys
	 */
	public static $tags = array( 'a' => 0, 'abbr' => 1, 'acronym' => 2, 'address' => 3, 'applet' => 4, 'area' => 5, 'b' => 6, 'base' => 7, 'basefont' => 8, 'bdo' => 9, 'big' => 10, 'blockquote' => 11, 'body' => 12, 'br' => 13, 'button' => 14, 'canvas' =>0, 'caption' => 15, 'center' => 16, 'cite' => 17, 'code' => 18, 'col' => 19, 'colgroup' => 20, 'dd' => 21, 'del' => 22, 'dfn' => 23, 'dir' => 24, 'div' => 25, 'dl' => 26, 'dt' => 27, 'em' => 28, 'fieldset' => 29, 'font' => 30, 'form' => 31, 'frame' => 32, 'frameset' => 33, 'head' => 34, 'h1' => 35, 'h2' => 36, 'h3' => 37, 'h4' => 38, 'h5' => 39, 'h6' => 40, 'hr' => 41, 'html' => 42, 'i' => 43, 'iframe' => 44, 'img' => 45, 'input' => 46, 'ins' => 47, 'kbd' => 48, 'label' => 49, 'legend' => 50, 'li' => 51, 'link' => 52, 'map' => 53, 'menu' => 54, 'meta' => 55, 'noframes' => 56, 'noscript' => 57, 'object' => 58, 'ol' => 59, 'optgroup' => 60, 'option' => 61, 'p' => 62, 'param' => 63, 'pre' => 64, 'q' => 65, 's' => 66, 'samp' => 67, 'script' => 68, 'select' => 69, 'small' => 70, 'span' => 71, 'strike' => 72, 'strong' => 73, 'style' => 74, 'sub' => 75, 'sup' => 76, 'table' => 77, 'tbody' => 78, 'td' => 79, 'textarea' => 80, 'tfoot' => 81, 'th' => 82, 'thead' => 83, 'title' => 84, 'tr' => 85, 'tt' => 86, 'u' => 87, 'ul' => 88, 'var' => 89, 'embed' => 90,'header'=>91,'aside'=>92,'article'=>93,'nav'=>94,'section'=>95,'footer'=>96,'q'=>97,'mark'=>0,'');
	public static $quick_tags = array( 'area' => 0, 'base' => 1, 'basefont' => 2, 'br' => 3, 'col' => 4, 'frame' => 5, 'hr' => 6, 'img' => 7, 'input' => 8, 'link' => 9, 'meta' => 10, 'param' => 11,'embed' => 12);
	
	/**
	 * Map LHTML elements to their proper interpreters
	 */
	public static $lhtml_special = array(
		/* 'tag' => 'class' */
	);
	
	/**
	 * Exclude elements from rendering
	 */
	public static $lhtml_exclude = array(
		/* 'tag' => false */
		'?xml' => false
	);
	
	/**
	 * This maintains information during the iteration of an interface loop.
	 */
	public $loop_type = 'content';
	public $is_loop = false;
	
	/**
	 * The element name (tag)
	 */
	public $el = false;
	public $fel = false;
	
	/**
	 * An array of links to the child elements.
	 */	
	public $children = array();
	
	/**
	 * An array of the element's attributes and values.
	 */
	public $attr = array();
	
	/**
	 * An array of data which assists PHP in accessing specific elements.
	 */
	protected $index = array();
	
	/**
	 * Current child element count.
	 */
	protected $ec = 0;
	
	/**
	 * Store of parent element object
	 */
	public $_ = false;
	
	/**
	 * Information access layer.
	 */
	public $_data;

	/**
	 * All parsed data requests
	 *
	 * @var string
	 */
	protected $_data_requests;
	
	/**
	 * Extending Content
     */
	public $_extending_content = false;
	
	public function __construct($el = false, $parent = false) {
		$this->fel = $el;
		$this->el = isset(self::$tags[$el]) || isset(self::$lhtml_special[$el]) || strpos($el, 'fb') > -1 ? $el : false;
		$this->el = isset(self::$lhtml_exclude[$el]) ? false : $this->el;
		
		$this->_ = $parent;
		
		if(!$parent) $this->_data = new Scope;
		else $this->_data = false;
	}
	
	public function boot() {
		/**
		 * Loop through the children and boot them
		 */
		foreach($this->children as $child) if($child instanceof InterfaceObj) $child->boot();
		$this->initialize();
	}
	
	protected function initialize() {}
	
	/**
	 * Search Indexes
	 */
	public static $_search_index = array();
	public static $_ext_search_index = array();

	public function __wakeup() {
		if(class_exists('Parser')) {
			if(Parser::$callback) {
				call_user_func(Parser::$callback, 'startup', $this, Parser::$callback_data);
				call_user_func(Parser::$callback, 'attr', $this, Parser::$callback_data);
			}
		}
		
		self::$_ext_search_index[$this->fel]['*'][] = array($this, $this->fel, isset($this->attr['class']) ? $this->attr['class'] : '', isset($this->attr['id']) ? $this->attr['id'] : '');
		self::$_ext_search_index[$this->fel]['id'][isset($this->attr['id']) ? $this->attr['id'] : ''][] = array($this, $this->fel, isset($this->attr['class']) ? $this->attr['class'] : '', isset($this->attr['id']) ? $this->attr['id'] : '');
		self::$_ext_search_index['*']['id'][isset($this->attr['id']) ? $this->attr['id'] : ''][] = array($this, $this->fel, isset($this->attr['class']) ? $this->attr['class'] : '', isset($this->attr['id']) ? $this->attr['id'] : '');
		self::$_search_index[] = array($this, $this->fel, isset($this->attr['class']) ? $this->attr['class'] : '', isset($this->attr['id']) ? $this->attr['id'] : '');
	}

	public function _has_content() {
		return count($this->children) > 0 ? true : false;
	}
	
	public function _extending_content() {
		if(!$this->_extending_content && $this->_) return $this->_->_extending_content();
		else return $this->_extending_content;
	}

	public function _string_parse($value) {
		$vars = $this->extract_vars($value);
		if(is_array($vars)) foreach($vars as $var) {
			$data_response = $this->_data()->$var;
			if(is_object($data_response)) $data_reponse = $this->describe($data_response);
			$value = str_replace('{'.$var.'}', $data_response, $value);
		}
		
		return $value;
	}
	
	public function utility_get_attributes_html() {
		$protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
		$html = '';
		
		foreach($this->attr as $attr=>$value) {
			$vars = $this->extract_vars($value);
			if(is_array($vars)) foreach($vars as $var) {
				$data_response = $this->data()->$var;
				if(is_object($data_response)) $data_response = $this->describe($data_response);
				$value = str_replace('{'.$var.'}', $data_response, $value);
			}
			
			$attr_locs = array('href','action','src');
			
			if(in_array($attr, $attr_locs) && (strpos($value, '://') > 0 && (strpos($value, 'http://') !== 0 || strpos($value, 'https://') !== 0))) {
				if(strpos($value, '://') > 0) $access_key = substr($value, 0, substr($value, '://'));
				$dir = '/';
				
				switch($access_key) {
					case 'static':
						$value = str_replace('static://', $protocol.'://'.LHTML_STATIC_DMN, $value);
					break;
					case 'http':
					case 'https':
					 	$value = $value;
					break;
				}
				
			}
			
			if(substr($attr,0,1) == '_' || substr($attr,0,1) == ':' || substr(0,6) == 'lhtml:') continue;
			if(strlen($value) > 0) $html .= " $attr=\"$value\"";
		}
		
		return $html;
	}
	
	public function utility_get_class_html() {
		$html = '';
		$value = (string) @$this->attr['class'];
		$vars = $this->extract_vars($value);
		if(is_array($vars)) foreach($vars as $var) {
			$data_response = $this->_data()->$var;
			$value = str_replace('{'.$var.'}', $data_response, $value);
		}
		
		return $value;
	}
	
	public function extract_vars($content) {
		if(!is_string($content)) return false;
		
		/**
		 * If no variables are found return false
		 */
		if(strpos($content, '{') === false) return false;
		
		/**
		 * Find all the variables and return an array
		 */
		preg_match_all(
			"/{([\w:;|.\,\(\)\/\-\%&#  \[\]\?'=]+?)}/",
			$content,
			$matches,
			PREG_SET_ORDER
		);
		
		/**
		 * Put all matches in an array
		 */
		foreach((array)$matches as $var) $vars[] = $var[1];
		
		return $vars;
	}
	
	private function describe(&$object) {
		if(method_exists($object, '__toString'))
			return $object->__toString();
		$class = get_class($object);
	    $xtra = '';
	    $xtra .= @$object->name;
	    if(strlen($xtra) < 1)
	        $xtra .= @$object->name();
	    if(strlen($xtra) > 0)
	        $xtra = ': ' . $xtra;
		return "[$class$xtra]";
	}
	
	/**
	 * FROM HERE ONOUT IS JUST INTERFACE MANIPULATION
	 */
	
	public function _attr($array) {
		if(is_array($array)) foreach($array as $attr->$val) {
			if(substr($attr,0,5) == 'lhtml' && method_exists($this, 'lhtml_'.substr($attr,5))) {
				$c = 'lhtml_'.substr($attr,5); $this->$c($val);
			}
			else {
				$c = 'attr_'.$attr;
				if(method_exists($this, $c)) $this->$c($val);
				$this->attr[$attr] = $val;
			}
		}
		
		if(class_exists('Parser')) {
			if(Parser::$callback) call_user_func(Parser::$callback,'attr',$this,Parser::$callback_data);
		}
		
		$this->__wakeup();
		return $this;
	}
	
	/**
	 * Pickup from IXML Interface.php line  450 - Monday 10th
	 */
	
	public function _el($el) {
		$this->ec++;
		return ($this->children[$this->ec] new InterfaceObj($el, $this));
	}
	
	public function _hardwire($object, $extreme = 0) {
		$object->_ = $this;
		$this->ec++;
		if($extreme) foreach($object->children as $child) {
			if($child instanceof InterfaceObj) $this->_hardwire($child);
		}
		else $this->children[$this->ec] = $object;
		
		return $this;
	}
	
	public function _data() {
		if($this->_data) return $this->_data;
		else return $this->_->_data();
	}
	
}