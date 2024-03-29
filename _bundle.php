<?php

namespace Evolution\LHTML;
use Evolution\Kernel\Configure;
use Evolution\Kernel\Service;
use Evolution\Text\JSON;
use Exception;

class Bundle {
	
	private $file;
	private $stack;
	
	public function __construct($dir) {
		/**
		 * Add the site service
		 */
		Service::bind('Evolution\LHTML\Router::route', 'router:route:lhtml', 'portal:route:lhtml');
		
		/**
		 * Add lhtml to default router and portal routing
		 */
		Configure::add('portal.defaults.run_with', 'lhtml');
		Configure::add('router.defaults.run_with', 'lhtml');
		Service::bind(array($this, '__bind_hooks'), 'site:ready');
	}
	
	public function __bind_hooks() {
		Service::run('lhtml:bind:hooks');
	}
	
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