<?php
namespace Core;

if ( !defined( "D_CLASS_COLMULTI" ) )
{
	define( "D_CLASS_COLMULTI", true );
	require_once( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_multi.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class MultiColumn extends Column
	{
		public $options	= array();
		
		/**
		 * Changes the required error message from the original error handler.
		 *
		 * @param mixed the value to be evaluated. (Default = NULL)
		 * @uses Column::$value
		 * @uses Column::errors
		 * @return string the error message.
		 */
		public function errors( $value = NULL )
		{
			// initialize variables
			if ( is_null( $value ) )
				$value = $this->value;
			$errors = parent::errors( $value );
			
			// this error doesn't really apply to this column type
			if ( $errors == "This field is required." )
				$errors = "";
			
			return $errors;
		}
	}
}
?>