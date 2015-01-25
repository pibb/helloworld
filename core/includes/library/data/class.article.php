<?php
namespace Core;

if ( !defined( "D_CLASS_ARTICLE" ) )
{
	define( "D_CLASS_ARTICLE", true );
	require( __DIR__ . "/class.data.php" );

	/**
 	 * File: class.article.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @todo image and external links
	 * @version 1.0.0
	 */
	class Article extends Data
	{
		protected $name 		= "";
		protected $slug 		= "";
		protected $content 		= "";
		//protected $action 		= "";
		//protected $action2 		= "";
		//protected $action_url 	= "";
		//protected $action_url2 	= "";
		//protected $img		 	= "";
		protected $type		 	= "";
		protected $short		= "";
		//protected $links		= array();
		public $href			= "";
		
		const TABLE				= Database::ARTICLES;
		const PREFIX			= Database::ARTICLES_PRE;
		const SHORT_TAGS		= '<a><strong><em>';
		const QUERY				= "article_enabled != '0' AND article_deleted = '0' ";
		
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
			$rows = Database::select( Database::ARTICLES, "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) ); 
			return self::getx_data( __CLASS__, $rows, $flag_first, "id", $webpage ); 
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
			$rows = Database::select( Database::ARTICLES, "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) ); 
			$data = self::getx_array_data( $rows, $flag_first ); 
			return self::append_data_array( $data, $webpage );
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
			$data = parent::getx_published_array( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage, $flag_first ); 
			return self::append_data_array( $data, $webpage );
		}
		
		/**
		 * Does some extra work (href and short clean-up) after rows have been setup in arrays
		 * 
		 * @param Array the data rows.
		 * @param WebPage the current webpage to pass on, if any. (Default = NULL)
		 * @uses WebPage::anchor
		 * @return Array
		 */
		static public function append_data_array( $data, WebPage &$webpage = NULL ) 
		{ 
			foreach( $data as $index => $row )
			{
				// fix the short descriptions
				foreach( $row as $key => $value )
					if ( $key == "short" )
						$data[ $index ][ "short" ] = strip_tags( $value, self::SHORT_TAGS );
				
				// if we can access the anchor method, create the href links from the slugs
				if ( $webpage )
					$data[ $index ][ 'href' ] = $webpage->anchor( MAIN_ARTICLES, array( 'slug' => $row[ 'slug' ] ) );
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
		 * @uses Article::$name
		 * @uses Article::$content
		 * @uses Article::$short
		 * @uses Article::$type
		 * @uses Article::$slug
		 * @uses Article::$href
		 * @uses Article::set_name
		 * @uses Article::set_content
		 * @uses Article::set_short
		 * @uses Article::set_type
		 * @uses Article::set_slug
		 * @uses Base::$webpage
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @uses WebPage::anchor
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$this->not_recorded []=  'href';
			$data = parent::setup( $data );

			// create columns
			$this->name 		= new Column( self::TABLE, self::PREFIX, "name" );
			$this->content 		= new Column( self::TABLE, self::PREFIX, "content" );
			$this->short 		= new Column( self::TABLE, self::PREFIX, "short" );
			//$this->action 		= new Column( self::TABLE, self::PREFIX, "action" );
			//$this->action_url 	= new Column( self::TABLE, self::PREFIX, "action_url" ); 
			//$this->action2 		= new Column( self::TABLE, self::PREFIX, "action2" );
			//$this->action_url2 	= new Column( self::TABLE, self::PREFIX, "action_url2" ); 
			$this->type 		= new IntColumn( self::TABLE, self::PREFIX, "type" ); 
			//$this->img 			= new ImageColumn( self::TABLE, self::PREFIX, "img", Website::IMAGES_DIR . "articles" . DIRECTORY_SEPARATOR, Website::IMAGES_DIR . "articles/", ImageColumn::IMAGE_TYPES );
			$this->slug 		= new Column( self::TABLE, self::PREFIX, "slug" );
			
			// add columns
			$this->add_col( 'name', true, true );
			$this->add_col( 'content', true );
			$this->add_col( 'short', true );
			//$this->add_col( 'img' );
			//$this->add_col( 'action' );
			//$this->add_col( 'action_url' );
			//$this->add_col( 'action2' );
			//$this->add_col( 'action_url2' );
			$this->add_col( 'type', true );
			$this->add_col( 'slug', true );
			
			if ( $data )
			{
				//$links = $data[ $this->prefix . 'action' ] ? array( $data[ $this->prefix . 'action' ] => $data[ $this->prefix . 'action_url' ] ) : array();
				
				//$this->set_id( $data[ $this->prefix . 'id' ] );
				$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_slug( $data[ $this->prefix . 'slug' ] );
				$this->set_content( $data[ $this->prefix . 'content' ] );
				$this->set_short( $data[ $this->prefix . 'short' ] );
				//$this->set_img( $data[ $this->prefix . 'img' ] );
				$this->set_type( $data[ $this->prefix . 'type' ] );
				//$this->set_action( $data[ $this->prefix . 'action' ] );
				//$this->set_action_url( $data[ $this->prefix . 'action_url' ] );
				//$this->set_action2( $data[ $this->prefix . 'action2' ] );
				//$this->set_action_url2( $data[ $this->prefix . 'action_url2' ] );
				
				if ( $this->webpage )
				{
					$this->href = $this->webpage->anchor( MAIN_ARTICLES, array( 'slug' => $this->slug ) );
				}
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Article::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Article::$name
		 * @param string
		 * @return string
		 */
		protected function set_name( $a )		
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Article::slug. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Article::$slug
		 * @param string
		 * @return string
		 */
		protected function set_slug( $a )		
		{ 
			return $this->slug->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Article::content. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Article::$content
		 * @param string
		 * @return string
		 */
		protected function set_content( $a )	
		{ 
			return $this->content->value = htmlspecialchars_decode( trim( stripslashes( $a ) ) ); 
		}
		
		/**
		 * Sets the value of Article::short. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Article::$short
		 * @param string
		 * @return string
		 */
		protected function set_short( $a )		
		{ 
			return $this->short->value = strip_tags( htmlspecialchars_decode( trim( stripslashes( $a ) ) ), self::SHORT_TAGS ); 
		}
		
		/**
		 * Sets the value of Article::type. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Article::$type
		 * @param int
		 * @return int
		 */
		protected function set_type( $a )		
		{ 
			return $this->type->value = (int)$a; 
		}
		
		
		//protected function set_img( $a )		{ return $this->img->value = trim( stripslashes( $a ) ); }
		//protected function set_action( $a )		{ return $this->action->value = trim( stripslashes( $a ) ); }
		//protected function set_action_url( $a )	{ return $this->action_url->value = trim( stripslashes( $a ) ); }
		//protected function set_action2( $a )	{ return $this->action2->value = trim( stripslashes( $a ) ); }
		//protected function set_action_url2( $a ){ return $this->action_url2->value = trim( stripslashes( $a ) ); }
	}
}
?>