<?php
namespace Core;

if ( !defined( "D_CLASS_BASE" ) )
{
	define( "D_CLASS_BASE", true );
	require( 'class.globals.php' );
	require( 'class.generic.php' );
	require( 'class.validation.php' );
	
	/**
 	 * File: class.base.php
	 * 
	 * As its name suggests, this is the Base class for everything in the framework. It isn't
	 * required, but it provides some useful object inheritance property access rules.
	 *
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.1
	 */
	abstract class Base
	{
		protected $_getters = array();
		protected $parent	= NULL;
		protected $webpage	= NULL;
		
		const CLASS_PREFIX 	= "class.";
		const CLASS_EXT 	= ".php";
		
		/**
		 * Retrieves the value of protected proerties and throws an error if not allowed.
		 *
		 * Sometimes we need properties to be readable but not writable. This method checks
		 * a base property called _getters that can be overloaded to include any protected
		 * properties that can then be read by scripts. It will first check the class for a
		 * defined get_X method (where X is the name of the property) and call that instead;
		 * otherwise it will simply return the value. If neither exists, it will thrown an error.
		 *
		 * (Note: This method is automatically called whenever a script tries to read any
		 * property from outside the scope of the class.)
		 * 
		 * @param string The name of the property.
		 * @uses Base::$_getters to determine which properties can be read.
		 * @return mixed The value of the property.
		 */
		public function __get( $p ) 
		{
			// initialize variables
			$var 		= NULL;
			$error		= "";
			$func		= "get_" . $p;
			list( $e ) 	= debug_backtrace();
			
			// try to find the method before triggering an error. anything in the $_getters array will also work
			if ( method_exists( $this, $func ) )				$var = call_user_func( array( $this, $func ) );
			else if ( in_array( $p, $this->_getters ) )			$var = $this->$p;
			else if ( !property_exists( $this, $p ) )			$error = "Property \"{$p}\" does not exist";
			else if ( method_exists( $this, 'set_' . $p ) )		$error = "Property \"{$p}\" is write-only";
			else												$error = "Property \"{$p}\" is not accessible";
			 
			// trigger an error if there was one
			if ( $error ) trigger_error( $error . " in <code>" . $e[ 'file' ] . "</code> on line <code>" . $e[ 'line' ] . "</code>.", E_USER_ERROR );
			
			return $var;
		}
		
		/**
		 * Sets the given property to the given value and throws an error if not allowed.
		 *
		 * Checks to see if a given property has write permissions before attempting to change
		 * a value. It will first check the class for a defined set_X method (where X is the name
		 * of the property) and call that instead using the $value as an argument. This is
		 * useful if a property's value needs to be validated automatically (i.e. keeping a number
		 * between minimum and maximum values).
		 *
		 * (Note: This method is automatically called whenever a script tries to set any
		 * property from outside the scope of the class.)
		 * 
		 * @param string The name of the property.
		 * @param mixed The value to set the property to.
		 * @uses Base::$_getters to determine which properties can be read.
		 * @return mixed The value of the property.
		 */
		public function __set( $p, $value ) 
		{
			// initialize variables
			$var 		= NULL;
			$error		= "";
			$func		= "set_" . $p;
			list( $e ) 	= debug_backtrace();
				
			// try to find the method before triggering an error. anything in the $_getters array will also work
			if ( method_exists( $this, $func ) )	
				$var = call_user_func( array( $this, $func ), $value );
			else if ( !property_exists( $this, $p ) )			
				$error = "Property \"{$p}\" does not exist";
			else if ( in_array( $p, $this->_getters ) || method_exists( $this, 'get_' . $p ) )		
				$error = "Property \"{$p}\" is read-only";
			else												
				$error = "Property \"{$p}\" is not accessible";
				
			// trigger an error if there was one
			if ( $error ) trigger_error( $error . " in <code>" . $e[ 'file' ] . "</code> on line <code>" . $e[ 'line' ] . "</code>.", E_USER_ERROR );
			
			return $var;
		}
			
		/**
		 * Returns the reference to the current object's parent. Automatically called by __get
		 *
		 * Base objects can know their parents, and any children set from the adopt() method will inherit a reference
		 * to its parent for internal reference (i.e., $program->episode->parent would refer back to the $program if
		 * it was properly adopted). Automatically called by __get.
		 *
		 * <code>
		 * $episode->segment->get_parent(); // returns a reference to $episode
		 * </code>
		 *
		 * @uses Base::$parent as a return value.
		 * @return Base The parent object (if any)
		 */
		public function get_parent() 				
		{ 
			return $this->parent; 
		}
			
		/**
		 * Returns the reference to the current object's WebPage object. Automatically called by __get
		 *
		 * Frequently, objects will have a reference to the current WebPage object in order to access its variables
		 * and methods, especially for creating links (i.e., $this->webpage->anchor()). Automatically called by __get.
		 *
		 * @uses Base::$webpage as a return value.
		 * @return WebPage Description
		 */
		final public function get_webpage() 				
		{ 
			return $this->webpage; 
		}
			
		/**
		 * Sets the parent property to the given Base object by reference. Automatically called by __set.
		 *
		 * @param Base The parent object must also be a Base object.
		 */
		public function set_parent( $a )
		{ 
			$this->parent = $a;
		}
			
		/**
		 * Set the webpage property to the given WebPage object by reference. Automatically called by __set.
		 *
		 * @param WebPage The value must be a WebPage object.
		 */
		final public function set_webpage( WebPage &$a ) 	
		{ 
			$this->webpage = $a;
		}
			
		/**
		 * Adopts the value of the given property if it's a Base object. Also works on 1D arrays of Base objects.
		 *
		 * In order for child objects to access their parent's properties and methods, the parent has to
		 * "adopt" the object. This method takes a given property (by string), checks to see if it is valid then
		 * checks to see if the value is a Base object. If it is, it will set that child's parent to the current
		 * object. 
		 *
		 * If $this->webpage is set, it will automatically give that to the child. 
		 *
		 * If the child has a defined "adoption" method, this function will use it as a callback automatiicaly.
		 *
		 * Finally, this method doesn't just work on a single Base object. If the given property is a one-dimensional 
		 * array, it will traverse it and adopt all of the Base objects inside. (This is not recursive.)
		 * 
		 * @param string The name of the property to be adopted.
		 * @uses Base::$webpage and passes it on to children.
		 * @uses Base::set_webpage
		 * @uses Base::set_parent
		 * @return bool Whether or not an adoption took place.
		 */
		protected function adopt( $property )
		{
			// initialize variables
			$a 			= &$this->$property;
			$is_page 	= $this instanceof WebPage;
			$has_page 	= !$is_page && isset( $this->webpage ) && ( $this->webpage instanceof WebPage );
			$adopted	= false;
			
			// this isn't recursive, but it will shallowly traverse children if it's an array
			if ( !is_array( $a ) && ( $a instanceof Base ) && $a )
			{
				$a->set_parent( $this );
				if ( $is_page )
				{
					$a->set_webpage( $this );
					$adopted = true;
				}
				else if ( $has_page )
				{
					$a->set_webpage( $this->webpage );
					$adopted = true;
				}
				
				// sometimes objects reference paths that could change depending on the webpage object
				if ( $adopted && method_exists( $a, 'adoption' ) ) 
					call_user_func( array( $a, adoption ) );
			}
			else
			{
				foreach( $a as $i => $p )
				{
					$cur_adoption = false;
					if ( $p && ( $p instanceof Base ) )
					{
						$a[ $i ]->set_parent( $this );
						if ( $is_page )
						{
							$a[ $i ]->set_webpage( $this );
							$cur_adoption = $adopted = true;
						}
						else if ( $has_page )
						{
							$a[ $i ]->set_webpage( $this->webpage );
							$cur_adoption = $adopted = true;
						}
						
						// sometimes objects reference paths that could change depending on the webpage object
						if ( $cur_adoption && method_exists( $a[ $i ], 'adoption' ) ) 
							call_user_func( array( $a[ $i ], adoption ) );
					}
				}
			}
			
			return $adopted;
		}
	}
}
?>