<?php
namespace Core;
if ( !defined( "D_CLASS_GLOBALS" ) )
{
	define( "D_CLASS_GLOBALS", true );
	
	/**
	 * File: class.globals.php
	 *
	 * @package Library/Static
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	abstract class Globals
	{
		/**
		 * Returns a safer value from the index of $_COOKIE.
		 *
		 * @param string the variable to be interpretted
		 * @param string a default value to be assigned if all else fails. (Default = NULL)
		 * @return mixed
		 */
		static public function cookie( $var, $default = NULL )
		{
			return isset( $_COOKIE[ $var ] ) ? stripslashes( trim( $_COOKIE[ $var ] ) ) : $default;
		}
						
		/**
		 * Returns a safer value from the index of $_GET.
		 *
		 * @param string the variable to be interpretted
		 * @param string a default value to be assigned if all else fails. (Default = NULL)
		 * @uses Generic::stripslashes_from_array
		 * @return mixed
		 */
		static public function get( $var, $default = NULL )
		{
			return isset( $_GET[ $var ] ) 
				? ( is_array( $_GET[ $var ] ) ? Generic::stripslashes_from_array( $_GET[ $var ] ) : stripslashes( trim( $_GET[ $var ] ) ) ) 
				: $default;
		}
									
		/**
		 * Returns a safer value from the index of $_POST.
		 *
		 * @param string the variable to be interpretted
		 * @param string a default value to be assigned if all else fails. (Default = NULL)
		 * @uses Generic::stripslashes_from_array
		 * @return mixed
		 */
		static public function post( $var, $default = NULL )
		{
			return isset( $_POST[ $var ] ) 
				? ( is_array( $_POST[ $var ] ) ? Generic::stripslashes_from_array( $_POST[ $var ] ) : stripslashes( trim( $_POST[ $var ] ) ) ) 
				: $default;
		}
						
		/**
		 * Returns a safer value from the index of $_REQUEST.
		 *
		 * @param string the variable to be interpretted
		 * @param string a default value to be assigned if all else fails. (Default = NULL)
		 * @uses Generic::stripslashes_from_array
		 * @return mixed
		 */
		static public function request( $var, $default = NULL )
		{
			return isset( $_REQUEST[ $var ] ) 
				? ( is_array( $_REQUEST[ $var ] ) ? Generic::stripslashes_from_array( $_REQUEST[ $var ] ) : stripslashes( trim( $_REQUEST[ $var ] ) ) ) 
				: $default;
		}
						
		/**
		 * Returns a safer value from the index of $_SESSION.
		 *
		 * @param string the variable to be interpretted
		 * @param string a default value to be assigned if all else fails. (Default = NULL)
		 * @return mixed
		 */
		static public function session( $var, $default = NULL )
		{
			return isset( $_SESSION[ $var ] ) ? stripslashes( trim( $_SESSION[ $var ] ) ) : $default;
		}
	}
}
?>