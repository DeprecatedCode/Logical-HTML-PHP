<?php

namespace lhtml\tags;

class tag_if extends \lhtml\node {
	
	public function __construct($element = false, $parent = false) {
		parent::__construct($element, $parent);
		$this->element = false;
	}
	
	public function output() {		
		$this->_init_scope();
		
		/**
		 * Render the code
		 */
		if($this->process()){
		
			if(!empty($this->children)) foreach($this->children as $child) {			
				if(is_object($child)) $output .= $child->output();
				else if(is_string($child)) $output .= $this->_string_parse($child);
			}
		
		}
		else {
			if(!empty($this->children)) foreach($this->children as $child) {	
				if(is_object($child)) {
					if($child->fake_element == ':else') $output .= $child->output();
				}
			}
		}
		
		return $output;
	}
	
	public function process() {
		$v = $this->attributes['cond'];
		
		$vars = $this->extract_vars($v);
		if($vars) foreach($vars as $var) {
			$data_response = $this->_data()->$var;	
			$v = str_replace('{'.$var.'}', $data_response, $v);				
		}				
		
		$v = explode(' ', $v);
		
		if($v[0] === 'true') $v[0] = true;
		if($v[2] === 'true') $v[2] = true;
		if($v[0] === 'false') $v[0] = false;
		if($v[2] === 'false') $v[2] = false;
		
		if(!is_bool($v[0])) $v[0] = $this->_data()->$v[0];
		if(!is_bool($v[2])) $v[2] = $this->_data()->$v[2];
		
		if($v[0] === true) $v[0] = 'true';
		if($v[2] === true) $v[2] = 'true';
		if($v[0] === false) $v[0] = 'false';
		if($v[2] === false) $v[2] = 'false';
		
		$v = implode(' ', $v);
				
		eval("\$retval = ".$v.';');
		
		if(!$retval) foreach($this->children as $child) {
			if($child->fake_element == ':else') $child->show_else = 1;
		}
		
		return $retval;
	}
	
}


class tag_else extends \lhtml\node {
	public $show_else = 0;
	public function __construct($element = false, $parent = false) {
		parent::__construct($element, $parent);
		$this->element = false;
		
		if($this->show_else == 1) {
			$this->show_else = 0;
		}
	}
	
	public function output() {
		$this->_init_scope();
		
		/**
		 * Render the code
		 */
		if($this->show_else){
			if(!empty($this->children)) foreach($this->children as $child) {			
				if(is_object($child)) $output .= $child->output();
				else if(is_string($child)) $output .= $this->_string_parse($child);
			}
		
		}
		
		return $output;
	}
	
}