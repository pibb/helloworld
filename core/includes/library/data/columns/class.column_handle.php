<?php
namespace Core;

if ( !defined( "D_CLASS_HANDLECOL" ) )
{
	define( "D_CLASS_HANDLECOL", true );
	require( __DIR__ . "/class.column.php" );

	/**
 	 * File: class.column_handle.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class HandleColumn extends Column
	{
		/**
		 * Adds a handle validator to the error checking method.
		 *
		 * @param mixed the value to be evaluated. (Default = NULL)
		 * @uses Column::$value
		 * @uses Column::errors
		 * @uses Validation::is_handle
		 * @return string the error message.
		 */
		public function errors( $value = NULL )
		{
			// initialize variables
			if ( is_null( $value ) )
				$value = $this->value;
			$errors = parent::errors( $value );
		
			if ( !$errors && $value && !Validation::is_handle( $value ) )
				$errors = "Handles must begin with @ and have 1-15 characters.";
			
			return $errors;
		}
	}
}
?>