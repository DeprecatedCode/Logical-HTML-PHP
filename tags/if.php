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
		$retval = true;
		
		$v = $this->attributes['var'];
		
		$vars = $this->extract_vars($v);
		if($vars) foreach($vars as $var) {
			$data_response = $this->_data()->$var;	
			$v = str_replace('{'.$var.'}', $data_response, $v);				
		}				

		$val = $this->_data()->$v;
		
		if(isset($this->attributes['equals'])) {
			$v = $this->attributes['equals'];
			$vars = $this->extract_vars($v);
			if($vars) foreach($vars as $var) {
				$data_response = $this->_data()->$var;	
				$v = str_replace('{'.$var.'}', $data_response, $v);				
			}				
			if($v == $val) $retval = true;
			else $retval = false;
		}
		
        if(isset($this->attributes['not'])) {
			$v = $this->attributes['not'];
			$vars = $this->extract_vars($v);
			if($vars) foreach($vars as $var) {
				$data_response = $this->_data()->$var;	
				$v = str_replace('{'.$var.'}', $data_response, $v);								
			}
			if($v != $val) $retval = true;
			else $retval = false;
		}
		
		if(isset($this->attributes['empty'])) {
			if(is_string($val)) $val = trim($val);	
			if(($this->attributes['empty'] == 'false' && !empty($val)) || ($this->attributes['empty'] == 'true' && empty($val))) $retval = true;
			else $retval = false;
		}
		
		if(isset($this->attributes['in'])){
			if(in_array($val,explode(',',$this->attributes['in']))) $retval = true;
			else $retval = false;
		}
		
		if(isset($this->attributes['contains'])){
			if(strpos($val,$this->attributes['contains']) !== false) $retval = true;
			else $retval = false;
		}

		if(isset($this->attributes['not_in'])){
			if(!in_array($val,explode(',',$this->attributes['not_in']))) $retval = true;
			else $retval = false;
		}
		
		if(isset($this->attributes['gt']) && isset($this->attributes['lt'])) {
			if($val > $this->attributes['gt'] && $val < $this->attributes['lt']) $retval = true;
			else $retval = false;
		}
		
		else if(isset($this->attributes['gt'])) {
			$v = $this->attributes['gt'];
			$vars = $this->extract_vars($v);
			if($vars) foreach($vars as $var) {
				$data_response = $this->_data()->$var;	
				$v = str_replace('{'.$var.'}', $data_response, $v);								
			}
			if($val > $v) $retval = true;
			else $retval = false;
		}
		
		else if(isset($this->attributes['gte'])) {
			if($val >= $this->attributes['gte']) $retval = true;
			else $retval = false;
		}
		
		elseif(isset($this->attributes['lt'])) {
			$v = $this->attributes['lt'];
			$vars = $this->extract_vars($v);
			if($vars) foreach($vars as $var) {
				$data_response = ($this->_data()->$var);	
				$v = str_replace('{'.$var.'}', $data_response, $v);				
			}					
			if($val < $v) $retval = true;
			else $retval = false;
		}
		
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