<?php
namespace Core;

if ( !defined( "D_CLASS_PHONE" ) )
{
	define( "D_CLASS_PHONE", true );
	require( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.phone.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Phone extends Data
	{
		protected $number 	= "";
		protected $ext 		= "";
		protected $publish	= false;
		
		const TABLE			= Database::PHONE;
		const PREFIX		= Database::PHONE_PRE;
		
		/**
		 * String conversion 
		 *
		 * @uses Phone::$name
		 * @uses Phone::$number
		 * @uses Generic::format_phone
		 * @return string HTML
		 */
		public function __toString()
		{
			return "<span class=\"phone\">" . ( $this->name ? $this->name . ": " : "" ) . Generic::format_phone( $this->number ) . "</span>";
		}
		
		/**
		 * Get a specific field from a given row.
		 *
		 * @param string the name of the column/field.
		 * @param mixed the row identifier.
		 * @param string the column identifier. (Default = "id")
		 * @uses Database::fetch_cell
		 * @return mixed
		 */
		static public function get_field( $col, $id, $identifier = "id" ) 
		{ 
			return Database::fetch_cell( self::TABLE, self::PREFIX . $col, self::PREFIX . $identifier . " = '" . $id . "'" ); 
		}
		//static public function geta( $amend = "", WebPage &$webpage = NULL ) 									{ return parent::getx( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		//static public function geta_many( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 				{ return parent::getx_many( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		//static public function geta_many_published( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 	{ return parent::getx_many_published( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		//static public function geta_published( $amend = "", WebPage &$webpage = NULL ) 							{ return parent::getx_published( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		
		/**
		 * Initialize MySQL table name using class constant.
		 */
		protected function init_table() 	
		{ 
			return self::TABLE; 
		}
		
		/**
		 * Initialize MySQL column prefix using class constant.
		 */
		protected function init_prefix()
		{ 
			return self::PREFIX; 
		}
		
		/**
		 * Initialize class name.
		 */
		protected function init_classname()	
		{ 
			return __CLASS__;
		}
		
		/**
		 * Initializing method that takes given row information and puts them into properties.
		 *
		 * @param mixed if an array is passed, it fills the properties; otherwise, it will attempt to get an array using it as an id.
		 * @uses Phone::set_number
		 * @uses Phone::set_ext
		 * @uses Phone::set_publish
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			if ( $data )
			{
				$this->set_number( $data[ $this->prefix . 'number' ] );
				$this->set_ext( $data[ $this->prefix . 'ext' ] );
				$this->set_publish( $data[ $this->prefix . 'publish' ] );
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Phone::number. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Phone::$number
		 * @param string
		 * @return string
		 */
		protected function set_number( $a )		
		{ 
			return $this->number = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Phone::ext. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Phone::$ext
		 * @param string
		 * @return string
		 */
		protected function set_ext( $a )		
		{ 
			return $this->ext = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Phone::publish. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Phone::$publish
		 * @param bool
		 * @return bool
		 */
		protected function set_publish( $a )	
		{ 
			return $this->publish = (bool)$a; 
		}
	}
}
?>