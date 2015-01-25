<?php
namespace Core;

if ( !defined( "D_CLASS_ROUND" ) )
{
	define( "D_CLASS_ROUND", true );
	require_once( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.round.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Round extends Data
	{
		protected $name 		= NULL;
		protected $competition 	= NULL;
		protected $max 			= NULL;
		protected $order 		= NULL;
		protected $voting 		= NULL;
		protected $regionals 	= NULL;
		
		const TABLE		= Database::ROUNDS;
		const PREFIX	= Database::ROUNDS_PRE;
		const QUERY		= "ORDER BY r.round_id DESC";
		
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
			$rows = Database::select( Database::ROUNDS . " as r", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
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
			$rows = Database::select( Database::ROUNDS . " as r", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
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
		 * @uses Round::$name
		 * @uses Round::$competition
		 * @uses Round::$max
		 * @uses Round::$order
		 * @uses Round::$voting
		 * @uses Round::$regionals
		 * @uses Round::set_name
		 * @uses Round::set_competition
		 * @uses Round::set_max
		 * @uses Round::set_order
		 * @uses Round::set_voting
		 * @uses Round::set_regionals
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			// create columns
			$this->name 		= new Column( self::TABLE, self::PREFIX, "name" );
			$this->competition 	= new Many2OneColumn( self::TABLE, self::PREFIX, "competition", Database::COMPETITIONS, Database::COMPETITIONS_PRE, "name"  );
			$this->max 			= new IntColumn( self::TABLE, self::PREFIX, "max" );
			$this->order 		= new IntColumn( self::TABLE, self::PREFIX, "order" );
			$this->voting 		= new IntColumn( self::TABLE, self::PREFIX, "voting" );
			$this->regionals 	= new IntColumn( self::TABLE, self::PREFIX, "regionals" );
			 
			// add columns
			$this->add_col( 'name', true, true );
			$this->add_col( 'competition', true, array( Database::COMPETITIONS, Database::COMPETITIONS_PRE, "name" ) );
			$this->add_col( 'max', true );
			$this->add_col( 'order' );
			$this->add_col( 'voting' );
			$this->add_col( 'regionals' );
			
			if ( $data )
			{
				$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_competition( $data[ $this->prefix . 'competition' ] );
				$this->set_max( $data[ $this->prefix . 'max' ] );
				$this->set_order( $data[ $this->prefix . 'order' ] );
				$this->set_voting( $data[ $this->prefix . 'voting' ] );
				$this->set_regionals( $data[ $this->prefix . 'regionals' ] );
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Round::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Round::$name
		 * @param string
		 * @return string
		 */
		protected function set_name( $a )			
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Round::competition. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Round::$competition
		 * @param int
		 * @return int
		 */
		protected function set_competition( $a )	
		{ 
			return $this->competition->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Round::max. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Round::$max
		 * @param int
		 * @return int
		 */
		protected function set_max( $a )			
		{ 
			return $this->max->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Round::order. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Round::$order
		 * @param int
		 * @return int
		 */
		protected function set_order( $a )			
		{ 
			return $this->order->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Round::voting. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Round::$voting
		 * @param bool
		 * @return bool
		 */
		protected function set_voting( $a )			
		{ 
			return $this->voting->value = (bool)$a; 
		}
		
		/**
		 * Sets the value of Round::regionals. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Round::$regionals
		 * @param bool
		 * @return bool
		 */
		protected function set_regionals( $a )		
		{ 
			return $this->regionals->value = (bool)$a; 
		}
	}
}
?>