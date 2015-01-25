<?php
namespace Core;

if ( !defined( "D_CLASS_NATPROGRAM" ) )
{
	define( "D_CLASS_NATPROGRAM", true );
	require( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.natprogram.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class NatProgram extends Data
	{
		protected $pid	 			= "";
		protected $name 			= "";
		protected $slug 			= "";
		protected $short	 		= "";
		protected $description 		= "";
		protected $nola	 			= "";
		protected $resource_uri 	= "";
		protected $underwriting 	= "";
		protected $shop		 		= "";
		protected $itunes		 	= "";
		protected $cast		 		= "";
		protected $kids		 		= "";
		protected $img_mezzanine 	= "";
		protected $img_logo 		= "";
		protected $img_iphone_small	= "";
		protected $img_iphone_med	= "";
		protected $img_ipad_small	= "";
		protected $website 			= "";
		protected $scheduled		= false;
		protected $episodes			= "";
		protected $previews			= "";
		protected $covecheck		= 0;
		protected $href 			= "";
		
		const TABLE			= Database::NATPROGRAMS;
		const PREFIX		= Database::NATPROGRAMS_PRE;
		
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
		 * @uses NatProgram::$pid
		 * @uses NatProgram::$name
		 * @uses NatProgram::$short
		 * @uses NatProgram::$description
		 * @uses NatProgram::$nola
		 * @uses NatProgram::$resource_uri
		 * @uses NatProgram::$underwriting
		 * @uses NatProgram::$shop
		 * @uses NatProgram::$itunes
		 * @uses NatProgram::$cast
		 * @uses NatProgram::$kids
		 * @uses NatProgram::$website
		 * @uses NatProgram::$img_mezzanine
		 * @uses NatProgram::$img_logo
		 * @uses NatProgram::$img_iphone_small
		 * @uses NatProgram::$img_iphone_med
		 * @uses NatProgram::$img_ipad_small
		 * @uses NatProgram::$scheduled
		 * @uses NatProgram::$episodes
		 * @uses NatProgram::$previews
		 * @uses NatProgram::$covecheck
		 * @uses NatProgram::$slug
		 * @uses NatProgram::$href
		 * @uses NatProgram::set_pid
		 * @uses NatProgram::set_name
		 * @uses NatProgram::set_short
		 * @uses NatProgram::set_description
		 * @uses NatProgram::set_nola
		 * @uses NatProgram::set_resource_uri
		 * @uses NatProgram::set_underwriting
		 * @uses NatProgram::set_shop
		 * @uses NatProgram::set_itunes
		 * @uses NatProgram::set_cast
		 * @uses NatProgram::set_kids
		 * @uses NatProgram::set_website
		 * @uses NatProgram::set_img_mezzanine
		 * @uses NatProgram::set_img_logo
		 * @uses NatProgram::set_img_iphone_small
		 * @uses NatProgram::set_img_iphone_med
		 * @uses NatProgram::set_img_ipad_small
		 * @uses NatProgram::set_scheduled
		 * @uses NatProgram::set_episodes
		 * @uses NatProgram::set_previews
		 * @uses NatProgram::set_covecheck
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$not_recorded
		 * @uses Data::$prefix
		 * @uses Base::$webpage
		 * @uses WebPage::anchor
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			// extra properties
			$this->not_recorded = array_merge( $this->not_recorded, array( 'href' ) );
			
			// create columns
			$this->pid 				= new IntColumn( self::TABLE, self::PREFIX, "pid" );
			$this->name 			= new Column( self::TABLE, self::PREFIX, "name" );
			$this->short	 		= new Column( self::TABLE, self::PREFIX, "short" );
			$this->description 		= new Column( self::TABLE, self::PREFIX, "description" );
			$this->nola 			= new Column( self::TABLE, self::PREFIX, "nola" );
			$this->resource_uri 	= new URLColumn( self::TABLE, self::PREFIX, "resource_uri" );
			$this->underwriting 	= new URLColumn( self::TABLE, self::PREFIX, "underwriting" );
			$this->shop 			= new URLColumn( self::TABLE, self::PREFIX, "shop" );
			$this->itunes 			= new URLColumn( self::TABLE, self::PREFIX, "itunes" );
			$this->cast 			= new Column( self::TABLE, self::PREFIX, "cast" );
			$this->kids 			= new IntColumn( self::TABLE, self::PREFIX, "kids" );
			$this->website 			= new URLColumn( self::TABLE, self::PREFIX, "website" );
			$this->img_mezzanine 	= new URLColumn( self::TABLE, self::PREFIX, "img_mezzanine" );
			$this->img_logo 		= new URLColumn( self::TABLE, self::PREFIX, "img_logo" );
			$this->img_iphone_small = new URLColumn( self::TABLE, self::PREFIX, "img_iphone_small" );
			$this->img_iphone_med 	= new URLColumn( self::TABLE, self::PREFIX, "img_iphone_med" );
			$this->img_ipad_small 	= new URLColumn( self::TABLE, self::PREFIX, "img_ipad_small" );
			$this->scheduled 		= new Column( self::TABLE, self::PREFIX, "scheduled" );
			$this->episodes 		= new Column( self::TABLE, self::PREFIX, "episodes" );
			$this->previews 		= new Column( self::TABLE, self::PREFIX, "previews" );
			$this->covecheck 		= new IntColumn( self::TABLE, self::PREFIX, "covecheck" );
			
			// add columns
			$this->add_col( 'pid', true, true );
			$this->add_col( 'name', true, true );
			$this->add_col( 'short' );
			$this->add_col( 'description' );
			$this->add_col( 'nola' );
			$this->add_col( 'resource_uri' );
			$this->add_col( 'shop' );
			$this->add_col( 'itunes' );
			$this->add_col( 'cast' );
			$this->add_col( 'kids' );
			$this->add_col( 'website' );
			$this->add_col( 'img_mezzanine' );
			$this->add_col( 'img_logo' );
			$this->add_col( 'img_iphone_small' );
			$this->add_col( 'img_iphone_med' );
			$this->add_col( 'img_ipad_small' );
			$this->add_col( 'scheduled' );
			$this->add_col( 'episodes' );
			$this->add_col( 'previews' );
			$this->add_col( 'covecheck' );
			
			if ( $data )
			{
				$this->set_pid( $data[ $this->prefix . 'pid' ] );
				$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_slug( $data[ $this->prefix . 'slug' ] );
				$this->set_description( $data[ $this->prefix . 'description' ] );
				$this->set_short( $data[ $this->prefix . 'short' ] );
				$this->set_nola( $data[ $this->prefix . 'nola' ] );
				$this->set_resource_uri( $data[ $this->prefix . 'resource_uri' ] );
				$this->set_shop( $data[ $this->prefix . 'shop' ] );
				$this->set_itunes( $data[ $this->prefix . 'itunes' ] );
				$this->set_cast( $data[ $this->prefix . 'cast' ] );
				$this->set_kids( $data[ $this->prefix . 'kids' ] );
				$this->set_website( $data[ $this->prefix . 'website' ] );
				$this->set_img_mezzanine( $data[ $this->prefix . 'img_mezzanine' ] );
				$this->set_img_logo( $data[ $this->prefix . 'img_logo' ] );
				$this->set_img_iphone_small( $data[ $this->prefix . 'img_iphone_small' ] );
				$this->set_img_iphone_med( $data[ $this->prefix . 'img_iphone_med' ] );
				$this->set_img_ipad_small( $data[ $this->prefix . 'img_ipad_small' ] );
				$this->set_covecheck( $data[ $this->prefix . 'covecheck' ] );
				$this->set_covecheck( $data[ $this->prefix . 'scheduled' ] );
				
				if ( $this->webpage )
					$this->href = $this->webpage->anchor( PROGRAMS_INDEX ) . $this->slug;
			}
			
			return $data;
		}
		
		/**
		 * Look up videos. This is very resource intensive and should be called on an as-needed basis.
		 *
		 * @param int the natprogram_id. If false, uses this object and sets properties. (Default = false)
		 * @uses Database::fetch
		 * @uses Data::$id
		 * @uses NatProgram::set_episodes
		 * @uses NatProgram::set_previews
		 * @return Array array( $episodes, $previews )
		 */
		public function lookup_videos( $id = false )
		{
			$data = Database::fetch( self::TABLE, self::PREFIX . "episodes, " . self::PREFIX . "previews", "natprogram_id = '" . ( $id === false ? (int)$this->id : (int)$id ) . "'" );
			if ( $id === false )
			{
				$this->set_episodes( $data[ $this->prefix . 'episodes' ] );
				$this->set_previews( $data[ $this->prefix . 'previews' ] );
			}
			return array( $data[ $this->prefix . 'episodes' ], $data[ $this->prefix . 'previews' ] );
		}
		
		/**
		 * Sets the value of NatProgram::pid. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$pid
		 * @param int
		 * @return int
		 */
		protected function set_pid( $a )				
		{ 
			return $this->pid->value = (int)$a; 
		}
		
		/**
		 * Sets the value of NatProgram::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$name
		 * @param string
		 * @return string
		 */
		protected function set_name( $a )				
		{ 
			return $this->name->value = trim( stripslashes( html_entity_decode( htmlspecialchars_decode( $a ) ) ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::slug. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$slug
		 * @param string
		 * @return string
		 */
		protected function set_slug( $a )				
		{ 
			return $this->slug = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::description. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$description
		 * @param string
		 * @return string
		 */
		protected function set_description( $a )		
		{ 
			return $this->description->value = trim( stripslashes( html_entity_decode( htmlspecialchars_decode( $a ) ) ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::short. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$short
		 * @param string
		 * @return string
		 */
		protected function set_short( $a )				
		{ 
			return $this->short->value = trim( stripslashes( html_entity_decode( htmlspecialchars_decode( $a ) ) ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::nola. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$nola
		 * @param string
		 * @return string
		 */
		protected function set_nola( $a )				
		{ 
			return $this->nola->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::resource_uri. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$resource_uri
		 * @param string
		 * @return string
		 */
		protected function set_resource_uri( $a )		
		{ 
			return $this->resource_uri->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::shop. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$shop
		 * @param string
		 * @return string
		 */
		protected function set_shop( $a )				
		{ 
			return $this->shop->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::itunes. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$itunes
		 * @param string
		 * @return string
		 */
		protected function set_itunes( $a )				
		{ 
			return $this->itunes->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::cast. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$cast
		 * @param string
		 * @return string
		 */
		protected function set_cast( $a )				
		{ 
			return $this->cast->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::website. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$website
		 * @param string
		 * @return string
		 */
		protected function set_website( $a )			
		{ 
			return $this->website->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::kids. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$kids
		 * @param int
		 * @return int
		 */
		protected function set_kids( $a )				
		{ 
			return $this->kids->value = (int)$a; 
		}
		
		/**
		 * Sets the value of NatProgram::img_mezzanine. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$img_mezzanine
		 * @param string
		 * @return string
		 */
		protected function set_img_mezzanine( $a )		
		{ 
			return $this->img_mezzanine->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::img_logo. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$img_logo
		 * @param string
		 * @return string
		 */
		protected function set_img_logo( $a )			
		{ 
			return $this->img_logo->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::img_iphone_small. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$img_iphone_small
		 * @param string
		 * @return string
		 */
		protected function set_img_iphone_small( $a )	
		{ 
			return $this->img_iphone_small->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::img_iphone_med. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$img_iphone_med
		 * @param string
		 * @return string
		 */
		protected function set_img_iphone_med( $a )		
		{ 
			return $this->img_iphone_med->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::img_ipad_small. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$img_ipad_small
		 * @param string
		 * @return string
		 */
		protected function set_img_ipad_small( $a )		
		{ 
			return $this->img_ipad_small->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::scheduled. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$scheduled
		 * @param bool
		 * @return bool
		 */
		protected function set_scheduled( $a )			
		{ 
			return $this->scheduled->value = (bool)$a; 
		}
		
		/**
		 * Sets the value of NatProgram::episodes. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$episodes
		 * @param string
		 * @return string
		 */
		protected function set_episodes( $a )			
		{ 
			return $this->episodes->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::previews. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$previews
		 * @param string
		 * @return string
		 */
		protected function set_previews( $a )			
		{ 
			return $this->previews->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of NatProgram::covecheck. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses NatProgram::$covecheck
		 * @param int
		 * @return int
		 */
		protected function set_covecheck( $a )			
		{ 
			return $this->covecheck->value = (int)$a; 
		}
	}
}
?>