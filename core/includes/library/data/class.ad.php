<?php
namespace Core;

if ( !defined( "D_CLASS_AD" ) )
{
	define( "D_CLASS_AD", true );
	require( __DIR__ . "/class.articledata.php" );
	
	/**
 	 * File: class.ad.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 * @todo incomplete getter methods.
	 * @todo increment_clicks and increment_views
	 */
	class Ad extends ArticleData
	{
		protected $href		= "";
		protected $img		= "";
		protected $start	= "";
		protected $length	= "";
		protected $partner	= false;
		protected $underwriter	= "";

		const TABLE			= Database::ADS;
		const PREFIX		= Database::ADS_PRE;
		
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
		 * Get published rows from the database.
		 *
		 * @param string the name of the MySQL table.
		 * @param string the prefix for the column names.
		 * @param string amendment for the MySQL query in sprintf format. (Default = "%s")
		 * @uses Database::select_published
		 * @return Array MySQL result set.
		 */
		static public function get_published_data( $table, $prefix, $amend = "%s" )
		{
			return Database::select_published( $table . " as a, " . Database::ARTICLES . " as b, " . Database::ADS2PAGES . " as x", "*", sprintf( $amend, "a.ad_deleted = '0' AND a.ad_article = b.article_id AND a.ad_id = x.ad_id" ), "a.ad_" );
		}
		
		
		//static public function geta( $amend = "", WebPage &$webpage = NULL ) 									{ return parent::getx( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		//static public function geta_many( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 				{ return parent::getx_many( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		//static public function geta_many_published( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 	{ return parent::getx_many_published( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		//static public function geta_published( $amend = "", WebPage &$webpage = NULL ) 							{ return parent::getx_published( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		//static public function get_published_array( $amend = "%s", WebPage &$webpage = NULL ) { return parent::getx_published_array( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		/*static public function get_page_published( $page_id, $amend = "", WebPage &$webpage = NULL )
		{
			// initialize variables
			$data 	= array();
			/*$amend 	= self::fix_amendment( $amend );
			$rows 	= Database::select_published( Database::ADS . " as a, " . Database::ARTICLES . " as b, " . Database::ADS2PAGES . " as x", "*", "a.ad_deleted = '0' AND a.ad_article = b.article_id AND a.ad_id = x.ad_id AND x.page_id = '" . $page_id . "' " . $amend, "a.ad_" );
			
			foreach( $rows as $row )
				$data[] = new Ad( $row, $webpage );
				
			return $data;
		}*/
		
		/**
		 * Get published rows using arrays.
		 *
		 * @param string the name of the MySQL table.
		 * @param string the prefix for the column names.
		 * @param string amendment for the MySQL query in sprintf format. (Default = "%s")
		 * @uses Database::get_published_data
		 * @return Array keys are column names.
		 */
		static public function get_published_array( $amend = "%s", WebPage &$webpage = NULL, $flag_first = true )
		{
			// initialize variables
			$data = array();
			$rows = self::get_published_data( self::TABLE, self::PREFIX, $amend );

			foreach( $rows as $index => $row )
			{
				// once the prefix is removed, the article image will replace the ad one if we don't get rid of it now
				unset( $data[ $index ][ 'article_img' ], $row[ 'article_img' ] );
				foreach( $row as $key => $value )
					if ( $key != "page_id" )
						$data[ $index ][ preg_replace( "/^[A-Za-z]+_/", "", $key ) ] = htmlspecialchars_decode( stripslashes( trim( $value ) ) );
				$data[ $index ][ 'is_first' ] = $index == 0 && $flag_first;
			}
			
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
		 * @uses Ad::$img
		 * @uses Ad::$href
		 * @uses Ad::$start
		 * @uses Ad::$length
		 * @uses Ad::$partner
		 * @uses Ad::$underwriter
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$table
		 * @uses Data::$prefix
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			// initialize variables
			$data 	= parent::setup( $data );
			$local 	= Website::IMAGES_DIR . "ads" . DIRECTORY_SEPARATOR;
			$url 	= Website::IMAGES_DIR . "ads/";
			
			$ad_dir = "";
			$ad_url_dir = "";
			if ( $this->webpage ) $ad_dir = $this->webpage->site->local . $local;
			if ( $this->webpage ) $ad_url_dir = $this->webpage->site->url . $url;
			$this->def_col( "img", $data, "ImageColumn", false, array( $ad_dir, $ad_url_dir, ImageColumn::IMAGE_TYPES  )  );
			$this->def_col( "href", $data );
			$this->def_col( "start", $data );
			$this->def_col( "length", $data );
			$this->def_col( "partner", $data );
			$this->def_col( "underwriter", $data );

			return $data;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Convert To String
		#----------------------------------------------------------------------------------------------------
		/*public function parse( Webpage $webpage, $increment = true )
		{
			$html = "";
			
			//if ( $increment )
			//	$this->increment_views( $webpage );
			
			if ( $this->content->value )
				$html .= "<div><h2>" . $this->name->value  . "</h2>" . $this->content->value . "</div>";
			else
			{
				$href = strpos( $this->href->value, "mailto:" ) != -1 ? $this->href->value : $webpage->anchor( MAIN_PORTAL, array( 'id' => $this->id ) );
				$html .= "<li><a href=\"" . $href . "\" target=\"_blank\" tabindex=\"" . $webpage->tab_nav++ . "\"><img src=\"" . $webpage->site->url . Website::IMAGES_DIR . "ads/" . $this->img->value . "\" alt=\"" . $this->name->value . "\" /></a></li>\n";
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Increment the views by 1
		# ~ Written by Travis; Updated by Michael for times
		#----------------------------------------------------------------------------------------------------
		/*public function increment_views( Webpage $webpage ) 
		{
			$sql = "INSERT INTO " . Database::ADS2VIEWS . " (view_ad, view_time) VALUES ('" . $this->id . "','" . time() . "')";
			$webpage->db->query( $sql );
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Increment the cicks by 1
		# ~ Written by Travis; Updated by Michael for times
		#----------------------------------------------------------------------------------------------------
		/*public function increment_clicks( Webpage $webpage ) 
		{
			$sql = "INSERT INTO " . Database::ADS2CLICKS . " (click_ad, click_time) VALUES ('" . $this->id . "','" . time() . "')";
			$webpage->db->query( $sql );
		}*/
		
		/**
		 * Sets the value of Ad::href. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Ad::$href
		 * @param string
		 * @return string
		 */
		/*protected function set_href( $a )		
		{ 
			return $this->href->value = trim( stripslashes( $a ) ); 
		}*/
		
		/**
		 * Sets the value of Ad::img. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Ad::$img
		 * @param string
		 * @return string
		 */
		/*protected function set_img( $a )		
		{ 
			return $this->img->value = trim( stripslashes( $a ) ); 
		}*/
		
		/**
		 * Sets the value of Ad::start. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Ad::$start
		 * @param string
		 * @return string
		 */
		/*protected function set_start( $a )		
		{ 
			return $this->start->value = trim( stripslashes( $a ) ); 
		}*/
		
		/**
		 * Sets the value of Ad::length. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Ad::$length
		 * @param string
		 * @return string
		 */
		/*protected function set_length( $a )		
		{ 
			return $this->length->value = trim( stripslashes( $a ) ); 
		}*/
		
		/**
		 * Sets the value of Ad::partner. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Ad::$partner
		 * @param bool
		 * @return bool
		 */
		/*protected function set_partner( $a )	
		{ 
			return $this->partner->value = (bool)$a; 
		}*/
		
		/**
		 * Sets the value of Ad::underwriter. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Ad::$underwriter
		 * @param int
		 * @return int
		 */
		/*protected function set_underwriter( $a )
		{ 
			return $this->underwriter->value = (int)$a; 
		}*/
	}
}
?>