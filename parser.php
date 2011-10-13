<?php

namespace lhtml;
use \Exception;

class Parser2 {
	
	public $lineNumber;
	public $colNumber;
	
	public $cdataTags = array('script', 'style');
	
	// The entire syntax is defined here as what token the next char implies
	private $parserSyntax = array(
		
		# _
		'default' 			=> array(	'<' => 'tag-start' 			),
		
		# <_
		'tag-start' 		=> array(	' ' => 'error',
										'<' => 'error',
										'>' => 'error',
										'"' => 'error',
										"'" => 'error',
										'/' => 'tag-close',
										'*' => '!tag-open-name' 		),
		# </_					
		'tag-close'			=> array(	' ' => 'error',
										'/' => 'error',
										'<' => 'error',
										'>' => 'error',
										'"' => 'error',
										"'" => 'error',
										'*' => '!tag-close-name'		),
		# <a_								
		'tag-open-name' 	=> array(	' ' => 'tag-open-body',
										'<' => 'error',
										'>' => 'tag-end-inside',
										'"' => 'error',
										"'" => 'error',
										'/' => 'tag-end-close'		),
		# </a_							
		'tag-close-name' 	=> array(	' ' => 'error',
										'<' => 'error',
										'>' => 'tag-end-outside',
										'"' => 'error',
										"'" => 'error',
										'/' => 'error'				),
		# <a ... _						
		'tag-open-body'		=> array(	' ' => '#drop',
										'<' => 'error',
										'>' => 'tag-end-inside',
										'"' => 'error',
										"'" => 'error',
										'/' => 'tag-end-close',
										'*' => '!tag-attr-name'		),
		# <a ... b_						
		'tag-attr-name'		=> array(	' ' => 'error',
										'<' => 'error',
										'>' => 'error',
										'"' => 'error',
										"'" => 'error',
										'/' => 'error',
										'=' => 'tag-attr-equal'		),
		# <a ... b=_						
		'tag-attr-name'		=> array(	'"' => 'tag-attr-quote',
										'*' => 'error'				),
		# <a ... b="_						
		'tag-attr-quote'	=> array(	'"' => 'tag-attr-qend',
										'*' => '!tag-attr-value'	),
		# <a ... b="c_						
		'tag-attr-value'	=> array(	'escape' => '\\',
										'"' => 'tag-attr-qend'		),
		# <a ... b="c"_						
		'tag-attr-qend'		=> array(	'*' => '!tag-open-body'		),
		
		# <a ... /_								
		'tag-end-close' 	=> array(	' ' => 'error',
										'<' => 'error',
										'>' => 'tag-end-outside',
										'"' => 'error',
										"'" => 'error',
										'/' => 'error'				),
		# <a ... />_ or </a>_							
		'tag-end-outside'	=> array(	'*' => '!default'			),
										
		# <a>_						
		'tag-end-inside' => array(	'type' => 'conditional',
		
			# <script...>_
			array(	'token' 	=> 'tag-open-name',
					'equals'	=> 'script',
					
					'special'	=> array(
						'</script>'	=> '!default',
						'//'		=> 'cdata-line-comment',
						'/*'		=> 'cdata-block-comment',
					),
					
					'"' => 'cdata-string-double',
					"'" => 'cdata-string-single'		),
			
			# <style...>_
			array(	'token' 	=> 'tag-open-name',
					'equals'	=> 'style',
					
					'special'	=> array(
						'</style>'	=> '!default',
						'/*'		=> 'cdata-block-comment',
					),
					
					'"' => 'cdata-string-double',
					"'" => 'cdata-string-single'		),
					
			# <other...>_
			array(	'<' => 'tag-start'					)
		),
		
	);

	public function build($file) {
		
		//If the LHTML file does not exist throw an exception
		if(!is_file($file))
			throw new \Exception('LHTML could not load `$file`');
		
		// Get file contents
		$lhtml = file_get_contents($file);
		
		// Parse file into stack
		$stack = $this->parse($lhtml);
		
		var_dump($stack);die;
		
		// Return stack output
		return $stack->output();
	}
	
	public function parse(&$lhtml) {
		
		// Reset line number
		$this->lineNumber = 1;
		$this->colNumber = 1;
		
		// Setup stack
		$stack = null;
		
		// Go through the code one char at a time, starting with default token
		$length = strlen($lhtml);
		$tokens = array();
		$token = 'default';
		$queue = '';
		$x = $this->parserSyntax;
		for($pointer = 0; $pointer < $length; $pointer++) {
			
			// Get char
			$char = substr($lhtml, $pointer, 1);
			
			// Increment line count
			if($char == "\n" || $char == "\r") {
				$this->lineNumber++;
				$this->colNumber = 0;
			}
			
			// Increment column count
			$this->colNumber++;
			
			// Check that the current token is defined
			if(!isset($x[$token]))
				throw new Exception("The parser has encountered an invalid $token token");
			
			// Check if the current token has an action for this char
			if($char === "\n" || $char === "\r" || $char === "\t")
				$checksp = true;
			else
				$checksp = false;
			$literal = isset($x[$token][$checksp ? ' ' : $char]);
			$star = isset($x[$token]['*']);
			if($literal || $star) {
				$ntoken = $x[$token][$literal ? $char : '*'];
				if(is_array($ntoken)) {
					var_dump($tokens);die;
				}
				
				// Handle 'error' token
				if($ntoken === 'error') {
					throw new Exception("LTHML Syntax Error: Unexpected <code><b>$char</b></code> after $token on line $this->lineNumber at column $this->colNumber, code: $queue$char");
				}
				
				// Handle !tokens by immediately processing new token with same char
				if(substr($ntoken, 0, 1) === '!') {
					$tokens[] = array('token' => $token, 'value' => $queue);
					$token = substr($ntoken, 1);
					$queue = $char;
				}
				
				// Normal tokens, they process on next char
				else {
					$tokens[] = array('token' => $token, 'value' => $queue);
					$token = $ntoken;
				}
			}
			
			// If no match for character, add to queue
			else {
				$queue .= $char;
			}
			
			continue;
			
			if(!$stack && (!($stack instanceof Node))) $stack = new Node($name);
			else $stack = $stack->_nchild($name);
		}
		return $stack;
	}

}

// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK
// DELETE EVERYTHING BELOW THIS BLOCK

define(__NAMESPACE__.'\LHTML_VAR_REGEX', "/{([\w:|.\,\(\)\/\-\% \[\]\?'=]+?)}/");
define(__NAMESPACE__.'\LHTML_VAR_REGEX_SPECIAL', "/{(\%[\w:|.\,\(\)\[\]\/\-\% ]+?)}/");

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
		if(!file_exists($file)) throw new \Exception('LHTML Could not load $file');
		
		/**
		 * Check to see if a valid cache exists and use it
		 * Else pull in the LHTML file
		 */		
		$cache = __DIR__.'/cache/'.md5($file.$_SERVER['HTTP_HOST']);
		//if(@filemtime($file) < @filemtime($cache)) $this->file_cont = @file_get_contents($cache);
		//else $this->file_cont = @file_get_contents($file);
		$this->file_cont = @file_get_contents($file);
				
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
		return $output;
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
		while($tag = $this->parse_tag($force_html)) {
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
				throw new \Exception('LHTML Parse Error', $this->file_name, $this->line_numb, 'I was expecting the end tag <code>&lt;/'.$open_tags[$open_tag_id].'&gt;</code>, instead I got <code>&lt;/'.$tag_name.'&gt;</code>');
			
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
					$stack->_cdata($name);
				break;
			}		
			/**
			 * End Switch
			 */
				
		}
		/**
		 * End While Loop
		 */
		var_dump($stack);die;				
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
			$this->pointer = $search;						
			return array('type' => 'cdata', 'name' => $node, 'attributes'=> false);
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