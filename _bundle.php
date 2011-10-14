<?php

namespace lhtml;

require_once "parser.php";
require_once "node.php";
require_once "scope.php";

class bundle {
	
	public static $hooks = array();
	
	public function add_hook($name,&$obj) {
		if(substr($name, 0, 1) !== ':') throw new \Exception('You must prefix your LHTML hook with a colon! Error in hook $name');
		self::$hooks[$name] =& $obj;
	}
	
	public static function ahook($name,&$obj) {
		if(substr($name, 0, 1) !== ':') throw new \Exception('You must prefix your LHTML hook with a colon! Error in hook $name');
		self::$hooks[$name] =& $obj;
	}
	
	public function build($file, $retstack = false) {
		$parser = new Parser;
		return $parser->build($file, $retstack);
	}
	
}