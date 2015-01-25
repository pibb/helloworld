<?php
namespace Core;

if ( !defined( "D_CLASS_TEMPLATE" ) )
{
	define( "D_CLASS_TEMPLATE", true );
	
	/**
 	 * File: class.template.php
	 *
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Template extends Base
	{
		public $file 			= "";
		public $parsed			= false;
		
		protected $vars 		= array();
		protected $functions	= array();
		protected $webpage		= NULL;
		protected $dir_temps	= "";
		protected $cache		= "";
		protected $local_cache	= "";
		protected $temp_id		= 0;
		
		/**
		 * Class constructor.
		 *
		 * @uses Template::$webpage
		 * @uses Template::$dir_temps
		 * @uses Template::$cache
		 * @uses Template::$local_cache
		 * @uses Template::$temp_id
		 * @uses Template::$file
		 * @uses Website::$local
		 * @uses WebPage::$theme
		 * @uses WebPage::$temp_vars
		 * @uses WebPage::$temp_funcs
		 * @param WebPage the current webpage object.
		 * @param string the name of the template file (i.e., header.html).
		 */
		function __construct( WebPage &$webpage, $file )
		{
			if ( !$file ) trigger_error( "Requested a template without a file name.", E_USER_ERROR );
			
			$f = $file;
			// initialize variables
			$this->webpage 	= $webpage;
			$local 			= str_replace( basename( $_SERVER[ 'SCRIPT_FILENAME' ] ), "", $_SERVER[ 'SCRIPT_FILENAME' ] );
			$dir 			= "themes" . DIRECTORY_SEPARATOR . $webpage->theme . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
			$this->dir_temps=  $local . $dir;
			
			$mdir = strpos( $_SERVER[ 'SCRIPT_FILENAME' ], "alpha" ) !== false ? "alpha/" : "public_html/";
			$public_html_dir = strpos( $_SERVER[ 'SCRIPT_FILENAME' ], $mdir );
			$public_html_dir = substr( $_SERVER[ 'SCRIPT_FILENAME' ], 0, $public_html_dir + strlen( $mdir ) );
			$ndir = str_replace( $public_html_dir, "", $_SERVER[ 'SCRIPT_FILENAME' ] );
			$ndir = $webpage->site->local . str_replace( basename( $ndir ), "", $ndir );
			$this->dir_temps = $ndir . $dir;
			
			$rel_file 		= $this->dir_temps . $file;
			$this->cache	= $webpage->site->local . $dir . "cache" . DIRECTORY_SEPARATOR;
			$script_file 	= basename( $_SERVER[ 'SCRIPT_NAME' ] );
			$script_dir 	= substr( str_replace( "/", DIRECTORY_SEPARATOR, str_replace( $script_file, "", $_SERVER[ 'SCRIPT_NAME' ] ) ), 1 );
			$this->local_cache = $webpage->site->local . $script_dir . $dir . "cache" . DIRECTORY_SEPARATOR;
			$file 			= $webpage->site->local . $dir . $file;
			$this->temp_id	= basename( $file );
			
			// search locally for the template before going back to the root
			if ( file_exists( $rel_file ) )
				$this->file = $rel_file;
			else if ( file_exists( $file ) )
				$this->file = $file;
			else
			{
				$d = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 0 );
				$error = "Template file does not exist: {$file}. <strong>Called from {$d[0]['file']} on line {$d[0]['line']}.</strong>" ;
				trigger_error( $error, E_USER_ERROR );
			}
			
			// initialize global template variables
			if ( $webpage->temp_vars ) $this->add_vars( $webpage->temp_vars );
			if ( $webpage->temp_funcs ) $this->add_funcs( $webpage->temp_funcs );
		}
		
		/**
		 * String conversion; parses template and returnd HTML. Automatically called.
		 *
		 * @uses User::$level
		 * @uses Template::parse
		 */
		public function __toString()
		{
			$html = "";
			
			try
			{
				$html = $this->parse( true );
			}
			catch( \Exception $e )
			{
				$msg = $this->webpage->user->level >= User::ADMIN ? $e->getMessage() : "An internal error occurred.";
				trigger_error( $msg, E_USER_ERROR );
			}
			
			return $html;
		}
	
		/**
		 * Adds variable to $this->vars.
		 *
		 * @param string variable name (i.e., V_TITLE).
		 * @param mixed value of that variable.
		 * @uses Template::$vars
		 */
		public function add_var( $index, $value )
		{
			$this->vars[ $index ] = $value;
		}
		
		/**
		 * Adds attay of variables to $this->vars.
		 *
		 * @param Array
		 * @uses Template::$vars
		 */
		public function add_vars( array $vars )
		{
			$this->vars = array_merge( $this->vars, $vars );
		}
	
		/**
		 * Adds function to $this->functions.
		 *
		 * @param string function handle.
		 * @param string function name.
		 * @param mixed parameters. (Defaut = array())
		 * @uses Template::$functions
		 */
		public function add_func( $index, $name, $params = array() )
		{
			$this->functions[ $index ] = array( 'name' => $name, 'params' => $params );
		}
	
	
		/*
		 * Adds function to $this->functions.
		 *
		 * @param string function handle.
		 * @uses Template::$functions
		 *
		public function add_func_void( $index )
		{
			$this->functions[ $index ] = array( 'name' => array( $this, "void" ) );
		}*/
		
		/**
		 * Adds array of function to $this->functions.
		 *
		 * @param Array
		 * @uses Template::$functions
		 */
		public function add_funcs( array $funcs )
		{
			$this->functions = array_merge( $this->functions, $funcs );
		}
		
		/**
		 * Creates a template from the given file and returns the output.
		 *
		 * @param WebPage cuurent WebPage object.
		 * @param string template file name.
		 * @uses Template::parse
		 */
		static public function get( WebPage &$webpage, $a )
		{
			if ( is_array( $a ) ) $a = $a[ 0 ];
			$html = new Template( $webpage, $a );
			
			return $html->parse();
		}
		
		/**
		 * Gets array of variables mentioned in the template file.
		 *
		 * @return Array
		 */
		public function get_vars()
		{
			$data = file_get_contents( $this->file );
			$r = preg_match_all( '/\{V_([\w_]+)\}/', $data, $patterns );
			return $patterns[ 1 ];
		}
		
		/**
		 * Returns parsed template file using object's webpage object.
		 *
		 * @param string name of the template file.
		 * @param bool eval() or not? (Default = false)
		 * @uses Template::parse
		 * @return string usually HTML from the file; nothing if eval()'d
		 */
		public function load( $file, $eval = false )
		{
			$html = new Template( $this->webpage, trim( $file ) );
			return $html->parse( $eval );
		}
		
		/**
		 * Parses the template file and caches results.
		 *
		 * @param bool eval() or not? (Default = false)
		 * @uses Template::translate
		 * @uses Template::$parsed
		 * @return string usually HTML from the file; nothing if eval()'d
		 */
		public function parse( $eval = true )
		{
			if ( !$this->parsed )
			{
				// determine if there already a cache file
				$base = basename( $this->file );
				$cache = str_replace( $base, "cache" . DIRECTORY_SEPARATOR . $base . ".php", $this->file );
				if ( $this->webpage->refresh_cache || !file_exists( $cache ) )
				{
					$output	= $this->translate( file_get_contents( $this->file ) );
					file_put_contents( $cache, $output );
				}
				
				// turning off error reporting because E_NOTICES get thrown around for undeclared variables
				$level = error_reporting();
				error_reporting( E_WARNING );
				
				// clear the global variable array
				if ( $eval )
				{
					$this->parsed = true;
					eval( '?>' . file_get_contents( $cache ) );
				}
				else
				{
					ob_start();
					eval( '?>' . file_get_contents( $cache ) );
					$output = ob_get_contents();
					ob_end_clean();
					return $output;
				}
				
				// return to normal error handling.
				error_reporting( $level );
			}
			return "";
		}	
		
		/**
		 * Replaces template pseudocode elements with given PHP code.
		 *
		 * @param string the text to be searched.
		 * @param string pseudocode needle 1.
		 * @param string pseudocode needle 2.
		 * @param string callback function that inserts code to put between open and close.
		 * @param string PHP for needle 1.
		 * @param string PHP for needle 2.
		 * @return string
		 */
		protected function translate_piece( $a, $open, $close, $cb, $replace_open = "", $replace_close = "" )
		{
			$offset = 0;
			$open_len = strlen( $open );
			$close_len = strlen( $close );
			while( $offset <= strlen( $a ) && is_int( $i = strpos( $a, $open, $offset ) ) )
			{
				$i += $open_len;
				if ( is_int( $j = strpos( $a, $close, $i ) ) )
				{
					$len = $j - $i;
					$name = substr( $a, $i, $len );
					$replace_text = $replace_open . call_user_func( array( $this, $cb ), $name ) . $replace_close;
					$a = substr( $a, 0, ( $i - $open_len ) ) . $replace_text . substr( $a, ( $j + $close_len ) );
					
					$offset = $i - strlen( $replace_open ) + strlen( $replace_text );
				}
				
			}
			
			return $a;
		}
		
		/**
		 * Interprets pseudo-iF statments.
		 *
		 * Goes through each token in the IF statement "IF ( TOKEN TOKEN TOKEN )" and finds variables to
		 * search for in the template. If they're found, they are replaced. If not, they are replaced with
		 * a "0" to evaluate as false. Operators should be ignored.
		 *
	 	 * @todo The array variables only go three levels deep (i.e., V_ARRAY.ARRAY.ARRAY.KEY). There needs to be a recursive way to make them go deeper.
		 * @param string arguments deliminated by spaces.
		 * @uses Template::$vars
		 * @return string parsed arguments.
		 */
		protected function translate_if( $args )
		{
			// initialize variables
			$args_array = explode( " ", $args );
			$opposite = false;

			foreach( $args_array as $i => $a )
			{
				if ( !$opposite && strtoupper( $a ) == "NOT" )
				{
					$opposite = true;
					$args_array[ $i ] = "";
				}
				else if ( strtoupper( $a ) == "AND" )
					$args_array[ $i ] = "&&";
				else if ( strtoupper( $a ) == "OR" )
					$args_array[ $i ] = "||";
				else if ( preg_match( "/^[A-Za-z][A-Za-z0-9_]+$/", $a ) )
				{
					$args_array[ $i ] = /*"isset( \$this->vars['" . $a . "'] ) && " .*/ ( $opposite ? "!" : "" ) . "\$this->vars['" . $a . "']";
					$opposite = false;
				}
				else if ( preg_match( "/^([A-Za-z][A-Za-z0-9_]+)\.([A-Za-z0-9_]+)$/", $a ) )
				{
					// initialize the variable pair
					list( $var, $key ) = explode( ".", $a );
					$key = strtolower( $key );
					
					// variable reference depends upon whether the foreach variable.key is an array or object
					if ( !isset( $this->vars[ $var ] ) && $this->vars[ $var ] )
						$args_array[ $i ] = 0;
					else 
					{
						reset( $this->vars[ $var ] );
						$firstkey = key( $this->vars[ $var ] );
						if ( isset( $this->vars[ $var ][ $firstkey ] ) )
						{
							if ( is_array( $this->vars[ $var ][ $firstkey ] ) )
								$args_array[ $i ] = /*"isset( \$var['" . $key . "'] ) && " .*/ ( $opposite ? "!" : "" ) . "\$var['" . $key . "']";
							else
								$args_array[ $i ] = "( property_exists( get_class( \$var ), '" . $key . "' ) && " . ( $opposite ? "!" : "" ) . "\$var->" . $key . ")";
						}
					}
					
					// reset opposite flag
					$opposite = false;
				}
				else if ( preg_match( "/^([A-Za-z][A-Za-z0-9_]+)\.([A-Za-z0-9_]+)\.([A-Za-z0-9_]+)$/", $a ) )
				{
					// initialize the variable pair
					list( $var, $key1, $key2 ) = explode( ".", $a );
					$key1 = strtolower( $key1 );
					$key2 = strtolower( $key2 );
					
					// variable reference depends upon whether the foreach variable.key is an array or object
					if ( !isset( $this->vars[ $var ] ) && $this->vars[ $var ] )
						$args_array[ $i ] = 0;
					else 
					{
						reset( $this->vars[ $var ] );
						$firstkey = key( $this->vars[ $var ] );
						if ( isset( $this->vars[ $var ][ $firstkey ] ) )
						{
							if ( is_array( $this->vars[ $var ][ $firstkey ] ) )
								$args_array[ $i ] = /*"isset( \$var2['" . $key2 . "'] ) && " .*/ ( $opposite ? "!" : "" ) . "\$var2['" . $key2 . "']";
							else
								$args_array[ $i ] = "( property_exists( get_class( \$var2 ), '" . $key2 . "' ) && " . ( $opposite ? "!" : "" ) . "\$var2->" . $key2 . ")";
						}
					}
					
					// reset opposite flag
					$opposite = false;
				}
				else if ( preg_match( "/^([A-Za-z][A-Za-z0-9_]+)\.([A-Za-z0-9_]+)\.([A-Za-z0-9_]+)\.([A-Za-z0-9_]+)$/", $a ) )
				{
					// initialize the variable pair
					list( $var, $key1, $key2, $key3 ) = explode( ".", $a );
					$key1 = strtolower( $key1 );
					$key2 = strtolower( $key2 );
					$key3 = strtolower( $key3 );
					
					// variable reference depends upon whether the foreach variable.key is an array or object
					if ( !isset( $this->vars[ $var ] ) && $this->vars[ $var ] )
						$args_array[ $i ] = 0;
					else 
					{
						reset( $this->vars[ $var ] );
						$firstkey = key( $this->vars[ $var ] );
						if ( isset( $this->vars[ $var ][ $firstkey ] ) )
						{
							if ( is_array( $this->vars[ $var ][ $firstkey ] ) )
								$args_array[ $i ] = /*"isset( \$var3['" . $key3 . "'] ) && " .*/ ( $opposite ? "!" : "" ) . "\$var3['" . $key3 . "']";
							else
								$args_array[ $i ] = "( property_exists( get_class( \$var3 ), '" . $key3 . "' ) && " . ( $opposite ? "!" : "" ) . "\$var3->" . $key3 . ")";
						}
					}
					
					// reset opposite flag
					$opposite = false;
				}
			}
					
			return implode( " ", $args_array );
		}
		
		/**
		 * Interprets pseudo-include statments.
		 *
		 * @param string file to include.
		 * @uses Template::add_vars
		 * @uses Template::parse
		 * @return string file
		 */
		protected function translate_include( $file )
		{
			$file = trim( $file );
			$html = new Template( $this->webpage, $file );
			$html->add_vars( $this->vars );
			$html->parse( false );
			return $file;
		}
		
		/**
		 * Interprets pseudo-foreach statments.
		 *
	 	 * @todo The array variables only go three levels deep (i.e., V_ARRAY.ARRAY.ARRAY.KEY). There needs to be a recursive way to make them go deeper.
		 * @param string Array name to loop across
		 * @uses Template::$vars
		 * @return string foreach code
		 */
		protected function translate_begin( $var )
		{
			$var = trim( $var );
			if ( preg_match( "/^([A-Za-z][A-Za-z0-9_]+)\.([A-Za-z0-9_]+)\.([A-Za-z0-9_]+)$/", $var ) )
			{
				// initialize the variable pair
				list( $var, $keya, $keyb ) = explode( ".", $var );
				$keya = strtolower( $keya );
				$keyb = strtolower( $keyb );
				
				$var = "\$var2['" . $keyb . "']";
				$var = "{$var} ) { foreach ( {$var} as \$index3 => \$var3";
			}
			else if ( !preg_match( "/^([A-Za-z][A-Za-z0-9_]+)\.([A-Za-z0-9_]+)$/", $var ) )
			{
				$var = "\$this->vars['" . trim( $var ) . "']";
				$var = "{$var} ) { foreach ( {$var} as \$index => \$var";
			}
			else
			{
				// initialize the variable pair
				list( $var, $key ) = explode( ".", $var );
				$key = strtolower( $key );
				
				$var = "\$var['" . $key . "']";
				$var = "{$var} ) { foreach ( {$var} as \$index2 => \$var2";
			}
			return $var;
		}
		
		/**
		 * Translates template pseudocode and drops anything not recognized.
		 *
	 	 * @todo The array variables only go three levels deep (i.e., V_ARRAY.ARRAY.ARRAY.KEY). There needs to be a recursive way to make them go deeper.
		 * @param string text to translate.
		 * @global $temp only way for eval()'d script to know translated template variables.
		 * @uses Template::translate_if
		 * @uses Template::translate_begin
		 * @uses Template::translate_include
		 * @uses Template::$cache
		 * @uses Template::$local_cache
		 * @uses Template::$functions
		 * @uses Template::$vars
		 * @return string translated text
		 */
		protected function translate( $a )
		{
			global $temp;
			
			// look for PHP constructions
			$a = $this->translate_piece( $a, "<!-- IF ", "-->", "translate_if", "<?php if ( ", " ) { ?>" );
			$a = $this->translate_piece( $a, "<!-- ELSE IF ", "-->", "translate_if", "<?php } else if ( ", " ) { ?>" );
			$a = $this->translate_piece( $a, "<!-- BEGIN ", "-->", "translate_begin", "<?php if ( ", " ) { ?>" );
			$a = $this->translate_piece( $a, "<!-- INCLUDE ", "-->", "translate_include", "<?php include('" . $this->cache, ".php'); ?>" );
			$a = $this->translate_piece( $a, "<!-- LOCAL_INCLUDE ", "-->", "translate_include", "<?php include('" . $this->local_cache, ".php'); ?>" );
			$a = str_replace( "<!-- ENDIF -->", "<?php } ?>", $a );
			$a = str_replace( "<!-- END -->", "<?php } ?>", $a );
			$a = str_replace( "<!-- ENDBEGIN -->", "<?php } } ?>", $a );
			$a = str_replace( "<!-- ELSEBEGIN -->", "<?php } } else { ?>", $a );
			$a = str_replace( "<!-- ENDELSEBEGIN -->", "<?php } ?>", $a );
			$a = str_replace( "<!-- ELSE -->", "<?php } else { ?>", $a );
			
			// look for function calls (as they may return different results upon each call)
			$offset = 0;
			while( $i = strpos( $a, "{F_", $offset ) )
			{
				$i += 3;
				if ( $j = strpos( $a, "}", $i ) )
				{
					$len 	= $j - $i;
					$name 	= strtoupper( substr( $a, $i, $len ) );
					$exists = isset( $this->functions[ $name ] ) && is_callable( $this->functions[ $name ][ 'name' ] );
					
					if ( isset( $this->functions[ $name ] ) && $exists )
					{
						$f = &$this->functions[ $name ];
						$b = ( isset( $f[ 'params' ] ) && $f[ 'params' ] ) ? call_user_func( $f[ 'name' ], $f[ 'params' ] ) : call_user_func( $f[ 'name' ] );
						
						if ( $b === false ) $b = "Could not call: " . $f[ 'name' ];
						$a = substr( $a, 0, ( $i - 3 ) ) . $b . substr( $a, ( $j + 1 ) );
					}
				}
				$offset = $i + 1;
			}
			
			// look for predefined variables
			foreach( $this->vars as $index => $var )
			{
				if ( is_array( $var ) )
				{
					foreach( $var as $i => $v )
					{
						// if the variable is an associated array, we'll try to match keys to values
						if ( is_array( $v ) )
						{
							foreach( $v as $j => $w )
							{
								// {V_ARRAY.KEY}
								if ( is_array( $w ) )
								{
									foreach( $w as $k => $x )
									{
										if ( is_array( $x ) )
										{
											// {V_ARRAY.ARRAY.KEY}
											foreach( $x as $m => $y )
											{
												if ( is_array( $y ) )
												{
													foreach( $y as $n => $z )
													{
															//die( var_dump( $z ) );
														if ( is_array( $z ) )
														{
															// {V_ARRAY.ARRAY.ARRAY.KEY}
															foreach( $z as $o => $zz )
															{
																$a = str_replace( '{' . strtoupper( $index ) . '.' . strtoupper( $j ) . '.' . strtoupper( $m ) . '.' . strtoupper( $o ) . '}', "<?php echo \$var3['" . $o . "']; ?>", $a );
															}
														}
														elseif ( $z instanceof Data )
														{
															// if the variable is a data object, then we'll iterate object properties
															foreach( $z->get_props() as $key => $val )
															{
																$a = str_replace( '{' . strtoupper( $index ) . '.' . strtoupper( $j ) . '.' . strtoupper( $m ) . '.' . strtoupper( $key ) . '}', "<?php echo \$var3->$key; ?>", $a );
															}
														}
													}
												}
												else
												{
													$a = str_replace( '{' . strtoupper( $index ) . '.' . strtoupper( $j ) . '.' . strtoupper( $m ) . '}', "<?php echo \$var2['" . $m . "']; ?>", $a );
												}
											}
										}
										elseif ( $x instanceof Data )
										{
											// if the variable is a data object, then we'll iterate object properties
											foreach( $x->get_props() as $key => $val )
											{
												$a = str_replace( '{' . strtoupper( $index ) . '.' . strtoupper( $j ) . '.' . strtoupper( $key ) . '}', "<?php echo \$var2->$key; ?>", $a );
											}
										}
									}
								}
								else
								{
									$a = str_replace( '{' . strtoupper( $index ) . '.' . strtoupper( $j ) . '}', "<?php echo \$var['" . $j . "']; ?>", $a );
								}
							}
						}
						elseif ( $v instanceof Data )
						{
							// if the variable is a data object, then we'll iterate object properties
							foreach( $v->get_props() as $key => $val )
							{
								$a = str_replace( '{' . strtoupper( $index ) . '.' . strtoupper( $key ) . '}', "<?php echo \$var->$key; ?>", $a );
							}
						}
					}
				}
				else
				{
					if ( isset( $this->vars[ $index ] ) )
						$a = str_replace( '{' . strtoupper( $index ) . '}', "<?php echo \$this->vars['" . $index . "']; ?>", $a );
					else
						$a = str_replace( '{' . strtoupper( $index ) . '}', "", $a );
				}
			}
			
			// clear out the unfound variables so the file looks more professional
			$a = preg_replace( "/\{[A-Za-z0-9_]+\}/", "", $a );
			
			return $a;
		}
	
		/**
		 * Returns $this->vars.
		 *
		 * @uses Template::$vars
		 * @return Array
		 */
		public function get_temp_vars() 
		{ 
			return $this->vars; 
		}
		
		/**
		 * Returns the value of the given template variable.
		 *
		 * @param string the name of the template variable.
		 * @uses Template::$vars
		 * @return mixed returns false if it doesn't exist.
		 */
		public function get_temp_var( $index ) 
		{ 
			return isset( $this->vars[ $index ] ) ? $this->vars[ $index ] : false; 
		}
		
		#--------------------------------------------------------------------------------------------------
		# * Void
		#--------------------------------------------------------------------------------------------------
		//static public function void() {}
	};
}
?>