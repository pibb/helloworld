<?php
namespace Core;

if ( !defined( "D_CLASS_COLINT" ) )
{
	define( "D_CLASS_COLINT", true );
	require_once( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_int.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class IntColumn extends Column
	{
		/**
		 * Turns the POST'd value into an integer.
		 *
		 * @uses Globals::post
		 * @uses Column::$name
		 * @return mixed
		 */
		public function clean_post()
		{
			return (int)Globals::post( $this->name );
		}
		
		/**
		 * Adds a integer validator with min/max to the error checking method.
		 *
		 * @param mixed the value to be evaluated. (Default = NULL)
		 * @uses Column::$value
		 * @uses Column::$is_required
		 * @uses Column::$min
		 * @uses Column::$max
		 * @uses Column::errors
		 * @uses Validation::is_handle
		 * @return string the error message.
		 */
		public function errors( $value = NULL )
		{
			// initialize variables
			$errors = array();
			if ( is_null( $value ) )
				$value = $this->value;
		
			// check to see if it was required
			if ( $this->is_required && ( $value == "" || is_null( $value ) ) )
				$errors = "Please enter a valid number";
			else
			{
				if ( $this->min && $this->max )
				{
					if ( $value < $this->min || $value > $this->max )
						$errors = "This number must be between {$this->min} and {$this->max}.";
				}
				else if ( $this->min && $value < $this->min )
					$errors = "This number must be at least {$this->min}.";
				else if ( $this->max && $value > $this->max )
					$errors = "This number may not exceed {$this->min}.";
			}
			
			return $errors;
		}
	}
}
?>