<?php
namespace Core;

if ( !defined( "D_CLASS_GROUP" ) )
{
	define( "D_CLASS_GROUP", true );
	require( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.group.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Group extends Data
	{
		protected $name 			= NULL;
		protected $parent 			= NULL;
		protected $may_always_login = NULL;
		
		const TABLE				= Database::GROUPS;
		const PREFIX			= Database::GROUPS_PRE;
		const QUERY				= "1";
		const PUBQUERY			= "AND group_enabled != '0' AND group_deleted = '0' AND group_status = '1'";
		
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
			$rows = Database::select( self::TABLE, "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
			$data = self::getx_data( __CLASS__, $rows, $flag_first, "id", $webpage );
			
			return $data;
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
			$rows = Database::select( self::TABLE, "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
			$data = self::getx_array_data( $rows, $flag_first );
			
			return $data;
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
		 * @uses User::$name
		 * @uses User::$parent
		 * @uses User::$may_always_login
		 * @uses User::set_name
		 * @uses User::set_parent
		 * @uses User::set_may_always_login
		 * @uses MultiColumn::$options
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @uses Data::$not_recorded
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			// extra properties that don't go into the db
			//$this->not_recorded = array_merge( $this->not_recorded, array( '' ) );
			
			// create columns
			$this->name				= new Column( self::TABLE, self::PREFIX, "name" );
			$this->parent			= new Many2OneColumn( self::TABLE, self::PREFIX, "parent", self::TABLE, self::PREFIX, "name" );
			$this->may_always_login	= new MultiColumn( self::TABLE, self::PREFIX, "may_always_login" );
			
			// parent is a Base property that can be overridden. It needs to be unignored, though.
			if ( $key = array_search( 'parent', $this->not_recorded ) )
				unset( $this->not_recorded[ $key ] );
			
			// make adjustments
			$this->name->min = 3;
			$this->may_always_login->options = array( 0 => 'Inherit', 1 => 'Yes', -1 => 'No' );
			
			// add columns
			$this->add_col( 'name', true, true );
			$this->add_col( 'parent', false, array( self::TABLE, self::PREFIX, "name" ) );
			$this->add_col( 'may_always_login', true );
			
			if ( $data )
			{
				$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_parent( $data[ $this->prefix . 'parent' ] );
				$this->set_may_always_login( $data[ $this->prefix . 'may_always_login' ] );
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Group::parent. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Group::$parent
		 * @param int
		 * @return int
		 */
		public function set_parent( $a ) 
		{ 
			return $this->parent->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Group::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Group::$name
		 * @param string
		 * @return string
		 */
		protected function set_name( $a ) 				
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Group::may_always_login. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Group::$may_always_login
		 * @param int
		 * @return int
		 */
		protected function set_may_always_login( $a ) 	
		{ 
			return $this->may_always_login->value = (int)$a; 
		}
	}
}
?>