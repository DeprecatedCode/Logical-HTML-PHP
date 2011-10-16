<?php

namespace Evolution\LHTML;
use Exception;

class tag_include extends Node {
	
	public function init() {
		$this->element = false;
	}
	
	public function output() {
		$this->process();
		return parent::output();
	}
	
	public function process() {
		$v = $this->attributes['file'];		
		
		$vars = $this->extract_vars($v);
		if($vars) foreach($vars as $var) {
			$data_response = $this->_data()->$var;	
			$v = str_replace('{'.$var.'}', $data_response, $v);				
		}
				
		$parser = new Parser;
		$this->children = $parser->build($v, true);
	}
	
}