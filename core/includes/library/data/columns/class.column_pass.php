<?php
namespace Core;

if ( !defined( "D_CLASS_COLPASS" ) )
{
	define( "D_CLASS_COLPASS", true );
	require_once( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_pass.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class PassColumn extends Column
	{
		public $match 		= true;
		public $salt 		= false;
		public $salt_token	= 'salt';
		
		/**
		 * Class constructor
		 *
		 * @param string The name of the MySQL table.
		 * @param string The prefix for the colum name (usually related to table name).
		 * @param string The name of the column minus prefix.
		 * @param bool Are we confirming this field with another one? (Default = true)
		 * @param bool Are we adding a salt character to the password? (Default = false)
		 * @param string the salt token. (Default = 'salt')
		 * @uses Column::__construct
		 * @uses PassColumn::$match
		 * @uses PassColumn::$salt
		 * @uses PassColumn::$salt_token
		 */
		public function __construct( $table, $prefix, $name, $match = true, $salt = false, $token = 'salt' )
		{
			parent::__construct( $table, $prefix, $name );
			
			// make adjustments
			$this->match 		= $match;
			$this->salt 		= $salt;
			$this->salt_token	= $token;
		}
		
		/**
		 * Turns the POST'd value into an integer.
		 *
		 * @uses Globals::post
		 * @uses Column::$name
		 * @uses PassColumn::$match
		 * @return mixed
		 */
		public function clean_post()
		{
			return trim( Globals::post( $this->name . ( $this->match ? "1" : "" ) ) );
		}
		
		/**
		 * MD5's the given string.
		 *
		 * @param string
		 * @return string
		 */
		public function encrypt( $a )
		{
			return md5( $a );
		}
		
		/**
		 * Changes the required error message from the original error handler.
		 *
		 * @param mixed the value to be evaluated. (Default = NULL)
		 * @param mixed value to compare to. (Default = false)
		 * @uses Column::$value
		 * @uses Column::errors
		 * @uses Validation::is_alphanumeric
		 * @return string the error message.
		 */
		public function errors( $value = NULL, $compare = false )
		{
			if ( is_null( $value ) )
				$value = $this->value;
			$errors = parent::errors( $value );
			
			if ( !$errors && $value )
			{
				if ( !Validation::is_alphanumeric( $value ) )
					$errors = "May only contain letters and numbers.";
				else if ( $compare !== false && $value != $compare )
					$errors = "Passwords do not match.";
			}
			
			return $errors;
		}
	}
}
?>