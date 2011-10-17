<?php

namespace Evolution\LHTML;
use Exception;

/**
 * Load LHTML Functions
 */
$d = dir(__DIR__.'/tags'); 
while($filename = $d->read()) { 
	if(substr($filename,0,1) !='.') include_once(__DIR__."/tags/$filename");
} $d->close();

class Node {
	
	/**
	 * Parts of the element
	 */
	public $element;
	public $fake_element;
	public $attributes = array();
	public $children = array();
	
	/**
	 * Iteration Variables
	 */
	public $loop_type;
	public $is_loop;
	
	/**
	 * Parent in the Node Stack
	 */
	public $_;
	
	/**
	 * Tags that are complete
	 */
	public static $complete_tags = array('br','hr','link','img');
	
	public function __construct($element, $parent = false) {		
		/**
		 * Initialize the element and set the parent if one exists
		 */
		$this->fake_element = $element;
		$this->element = $element;
		if($parent) $this->_ = $parent;
		
		/**
		 * Initialize a new Scope if one does not exist
		 */
		if(!is_object($this->_)) $this->_data = new Scope;
		
		/**
		 * Run any initialization scripts for the custom tags
		 */
		$this->init();
	}
	
	public function init() {}
	
	public function _nchild($name) {
		/**
		 * If is a lhtml tag create it in the stack
		 * @todo allow namespaced tags
		 */
		$class_name = str_replace(':','',__NAMESPACE__."\\tag_$name");
		if(strpos($name, ':') === 0) $nchild = new $class_name($name, $this);
		
		/**
		 * If is a normal element create it in the stack
		 */
		else $nchild = new Node($name, $this);
		
		/**
		 * Set the new child element to this object and return the new child
		 */
		$this->children[] =& $nchild;
		return $nchild;
	}
	
	public function _cdata($cdata) {
		if(!is_string($cdata)) return false;
		
		/**
		 * Save the string to the children array then return true
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
		 * If requires iteration
		 */
		if($this->is_loop) {
			$this->_data()->reset();
			while($this->_data()->iterate()) {
		
				/**
				 * If is a complete tag render it and return
				 */
				if(in_array($this->element, self::$complete_tags)) return "<$this->element".$this->_attributes_parse().' />';

				/**
				 * If is a real element create the opening tag
				 */
				if($this->element !== '' && $this->element) $output .= "<$this->element".$this->_attributes_parse().'>';

				/**
				 * Loop thru the children and populate this tag
				 */
				if(!empty($this->children)) foreach($this->children as $child) {			
					if($child instanceof Node) $output .= $child->output();
					else if(is_string($child)) $output .= $this->_string_parse($child);
				}

				/**
				 * Close the tag
				 */
				if($this->element !== '' && $this->element) $output .= "</$this->element>";
				
			}
			
		}
		
		/**
		 * Else load normally
		 */
		else {
			
			/**
			 * If is a complete tag render it and return
			 */
			if(in_array($this->element, self::$complete_tags)) return "<$this->element".$this->_attributes_parse().' />';

			/**
			 * If is a real element create the opening tag
			 */
			if($this->element !== '' && $this->element) $output .= "<$this->element".$this->_attributes_parse().'>';

			/**
			 * Loop thru the children and populate this tag
			 */
			if(!empty($this->children)) foreach($this->children as $child) {			
				if($child instanceof Node) $output .= $child->output();
				else if(is_string($child)) $output .= $this->_string_parse($child);
			}

			/**
			 * Close the tag
			 */
			if($this->element !== '' && $this->element) $output .= "</$this->element>";
			
		}
		
		/**
		 * Return the rendered page
		 */
		return $output;
	}
	
	public function _data() {
		/**
		 * Grab the next instance of Scope in line
		 */
		if(isset($this->_data)) return $this->_data;
		else return $this->_->_data();
	}
	
	public function _init_scope($new = false){
		if(!$new) {
			$var = false;
			/**
			 * If there is a load attribute load load the var as this var
			 */
			if(isset($this->attributes[':load']))
				$var = $this->attributes[':load'];
			if(!$var) return false;
			
			/**
			 * Instantiate a new scope for the children of this element
			 */
			list($source, $as) = explode(' as ', $var);	
			$vars = $this->extract_vars($source);
			if($vars) {
				foreach($vars as $var) {
					$data_response = ($this->_data()->$var);
					$source = str_replace('{'.$var.'}', $data_response, $source);
				}
			}
		}
		
		/**
		 * Load IXML Iterate
		 */
		if($this->attr[':load'] && $this->attr[':iterate']) {
			$this->loop_type = $this->attr[':iterate'];
			$this->is_loop = true;
		}

		/**
		 * Instantiate a new scope for the children of this element
		 */
		$this->_data = new Scope($this->_ ? $this->_->_data() : false);
		if(isset($source) && isset($as)) $this->_data()->source($source, $as);
	}
	
	/**
	 * Parse the variables in a string
	 *
	 * @param string $value 
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
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
	
	/**
	 * Parse the variables in an attribute then return them properly formatted
	 *
	 * @return void
	 * @author Kelly Lauren Summer Becker
	 */
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
			if(substr($attr,0,1) == ':') continue;
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