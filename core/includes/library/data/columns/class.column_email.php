<?php
namespace Core;

if ( !defined( "D_CLASS_COLEMAIL" ) )
{
	define( "D_CLASS_COLEMAIL", true );
	require_once( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_email.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class EmailColumn extends Column
	{
		/**
		 * Adds an e-mail validator to the error checking method.
		 *
		 * @param mixed the value to be evaluated. (Default = NULL)
		 * @uses Column::$value
		 * @uses Column::errors
		 * @uses Validation::is_email
		 * @return string the error message.
		 */
		public function errors( $value = NULL )
		{
			// initialize variables
			if ( is_null( $value ) )
				$value = $this->value;
			$errors = parent::errors( $value );
		
			if ( !$errors && !Validation::is_email( $value ) )
				$errors = "Invalid e-mail address.";
			
			return $errors;
		}
	}
}
?>