<?php

namespace lhtml;

class Node {
	
	public $element;
	public $attributes = array();
	public $children = array();
	
	public $_;
	
	public function __construct($element, $parent = false) {
		$this->element = $element;
		if($parent) $this->_ = $parent;
	}
	
	public function _nchild($name) {
		$nchild = new Node($name, $this);
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
		$attrs = '';
		
		/**
		 * Prepare attributes if there are any
		 */
		if(!empty($this->attributes)) foreach($this->attributes as $attr=>$val) $attrs .= " $attr=\"$val\"";
		
		/**
		 * Render the code
		 */
		$output .= "<$this->element".$attrs.'>';
		if(!empty($this->children)) foreach($this->children as $child) {			
			if($child instanceof Node) $output .= $child->output();
			else if(is_string($child)) $output .= $child;
		}
		$output .= "</$this->element>";
		
		return $output;
	}
	
	public function render() {
		$scope = new Scope;
		$output = $this->output();
		$output = $scope->map($output);
		return $output['vars'][0];
	}
	
}