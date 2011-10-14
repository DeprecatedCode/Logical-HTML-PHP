<?php

namespace lhtml\tags;

class tag_include extends \lhtml\node {
	
	public function init() {
		$this->element = false;
	}
	
	public function output() {
		$this->process();
		parent::output();
	}
	
	public function process() {
		$v = $this->attributes['file'];		
		
		$vars = $this->extract_vars($v);
		if($vars) foreach($vars as $var) {
			$data_response = $this->_data()->$var;	
			$v = str_replace('{'.$var.'}', $data_response, $v);				
		}
				
		$parser = new \lhtml\Parser;
		$this->children = $parser->build($v, true);
	}
	
}