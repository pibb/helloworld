<?php
namespace Core;

if ( !defined( "D_CLASS_JUDGE" ) )
{
	define( "D_CLASS_JUDGE", true );
	require_once( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.judge.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Judge extends Data
	{
		protected $name 	= NULL;
		protected $bio 		= NULL;
		protected $reveal 	= NULL;
		
		const TABLE		= Database::JUDGES;
		const PREFIX	= Database::JUDGES_PRE;
		const QUERY		= "ORDER BY j.judge_id DESC";
		
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
		
		/**
		 * Get published rows.
		 *
		 * @param string amendment for the MySQL query in sprintf format. (Default = "%s")
		 * @param WebPage the current webpage if it's being passed on. (Default = NULL)
		 * @param bool whether or not to flag the first result. (Default = true)
		 * @uses Data::getx_published
		 * @return Array of Ad objects.
		 */
		static public function get_published( $amend = "%s", WebPage &$webpage = NULL, $flag_first = true ) 
		{ 
			return parent::getx_published( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage, $flag_first ); 
		}
		
		/**
		 * Get published rows using arrays.
		 *
		 * @param string the name of the MySQL table.
		 * @param string the prefix for the column names.
		 * @param string amendment for the MySQL query in sprintf format. (Default = "%s")
		 * @uses Database::get_published_data
		 * @uses Article::append_data_array
		 * @return Array keys are column names.
		 */
		static public function get_published_array( $amend = "%s", WebPage &$webpage = NULL, $flag_first = true ) 
		{ 
			return parent::getx_published_array( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage, $flag_first ); 
		}
		
		/**
		 * Get rows.
		 *
		 * @param string amendment for the MySQL query in sprintf format. (Default = "%s")
		 * @param WebPage the current webpage if it's being passed on. (Default = NULL)
		 * @param bool whether or not to flag the first result. (Default = true)
		 * @uses Data::getx_data
		 * @return Array of Ad objects.
		 */
		static public function get( $amend = "%s", WebPage $webpage = NULL, $flag_first = true )
		{
			// initialize variables
			$rows = Database::select( Database::JUDGES . " as j", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
			return self::getx_data( __CLASS__, $rows, $flag_first, "id", $webpage );
		}
		
		/**
		 * Get rows using arrays.
		 *
		 * @param string the name of the MySQL table.
		 * @param string the prefix for the column names.
		 * @param string amendment for the MySQL query in sprintf format. (Default = "%s")
		 * @uses Data::getx_array_data
		 * @uses Database::select
		 * @uses Article::append_data_array
		 * @return Array keys are column names.
		 */
		static public function get_array( $amend = "%s", WebPage $webpage = NULL, $flag_first = true )
		{
			// initialize variables
			$rows = Database::select( Database::JUDGES . " as j", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
			return self::getx_array_data( $rows, $flag_first );
		}
		
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
		 * @uses Judge::$name
		 * @uses Judge::$bio
		 * @uses Judge::$reveal
		 * @uses Judge::set_name
		 * @uses Judge::set_bio
		 * @uses Judge::set_reveal
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			// create columns
			$this->name = new Column( self::TABLE, self::PREFIX, "name" );
			$this->bio = new Column( self::TABLE, self::PREFIX, "bio" );
			$this->reveal = new MultiColumn( self::TABLE, self::PREFIX, "reveal" );
			 
			// make adjustments
			$this->reveal->options = array( 0 => "No", 1 => "Yes" );
			
			// add columns
			$this->add_col( 'name', true, true );
			$this->add_col( 'bio' );
			$this->add_col( 'reveal' );
			
			if ( $data )
			{
				$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_bio( $data[ $this->prefix . 'bio' ] );
				$this->set_reveal( $data[ $this->prefix . 'reveal' ] );
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Judge::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Judge::$name
		 * @param string
		 * @return string
		 */
		protected function set_name( $a )	
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Judge::bio. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Judge::$bio
		 * @param string
		 * @return string
		 */
		protected function set_bio( $a )	
		{ 
			return $this->bio->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Judge::reveal. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Judge::$reveal
		 * @param bool
		 * @return bool
		 */
		protected function set_reveal( $a )	
		{ 
			return $this->reveal->value = (bool)$a; 
		}
	}
}
?>