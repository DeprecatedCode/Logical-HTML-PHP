<?php

namespace lhtml;

class Parser {
	
	public $file_name;
	public $file_cont;
	
	public $line_numb = 0;
	public $line_cont;
	
	public $cache;
	public $nodestack;

	public $parse_time = 0;
	
	public $html_tags = array('script','style');
	private $pointer = 0;
	
	public function __construct() {
	}
	
	public function build($file) {
		/**
		 * If the LHTML file does not exist throw an exception
		 */
		if(!file_exists($file)) throw new Exception('LHTML Could not load $file');
		
		/**
		 * Check to see if a valid cache exists and use it
		 * Else pull in the LHTML file
		 */
		$cache = __DIR__.'/cache/'.md5($file.$_SERVER['HTTP_HOST']);
		if(@filemtime($file) > @filemtime($cache)) return file_get_contents($cache);
		else $this->file_cont = file_get_contents($file);
		
		/**
		 * Parse the LHTML file
		 */
		$time = microtime(true);
		$output = $this->parse();
		$this->parse_time = microtime(true) - $time;
		
		/**
		 * Save a cache
		 */
		file_put_contents($cache, $output);
		
		/**
		 * Return the parsed data
		 */
		echo $output;
	}
	
	public function parse() {
		/**
		 * Create new Interdace and assign it to workable variables
		 */
		
		/**
		 * Create holders to determine open tags
		 * and cdata
		 */
		$open_tags = array();
		$open_tag_id = 0;
		$force_html = false;
		
		/**
		 * Loop through each tag
		 */
		while($tag = $this->get_tag($force_html)) {
			/**
			 * Extract the variables from the array
			 */
			extract($tag);
			
			/**
			 * If the tags contents must be treated as html based on tag type
			 */
			if($type == 'open' && in_array($name, $this->html_tags)) $force_html = $name;
			else $force_html = false;
			
			/**
			 * Process comment in the interface
			 */
			if($type == 'comment') $stack->_cdata($name);
			
			/**
			 * Show parse error if an expected closing tag does not occur
			 */
			if($type == 'close' && $name !== $open_tags[$open_tag_id])
				throw new ParseException('LHTML Parse Error', $this->file_name, $this->line_numb, 'I was expecting the end tag <code>&lt;/'.$open_tags[$open_tag_id].'&gt;</code>, instead I got <code>&lt;/'.$tag_name.'&gt;</code>');
			
			/**
			 * Handle each tag type based on its type
			 */
			switch($type) {
				case 'open':
					/**
					 * Increment open tag ID
					 */
					$open_tag_id++;
					$open_tags[$open_tag_id] = $name;
					
					/**
					 * If no stack has been initialized, create one else create a new node
					 */
					if(!$stack && (!($stack instanceof Node))) $stack = new Node($name);
					else $stack = $stack->_nchild($name);
					
					/**
					 * Process the tags attributes
					 */
					$stack->_attrs($attributes);
				break;
				case 'complete':
					/**
					 * If no stack has been initialized, create one else create a new node
					 */
					if(!$stack && (!($stack instanceof Node))) $stack = new Node($name);
					else $stack = $stack->_nchild($name);
					
					/**
					 * Process the tags attributes then step down the stack
					 */
					$stack->_attrs($attributes);
					if($stack->_ instanceof Node) $stack = $stack->_;
				break;
				case 'close':
					/**
					 * Decrement open tag ID
					 */
					unset($open_tags[$open_tag_id]);
					$open_tag_id--;
					
					/**
					 * Step down the stack
					 */
					if($stack->_ instanceof Node) $stack = $stack->_;
				break;
				case 'cdata':
					/**
					 * Send the cdata to the stack
					 */
					$stack->_cdata($attributes);
				break;
			}		
			/**
			 * End Switch
			 */
			
		}
		/**
		 * End While Loop
		 */
		
		return $stack->output();
	}
	
	public function parse_tag($force_html) {
		/**
		 * Search for the opening or closing tag
		 */
		$search = $force_html ? strpos($this->file_cont, '</'.$force_html.'>', $this->pointer) : strpos($this->file_cont, '<', $this->pointer);
		
		/**
		 * Is this new tag a comment
		 */
		$comment = strpos($this->file_cont, '!--', $search) - $search == 1 ? true : false;
		
		/**
		 * If no tag was found return false
		 */
		if($search === false) return false;
		
		/**
		 * Return cdata
		 */
		$node = trim(substr($this->file_cont, $this->pointer, $search - $this->pointer));
		if(strlen($node) != 0) {
			$cdata = $this->extract_vars($node, $force_html ? true : false);
			$this->pointer = $search;
			
			return array('type' => 'cdata', 'name' => $node, 'attributes'=> $cdata);
		}
		
		/**
		 * Find the end of the tag
		 */
		$close = $comment ? strpos($this->file_cont, '-->', $search) + 3 : strpos($this->file_cont, '>', $search) + 1;
		
		/**
		 * Set the pointer to the position of the end of the tag
		 */
		$this->pointer = $close;
		
		/**
		 * Grab the whole tag
		 */
		$tag = substr($this->file_cont, $search, $close - $search);
		
		/**
		 * Prepare Return Array
		 */
		$return = array();
		
		/**
		 * Is this a comment if so set its attributes
		 */
		if($comment) {
			$return['name'] = $tag;
			$return['type'] = 'comment';
			$return['attributes'] = false;
		}
		
		/**
		 * If the tag is not a comment set its attributes
		 */
		if(!$comment) {
			/**
			 * Strip the attributes from the tag if there are any
			 * And grab just the tag name.
			 */
			$return['name'] = strpos($tag, ' ') !== false ? trim(substr($tag, 1, strpos($tag, ' ')), '<>/ ') : trim($tag, '<>/ ');
			
			/**
			 * Determine what type of tag this is
			 */
			$return['type'] = strpos($tag, '/>') !== false ? 'complete' : (strpos($tag, '</') !== false ? 'close' : 'open');
			
			/**
			 * Parse the attributes of a tag (if there are any)
			 */
			$return['attributes'] = $this->get_attributes($tag);
		}
		
		/**
		 * Return the tag
		 */
		return $return;
	}
		
	private function extract_vars($string, $special = false) {		
		/**
		 * Match the variables
		 */
		preg_match_all(
			$special ? LHTML_VAR_REGEX_SPECIAL : LHTML_VAR_REGEX, // Regex Search
			$string, // Source String
			$matches, // Array of matches
			PREG_SET_ORDER // Settings
		);
		
		/**
		 * Loop thru the matches and assign them to an array
		 */
		$vars = array();
		foreach((array)$matches as $var) {
			$vars[] = $var[1];
		}
		
		/**
		 * Return the list of variables
		 */
		return $vars;		
	}	
	
	private function get_attributes($tag){
		/**
		 * Regex match the attributes
		 */
		preg_match_all('/(?:([^\s]*[\:]*[^\s]*))="(?:([^"]*))"/', $tag, $matches, PREG_SET_ORDER);
		
		/**
		 * Loop thru the matches and assign them to an array
		 */
		$attrs = array();
		foreach($matches as $match) {
			$attrs[$match[1]] = $match[2];
		}
				
		/**
		 * Return the list of variables
		 */
		return $attrs;
	}	
	
}