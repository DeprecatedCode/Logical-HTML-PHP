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
			$dirs = Configure::getArray('interface.location');
		
		// Make sure path contains valid controller name
		if(!isset($path[0]) || $path[0] == '')
			return;
		
		// Get the interface name
		$name = strtolower($path[0]);
		
		// Check all dirs for a matching interface
		foreach($dirs as $dir) {
			// Look in interfaces folder
			if(basename($dir) !== 'interfaces')
				$dir .= '/interfaces';
			
			// Skip if missing
			if(!is_dir($dir))
				continue;
				
			// File to check
			$file = "$dir/$name.lhtml";
			
			// Skip if incorrect file
			if(!is_file($file))
				continue;
	
			// Parse the interface file
			$result = Service::run('lhtml:parse', $file);
			
			// Output the interface
			echo $result[0];
			            
            // Complete the current binding queue
            throw new Completion($result);
		}
	}
}