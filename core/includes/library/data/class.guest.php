<?php
namespace Core;

if ( !defined( "D_CLASS_GUEST" ) )
{
	define( "D_CLASS_GUEST", true );
	require( __DIR__ . "/class.data.php" );

	/**
 	 * File: class.guest.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Guest extends Data
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		protected $name 	= NULL;
		protected $bio 		= NULL;
		protected $title 	= NULL;
		protected $company 	= NULL;
		//protected $email	= array();
		//protected $phone	= array();
		//protected $websites	= array();
		
		const TABLE			= Database::GUESTS;
		const PREFIX		= Database::GUESTS_PRE;
		const QUERY			= "AND x.guest_id = g.guest_id ORDER BY g.guest_id DESC";
		#----------------------------------------------------------------------------------------------------
		# * Convert To String
		#----------------------------------------------------------------------------------------------------
		/*public function __toString()
		{
			// initialize variables
			$html 	= "<div class=\"guest\">\n\t<span class=\"guest-name\">" . $this->name . "</span><br />\n" 
					. ( $this->title->value ? "<span class=\"guest-title\">" . $this->title : "</span>\n" )
					. ( $this->company->value ? ", <span class=\"guest-company\">" . $this->company : "</span>\n" )
					. ( $this->bio->value ? "<p class=\"guest-bio\"><strong>Biography:</strong> " . $this->bio . "</p>\n" : "" );
			$props 	= array( 'phone', 'email', 'websites' );
			
			// add contact information
			foreach( $props as $prop )
			{
				if ( $this->$prop )
				{
					$html .= "\t<ul class=\"" . $prop . "\">\n";
					foreach( $this->$prop as $p )
						if ( $p->publish )
							$html .= "\t\t<li>" . $p . "</li>\n";
					$html .= "\t</ul>\n";
				}
			}
				
			// list company information
			//if ( $this->company )
			//	$html .= $this->company;
			
			return $html . "</div>\n";
		}*/
		
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
		
		//static public function geta( $amend = "", WebPage &$webpage = NULL ) 									{ return parent::getx( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		//static public function geta_many( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 				{ return parent::getx_many( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		//static public function geta_many_published( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 	{ return parent::getx_many_published( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		//static public function geta_published( $amend = "", WebPage &$webpage = NULL ) 							{ return parent::getx_published( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		
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
			$rows = Database::select( Database::GUESTS . " as g, " . Database::G2SEGMENTS . " as x", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
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
			$rows = Database::select( Database::GUESTS . " as g, " . Database::G2SEGMENTS . " as x", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
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
		 * @uses Guest::$name
		 * @uses Guest::set_name
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			$this->def_col( 'name', $data, "Column", true, true );
			$this->def_col( 'title', $data );
			$this->def_col( 'bio', $data );
			$this->def_col( 'company', $data, "Many2OneColumn", false, array( Database::COMPANIES, Database::COMPANIES_PRE, "name" ) );
			
			// create columns
			/////$this->name 	= new Column( self::TABLE, self::PREFIX, "name" );
			//$this->title 	= new Column( self::TABLE, self::PREFIX, "title" ); 
			//$this->bio 		= new Column( self::TABLE, self::PREFIX, "bio" ); 
			//$this->company	= new Many2OneColumn( self::TABLE, self::PREFIX, "company", Database::COMPANIES, Database::COMPANIES_PRE, "name" );
			
			// add columns
			/////$this->add_col( 'name', true, true );
			//$this->add_col( 'title' );
			//$this->add_col( 'bio' );
			//$this->add_col( 'company', false, array( Database::COMPANIES, Database::COMPANIES_PRE, "name" ) );
			
			// make adjustments
			//$this->name->min = 3;
			//$this->name->max = 255;
			
			if ( $data )
			{
				$this->set_name( $data[ $this->prefix . 'name' ] );
				//$this->set_bio( $data[ $this->prefix . 'bio' ] );
				//$this->set_company( $data[ $this->prefix . 'company' ] );
				//$this->set_title( $data[ $this->prefix . 'title' ] );
				
				// get info from the database
				//$this->email = Email::geta_many_published( Database::G2EMAIL, Database::EMAIL_PRE, $this->prefix . $this->identifier . " = '" . $this->id . "'", $this->webpage );
				//$this->phone = Phone::geta_many_published( Database::G2PHONE, Database::PHONE_PRE, $this->prefix . $this->identifier . " = '" . $this->id . "'", $this->webpage );
				//$this->websites = Anchor::geta_many_published( Database::G2SITES, Database::SITES_PRE, $this->prefix . $this->identifier . " = '" . $this->id . "'", $this->webpage );

				// add this object to children for reference
				//$this->adopt( 'email' );
				//$this->adopt( 'phone' );
				//$this->adopt( 'websites' );
			}
			
			
			return $data;
		}
		
		/**
		 * Sets the value of Guest::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Guest::$name
		 * @param string
		 * @return string
		 */
		/*protected function set_name( $a ) 		
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}*/
		
		//protected function set_bio( $a ) 		{ return $this->bio->value = trim( stripslashes( $a ) ); }
		//protected function set_title( $a ) 		{ return $this->title->value = trim( stripslashes( $a ) ); }
		//protected function set_company( $a ) 	{ return $this->company->value = new Company( (int)$a ); }
	}
}
?>