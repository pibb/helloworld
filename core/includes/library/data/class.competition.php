<?php
namespace Core;

if ( !defined( "D_CLASS_COMPETITION" ) )
{
	define( "D_CLASS_COMPETITION", true );
	require( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.competition.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Competition extends Data
	{
		protected $name 		= NULL;
		protected $href			= "";
		
		const TABLE				= Database::COMPETITIONS;
		const PREFIX			= Database::COMPETITIONS_PRE;
		const QUERY				= "ORDER BY c.competition_id DESC";
		
		/**
		 * Get a specific field from a given row.
		 *
		 * @param string the name of the column/field.
		 * @param mixed the row identifier.
		 * @param string the column identifier. (Default = "id")
		 * @uses Database::fetch_cell
		 * @return mixed
		 */
		static public function get_field( $col, $id ) 
		{ 
			return Database::fetch_cell( self::TABLE, self::PREFIX . $col, self::PREFIX . "id = '" . (int)$id . "'" ); 
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
			$rows = Database::select( Database::COMPETITIONS . " as c", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
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
			$rows = Database::select( Database::COMPETITIONS . " as c", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
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
		 * @uses Competition::$name
		 * @uses Competition::$href
		 * @uses Competition::set_name
		 * @uses Base::$webpage
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @uses Data::$not_recorded
		 * @uses WebPage::anchor
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			// extra properties
			$this->not_recorded = array_merge( $this->not_recorded, array( 'href' ) );
		
			// create columns
			$this->name 	= new Column( self::TABLE, self::PREFIX, "name" );
			
			// add columns
			$this->add_col( 'name', true, true );
			
			if ( $data )
			{
				$this->set_name( $data[ 'competition_name' ] );
				
				if ( $this->webpage )
					$this->href = $this->webpage->anchor( RISINGSTAR_WATCH, array( 'id' => (int)$this->name->value ) );
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Competition::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Competition::$name
		 * @param string
		 * @return string
		 */
		protected function set_name( $a )			
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}
	}
}
?>