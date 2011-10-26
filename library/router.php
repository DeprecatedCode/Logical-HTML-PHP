<?php

namespace Evolution\LHTML;
use Evolution\Kernel\Configure;
use Evolution\Kernel\Service;
use Evolution\Kernel\Completion;
use Exception;

/**
 * Router Interfaces
 * @author Kelly Becker
 */
class Router {
	
	public static function route($path, $dirs = null) {
		
		// If dirs are not specified, use defaults
		if(is_null($dirs))
			$dirs = Configure::getArray('lhtml.location');
		
		// Make sure path contains valid controller name
		if(!isset($path[0]) || $path[0] == '')
			return;
		
		// Get the lhtml name
		$name = strtolower($path[0]);
		
		// Check all dirs for a matching lhtml
		foreach($dirs as $dir) {
			// Look in lhtml folder
			if(basename($dir) !== 'lhtml')
				$dir .= '/lhtml';
			
			// Skip if missing
			if(!is_dir($dir))
				continue;
				
			// File to check
			$file = "$dir/$name.lhtml";
			
			// Skip if incorrect file
			if(!is_file($file))
				continue;
	
			// Parse the lhtml file and build the stack
			echo Parser::parse($file)->build();
			            
            // Complete the current binding queue
            throw new Completion($result);
		}
	}
}