<?php
namespace Core;

if ( !defined( "D_CLASS_COLPHONEEXT" ) )
{
	define( "D_CLASS_COLPHONEEXT", true );
	require_once( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_phone.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class PhoneColumn extends Column
	{		
		/**
		 * Strips POST'd value of non-numbers.
		 *
		 * @uses Globals::post
		 * @uses Column::$name
		 * @return mixed
		 */
		public function clean_post()
		{
			return preg_replace( "/[^0-9]/", "", Globals::post( $this->name ) );
		}
		
		/**
		 * Adds number lengths to the normal error reporting.
		 *
		 * @param mixed the value to be evaluated. (Default = NULL)
		 * @uses Column::$is_required
		 * @uses Column::$value
		 * @uses Column::errors
		 * @return string the error message.
		 */
		public function errors( $value = NULL )
		{
			// initialize variables
			$errors = array();
			if ( is_null( $value ) )
				$value = $this->value;
			$len 	= strlen( $value );
		
			// check to see if it was required
			if ( $this->is_required && ( $value == "" || is_null( $value ) ) )
				$errors = "Please enter a valid phone number";
			if ( $len != 10 && $len != 0 )
				$errors = "Please enter a ten-digit phone number, including the area code.";
			
			return $errors;
		}
	}
}
	
if ( !defined( "D_CLASS_COLPHONE" ) )
{
	define( "D_CLASS_COLPHONE", true );
	require_once( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_phone.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class PhoneExtColumn extends Column
	{
		/**
		 * Turns POST'd value into an integer.
		 *
		 * @uses Globals::post
		 * @uses Column::$name
		 * @return mixed
		 */
		public function clean_post()
		{
			return $_POST[ 'ext' ] ? (int)Globals::post( 'ext' ) : "";
		}
	}
}