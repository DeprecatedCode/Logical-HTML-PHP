<?php

namespace lhtml

class Node {
	
	public $element;
	public $attributes = array();
	
	public $children = array();
	public $_;
	
	public function __construct($element, $parent = false) {
		$this->element = $element;
		if($parent) $this->_ = $parent;
	}
	
	public function output() {
		$output .= "<$this->element".($this->attributes ? implode(' ', $this->attributes)) : '').'>';
		if(!empty($this->children)) foreach($this->children as $child) {
			if($child instanceof Node) $output .= $child->output();
			else if(is_string($child)) $output .= $child;
		}
		$output .= "</$this->element>";
	}
	
}