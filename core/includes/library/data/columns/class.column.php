<?php
namespace Core;

if ( !defined( "D_CLASS_COL" ) )
{
	define( "D_CLASS_COL", true );
	require( __DIR__ . '/../../class.base.php' );
	
	/**
 	 * File: class.column.php
	 *
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Column extends Base
	{
		public $name 		= "";
		public $fullname	= "";
		public $label		= "";
		public $table		= "";
		public $handle		= "";
		public $ref			= "";
		public $prefix		= "";
		public $is_required = false;
		public $min			= false;
		public $max			= false;
		public $reveal		= false;
		public $unique		= false;
		public $value		= NULL;
		
		/**
		 * Class constructor
		 *
		 * @uses Column::$table
		 * @uses Column::$ref
		 * @uses Column::$prefix
		 * @uses Column::$fullname
		 * @uses Column::$name
		 * @uses Column::$label
		 * @uses Column::$handle
		 * @param string The name of the MySQL table.
		 * @param string The prefix for the colum name (usually related to table name)
		 * @param string The name of the column minus prefix.
		 * @param string Column reference. Array(table, prefix, column) (Default = "")
		 */
		public function __construct( $table, $prefix, $name, $ref = "" )
		{
			$this->table		= $table;
			$this->ref			= $ref;
			$this->prefix		= $prefix;
			$this->fullname		= $prefix . $name;
			$this->name 		= $name;
			$this->label		= ucwords( $name );
			$this->handle 		= strtoupper( $name );
		}
		
		/**
		 * String conversion; called automatically (i.e., echo or concatenation).
		 * 
		 * @return string $this->value
		 */
		public function __toString()
		{
			return (string)$this->value;
		}
		
		/**
		 * Finds $_POST variable and adds slashes, html special chas, and trims it.
		 * 
		 * @uses Globals::post
		 * @return string cleaned $_POST[ $this->name ]
		 */
		public function clean_post()
		{
			return addslashes( htmlspecialchars( trim( Globals::post( $this->name ) ) ) );
		}
		
		/**
		 * Performs basic error checking (i.e., required fields were completed and proper length)
		 *
		 * Encouraging extending columns to override this function (but call it still), this
		 * function should be called during form processing to check for errors. It checks the given
		 * or property value to make sure it was completed (if it was required). Then it verifies 
		 * numeric boundaries or string length (if specified).
		 * 
		 * @param mixed $this->value will be used if this is not provided. (Default = NULL)
		 * @uses Column::$value if no argument is given.
		 * @uses Column::$is_required
		 * @uses Column::$min
		 * @uses Column::$max
		 * @return string cleaned $_POST[ $this->name ]
		 */
		public function errors( $value = NULL )
		{
			// initialize variables
			$errors = "";
			
			if ( is_null( $value ) )
				$value = $this->value;
				
			// check to see if it was required
			if ( $this->is_required && !$value )
				$errors = "This field is required.";
			else if ( $this->is_required || ( !$this->is_required && $value ) )
			{
				$length = strlen( $value );
				if ( $this->min && $this->max )
				{
					if ( $length < $this->min || $length > $this->max )
						$errors = "Must be between {$this->min} and {$this->max} characters long.";
				}
				else if ( $this->min && $length < $this->min )
					$errors = "Must be at least {$this->min} characters long.";
				else if ( $this->max && $length > $this->max )
					$errors = "May not exceed {$this->min} characters.";
			}
			
			return $errors;
		}
		
		/**
		 * Called when the row is about to be deleted, clear any non database info here...
		 * 
		 *
		 * @return bool if deleted successfully
		 */
		public function delete()
		{
			return true;
		}
		
		/*
		 * Automatically calls Globals:: (Not sure if this is even used)
		 * 
		 * @param mixed $this->value will be used if this is not provided. (Default = NULL)
		 * @uses Column::$is_required
		 * @uses Column::$min
		 * @uses Column::$max
		 * @return string cleaned $_POST[ $this->name ]
		 *
		public function post()
		{
			return post( $this->name );
		}*/
	}
}