<?php

namespace lhtml;

class bundle {
	
	public static $hooks = array();
	
	public function add_hook($name, $obj) {
		if(strpos($name, 0, 1) !== ':') throw new \Exception('You must prefix your LHTML hook with a colon! Error in hook $name');
		self::$hooks[$name] =& $obj;
	}
	
	public function parse($file) {
		$parser = new Parser;
		return $parser->build($file);
	}
	
}