<?php
namespace Core;

if ( !defined( "D_CLASS_EMAILCOL" ) )
{
	define( "D_CLASS_EMAILCOL", true );
	require( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_url.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class URLColumn extends Column
	{
		/**
		 * Adds URL validation to the normal error reporting.
		 *
		 * @param mixed the value to be evaluated. (Default = NULL)
		 * @uses Column::$value
		 * @uses Column::errors
		 * @uses Validation::is_url
		 * @return string the error message.
		 */
		public function errors( $value = NULL )
		{
			// initialize variables
			if ( is_null( $value ) )
				$value = $this->value;
			$errors = parent::errors( $value );
		
			if ( !$errors && $value && !Validation::is_url( $value ) )
				$errors = "Invalid URL.";
			
			return $errors;
		}
	}
}
?>