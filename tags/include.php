<?php

namespace lhtml\tags;

class tag_include extends \lhtml\node {
	
	public function __construct($element = false, $parent = false) {
		parent::__construct($element, $parent);
		$this->element = false;
		$this->process();
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