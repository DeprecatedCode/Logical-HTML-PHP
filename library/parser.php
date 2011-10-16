<?php

namespace Evolution\LHTML;
use Exception;

class Parser {
	
	// The entire syntax is defined here as what token the next char implies
	private $tokenRules = array(
		
		# _
		'default' 			=> array(	'<' => 'tag-start' 			),
		
		# <_
		'tag-start' 		=> array(	' ' => '#error',
										'<' => '#error',
										'>' => '#error',
										'"' => '#error',
										"'" => '#error',
										'/' => 'tag-close',
										'*' => 'tag-open-name' 		),
		# </_					
		'tag-close'			=> array(	' ' => '#error',
										'/' => '#error',
										'<' => '#error',
										'>' => '#error',
										'"' => '#error',
										"'" => '#error',
										'*' => '&tag-close-name'		),
		# <a_								
		'tag-open-name' 	=> array(	' ' => 'tag-open-body',
										'<' => '#error',
										'>' => 'tag-end-inside',
										'"' => '#error',
										"'" => '#error',
										'/' => 'tag-end-close'		),
		# </a_							
		'tag-close-name' 	=> array(	' ' => '#error',
										'<' => '#error',
										'>' => 'tag-end-outside',
										'"' => '#error',
										"'" => '#error',
										'/' => '#error'				),
		# <a ... _						
		'tag-open-body'		=> array(	' ' => '#self',
										'<' => '#error',
										'>' => 'tag-end-inside',
										'"' => '#error',
										"'" => '#error',
										'/' => 'tag-end-close',
										'*' => 'tag-attr-name'		),
		# <a ... b_						
		'tag-attr-name'		=> array(	' ' => '#error',
										'<' => '#error',
										'>' => '#error',
										'"' => '#error',
										"'" => '#error',
										'/' => '#error',
										'=' => 'tag-attr-equal'		),
		# <a ... b=_						
		'tag-attr-equal'		=> array(	'"' => 'tag-attr-quote',
											'*' => '#error'			),
		# <a ... b="_						
		'tag-attr-quote'	=> array(	'"' => 'tag-attr-qend',
										'*' => 'tag-attr-value'	),
		# <a ... b="c_						
		'tag-attr-value'	=> array(	'escape' => '\\',
										'"' => 'tag-attr-qend'		),
		# <a ... b="c"_						
		'tag-attr-qend'		=> array(	'*' => '&tag-open-body'		),
		
		# <a ... /_								
		'tag-end-close' 	=> array(	' ' => '#error',
										'<' => '#error',
										'>' => 'tag-end-outside',
										'"' => '#error',
										"'" => '#error',
										'/' => '#error'				),
		# <a ... />_ or </a>_							
		'tag-end-outside'	=> array(	'*' => 'default'			),
		
		# <a ... >_ or <a>_							
		'tag-end-inside'	=> array(	'*' => 'tag-contents'		),
						
		# <a>_						
		'tag-contents' 		=> array(	'type' => 'conditional',
		
			# <script...>_
			array(	'token' 	=> 'tag-open-name',
					'value'		=> 'script',
					
					'special'	=> array(
						'</script>'	=> '&default',
						'//'		=> 'cdata-line-comment',
						'/*'		=> 'cdata-block-comment',
					),
					
					'"' => 'cdata-string-double',
					"'" => 'cdata-string-single'		),
			
			# <style...>_
			array(	'token' 	=> 'tag-open-name',
					'value'		=> 'style',
					
					'special'	=> array(
						'</style>'	=> '&default',
						'/*'		=> 'cdata-block-comment',
					),
					
					'"' => 'cdata-string-double',
					"'" => 'cdata-string-single'		),
					
			# <other...>_
			array(	'<' => 'tag-start'					)
		),
		
	);

	public function build($file, $retstack = false) {
		
		//If the LHTML file does not exist throw an exception
		if(!is_file($file))
			throw new Exception("LHTML could not load `$file`");
		
		// Get file contents
		$lhtml = file_get_contents($file);
		
		// Parse file into stack
		try {
			$tokens = $this->tokenize($lhtml, $this->tokenRules);
		} catch(Exception $exception) {
			throw new Exception($exception->getMessage() . " in file $file", 0, $exception);
		}
		
		if(isset($_GET['--tokens'])) {
			?>
			<style>
				body {
					margin: 10px 50px 10px 20px;	
				}
				.tokens > div {
					padding: 1px;
					float: left;
					white-space:pre;
					position: relative;
					font-size: 12px;
					color: #000;
					height: 15px;
					background: #eee;
					margin: 50px 1px;
					font-family: monospace;
					white-space:nowrap;
				}
				.tokens > div > span {
					position: absolute;
					font-size: 9px;
					border-left: 1px solid #ccc;
					left: -1px;
				}
				
				/*Up*/
				.tokens > div > span.x1 {	
					top: -14px;
					padding: 0 3px 19px;
				}
				.tokens > div > span.x3 {	
					top: -26px;
					padding: 0 3px 31px;
				}
				.tokens > div > span.x5 {	
					top: -38px;
					padding: 0 3px 43px;
				}
				
				/*Down*/
				.tokens > div > span.x2 {	
					top: 0px;
					padding: 18px 3px 0;
				}
				.tokens > div > span.x4 {	
					top: 0px;
					padding: 30px 3px 0;
				}
				.tokens > div > span.x6 {	
					top: 0px;
					padding: 42px 3px 0;
				}
				
				/* Styles */
				.tokens > div.blank {
					background: #fee;
					color: #fbb;
				}
				.tokens > div.tag-start, .tokens > div.tag-end-inside, .tokens > div.tag-end-outside {
					background: #fff000;
					color: #885;
				}
				.tokens > div.tag-open-name, .tokens > div.tag-close-name {
					background: #fff999;
					color: #885;
				}
				.tokens > div.tag-close, .tokens > div.tag-end-close {
					background: #ffe999;
					color: #000;
				}
				.tokens > div.tag-attr-quote, .tokens > div.tag-attr-qend {
					background: #fbb;
					color: #855;
				}
				.tokens > div.tag-attr-value {
					background: #fdd;
					color: #855;
				}
				.tokens > div.tag-attr-name {
					background: #fed;
					color: #865;
				}
				.tokens > div > b.newline {
					background: #def;
					color: #88f;
					padding: 1px 2px 0;
					margin: 0 1px;
				}
				.tokens > div > b.tab {
					background: #dfe;
					color: #4d4;
					padding: 1px 20px 0;
					margin: 0 1px;
				}
				.clear {
					clear: left;
					padding: 1px 0;
				}
			</style>
			<div class="tokens">
			<?php
			$i = 0;
			foreach($tokens as $token) {
				$class = $token->name;
				$v = htmlspecialchars($token->value);
				switch($v) {
					case '':
						$v = '&empty;';
						$class .= " blank";
						break;
				}
				$v = str_replace(" ", '&nbsp;', $v);
				$v = str_replace("\n", '<b class="newline">&crarr;</b></div><div class="clear">', $v);
				$v = str_replace("\r", '<b class="newline">&crarr;</b></div><div class="clear">', $v);
				$v = str_replace("\t", '<b class="tab">&raquo;</b>', $v);
				$i++;
				$pos = $i % 6 + 1;
				echo "<div class='$class'><span class='x$pos'>$token->name</span>$v</div>";
			}
			
			?></div><?php
			die;
		}
		
		// Track open tags
		$openTags = array();
		$openTagsDepth = -1;
		
		// Create the root stack
		$stack = new Node('');
		foreach($tokens as $token) {
			
			// Decide what to do based on token
			switch($token->name) {
				
				// Open tag
				case 'tag-open-name':
					
					// Record open tag
					$openTagsDepth++;
					$openTags[$openTagsDepth] = $token->value;
					
					// Add element to the node stack
					$stack = $stack->_nchild($token->value);
					break;
					
				// Close tag
				case 'tag-end-close':
				case 'tag-close-name':
					
					// Check for long (full) tag
					$long = $token->name === 'tag-close-name';
					
					// Check that this matches the currently open tag
					$oname = $openTags[$openTagsDepth];
					if($long && $oname !== $token->value)
						throw new Exception("LHTML Parse Error: Found closing tag `&lt;/$token->value&gt;`
						when `&lt;$oname&gt;`still needs to be closed
						on line $token->line at character $token->col");	
					
					// Close the tag
					unset($openTags[$openTagsDepth]);
					$openTagsDepth--;
					
					// Move up the stack
					$stack = $stack->_;
					break;
				
				// Tag attribute name
				case 'tag-attr-name':
					$attr = $token->value;
					break;
					
				// Tag attribute value
				case 'tag-attr-value':
					$stack->_attr($attr, $token->value);
					break;
					
				// Tag contents
				case 'default':
				case 'tag-contents':
					
					// Save the string as a child
					$stack->_cdata($token->value);
					break;
					
				default:
					continue;
			}
		}
		
		if(isset($_GET['--stack'])) {
			var_dump($stack);die;
		}
		
		// Return stack output
		if($retstack) return $stack;
		else return $stack->output();
	}
	
	/** tokenize **/
	public function tokenize(&$source, &$rules, $token = 'default') {
		
		// Reset line number
		$lineNumber = 1;
		$colNumber = 0;
		
		// Token start positions
		$tokenLine = 1;
		$tokenCol = 0;
		
		// Go through the code one char at a time, starting with default token
		$length = strlen($source);
		$tokens = array();
		$queue = '';
		$processImmediately = false;
		for($pointer = 0; $pointer <= $length; true) {
			
			// Check if processing a forwarded $char
			if($processImmediately) {
				
				// Shut off process flag
				$processImmediately = false;
			}
			
			// Else get a new $char
			else {
				
				// Get char at pointer
				$char = substr($source, $pointer, 1);
				
				// Step ahead after we have the char
				$pointer++;
				
				// Increment line count
				if($char == "\n" || $char == "\r") {
					$lineNumber++;
					$colNumber = -1;
				}
				
				// Increment column count
				$colNumber++;
			}
			
			// Check that the current token is defined
			if(!isset($rules[$token]))
				throw new Exception("The tokenizer has encountered an invalid <i>$token</i> token");
			
			// Use the token
			$xtoken = $rules[$token];
			
			// Check for special token types
			if(isset($xtoken['type'])) {
				switch($xtoken['type']) {
					
					// Check if the token is conditional, which means that there's a choice of
					// which token rules to follow, depending on the conditions specified.
					case 'conditional':
						$last = count($tokens);
						$last = $tokens[$last - 1];
						
						// Loop through all possible conditions
						foreach($xtoken as $key => $condtoken) {
						
							// Skip the type
							if($key === 'type')
								continue;
						
							// Check that the token matches the condition, if set
							if(isset($condtoken['token']) && $condtoken['token'] !== $last->name)
								continue;
								
							// Check for matching value or catch-all condition
							if(!isset($condtoken['value']) || $condtoken['value'] === $last->value) {
								
								// Switch to this version of the token
								$xtoken = $condtoken;
								break 2;
							}
						}
						
						// If no conditional match found, throw exception
						throw new Exception("LTML Tokenize Error: The tokenizer has encountered a conditional token <i>$token</i> ".
							"that has no valid match for the last token <i>$last[token]</i> and value <code>$last[value]</code>");
						
					default:
						throw new Exception("LTML Tokenize Error: The tokenizer has encountered an invalid token type <code>".
							$xtoken['type']."</code> for token <i>$token</i>");
				
				}
			}
			
			// Whether to check for the ' ' space token, matches all whitespace
			if($char === "\n" || $char === "\r" || $char === "\t")
				$checkchar = ' ';
			else
				$checkchar = $char;
			
			// Check if the current token has an action for this char, both literal and *
			$literal = isset($xtoken[$checkchar]);
			$star = isset($xtoken['*']);
			
			// If no match, char is part of token and continue
			if(!$literal && !$star) {
				$queue .= $char;
				continue;
			}
			
			// Load the next token
			$ntoken = $xtoken[$literal ? $checkchar : '*'];
			
			// Handle '#drop' token
			if($ntoken === '#drop') {
				continue;	
			}
			
			// Handle '#self' token
			if($ntoken === '#self') {
				$queue .= $char;
				continue;	
			}
			
			// Handle '#error' token
			if($ntoken === '#error') {
				//var_dump($tokens);
				//var_dump(array('token' => $token, 'queue' => $queue));
				return $tokens;
				throw new Exception("Unexpected <code><b>'$char'</b></code>
					after <i>$token</i> on line $lineNumber at column $colNumber, code: <code>$queue$char</code>");
			}
			
			// Add the current token to the stack and handle queue
			$tokens[] = (object) array('name' => $token, 'value' => $queue,
				'line' => $tokenLine, 'col' => $tokenCol);
			
			// Update line and column for next token
			$tokenLine = $lineNumber;
			$tokenCol = $colNumber;
			
			// Handle &tokens by immediately queueing the same char on the new token
			if(substr($ntoken, 0, 1) === '&') {
				$token = substr($ntoken, 1);
				$processImmediately = true;
				$queue = '';
			}
			
			// Normal tokens will start queue on next char
			else {
				$token = $ntoken;
				$queue = $char;
			}
		}
		
		// Return tokens
		return $tokens;
	}
}