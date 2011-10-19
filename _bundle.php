<?php

namespace Evolution\LHTML;
use Evolution\Kernel\Configure;
use Evolution\Kernel\Service;
use Evolution\Text\JSON;
use Exception;

class Bundle {
	
	private $file;
	private $stack;
	
	public function file($file) {
		$this->file = $file;
		if($this->stack)
			unset($this->stack);
		return $this;
	}
	
	public function parse() {
		if(!isset($this->file))
			throw new Exception("LHTML: No file specified to parse");
		$this->stack = Parser::parseFile($this->file);
		return $this;
	}
	
	public function build() {
		if(!isset($this->stack))
			$this->parse();
		return $this->stack->build();
	}
}