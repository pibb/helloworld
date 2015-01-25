<?php
namespace Core;

if ( !defined( "D_CLASS_COLMANY" ) )
{
	define( "D_CLASS_COLMANY", true );
	require_once( __DIR__ . "/class.column_int.php" );
	
	/**
 	 * File: class.column_many.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Many2OneColumn extends IntColumn
	{
		public $other_table 	= "";
		public $other_prefix 	= "";
		public $other_handle 	= "";
		
		/**
		 * Class constructor
		 *
		 * @param string The name of the MySQL table.
		 * @param string The prefix for the colum name (usually related to table name).
		 * @param string The name of the column minus prefix.
		 * @param string The name of the other MySQL table.
		 * @param string The prefix for the other colum name (usually related to table name).
		 * @param string The name of the other column minus prefix.
		 * @uses Column::__construct
		 * @uses Many2OneColumn::$other_table
		 * @uses Many2OneColumn::$other_prefix
		 * @uses Many2OneColumn::$other_handle
		 */
		function __construct( $table, $prefix, $name, $other_table, $other_prefix, $other_handle )
		{
			parent::__construct( $table, $prefix, $name );
			
			// get additional properties
			$this->other_table 	= $other_table;
			$this->other_prefix = $other_prefix;
			$this->other_handle = $other_handle;
		}
		
		/**
		 * Changes the error message to the original error handler.
		 *
		 * @param mixed the value to be evaluated. (Default = NULL)
		 * @uses Column::$value
		 * @uses Column::errors
		 * @return string the error message.
		 */
		public function errors( $value = NULL )
		{
			if ( is_null( $value ) )
				$value = $this->value;
			$errors = parent::errors( $value );
		
			return $errors == "Please enter a valid number" ? "Please select an option." : $errors;
		}
	}
}
?>