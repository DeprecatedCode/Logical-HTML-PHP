<?php

namespace lhtml;

$d = dir(__DIR__.'/tags'); 
while($filename = $d->read()) { 
	if(substr($filename,0,1) !='.') include_once(__DIR__."/tags/$filename");
} $d->close();

class Node {
	
	public $element;
	public $fake_element;
	public $attributes = array();
	public $children = array();
	
	public $cc = 0;
	
	public $_;
	
	public $complete_tags = array('br','hr','link','img');
	
	public function __construct($element, $parent = false) {		
		$this->fake_element = $element;
		$this->element = $element;
		if($parent) $this->_ = $parent;
		if(!is_object($this->_)) $this->_data = new Scope;
	}
	
	public function _nchild($name) {
		$class_name = str_replace(':','',"\lhtml\\tags\\tag_$name");
		if(strpos($name, ':') === 0) $nchild = new $class_name($name, $this);
		else $nchild = new Node($name, $this);
		$this->children[] =& $nchild;
		return $nchild;
	}
	
	public function _cdata($cdata) {
		if(!is_string($cdata)) return false;
		
		/**
		 * Save the string to the children variable then return true
		 */
		$this->children[] = $cdata;  return true;
	}
	
	public function _attr($name, $value = null) {
		/**
		 * Check if the attributes array is setup
		 */
		if(!is_array($this->attributes)) {
			$this->attributes = array();
		}
		
		/**
		 * Save the attribute to the array
		 */
		$this->attributes[$name] = $value; return true;
	}
	
	public function _attrs($attrs) {
		/**
		 * If the attributes are already formatted as an array
		 * Save the attributes to the object attribute array
		 */
		if(is_array($attrs)) { $this->attributes = $attrs; return true; }
		
		/**
		 * If the attributes came in as a string reformat them into the proper array structure
		 */
		$attrs = explode(' ', $attrs);
		foreach($attrs as $key=>$attr) {
			list($key, $attr) = explode('=',str_replace("\"", $attr));
			$attrs[$key] = $attr;
		}
		
		/**
		 * Save the reformatted attributes to the object array
		 */
		$this->attributes = $attrs; return true;
	}
	
	public function output() {
		$this->_init_scope();
		$output = "";
		
		/**
		 * Render the code
		 */
		if(in_array($this->element, $this->complete_tags)) return "<$this->element".$this->_attributes_parse().' />';
		
		if($this->element !== '')
			$output .= "<$this->element".$this->_attributes_parse().'>';
		
		if(!empty($this->children)) foreach($this->children as $child) {			
			if($child instanceof Node) $output .= $child->output();
			else if(is_string($child)) $output .= $this->_string_parse($child);
		}
		
		if($this->element !== '')
			$output .= "</$this->element>";
		
		return $output;
	}
	
	public function _data() {
		if(isset($this->_data)) return $this->_data;
		else return $this->_->_data();
	}
	
	public function _init_scope($new = false){
		if(!$new) {
			$var = false;
			if(isset($this->attributes[':load']))
				$var = $this->attributes[':load'];
			if(!$var) return false;

			list($source, $as) = explode(' as ', $var);	
			$vars = $this->extract_vars($source);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);
					$source = str_replace('{'.$var.'}', $data_response, $source);
				}
			}
		}

		$this->_data = new Scope($this->_ ? $this->_->_data() : false);
		if(isset($source) && isset($as)) $this->_data()->source($source, $as);
	}
	
	public function _string_parse($value) {
		
			$vars = $this->extract_vars($value);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);
					if(is_object($data_response))
						$data_response = describe($data_response);
					$value = str_replace('{'.$var.'}', $data_response, $value);				
				}				
			}
			return $value;
	}
	
	public function _attributes_parse() {
		
		$protocol = empty($_SERVER['HTTPS'])? 'http': 'https';
		$static_protocol = empty($_SERVER['HTTPS'])? 'http://assets': 'https://secure';
		$html = '';
		foreach($this->attributes as $attr => $value) {			
			$vars = $this->extract_vars($value);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);
					if(is_object($data_response))
						$data_response = describe($data_response);
					$value = str_replace('{'.$var.'}', $data_response, $value);				
				}				
			}
			
			if(($attr == 'href' || $attr == 'action' || $attr == 'src') && (strpos($value, '://') > 0 && (strpos($value, 'http://') !== 0 || strpos($value, 'https://') !== 0))) {
				if(strpos($value, '://') > 0) {
					$access_key = substr($value, 0, strpos($value, '://'));
				}
				$dir = @e::$env['http_path'] ? e::$env['http_path'] : '/';
				$portal = e::$url->portal;
				switch($access_key) {
					case 'static':
						$value = MODE_DEVELOPMENT ? str_replace('static://','http://static.momentumapp.dev/', $value) : str_replace('static://',$static_protocol.'.momentumapp.co/', $value);
						//$value =str_replace('static://','http://assets.momentumapp.co/', $value);
					break;
					case 'view' :
						$value = str_replace($access_key.'://',e::$url->view_path, $value);
					break;
					case 'protocol' :
						$value = str_replace($access_key.'://',$protocol.'://', $value);
					break;
					case 'http' :
					case 'https' :
						$value = $value;
					break;
					default :
						$value = str_replace($access_key.'://',$dir.$portal.'/', $value);
					break;
				}
				//$value = str_replace('^^/',$dir.$portal.'/', $value);
				//$value = str_replace('^/',$dir, $value);
			}
			
			if(substr($attr,0,1) == '_' || substr($attr,0,1) == ':' || substr($attr,0,5) == 'ixml:') continue;
			if(strlen($value) > 0) $html .= " $attr=\"$value\"";
		}
		return $html;
	}
	
	/**
	 * Extract Variables
	 */
	protected function extract_vars($content) {
		
		if(strpos($content, '{') === false) return array();
		// parse out the variables
		preg_match_all(
			"/{([\w:|.\,\(\)\/\-\% \[\]\?'=]+?)}/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = $var[1];
		}
		
		return $vars;
		
	}
	
	protected function extract_subvars($content) {
		
		if(strpos($content, '[') === false) return array();
		// parse out the variables
		preg_match_all(
			"/\[([\w:|.\,\(\)\/\-\% \[\]\?'=]+?)\]/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = $var[1];
		}
		
		return $vars;
		
	}
	
	protected function extract_funcs($content) {
		if(strpos($content, '(') === false) return array();
		// parse out the variables
		preg_match_all(
			"/([\w]+?)\(([\w:|.\,=@\(\)\/\-\%& ]*?)\)/", //regex
			$content, // source
			$matches_vars, // variable to export results to
			PREG_SET_ORDER // settings
		);
		
		foreach((array)$matches_vars as $var) {
			$vars[] = array('func' => $var[1], 'string' => $var[0], 'args' => explode(',', $var[2]));
		}
		
		return $vars;
	}
	
}