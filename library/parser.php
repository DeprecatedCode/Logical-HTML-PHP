<?php

namespace Evolution\LHTML;
use Evolution\Text\Lexer;
use Exception;

class Parser {
	
	// The entire syntax is defined here as what token the next char implies
	private $grammar = array(
		
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
		
		// Load lexer
		$lexer = new Lexer();
		$lexer->grammar($this->grammar)->sourceFile($file);
		
		// Debug if set
		if(isset($_GET['--tokens']))
			die($lexer->debugHTML());
		
		// Load tokens
		$tokens = $lexer->tokenize();
		
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
}