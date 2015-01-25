<?php
namespace Core;

if ( !defined( "D_CLASS_ARTICLEDATA" ) )
{
	define( "D_CLASS_ARTICLEDATA", true );
	require( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.articledata.php
	 *
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	abstract class ArticleData extends Data
	{
		protected $name		= NULL;
		protected $slug		= NULL;
		protected $content	= NULL;         
		
		
		const TABLE			= Database::ARTICLES;
		const PREFIX		= Database::ARTICLES_PRE;
		
		/**
		 * Class constructor.
		 *
		 * @param mixed The indentifier to create the object from. This can be something to match against the identifier, an array of row info, or NULL for a blank object.
		 * @param WebPage Current WebPage object to add to object. (Default = NULL)
		 * @param string The column name to compare $id to. (Default = "id")
		 * @uses Data::__construct
		 * @uses Data::$classname
		 */
		public function __construct( $id, WebPage &$webpage = NULL, $identifier = "id" )
		{
			parent::__construct( $id, $webpage, ( $identifier ? $identifier : ( !is_string( $id ) ? "id" : "slug" ) ) );
			$this->classname = __CLASS__;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Get
		#----------------------------------------------------------------------------------------------------
		/*public function get( $amend = "" )
		{
			// initialize variables
			$data = array();
			$rows = Database::select( $this->table . "as a, " . Database::ARTICLES . " as b", "*", "a." . $this->prefix . "deleted = '0'" . ( $amend ? " AND " . $amend : "" ) );
			
			foreach( $rows as $row )
				$data[] = new $this->classname( $row[ $this->prefix . $this->identifier ] );
			
			return $data;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Get Published
		#----------------------------------------------------------------------------------------------------
		/*public function get_published( $amend = "" )
		{
			// initialize variables
			$data = array();
			$rows = Database::select_published( $this->table . "as a, " . Database::ARTICLES . " as b", "*", "a." . $this->prefix . "deleted = '0'" . ( $amend ? " AND " . $amend : "" ), "a." . $this->prefix );
			
			foreach( $rows as $row )
				$data[] = new $this->classname( $row[ $this->prefix . $this->identifier ] );
			
			return $data;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Get Published NEW
		#----------------------------------------------------------------------------------------------------
		/*static public function getx_published( $class, $table, $prefix, $amend = "%s", $identifier = "id", WebPage &$webpage = NULL, $flag_first = true )
		{
			// initialize variables
			$rows = Database::select( $table . " as a, " . self::TABLE . " as b", "*", sprintf( $amend, "a." . $prefix . "deleted = '0' AND a." . $prefix . "article = b.article_id " ), "a." . $prefix );
			$data = self::getx_published_data( $class, $rows, $flag_first );
			
			return $data;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Get Published NEW
		#----------------------------------------------------------------------------------------------------
		static public function getx_published_array( $class, $table, $prefix, $amend = "%s", $identifier = "id", WebPage &$webpage = NULL, $flag_first = true )
		{
			// initialize variables
			$rows = Database::select( $table . " as a, " . self::TABLE . " as b", "*", sprintf( $amend, "a." . $prefix . "deleted = '0' AND a." . $prefix . "article = b.article_id " ), "a." . $prefix );
			$data = self::getx_published_array_data( $rows, $flag_first );
			
			return $data;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Get Published
		#----------------------------------------------------------------------------------------------------
		/*static public function getx_published( $class, $table, $prefix, $amend = "%s", $identifier = "id", WebPage &$webpage = NULL )
		{
			// initialize variables
			$data 	= array();
			$rows 	= Database::select( $table . " as a, " . self::TABLE . " as b", "*", sprintf( $amend, "a." . $prefix . "deleted = '0' AND a." . $prefix . "article = b.article_id " ), "a." . $prefix );

			foreach( $rows as $row )
				$data[] = new $class( $row, $webpage, $identifier );
			
			return $data;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Get Published Array
		#----------------------------------------------------------------------------------------------------
		static public function getx_published_array( $class, $table, $prefix, $amend = "%s", $identifier = "id", WebPage &$webpage = NULL )
		{
			// initialize variables
			$data 	= array();
			$rows 	= Database::select_published( $table . " as a, " . self::TABLE . " as b", "*", sprintf( $amend, "a." . $prefix . "deleted = '0' AND a." . $prefix . "article = b.article_id " ), "a." . $prefix );
			
			foreach( $rows as $index => $row )
				foreach( $row as $key => $value )
					$data[ $index ][ preg_replace( "/^[A-Za-z]+_/", "", $key ) ] = htmlspecialchars_decode( stripslashes( trim( $value ) ) );
			
			return $data;
		}*/
		
		/**
		 * Initializing method that takes given row information and puts them into properties.
		 *
		 * @param mixed if an array is passed, it fills the properties; otherwise, it will attempt to get an array using it as an id.
		 * @uses ArticleData::$name
		 * @uses ArticleData::$content
		 * @uses ArticleData::$slug
		 * @uses ArticleData::set_name
		 * @uses ArticleData::set_content
		 * @uses ArticleData::set_slug
		 * @uses Data::add_col
		 * @uses Data::$table
		 * @uses Data::$prefix
		 * @uses Data::$id
		 * @uses Data::$author
		 * @uses Data::$created
		 * @uses Data::$modified
		 * @uses Data::$modified_by
		 * @uses Data::$deleted
		 * @uses Data::$deleted_by
		 * @uses Data::$enabled_by
		 * @uses Data::$reviewed
		 * @uses Data::$reviewed_by
		 * @uses Data::$status
		 * @uses Data::$notes
		 * @uses Database::fetch
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			
			$this->override_members[ "author" ] 	= array( "table" => self::TABLE, "prefix" => self::PREFIX );
			$this->override_members[ "created" ] 	= array( "table" => self::TABLE, "prefix" => self::PREFIX );
			$this->override_members[ "modified" ] 	= array( "table" => self::TABLE, "prefix" => self::PREFIX );
			$this->override_members[ "modified_by" ] = array( "table" => self::TABLE, "prefix" => self::PREFIX );
			
			// create columns
			$this->name 	= new Column( self::TABLE, self::PREFIX, "name" );
			$this->content 	= new Column( self::TABLE, self::PREFIX, "content" );
			$this->slug 	= new Column( self::TABLE, self::PREFIX, "slug" );
			
			
			
			// make adjustments
			$this->name->min = 3;
			$this->name->max = 255;
			
			// add columns
			$this->add_col( 'name', true, true );
			$this->add_col( 'slug', true );
			$this->add_col( 'content' );
			
			if ( !is_null( $data ) && !is_array( $data ) )
				$data = Database::fetch( $this->table . " as a LEFT JOIN " . Database::ARTICLES . " as b ON a." . $this->prefix . "article = b.article_id", "*", "a." . $this->prefix . "id = '" . $this->id . "'" );
			
			if ( $data )
			{
				$this->id 			= $data[ $this->prefix . 'id' ];
				$this->author 		= $data[ Database::ARTICLES_PRE . 'author' ];
				$this->created 		= $data[ Database::ARTICLES_PRE . 'created' ];
				$this->modified		= $data[ Database::ARTICLES_PRE . 'modified' ];
				$this->modified_by	= $data[ Database::ARTICLES_PRE . 'modified_by' ];
				$this->deleted 		= $data[ $this->prefix . 'deleted' ];
				$this->deleted_by 	= $data[ $this->prefix . 'deleted_by' ];
				$this->enabled 		= $data[ $this->prefix . 'enabled' ];
				$this->enabled_by 	= $data[ $this->prefix . 'enabled_by' ];
				$this->reviewed 	= $data[ $this->prefix . 'reviewed' ];
				$this->reviewed_by 	= $data[ $this->prefix . 'reviewed_by' ];
				$this->status 		= (int)$data[ $this->prefix . 'status' ];
				$this->notes 		= trim( stripslashes( $data[ $this->prefix . 'notes' ] ) );
				
				$this->set_name( $data[ Database::ARTICLES_PRE . 'name' ] );
				$this->set_slug( $data[ Database::ARTICLES_PRE . 'slug' ] );
				$this->set_content( $data[ Database::ARTICLES_PRE . 'content' ] );
			}
			
			return $data;
		}
		
		
		/**
		 * Sets the value of ArticleData::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ArticleData::$name
		 * @param string the article name.
		 * @return string the article name.
		 */
		protected function set_name( $a )		{ return $this->name->value = trim( stripslashes( $a ) ); }
		
		/**
		 * Sets the value of ArticleData::slug. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ArticleData::$slug
		 * @param string the article slug.
		 * @return string the article slug.
		 */
		protected function set_slug( $a )		{ return $this->slug->value = trim( stripslashes( $a ) ); }
		
		/**
		 * Sets the value of ArticleData::content. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ArticleData::$content
		 * @param string the article content.
		 * @return string the article content.
		 */
		protected function set_content( $a )	{ return $this->content->value = htmlspecialchars_decode( trim( stripslashes( $a ) ) ); }
	}
}
?>