<?php
namespace Core;

if ( !defined( "D_CLASS_BANNER" ) )
{
	define( "D_CLASS_BANNER", true );
	require( __DIR__ . "/class.data.php" );

	/**
 	 * File: class.banner.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Banner extends Data
	{
		protected $name 	= "";	
		protected $img 		= "";
		protected $bgimg 	= "";
		protected $href 	= "";
		protected $external = "";
		protected $end	 	= "";
		protected $length 	= 0;
		
		const TABLE			= Database::BANNERS;
		const PREFIX		= Database::BANNERS_PRE;
		
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
		 * @uses Banner::$name
		 * @uses Banner::$img
		 * @uses Banner::$href
		 * @uses Banner::$length
		 * @uses Banner::$end
		 * @uses Banner::$external
		 * @uses Banner::set_name
		 * @uses Banner::set_img
		 * @uses Banner::set_href
		 * @uses Banner::set_length
		 * @uses Banner::set_end
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			// initialize variables
			$data 	= parent::setup( $data );
			$local 	= Website::IMAGES_DIR . "banners" . DIRECTORY_SEPARATOR;
			$url 	= Website::IMAGES_DIR . "banners/";
			
			// create columns
			$this->name 	= new Column( self::TABLE, self::PREFIX, "name" );
			$this->img 		= new ImageColumn( self::TABLE, self::PREFIX, "img", $local, $url, ImageColumn::IMAGE_TYPES );
			$this->bgimg 	= new Column( self::TABLE, self::PREFIX, "bgimg" );
			$this->href 	= new Column( self::TABLE, self::PREFIX, "href" );
			$this->length 	= new IntColumn( self::TABLE, self::PREFIX, "length" );
			$this->end 		= new DateColumn( self::TABLE, self::PREFIX, "end" );
			
			// make adjustments
			$this->name->min = 3;
			$this->name->max = 255;
			$this->img->dimensions = "980x290";
			
			// add columns
			$this->add_col( 'name', true, true );
			$this->add_col( 'img', true );
			$this->add_col( 'bgimg' );
			$this->add_col( 'href' );
			$this->add_col( 'length' );
			$this->add_col( 'end', false, true );
			
			if ( $data )
			{
				$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_img( $data[ $this->prefix . 'img' ] );
				$this->set_bgimg( $data[ $this->prefix . 'bgimg' ] );
				$this->set_href( $data[ $this->prefix . 'href' ] );
				$this->set_external( $data[ $this->prefix . 'external' ] );
				$this->set_end( $data[ $this->prefix . 'end' ] );
				$this->set_length( $data[ $this->prefix . 'length' ] );
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Banner::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Banner::$name
		 * @param string
		 * @return string
		 */
		protected function set_name( $a )		
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Banner::img. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Banner::$img
		 * @param string
		 * @return string
		 */
		protected function set_img( $a )		
		{ 
			return $this->img->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Banner::bgimg. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Banner::$bgimg
		 * @param string
		 * @return string
		 */
		protected function set_bgimg( $a )		
		{ 
			return $this->bgimg->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Banner::href. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Banner::$href
		 * @param string
		 * @return string
		 */
		protected function set_href( $a )		
		{ 
			return $this->href->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Banner::external. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Banner::$external
		 * @param string
		 * @return string
		 */
		protected function set_external( $a )	
		{ 
			return $this->external->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Banner::end. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Banner::$end
		 * @param string
		 * @return string
		 */
		protected function set_end( $a )		
		{ 
			return $this->end->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Banner::length. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Banner::$length
		 * @param int
		 * @return int
		 */
		protected function set_length( $a )		
		{ 
			return $this->length->value = (int)$a; 
		}
	}
}
?>