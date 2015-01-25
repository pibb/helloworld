<?php
namespace Core;

if ( !defined( "D_CLASS_DATA" ) )
{
	define( "D_CLASS_DATA", true );
	require( __DIR__ . "/columns/class.column.php" );
	require( __DIR__ . "/columns/class.column_file.php" );
	require( __DIR__ . "/columns/class.column_img.php" );
	require( __DIR__ . "/columns/class.column_int.php" );
	require( __DIR__ . "/columns/class.column_many.php" );
	require( __DIR__ . "/columns/class.column_multi.php" );
	require( __DIR__ . "/columns/class.column_date.php" );
	require( __DIR__ . "/columns/class.column_email.php" );
	require( __DIR__ . "/columns/class.column_handle.php" );
	require( __DIR__ . "/columns/class.column_url.php" );
	require( __DIR__ . "/columns/class.column_pass.php" );
	require( __DIR__ . "/columns/class.column_phone.php" );
	
	
	/**
 	 * File: class.data.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	abstract class Data extends Base
	{
		public $is_first 		= false;
		public $webpage			= NULL;
		protected $classname	= "";
		protected $identifier	= "";
		protected $id			= 0;
		protected $author		= 0;
		protected $created		= 0;
		protected $modified		= 0;
		protected $modified_by	= 0;
		protected $deleted		= 0;
		protected $deleted_by	= 0;
		protected $enabled		= 0;
		protected $enabled_by	= 0;
		protected $reviewed		= 0;
		protected $reviewed_by	= 0;
		protected $status		= 0;
		protected $notes		= "";
		protected $table		= NULL;
		protected $prefix		= NULL;
		protected $_getters		= array();
		protected $columns		= array();
		protected $auto_id		= true;
		protected $not_recorded = array( 'override_members', 'auto_id', 'not_recorded', 'is_first', 'webpage', 'classname', 'identifier', 'id', 'table', 'prefix', '_getters', 'columns', 'parent' );
		protected $override_members = array();
		protected $other_tables	= array();
		const PENDING			= 0;
		const APPROVED			= 1;
		const DENIED			= 2;
		const ANONYMOUS			= 0;
		
		/**
		 * Initialize TABLE constant.
		 */
		abstract protected function init_table();
		
		/**
		 * Initialize PREFIX constant.
		 */
		abstract protected function init_prefix();
		
		/**
		 * Get the Class Name for the object.
		 */
		abstract protected function init_classname();
		
		/**
		 * Class constructor
		 *
		 * The constructor calls a series of initializer methods, most being abstract.
		 *
		 * @param mixed The indentifier to create the object from. This can be something to match against the identifier, an array of row info, or NULL for a blank object.
		 * @param WebPage Current WebPage object to add to object. (Default = NULL)
		 * @param string The column name to compare $id to. (Default = "id")
		 * @uses Data::setup
		 * @uses Data::setup_getters
		 * @uses Data::init_classname
		 * @uses Data::$identifier
		 * @uses Data::$table
		 * @uses Data::$prefix
		 * @uses Data::$webpage
		 * @uses Data::$classname
		 */
		public function __construct( $id, WebPage &$webpage = NULL, $identifier = "id" )
		{
			$this->webpage = $webpage;
			$this->table = $this->init_table();
			$this->prefix = $this->init_prefix();
			
			$this->identifier = $identifier;
			$init = "set_" . $identifier;
			
			if ( !method_exists( $this, $init ) )
				trigger_error( "Unknown property `" . $this->identifier . "` in table `" . $this->table . "`.", E_USER_ERROR );
			else
				call_user_func( array( $this, $init ), $id );
				
			// if ID is an array, then it's full of row data and should be set immediately
			if ( is_array( $id ) || ( is_int( $id ) && $id > 0 ) || ( $identifier != "id" && !is_null( $id ) ) )
				$this->setup( $id );
			else
				$this->setup( NULL );
			
			$this->setup_getters();
			$this->classname = $this->init_classname();
		}
		
		/**
		 * Add property to $this->columns.
		 *
		 * @uses Data::$property
		 * @uses Data::$columns
		 * @uses Column::$is_required
		 * @uses Column::$reveal
		 * @uses Column::$ref
		 * @param string Must be a class property.
		 * @param bool Is this field required? (Default = false)
		 * @param reveal Showcase this field on the admin table? (Default = false)
		 * @param mixed Other column this column references: Array(Table, Prefix, Column) (Default = "")
		 * @return Column or false if property is not found
		 */
		public function add_col( $property, $req = false, $reveal = false, $ref = "" )
		{
			// initialize variable
			$col = false;
			
			// make sure the property name is valid before initializing the object
			if ( !property_exists( $this, $property ) )
				trigger_error( "Invalid property name `{$property}` in object `" . get_class( $this ) . "`.", E_USER_ERROR );
			else
			{
				// initialize
				
				$this->$property->is_required 	= $req;
				$this->$property->reveal 		= $reveal;
				$this->$property->ref			= $ref;
				$this->columns[ $property ] 	= &$this->$property;
				$col = &$this->columns[ $property ];
			}
			
			return $col;
		}
		
		/**
		 * Adoption callback
		 *
		 * If an object adopts Data as a child, it will borrow its WebPage object
		 * to determine local paths and urls for possible file processing.
		 *
		 * @uses Base::adopt callback
		 * @uses Website::$local
		 * @uses Website::$url
		 * @uses Data::$webpage to locate local path and urls for FileColumns
		 */
		public function adoption()
		{
			// update paths
			if ( $this->webpage && ( $this->webpage instanceof WebPage ) )
			{
				$properties = get_object_vars( $this );
				foreach( $properties as $p => $val )
				{
					if ( $this->$p instanceof FileColumn )
					{
						$this->$p->upload_dir 	= $this->webpage->site->local . $this->$p->upload_dir;
						$this->$p->url_dir 		= $this->webpage->site->url . $this->$p->url_dir;
					}
				}
			}
		}
		
		/**
		 * Get an array of undeleted rows from the database.
		 *
		 * @param string name of the MySQL table.
		 * @param string prefix of table columns.
		 * @param string clause to amend the MySQL query. Uses sprintf(). (Default = "%s") 
		 * @uses Database::select
		 * @return Array of undeleted rows from the database.
		 */
		static public function getx_rowdata( $table, $prefix, $amend = "%s" )
		{
			return Database::select( $table, "*", sprintf( $amend, $prefix . "deleted = '0' " ) );
		}
		
		/**
		 * Get an array of published rows from the database.
		 *
		 * Returns an array of published rows from the database, which means that the row is not:
		 * 1. deleted, 2. disabled, and 3. has an approved status.
		 *
		 * @param string name of the MySQL table.
		 * @param string prefix of table columns.
		 * @param string clause to amend the MySQL query. Uses sprintf(). (Default = "%s") 
		 * @uses Database::select
		 * @return Array of published rows from the database.
		 */
		static public function getx_published_rowdata( $table, $prefix, $amend = "%s" )
		{
			return Database::select( $table, "*", sprintf( $amend, "{$prefix}deleted = '0' AND {$prefix}enabled != '0' AND {$prefix}status = '" . self::APPROVED . "'" ) );
		}
		
		/**
		 * Creates Data objects from undeleted rows found in the database.
		 * 
		 * This method facilitates, in general, a child class' ability to obtain undeleted rows from the database and create
		 * an array of Data objects from them. The 'x' in the name is because you cannot override static methods in PHP.
		 *
		 * @param string class name of the Data object to be created.
		 * @param string name of the MySQL table.
		 * @param string prefix of table columns.
		 * @param string clause to amend the MySQL query. Uses sprintf(). (Default = "%s") 
		 * @param string the column name to compare $id to. (Default = "id")
		 * @param WebPage a spot to automatically pass on a webpage object to new objects. (Default = NULL)
		 * @param bool do you want to mark the $is_first property on the first object in the array? Used mostly for Templates. (Default = true)
		 * @uses Data::getx_rowdata
		 * @uses Data::getx_data
		 * @return Array of published Data objects
		 */
		static public function getx( $class, $table, $prefix, $amend = "%s", $identifier = "id", WebPage &$webpage = NULL, $flag_first = true )
		{
			// initialize variables
			$rows = self::getx_rowdata( $table, $prefix, $amend );
			$data = self::getx_data( $class, $rows, $flag_first, $identifier, $webpage );
			
			return $data;
		}
		
		/**
		 * Creates Data objects from published rows found in the database.
		 * 
		 * This method facilitates, in general, a child class' ability to obtain published rows from the database and create
		 * an array of Data objects from them. The 'x' in the name is because you cannot override static methods in PHP. Published
		 * rows means that the row is not deleted, is enabled, and has an approved status.
		 *
		 * @param string class name of the Data object to be created.
		 * @param string name of the MySQL table.
		 * @param string prefix of table columns.
		 * @param string clause to amend the MySQL query. Uses sprintf(). (Default = "%s") 
		 * @param string the column name to compare $id to. (Default = "id")
		 * @param WebPage a spot to automatically pass on a webpage object to new objects. (Default = NULL)
		 * @param bool do you want to mark the $is_first property on the first object in the array? Used mostly for Templates. (Default = true)
		 * @uses Data::getx_published_rowdata
		 * @uses Data::getx_data
		 * @return Array of published Data objects
		 */
		static public function getx_published( $class, $table, $prefix, $amend = "%s", $identifier = "id", WebPage &$webpage = NULL, $flag_first = true )
		{
			// initialize variables
			$rows = self::getx_published_rowdata( $table, $prefix, $amend );
			$data = self::getx_data( $class, $rows, $flag_first, $identifier, $webpage );
			
			return $data;
		}
		
		/**
		 * Creates an array of Data objects from the given row information.
		 *
		 * @param string class name of the Data object to be created.
		 * @param array an array of row information from the MySQL database.
		 * @param bool do you want to mark the $is_first property on the first object in the array? Used mostly for Templates. (Default = true)
		 * @param string the column name to compare $id to. (Default = "id")
		 * @param WebPage a spot to automatically pass on a webpage object to new objects. (Default = NULL)
		 * @uses Data::$is_first
		 * @return Array of published Data objects
		 */
		static public function getx_data( $class, $rows, $flag_first = true, $identifier = "id", WebPage &$webpage = NULL )
		{	
			$data = array();
			foreach( $rows as $index => $row )
			{
				$data[] = new $class( $row, $webpage, $identifier );
				if ( $index == 0 && $flag_first )
					$data[ $index ]->is_first = true;
			}
			
			return $data;
		}
		
		/**
		 * Takes an array of Data objects and removes duplicate rows with same id.
		 *
		 * @param string class name of the Data object to be created.
		 * @param array an array of row information from the MySQL database.
		 * @param bool do you want to mark the $is_first property on the first object in the array? Used mostly for Templates. (Default = true)
		 * @param string the column name to compare $id to. (Default = "id")
		 * @param WebPage a spot to automatically pass on a webpage object to new objects. (Default = NULL)
		 * @uses Data::$id to match against other objects
		 * @uses Data::getx_data to obtain the array of objects
		 * @return Array of published Data objects
		 */
		static public function getx_distinct_data( $class, $rows, $flag_first = true, $identifier = "id", WebPage &$webpage = NULL )
		{	
			// initialize variables
			$data = $ids = array();
			$results = self::getx_data( $class, $rows, $flag_first, $identifier, $webpage );
			
			foreach( $results as $item )
			{
				if ( !in_array( $item->id, $ids ) )
				{
					$ids[] = $item->id;
					$data[] = $item;
				}
			}
			
			return $data;
		}
		
		/**
		 * Creates an array from undeleted rows found in the database.
		 * 
		 * This method facilitates, in general, a child class' ability to obtain undeleted rows from the database and create
		 * a simple array from them. The 'x' in the name is because you cannot override static methods in PHP. This was intended
		 * to replace Data::getx where possible because it is much more efficient to use arrays than to populate whole objects
		 * in large amounts of data.
		 *
		 * @param string class name of the Data object to be created.
		 * @param string name of the MySQL table.
		 * @param string prefix of table columns.
		 * @param string clause to amend the MySQL query. Uses sprintf(). (Default = "%s") 
		 * @param string the column name to compare $id to. (Default = "id")
		 * @param WebPage a spot to automatically pass on a webpage object to new objects. (Default = NULL)
		 * @param bool do you want to mark the $is_first property on the first object in the array? Used mostly for Templates. (Default = true)
		 * @uses Data::getx_rowdata
		 * @uses Data::getx_array_data
		 * @return Array
		 */
		static public function getx_array( $class, $table, $prefix, $amend = "%s", $identifier = "id", WebPage &$webpage = NULL, $flag_first = true )
		{
			// initialize variables
			$rows = self::getx_rowdata( $table, $prefix, $amend );
			$data = self::getx_array_data( $rows, $flag_first, $prefix );
			
			return $data;
		}
		
		/**
		 * Creates an array from published rows found in the database.
		 * 
		 * This method facilitates, in general, a child class' ability to obtain published rows from the database and create
		 * an array of Data objects from them. The 'x' in the name is because you cannot override static methods in PHP. Published
		 * rows means that the row is not deleted, is enabled, and has an approved status. This was intended
		 * to replace Data::getx_published where possible because it is much more efficient to use arrays than to populate whole objects
		 * in large amounts of data.
		 *
		 * @param string class name of the Data object to be created.
		 * @param string name of the MySQL table.
		 * @param string prefix of table columns.
		 * @param string clause to amend the MySQL query. Uses sprintf(). (Default = "%s") 
		 * @param string the column name to compare $id to. (Default = "id")
		 * @param WebPage a spot to automatically pass on a webpage object to new objects. (Default = NULL)
		 * @param bool do you want to mark the $is_first property on the first object in the array? Used mostly for Templates. (Default = true)
		 * @uses Data::getx_published_rowdata
		 * @uses Data::getx_array_data
		 * @return Array
		 */
		static public function getx_published_array( $class, $table, $prefix, $amend = "%s", $identifier = "id", WebPage &$webpage = NULL, $flag_first = true )
		{
			// initialize variables
			$rows = self::getx_published_rowdata( $table, $prefix, $amend );
			$data = self::getx_array_data( $rows, $flag_first, $prefix );
			
			return $data;
		}
		
		/**
		 * Creates a usable array from the given row information and removes the prefix from column names.
		 *
		 * @param array an array of row information from the MySQL database.
		 * @param bool do you want to mark the $is_first property on the first object in the array? Used mostly for Templates.
		 * @param string the regex expression used to eliminate prefixes from column names. (Default = "[A-Za-z]+_")
		 * @return Array of published Data objects
		 */
		static public function getx_array_data( $rows, $flag_first, $prefix = "[A-Za-z]+_" )
		{	
			$data = array();
			foreach( $rows as $index => $row )
			{
				foreach( $row as $key => $value )
					$data[ $index ][ preg_replace( "/^{$prefix}/", "", $key ) ] = htmlspecialchars_decode( stripslashes( trim( $value ) ) );
				$data[ $index ][ 'is_first' ] = $index == 0 && $flag_first;
			}
			
			return $data;
		}
		
		/**
		 * Takes an array of rows and removes duplicate rows with same id.
		 *
		 * @param array an array of row information from the MySQL database.
		 * @param bool do you want to mark the $is_first property on the first object in the array? Used mostly for Templates. (Default = true)
		 * @uses Data::getx_array_data to obtain the array
		 * @return Array
		 */
		static public function getx_distinct_array_data( $rows, $flag_first )
		{	
			// initialize variables
			$data = $ids = array();
			$results = self::getx_array_data( $rows, $flag_first );
			
			foreach( $results as $item )
			{
				if ( !in_array( $item[ 'id' ], $ids ) )
				{
					$ids[] = $item[ 'id' ];
					$data[] = $item;
				}
			}
			
			return $data;
		}
			
		/**
		 * Takes an array of Data objects or rows and inserts "strong" tags where keywords are found.
		 *
		 * @param array an array of Data objects or MySQL row information.
		 * @param array an array of keywords to look for (the needles)
		 * @param array an array of properties to search in (the haystacks)
		 * @return Array
		 */
		static public function highlight_keywords( array $results, array $keywords, array $properties )
		{
			// it's faster to use arrays in preg_replace than to iterate each word for every instance
			$replace = array();
			foreach( $keywords as $index => $r )
			{
				$keywords[ $index ] = '/(' . $r . ')/i';
				$replace[ $index ] = '<strong>$1</strong>';
			}
			
			// replace keywords with strong tags
			foreach( $results as $index => $r )
			{
				if ( $r instanceof Data )
				{
					foreach( $properties as $p )
					{
						$results[ $index ]->$p = preg_replace( $keywords, $replace, trim( strip_tags( $results[ $index ]->$p ) ) );
					}
				}
				else if ( is_array( $r ) )
				{
					foreach( $properties as $p )
					{
						$results[ $index ][ $p ] = preg_replace( $keywords, $replace, $results[ $index ][ $p ] );
					}
				}
			}
			
			return $results;
		}
		
		/*
		 * Determines if the given object is considered published.
		 *
		 * @uses Data::$enabled
		 * @uses Data::$deleted
		 * @uses Data::$status
		 * @return bool
		 */
		public function is_published()
		{
			return $this->enabled && !$this->deleted && $this->status == self::APPROVED;
		}
		
		/*
		 * Determines if the given object is considered pending.
		 *
		 * @author Travis Shelton <tshelton@wnit.org>
		 * @uses Data::$enabled
		 * @uses Data::$deleted
		 * @uses Data::$status
		 * @return bool
		 */
		public function is_pending()
		{
			return $this->enabled && !$this->deleted && $this->status == self::PENDING;
		}
		
		/*
		 * Attempts to publish the given object.
		 *
		 * @author Travis Shelton <tshelton@wnit.org>
		 *
		 * @param User Optional. The instance of user who is trying to publish.
		 * @uses Data::$status
		 * @uses Data::$table
		 * @uses Data::approve
		 * @uses User::is_moderator_of
		 * @return bool
		 */
		public function publish( User &$user = null )
		{
			$result = false;
			
			if ( $this->status == self::DENIED )
			{
				$this->status = self::PENDING;
				$this->update( $user->id );
				$result = true;
			}
			if( $user && $user->is_moderator_of( $this->table ) )
				$this->approve( $user );
				
			return $result;
		}
		
		/*
		 * Approve a pending object.
		 *
		 * @author Travis Shelton <tshelton@wnit.org>
		 * @param User The instance of user who is trying to approve.
		 * @uses Data::$status
		 * @uses Data::table
		 * @uses Data::reviewed
		 * @uses User::is_moderator_of
		 * @return bool
		 */
		public function approve( User &$user  )
		{
			$result = false;
			
			if( $this->status == self::PENDING && $user->is_moderator_of( $this->table ) )
			{
				$this->reviewed++;
				$this->status = self::APPROVED;
				$this->update( $user->id );
				$result = true;
			}
			
			return $result;
		}
		
		/*
		 * Deny a pending object.
		 *
		 * @author Travis Shelton <tshelton@wnit.org>
		 * @param User The instance of user who is trying to deny.
		 * @uses Data::$status
		 * @uses Data::table
		 * @uses Data::reviewed
		 * @uses User::is_moderator_of
		 * @return bool
		 */
		public function deny( User &$user )
		{
			$result = false;
			
			
			if( $this->status == self::PENDING && $user->is_moderator_of( $this->table ) )
			{
				$this->reviewed = 0;
				$this->status = self::DENIED;
				$this->update( $user->id );
				$result = true;
			}
			
			return $result;
		}
		
		/*
		 * Unpublish an approved object.
		 *
		 * @author Travis Shelton <tshelton@wnit.org>
		 * @param User The instance of user who is trying to deny.
		 * @uses Data::$status
		 * @uses Data::table
		 * @uses Data::reviewed
		 * @uses User::is_moderator_of
		 * @return bool
		 */
		public function unpublish( User &$user  )
		{
			$result = false;
			
			if( $this->status == self::APPROVED && $user->is_moderator_of( $this->table ) )
			{
				$this->reviewed = 0;
				$this->status = self::DENIED;
				$this->update( $user->id );
				$result = true;
			}
			
			return $result;
		}
		
		/**
		 * Turns an id-holding property into a given object.
		 *
		 * To save on memory, objects frequently only remember ID's of other tables that it references. This
		 * method takes that ID and creates an object from it. Then it adopts them. Works on one-dimensional arrays.
		 *
		 * <code>
		 * // Create a new episode object and dump segment information. 
		 * // Result will be an array of ID's.
		 * $episode = new Episode( $episode_id );
		 * var_dump( $episode->segments );
		 * // After populating that column, it now results in an array of Segment objects.
		 * $episode->populate( 'segments', 'Segment' );
		 * var_dump( $episode->segments );
		 * </code>
		 *
		 * @param string the name of the Column or array of ID's to generate objects from.
		 * @param string the name of the Data class.
		 * @uses Base::adopt
		 * @return Array
		 */
		public function populate( $property, $object )
		{
			// turn the id's into objects
			$prop = &$this->$var;
			if ( $this->$var instanceof Column )
			{
				$id = (int)$prop->value;
				$prop = new $object( $id );
			}
			else if ( is_array( $this->$var ) )
			{
				$n = count( $this->$var );
				for( $i = 0; $i < $n; $i++ )
				{
					$id = (int)$prop[ $i ];
					$prop[ $i ] = new $object( $id );
				}
			}
			
			// adopt them
			$this->adopt( $var );
		}
		
		
		/**
		 * Initializing method that takes given row information and puts them into properties. Called by children.
		 *
		 * @param mixed if an array is passed, it fills the properties; otherwise, it will attempt to get an array using it as an id.
		 * @uses Database::fetch
		 * @uses Data::$id
		 * @uses Data::$author
		 * @uses Data::$created
		 * @uses Data::$modified
		 * @uses Data::$modified_by
		 * @uses Data::$deleted
		 * @uses Data::$deleted_by
		 * @uses Data::$enabled
		 * @uses Data::$enabled_by
		 * @uses Data::$reviewed
		 * @uses Data::$reviewed_by
		 * @uses Data::$status
		 * @uses Data::$notes
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			if ( !is_null( $data ) && !is_array( $data ) )
			{
				// grab the info from the database and return the array
				$id 		= &$this->identifier;
				$id_query 	= addslashes( $this->$id );
				$data 		= Database::fetch( $this->table, "*", $this->prefix . $this->identifier . " = '{$id_query}'" );
			}
			
			if ( $data )
			{
				$this->id 			= $data[ $this->prefix . 'id' ];
				$this->author 		= $data[ $this->prefix . 'author' ];
				$this->created 		= $data[ $this->prefix . 'created' ];
				$this->modified		= $data[ $this->prefix . 'modified' ];
				$this->modified_by	= $data[ $this->prefix . 'modified_by' ];
				$this->deleted 		= $data[ $this->prefix . 'deleted' ];
				$this->deleted_by 	= $data[ $this->prefix . 'deleted_by' ];
				$this->enabled 		= $data[ $this->prefix . 'enabled' ];
				$this->enabled_by 	= $data[ $this->prefix . 'enabled_by' ];
				$this->reviewed 	= $data[ $this->prefix . 'reviewed' ];
				$this->reviewed_by 	= $data[ $this->prefix . 'reviewed_by' ];
				$this->status 		= (int)$data[ $this->prefix . 'status' ];
				$this->notes 		= $data[ $this->prefix . 'notes' ];
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Data::author. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$author
		 * @param int the User ID of the author.
		 * @return int the User ID of the author.
		 */
		protected function set_author( $a )		{ $this->author = (int)$a; return min( 0, $this->author ); }
		
		/**
		 * Sets the value of Data::created. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$created
		 * @param int the timestamp when the row was created.
		 * @return int the timestamp when the row was created.
		 */
		protected function set_created( $a )	{ $this->created = (int)$a; return min( 0, $this->created ); }
		
		/**
		 * Sets the value of Data::deleted. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$deleted
		 * @param int the timestamp when the row was deleted.
		 * @return int the timestamp when the row was deleted.
		 */
		protected function set_deleted( $a )	{ $this->deleted = (int)$a; return min( 0, $this->deleted ); }
		
		/**
		 * Sets the value of Data::deleted_by. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$deleted_by
		 * @param int the User ID of the user that deleted the row.
		 * @return int the User ID of the user that deleted the row.
		 */
		protected function set_deleted_by( $a )	{ $this->deleted_by = (int)$a; return min( 0, $this->deleted_by ); }
		
		/**
		 * Sets the value of Data::enabled. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$enabled
		 * @param int the timestamp when the row was enabled.
		 * @return int the timestamp when the row was enabled.
		 */
		protected function set_enabled( $a )	{ $this->enabled = (int)$a; return min( 0, $this->enabled ); }
		
		/**
		 * Sets the value of Data::enabled_by. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$enabled_by
		 * @param int the User ID of the user that enabled the row.
		 * @return int the User ID of the user that enabled the row.
		 */
		protected function set_enabled_by( $a )	{ $this->enabled_by = (int)$a; return min( 0, $this->enabled_by ); }
		
		/**
		 * Sets the value of Data::modified. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$modified
		 * @param int the timestamp when the row was modified.
		 * @return int the timestamp when the row was modified.
		 */
		protected function set_modified( $a )	{ $this->modified = (int)$a; return min( 0, $this->modified ); }
		
		/**
		 * Sets the value of Data::modified_by. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$modified_by
		 * @param int the User ID of the user that modified the row.
		 * @return int the User ID of the user that modified the row.
		 */
		protected function set_modified_by( $a ){ $this->modified_by = (int)$a; return min( 0, $this->modified_by ); }
		
		/**
		 * Sets the value of Data::reviewed. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$reviewed
		 * @param int the timestamp when the row was reviewed.
		 * @return int the timestamp when the row was reviewed.
		 */
		protected function set_reviewed( $a )	{ $this->reviewed = (int)$a; return min( 0, $this->reviewed ); }
		
		/**
		 * Sets the value of Data::reviewed_by. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$reviewed_by
		 * @param int the timestamp when the row was reviewed.
		 * @return int the timestamp when the row was reviewed.
		 */
		protected function set_reviewed_by( $a ){ $this->reviewed_by = (int)$a; return min( 0, $this->reviewed_by ); }
		
		/**
		 * Sets the value of Data::status. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$status
		 * @param int the status identifier (see constants)
		 * @return int the status
		 */
		protected function set_status( $a )		{ $this->status = (int)$a; return min( 0, $this->status ); }
		
		/**
		 * Sets the value of Data::notes. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$notes
		 * @param string any notes that should be associated with this row.
		 * @return string
		 */
		protected function set_notes( $a )		{ $this->notes = trim( stripslashes( $a ) ); return $this->notes; }
		
		/**
		 * Sets the value of Data::id after verifiying its validity. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Data::$id
		 * @param mixed value of the id.
		 * @return mixed
		 */
		protected function set_id( $a )
		{
			if ( is_array( $a ) && isset( $a[ $this->prefix . "id" ] ) )
				$a = (int)$a[ $this->prefix . "id" ];
			if ( is_string( $a ) && !Database::get_total( $this->table, "WHERE " . $this->prefix . "id = '" . addslashes( $a ) . "'" ) )
				trigger_error( "Row #" . $a . " in table `" . $this->table . "` does not exist.", E_USER_ERROR );
			else
				return $this->id = $a;
		}
		
		/**
		 * Sets all protected properties to be read by $_getters.
		 *
		 * @uses Data::$_getters
		 * @return Array
		 */
		protected function setup_getters()
		{
			// by default, list all properties
			$properties = get_object_vars( $this );
			unset( $properties[ '_getters' ] );
			unset( $properties[ 'webpage' ] );
			
			return $this->_getters = array_keys( $properties );
		}
		
		/**
		 * Verifies that the given prefix is valid then sets it. Sends E_USER_ERROR if not valid.
		 *
		 * @param string the prefix
		 * @uses Database::fetch_fields
		 * @uses Data::$table
		 * @uses Data::$prefix
		 * @return string
		 */
		protected function setup_prefix( $a )
		{
			// initialize variables
			$valid 		= false;
			$a_id 		= $a . "id";
			$columns 	= Database::fetch_fields( $this->table );
			
			// check to see if the prefix is valid by comparing against the `id` column
			foreach( $columns as $c )
			{
				if ( $c == $a_id )
				{
					$valid = true;
					break;
				}
			}
			
			// set if it's valid; otherwise, trigger and error
			if ( !$valid )
				trigger_error( "Prefix `" . $a . "` isn't valid in table `" . $this->table . "`.", E_USER_ERROR );
			else
			{
				$this->prefix = $a;
				return $this->prefix;
			}
		}
		
		/**
		 * Verifies that the given table is valid then sets it. Sends E_USER_ERROR if not valid.
		 *
		 * @param string the table name
		 * @uses Database::table_exists
		 * @uses Data::$table
		 * @return string
		 */
		protected function setup_table( $a )
		{
			if ( !Database::table_exists( $a ) )
				trigger_error( "Table `" . $this->table . "` does not exist.", E_USER_ERROR );
			else
				$this->table = $a;
		}
		
		/**
		 * Returns a list of object properties.
		 *
		 * Returns a lilst of object properties because trying to find all of the properties (including protected or
		 * private ones) from outside the scope of the object won't be complete.
		 *
		 * @return Array of object properties
		 */
		public function get_props()
		{
			return get_object_vars( $this );
		}
		
		private function _insert_table( $table, &$table_data )
		{
			$variables = array();
			$values = array();
			
			foreach( $table_data as $column )
			{
				$variables[] = $column["column_name"];
				$values[] = $column["column_value"];
			}
			$variables 	= implode( ", ", $variables );
			$values 	= implode( ", ", $values );
			
			$sql 		= sprintf( "INSERT INTO %s ({VARIABLES})  VALUES ({VALUES})",  $table );
			$sql		= str_replace( "{VARIABLES}", $variables, str_replace( "{VALUES}", $values, $sql ) );
			$result 	= mysql_query( $sql );
			return mysql_insert_id();
		}
		
		private function _update_table( $table, $prefix, &$table_data, $id )
		{
			$variables = array();
			$values = array();
			
			$info = array();
			
			foreach( $table_data as $column )
				$info[] = sprintf( "%s = %s", $column["column_name"], $column["column_value"] );
			
			$info = implode( ", ", $info );
			$sql 		= sprintf( "UPDATE %s SET %s WHERE %sid = %d",  $table, $info, $prefix, $id );
			$result 	= mysql_query( $sql );
			return $result;
		}
		
		private function _delete_row( $table, $prefix, $id )
		{
			$sql 		= sprintf( "DELETE FROM %s WHERE %sid = %d LIMIT 1",  $table, $prefix, $id );
			$result 	= mysql_query( $sql );
			return $result;
		}
		
		/**
		 * Inserts this object into the database as a new row.
		 *
		 * @param int User ID of the author. (Default = self::ANONYMOUS)
		 * @param bool Automatically enable the row? (Default = false)
		 * @uses Data::$created
		 * @uses Data::$author
		 * @uses Data::$enabled if $auto_enable is set.
		 * @uses Data::$enabled_by if $auto_enable is set.
		 * @uses Data::$not_recorded to ignore columns that do not go into the database.
		 * @throws Exception if there was a MySQL error.
		 * @return int of the new row; false if it failed.
		 */
		public function insert( $user_id = self::ANONYMOUS, $auto_enable = false )
		{
			$result = false;
			
			// only create a new row if this one doesn't exist already
			if ( !$this->id && $this->auto_id )
			{
				// initialize variables
				$class 	= get_called_class();
				$vars 	= array_keys( get_class_vars( $class ) );
				$tables = $insert_ids = $prefixes = array();
				$this->created 	= time();
				$this->author 	= $user_id;
				
				// check auto enabler
				if ( $auto_enable )
				{
					$this->enabled = $this->created;
					$this->enabled_by = $this->user_id;
				}
				
				
				
				// put the properties into an array for the query
				foreach( $vars as $index => $var )
				{
					if ( !in_array( $var, $this->not_recorded ) && !is_array( $this->$var ) )
					{
						$prefix = $this->$var instanceof Column ? $this->$var->prefix : $class::PREFIX;
						$table = $this->$var instanceof Column ? $this->$var->table : $class::TABLE;
						
						if ( !isset( $tables[ $table ] ) ) $tables[ $table ] = array();
						$value = $this->$var instanceof Column ? $this->$var->value : (string)$this->$var;
						
						
						if ( isset( $this->override_members[ $var ] ) )
						{
							$table = $this->override_members[ $var ][ "table" ];
							$prefix = $this->override_members[ $var ][ "prefix" ];
						}
						
						$column = array( 
								"column_name" => $prefix . $var,
								"column_value" => "'" . trim( addslashes( $value ) ) . "'"
								);
						
						$tables[ $table ][ $var ] = $column;
						if ( !isset( $prefixes[ $table ] ) ) 	$prefixes[ $table ] = $prefix;

					}
				}
				
				
				// turn array into strings. then create the query
				foreach( $tables as $table => &$table_data )
					if ( $table != $this->table )
					{
						$id = $this->_insert_table( $table, $table_data );
						$hacked_prefix = substr( $prefixes[ $table ], 0, strlen( $prefixes[ $table ] ) -1 );
						
						$tables[ $this->table ][] = array( 
							"column_name" => $this->prefix . $hacked_prefix,
							"column_value" => $id
						);
					}
				
				$this->_insert_table( $this->table, $tables[ $this->table ] );
				
				// try to get the inserted id to complete the object
				if ( $error = mysql_error() )
				{
					die( $error );
					throw new \Exception( $error );
				}
				else
					$result = $this->id = mysql_insert_id();
			}
				
			return $result;
		}
		
		
		/**
		 * Deletes this object and all associated rows and files
		 *
		 * @return int of the new row; false if it failed.
		 */
		public function delete( $user_id = self::ANONYMOUS )
		{
			$result = true;
			
			// only delete if the id is set
			if ( $this->id  )
			{
				// initialize variables
				$class 	= get_called_class();
				$vars 	= array_keys( get_class_vars( $class ) );
				$ids = $prefixes = array();
				
				// put the properties into an array for the query
				foreach( $vars as $index => $var )
				{
					if ( !in_array( $var, $this->not_recorded ) && !is_array( $this->$var ) )
					{
						$prefix = $this->$var instanceof Column ? $this->$var->prefix : $class::PREFIX;
						$table = $this->$var instanceof Column ? $this->$var->table : $class::TABLE;
						
						if ( !isset( $ids[ $table ] ) )
						{
							if ( $this->table == $table )
								$ids[ $table ] = $this->id;
							else
							{
								$hacked_prefix = substr( $prefix, 0, strlen( $prefix ) -1 );
								$sql = sprintf(
									"SELECT %s%s FROM %s WHERE %s%s = %d",
									$this->prefix, $hacked_prefix, $this->table, $this->prefix, $this->identifier, $this->id );
								$r = mysql_query($sql);
								$r = mysql_result( $r, 0 );
								$ids[ $table ] = (int)$r;
							}
							$prefixes[ $table ] = $prefix;
						}
						
						if ( $this->$var instanceof Column )
							$this->$var->delete( $user_id );

					}
				}
				
				// turn array into strings. then create the query
				foreach( $ids as $table => $id )
					$this->_delete_row( $table, $prefixes[ $table ], $id );

				// try to get the inserted id to complete the object
				if ( $error = mysql_error() )
				{
					die( $error );
					throw new \Exception( $error );
				}
				else
					$result = $this->id = mysql_insert_id();
			}
				
			return $result;
		}
		
		/**
		 * Updates this object in the database.
		 *
		 * @param int User ID of the updater. (Default = self::ANONYMOUS)
		 * @uses Data::$modified
		 * @uses Data::$modified_by
		 * @throws Exception if there was a MySQL error.
		 * @return int of the new row; false if it failed.
		 */
		public function update( $user_id = self::ANONYMOUS, $override_class = null, $id = null )
		{
			// only update if there is an id
			if ( $this->id )
			{
				// initialize variables
				$class 	= $override_class ? $override_class : get_called_class();
				$vars 	= array_keys( get_class_vars( $class ) );
				$tables = $prefixes = array();
				$this->modified 	= time();
				$this->modified_by 	= $user_id;
				
				// put the properties into an array for the query
				
				
				foreach( $vars as $index => $var )
				{
					if ( !in_array( $var, $this->not_recorded ) && !is_array(  $this->$var ) )
					{
						$prefix = $this->$var instanceof Column ? $this->$var->prefix : $class::PREFIX;
						$table = $this->$var instanceof Column ? $this->$var->table : $class::TABLE;
						if ( !isset( $tables[ $table ] ) ) $tables[ $table ] = array();
						$value = $this->$var instanceof Column ? $this->$var->value : (string)$this->$var;
						
						
						if ( isset( $this->override_members[ $var ] ) )
						{
							$table = $this->override_members[ $var ][ "table" ];
							$prefix = $this->override_members[ $var ][ "prefix" ];
						}
						
						$column = array( 
								"column_name" => $prefix . $var,
								"column_value" => "'" . trim( addslashes( $value ) ) . "'"
								);
						
						$tables[ $table ][ $var ] = $column;
						$prefixes[ $table ] = $prefix;
						
						if ( !isset( $ids[ $table ] ) )
						{
							if ( $table != $this->table )
							{
								$hacked_prefix = substr( $prefix, 0, strlen( $prefix ) -1 );
								$sql = sprintf("SELECT %s%s FROM %s WHERE %sid = %d", $this->prefix, $hacked_prefix, $this->table, $this->prefix, $this->id );
								$r = mysql_query($sql);
								$r = mysql_result( $r, 0 );
								$ids[ $table ] = (int)$r;
							} 
								else
							{
								$ids[ $table ] = $this->id;
							}
						}
					}
				}


				// turn array into strings. then create the query
				foreach( $tables as $table => &$table_data )
						$this->_update_table( $table, $prefixes[ $table ], $table_data, $ids[ $table ] );

				
				// check for an error
				if ( $error = mysql_error() )
					throw new \Exception( $error );
			}
				
			return $this->id;
		}
		
		/**
		 * Finds the given row's parent, grandparent, etc.
		 *
		 * @param mixed the ID of the given row; false will use the available object property, $this->id.
		 * @param mixed the TABLE of the given row; false will use the available object property, $this->table.
		 * @param mixed the PREFIX of the given row; false will use the available object property, $this->prefix.
		 * @uses Data::get_family
		 * @return Array
		 */
		static public function get_ascendants( $id = false, $table = false, $prefix = false )
		{
			return self::get_family( $id, $table, $prefix, "parent", "id" );
		}
		
		/**
		 * Finds the given row's children, grandchildren, etc.
		 *
		 * @param mixed the ID of the given row; false will use the available object property, $this->id.
		 * @param mixed the TABLE of the given row; false will use the available object property, $this->table.
		 * @param mixed the PREFIX of the given row; false will use the available object property, $this->prefix.
		 * @uses Data::get_family
		 * @return Array
		 */
		static public function get_descendants( $id = false, $table = false, $prefix = false )
		{
			return self::get_family( $id, $table, $prefix, "id", "parent" );
		}
		
		/**
		 * Searches the database for the given row's given relatives recursively.
		 *
		 * @param mixed the ID of the given row; false will use the available object property, $this->id.
		 * @param mixed the TABLE of the given row; false will use the available object property, $this->table.
		 * @param mixed the PREFIX of the given row; false will use the available object property, $this->prefix.
		 * @param string the column to return (id for descendants, parent for ascendants)
		 * @param string the column to compare (id for ascendants, parent for descendants)
		 * @uses Data::$id
		 * @uses Data::$table
		 * @uses Data::$prefix
		 * @uses Data::get_family
		 * @uses Database::select
		 */
		static protected function get_family( $id, $table, $prefix, $select_col, $compare_col )
		{
			// if called inside an object, use properties.
			if ( $id === false && isset( $this ) )
			{
				$id = $this->id;
				$table = $this->table;
				$prefix = $this->prefix;
			}
			
			// prepend prefixes
			$select_col = $prefix . $select_col;
			$compare_col = $prefix . $compare_col;
			
			// look for relatives
			if ( !( $members = Database::select( $table, $select_col, $compare_col . " = '" . (int)$id . "' AND {$prefix}enabled != '0' AND {$prefix}deleted = '0'" ) ) )
				$groups = array( $id );
			else
			{
				$groups = array( $id );
				foreach( $members as $m )
					$groups = array_merge( $groups, self::get_family( $m[ $select_col ], $table, $prefix, $select_col, $compare_col ) );
			}
			
			return $groups;
		}
		
		/**
		 * Looks for PassColumns and encrypts their values for data storage.
		 *
		 * @uses Data::$columns
		 * @uses Globals::post
		 * @uses Column::$name
		 * @uses Column::$value
		 * @uses PassColumn::encrypt
		 * @uses PassColumn::$match
		 * @uses PassColumn::$salt
		 * @uses Session::salt
		 * @return Form $this for method chaining.
		 */
		protected function encrypt_passwords()
		{
			// encrypt passwords
			foreach( $this->columns as $col )
			{
				if ( $col instanceof PassColumn ) 
				{
					$pass = Globals::post( $col->name );
					$pass1 = Globals::post( $col->name . "1" );
					
					// if the password field was left blank, we don't want to encrypt an already encrypted pass
					if ( ( !$col->match && $pass == $col->value ) || ( $col->match && $pass1 == $col->value ) )
					{
						$column = $col->name;
						$salt = "";
						$salty = $col->salt && isset( $this->salt );
						if ( $salty )
						{
							$salt = Session::salt();
							$this->salt = $salt;
						}
						$this->$column = $col->encrypt( $this->$column->value . $salt );
					}
				}
			}
			
			return $this;
		}
		
		/**
		 * Retrieves the value of protected proerties and throws an error if not allowed.
		 *
		 * Sometimes we need properties to be readable but not writable. This method checks
		 * a base property called _getters that can be overloaded to include any protected
		 * properties that can then be read by scripts. It will first check the class for a
		 * defined get_X method (where X is the name of the property) and call that instead;
		 * otherwise it will simply return the value. If neither exists, it will thrown an error.
		 *
		 * (Note: This method is automatically called whenever a script tries to read any
		 * property from outside the scope of the class.)
		 * 
		 * @param string The name of the property.
		 * @uses Base::$_getters to determine which properties can be read.
		 * @return mixed The value of the property.
		 */
		public function __get( $p ) 
		{
			// initialize variables
			$var 		= NULL;
			$error		= "";
			$func		= "get_" . $p;
			list( $e ) 	= debug_backtrace();
			
			// try to find the method before triggering an error. anything in the $_getters array will also work
			if ( method_exists( $this, $func ) )				$var = call_user_func( array( $this, $func ) );
			else if ( in_array( $p, $this->_getters ) )			$var = $this->$p;
			else if ( !property_exists( $this, $p ) )			$var = NULL;
			else if ( method_exists( $this, 'set_' . $p ) )		$error = "Property \"{$p}\" is write-only";
			else												$error = "Property \"{$p}\" is not accessible";
			 
			// trigger an error if there was one
			if ( $error ) trigger_error( $error . " in <code>" . $e[ 'file' ] . "</code> on line <code>" . $e[ 'line' ] . "</code>.", E_USER_ERROR );
			
			return $var;
		}
		
		/**
		 * Sets the given property to the given value and throws an error if not allowed.
		 *
		 * Checks to see if a given property has write permissions before attempting to change
		 * a value. It will first check the class for a defined set_X method (where X is the name
		 * of the property) and call that instead using the $value as an argument. This is
		 * useful if a property's value needs to be validated automatically (i.e. keeping a number
		 * between minimum and maximum values).
		 *
		 * (Note: This method is automatically called whenever a script tries to set any
		 * property from outside the scope of the class.)
		 * 
		 * @param string The name of the property.
		 * @param mixed The value to set the property to.
		 * @uses Base::$_getters to determine which properties can be read.
		 * @return mixed The value of the property.
		 */
		public function __set( $p, $value ) 
		{
			// initialize variables
			$var 		= NULL;
			$error		= "";
			$func		= "set_" . $p;
			
			// try to find the method before triggering an error. anything in the $_getters array will also work
			if ( is_callable( $this->$func ) ||  method_exists( $this, $func ) )	
				$var = call_user_func( array( $this, $func ), $value );
			else if ( !property_exists( $this, $p ) )			
				$var = $this->$p = $value;
			else if ( in_array( $p, $this->_getters ) || method_exists( $this, 'get_' . $p ) )
				$error = "Property \"{$p}\" is read-only";
			else												
				$error = "Property \"{$p}\" is not accessible";
			
			list( $e ) 	= debug_backtrace();
			// trigger an error if there was one
			if ( $error ) trigger_error( $error . " in <code>" . $e[ 'file' ] . "</code> on line <code>" . $e[ 'line' ] . "</code>.", E_USER_ERROR );
			
			return $var;
		}
		
		/**
		 * Defines a new column with the given name, data, and type
		 **/
		public function &def_col( $prop, $data, $colname = "Column", $req = false, $reveal = false )
		{
			// get setter method name
			$method = "set_{$prop}";
			$colname = __NAMESPACE__ . "\\" . $colname;
			
			// create setter method
			if ( !method_exists( $this, $method ) )
			{
				switch( $colname )
				{
					case "IntColumn": $this->$method = function( $a ) use( $prop ) 
						{ 
							if ( $this->$prop instanceof Column )
								$value = &$this->$prop->value;
							else
								$value = &$this->$prop;
							$value = (int)$a;
							if ( is_int( $this->$prop->min ) )
								$value = max( $this->$prop->min, $value );
							if ( is_int( $this->$prop->max ) )
								$value = min( $this->$prop->max, $value );
							return $value;
						}; 
						break;
						
					case "FloatColumn": $this->$method = function( $a ) use( $prop ) 
						{ 
							if ( $this->$prop instanceof Column )
								$value = &$this->$prop->value;
							else
								$value = &$this->$prop;
							$value = (float)$a;
							if ( is_float( $this->$prop->min ) )
								$value = max( $this->$prop->min, $value );
							if ( is_float( $this->$prop->max ) )
								$value = min( $this->$prop->max, $value );
							return $value;
						}; 
						break;
					
					default: $this->$method = function( $a ) use( $prop ) 
						{ 
							return $this->$prop instanceof Column 	? $this->$prop->value = trim( stripslashes( $a ) ) 
																	: $this->$prop = trim( stripslashes( $a ) ); 
						};
				}
			}

			// many2onecolumns reference another table and use different arguments
			if ( !is_array( $reveal ) && $prop != "Many2OneColumn" )
				$this->$prop = new $colname( $this->table, $this->prefix, $prop );
			else
			{
				list( $table, $prefix, $handle ) = $reveal;
				$this->$prop = null;
				$this->$prop = new $colname( $this->table, $this->prefix, $prop, $table, $prefix, $handle );

			}
			
			// add new column and call setter if available
			$this->add_col( $prop, $req, $reveal );
			if ( isset( $data[ $this->prefix . $prop ] ) )
			{
				if ( is_callable( $this->$method ) )
					call_user_func( array( $this, $method ), $data[ $this->prefix . $prop ] );
				else
					$this->$prop = $data[ $this->prefix . $prop ];
			}
				
			// parent is a Base property that can be overridden. It needs to be unignored, though.
			if ( $prop == 'parent' && $key = array_search( 'parent', $this->not_recorded ) )
				unset( $this->not_recorded[ $key ] );
			
			return $this->$prop;
		}
		
		/**
		 * Magic call method to catch dynamically defined setter methods.
		 **/
		public function __call( $method, $args )
		{
			
			
			if ( is_callable( $this->$method ) ) 
			{
				$func = $this->$method;
				return call_user_func_array( $func, $args );
			}
			else
			{
				throw new MemberAccessException( 'Method ' . $method . ' not exists' );
			}
		}
	}
}
?>