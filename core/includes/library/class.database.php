<?php
namespace Core;

if ( !defined( "D_CLASS_DATABASE" ) )
{
	define( "D_CLASS_DATABASE", true );

	
	if ( !isset( $_CORE_CONFIG[ "mysql_username" ] ) ) {
		die("mysql_username not set in \$_CORE_CONFIG");
	}
	if ( !isset( $_CORE_CONFIG[ "mysql_password" ] ) ) {
		die("mysql_password not set in \$_CORE_CONFIG");
	}
	if ( !isset( $_CORE_CONFIG[ "mysql_server" ] ) ) {
		die("mysql_server not set in \$_CORE_CONFIG");
	}
	if ( !isset( $_CORE_CONFIG[ "database_name" ] ) ) {
		die("database_name not set in \$_CORE_CONFIG");
	}
	
	/**
 	 * File: class.database.php
	 *
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Database
	{
		public $id 		= NULL;
		public $user 	= "";
		public $host	= "";
		public $name	= "";	
		
		const ACTIVITY					= "activity";
		const ACTIVITY_PRE				= "activity_";
		const ADS 						= "advertisements";
		const ADS_PRE 					= "ad_";
		const ADS2PAGES 				= "advertisements_to_pages";
		const ADS2CLICKS				= "advertisements_clicks";
		const ADS2VIEWS					= "advertisements_views";
		//const AFFILIATES 				= "affiliates";
		//const AFFILIATES_PRE 			= "affiliate_";
		//const JOBAPPS	 				= "applications";
		//const JOBAPPS_PRE 				= "application_";
		//const APPROVALS					= "approvals";
		const ARTICLES 					= "articles";
		const ARTICLES_PRE				= "article_";
		const BANNERS 					= "banners";
		const BANNERS_PRE 				= "banner_";
		//const BOARDFILES				= "boardfiles";
		const CACHE						= "cache";
		const CACHE_PRE					= "cache_";
		const COMPANIES 				= "companies";
		const COMPANIES_PRE 			= "company_";
		const COMPETITIONS				= "competitions";
		const COMPETITIONS_PRE			= "competition_";
		//const C2PHONE 					= "companies_to_phonebook";
		//const C2SITES 					= "companies_to_websites";
		//const C2EMAIL 					= "companies_to_emailbook";
		const CONFIG 					= "config";
		const CONFIG_PRE 				= "config_";
		//const CONTESTANTS				= "contestants";
		//const CONTESTANTS_PRE			= "contestant_";
		const EMAIL 					= "emailbook";
		const EMAIL_PRE 				= "email_";
		const ENCORES					= "encores";
		const ENCORES_PRE				= "encore_";
		const ENTRIES					= "entries";
		const ENTRIES_PRE				= "entry_";
		const ENTRIES2COMPS				= "entries_to_competitions";
		const EPISODES					= "episodes";
		const EPISODES_PRE				= "episode_";
		const GUESTS					= "guests";
		const GUESTS_PRE				= "guest_";
		const G2SEGMENTS 				= "guests_to_segments";
		const G2PHONE 					= "guests_to_phonebook";
		const G2SITES 					= "guests_to_websites";
		const G2EMAIL 					= "guests_to_emailbook";
		const GROUPS					= "groups";
		const GROUPS_PRE				= "group_";
		//const JOBS	 					= "jobs";
		//const JOBS_PRE					= "job_";
		const JUDGES					= "judges";
		const JUDGES_PRE				= "judge_";
		const JUDGES2ROUNDS				= "judges_to_rounds";
		//const MERLIN_LOG				= "merlin_log";
		//const MERLIN_LOG_PRE			= "log_";
		const MODERATORS				= "moderators";
		const MODERATORS_PRE			= "moderator_";
		const NATPROGRAMS				= "natprograms";
		const NATPROGRAMS_PRE			= "natprogram_";
		const NEWSLETTER2PHOTOS 		= "newsletters_to_photos";
		const NEWSLETTERARTICLES		= "newsletterarticles";
		const NEWSLETTERARTICLES_PRE	= "newsletterarticle_";
		const NEWSLETTERSECTIONS		= "newslettersections";
		const NEWSLETTERSECTIONS_PRE	= "newslettersection_";
		const NEWSLETTERS				= "newsletters";
		const NEWSLETTERS_PRE			= "newsletter_";
		//const NOTICES					= "notifications";
		//const NOTICES_PRE				= "notice_";
		//const OPENIDS					= "openids";
		//const OPENIDS_PRE				= "openid_";
		const PCHOICES					= "pchoices";
		const PCHOICES_PRE				= "pchoice_";
		const PERFORMANCES				= "performances";
		const PERFORMANCES_PRE			= "performance_";
		const PERM						= "permissions";
		const PHONE 					= "phonebook";
		const PHONE_PRE 				= "phone_";
		const PHOTOS 					= "photos";
		const PHOTOS_PRE 				= "photo_";
		const PREVIEWS 					= "previews";
		const PREVIEWS_PRE 				= "preview_";
		const PROGRAMS					= "programs";
		const PROGRAMS_PRE				= "program_";
		const PROGCATS					= "program_categories";
		const PROGCATS_PRE				= "category_";
		const P2ARTICLES				= "programs_to_articles";
		const P2RESOURCES				= "programs_to_resources";
		const P2RESOURCES_PRE			= "type_";
		const RESOURCETYPES				= "resource_types";
		const RESOURCETYPES_PRE			= "resourcetype_";
		const ROUNDS 					= "rounds";
		const ROUNDS_PRE 				= "round_";
		const SEARCH 					= "pages";
		const SEARCH_PRE 				= "page_";
		const SEGMENTS 					= "segments";
		const SEGMENTS_PRE 				= "segment_";
		const SEGCATS					= "segment_categories";
		const SEGCATS_PRE				= "category_";
		const S2PHOTOS 					= "segments_to_photos";
		const S2SITES 					= "segments_to_websites";
		const SESSIONS					= "sessions";
		const SESSIONS_PRE				= "session_";
		const SCORES					= "scores";
		//const TEMPLATES					= "templates";
		//const TEMPLATES_PRE				= "template_";
		const USERS						= "users";
		const USERS_PRE					= "user_";
		const USERS2PAGES				= "users_to_pages";
		const SITES 					= "websites";
		const SITES_PRE 				= "website_";
		//const VOLUNTEERS				= "volunteers";
		//const VOLUNTEERS_PRE			= "volunteer_";
		const WIZARD 					= "wizard";
		const WIZARD_PRE 				= "wizard_";
		
		/**
		 * Wrapper for mysql_query().
		 *
		 * Wrapper for mysql_query(). It's preferable to use mysql_query() over this because it's faster, but this was created in case I wanted to do
		 * error handling automatically without replacing every instance of mysql_query.
		 *
		 * @return mixed
		 */
		public function query( $query )
		{
			return mysql_query( $query );
		}
		
		/**
		 * Invokes $this->query if Database is called like a function. Automatically called.
		 *
		 * <code>
		 * $result = Database( 'SELECT...' );
		 * </code>
		 *
		 * @uses Database::query
		 * @return mixed
		 */
		public function __invoke( $var )
		{
			return $this->query( $var );
		}

		/**
		 * Class constructor. Automatically connects to the MySQL database.
		 *
		 * @uses Database::connect
		 */
		public function __construct()
		{	
			global $_CORE_CONFIG;
			$this->connect( $_CORE_CONFIG[ "mysql_username" ], $_CORE_CONFIG[ "mysql_password" ], $_CORE_CONFIG[ "mysql_server" ], $_CORE_CONFIG[ "database_name" ] );
		}
		

		/**
		 * Class destructor. Automatically disconnects from the MySQL database.
		 *
		 * @uses Database::disconnect
		 */
		public function __destruct()
		{	
			$this->disconnect();
		}
		
		/**
		 * Connects to the database using the given credentials. Then it remembers them.
		 *
		 * @uses Database::$user
		 * @uses Database::$host
		 * @uses Database::$dbname
		 * @uses Database::$id
		 * @uses Database::change
		 * @param string user name
		 * @param string password
		 * @param string host name
		 * @param string database name
		 * @throws Exception if the database couldn't connect or be found
		 */
		public function connect( $user, $pass, $host, $dbname )
		{
			//die(var_dump($user, $pass, $host, $dbname));
			// try to connect
			if ( ( $this->id = mysql_connect( $host, $user, $pass ) ) === false ) { 
				throw new \Exception( "We couldn't connect to the database." );
				//die("error");
			}
			
			if ( $this->change( $dbname ) === false ) {
				throw new \Exception( "We couldn't find the database we wanted." );
			}
			
			// set properties
			$this->user = $user;
			$this->host = $host;
			$this->name = $dbname;
		
			return $this->id;
		}
		
		/**
		 * Wrapper for mysql_select_db.
		 *
		 * @param string name of database to connect to.
		 * @uses Database::$id
		 * @return bool
		 */
		public function change( $name )
		{
			return mysql_select_db( $name, $this->id );
		}
		
		/**
		 * Wrapper for mysql_close.
		 *
		 * @uses Database::is_connected
		 * @uses Database::$id
		 * @return bool
		 */
		public function disconnect()
		{
			return $this->is_connected() ? mysql_close( $this->id ) : false;
		}
		
		/**
		 * Determines if we're connected to the database or not.
		 *
		 * @uses Database::$id
		 * @return bool
		 */
		public function is_connected()
		{
			return (bool)$this->id;
		}
		
		/**
		 * Looks up the given table in the database and returns the id/column pairs in an array.
		 *
		 * @param string name of the database.
		 * @param string name of the index (or whatever you want the key to be).
		 * @param string name of the field you wish to become the values of the array.
		 * @uses Database::select
		 * @return Array
		 */
		static public function get_column( $table, $index, $col )
		{
			$columns = array();
			if ( $rows = isset( $this ) ? $this->select( $table, "$index, $col" ) : Database::select( $table, "$index, $col" ) )
				foreach( $rows as $r )
					$columns[ $r[ $index ] ] = $r[ $col ];
					
			return $columns;
		}
		
		/**
		 * Looks up the given table in the database and returns default value of the field.
		 *
		 * @param string name of the table to be interpretted
		 * @param string name of the field to be interpretted
		 * @uses Database::query
		 * @return mixed
		 */
		static public function get_default( $table, $field )
		{
			$sql 	= "SHOW COLUMNS FROM {$table} WHERE field = '{$field}'";
			$result = $this ? $this->query( $sql ) : mysql_query( $sql );
			$row 	= mysql_fetch_row( $result );
			
			return $row[ 4 ];
		}
		
		/**
		 * Takes the given query pattern and breaks it up into an array structure
		 *
		 * Takes the given query pattern and breaks it up into an array structure very similar
		 * to the result of the "SHOW COLUMNS..." query. It returns a structure with 
		 * the following keys: Field, Type, Null, Key, Default, and Extra. This could possibly 
		 * be used for comparing with said results. This public function assumes that the provided 
		 * query is valid.
		 *
		 * @param string query pattern to be interpretted
		 * @return Array
		 */
		static public function col_explode( $q )
		{
			// initialize variables
			$column = array( 	"Field" => "", "Type" => "", "Null" => "", 
								"Key" => "", "Default" => "", "Extra" => "" );
			
			// assign field
			$column[ 'Field' ] = trim( substr( $q, 0, ( $i = strpos( $q, " " ) ) ) );
			$q = trim( substr( $q, ( $i + 1 ) ) );
		
			// assign type
			if ( ( $i = strpos( $q, "(" ) ) === false )
				$column[ 'Type' ] = trim( substr( $q, 0, ( $i = strpos( $q, " " ) ) ) );
			else
			{
				$column[ 'Type' ] 	= strtolower( trim( substr( $q, 0, $i ) ) );
				$q		= trim( substr( $q, ( $i + 1 ) ) );
				$column[ 'Type' ] 	.= "(" . trim( substr( $q, 0, ( $i = strpos( $q, ")" ) ) ) ) . ")";
				$q		= trim( substr( $q, ( $i + 1 ) ) );
			}
		
			// assign default
			if ( ( $i = stripos( $q, "DEFAULT" ) ) !== false )
			{
				$column[ 'Default' ] = trim( substr( $q, strpos( $q, " ", $i ) ) );
				if ( ( $i = strpos( $column[ 'Default' ], " " ) ) !== false )
					$column[ 'Default' ] = trim( substr( $column[ 'Default' ], 0, $i ) );
		
				$column[ 'Default' ] = str_replace( "'", "", $column[ 'Default' ] );
				$column[ 'Default' ] = str_replace( '"', "", $column[ 'Default' ] );
			}
		
			// assign null
			if ( stristr( $q, "NOT NULL" ) !== false )
				$column[ 'Null' ] = "NO";
		
			// assign key
			if ( stristr( $q, "PRIMARY KEY" ) !== false )
				$column[ 'Key' ] = "PRI";
		
			// assign extra information
			if ( stristr( $q, "AUTO_INCREMENT" ) !== false )
				$column[ 'Extra' ] = "auto_increment";
		
			return $column;
		}
		
		/**
		 * This returns the first MySQL row result = fetch_array
		 *
		 * @param string name of the table to be interpretted.
		 * @param string fields to be returned in the result set. (Default = "[asterisk]")
		 * @param string additional WHERE statement to add to the query. (Default = "1")
		 * @uses Database::query
		 * @return Array
		 */
		static public function fetch( $table, $fields = "*", $clause = "1" )
		{
			$sql 	= "SELECT {$fields} FROM " . $table . " WHERE {$clause} LIMIT 1";
			$result = isset( $this ) ? $this->query( $sql ) : mysql_query( $sql );
			
			return mysql_fetch_array( $result, MYSQL_ASSOC );
		}
		
		/**
		 * Works like fetch_row(), except it only returns a single cell.
		 *
		 * @param string name of the table to be interpretted.
		 * @param string name of the column to find.
		 * @param string additional WHERE statement to add to the query. (Default = "")
		 * @uses Database::query
		 * @return mixed or false if query failed.
		 */
		static public function fetch_cell( $table, $col, $cond = "" )
		{
			$sql = "SELECT {$col} FROM {$table}" . ( $cond ? " WHERE {$cond}" : "" );
			
			if ( !isset( $this ) && !( $result = mysql_query( $sql ) ) )
				return false;
			if ( isset( $this ) && !( $result = $this->query( $sql ) ) )
				return false;
			else
			{
				$row = mysql_fetch_row( $result );
				return $row[ 0 ];
			}
		}
		
		/**
		 * Looks up the given table in the database and returns a list of columns.
		 *
		 * @param string name of the table to be interpretted
		 * @param bool whether or not to return the whole row. (Default = true)
		 * @uses Database::query
		 * @return Array
		 */
		static public function fetch_fields( $table, $fields_only = true )
		{
			$fields = array();
			$result = isset( $this ) ? $this->query( "SHOW COLUMNS FROM $table" ) : mysql_query( "SHOW COLUMNS FROM $table" );
			
			if ( $result )
			{
				while ( $row = mysql_fetch_assoc( $result ) )
					$fields[] = $fields_only ? $row[ 'Field' ] : $row;
		
				return $fields;
			}
			
			return $fields;
		}
		
		
		/**
		 * Get the prefix for the given table.
		 *
		 * @param string name of the table to be interpretted
		 * @uses Database::fetch_fields
		 * @return string
		 */
		static public function get_prefix( $table )
		{
			$fields = isset( $this ) ? $this->fetch_fields( $table ) : Database::fetch_fields( $table );
			$prefix = "";
			
			foreach( $fields as $f )
			{
				$prefix = substr( $f, 0, strpos( $f, "_" ) );
				break;
			}
			
			return $prefix;
		}
		
		/**
		 * Returns the number of rows of any given table.
		 *
		 * @param string name of the table to be interpretted
		 * @param string any additional WHERE statement to add to the query. (Default = "")
		 * @uses Database::query
		 * @return int or false if the query failed.
		 */
		static public function get_total( $table, $clause = "" )
		{
			$sql = "SELECT COUNT(*) FROM $table $clause";
			$n	 = 0;
		
			if ( ( $result = isset( $this ) ? $this->query( $sql ) : mysql_query( $sql ) ) === false )
				return false;
			else if ( ( $n = mysql_fetch_row( $result ) ) === false )
				return false;
			else
				return $n[ 0 ];
		}
		
		/**
		 * Works like implode(), except on a row result.
		 *
		 * @param Array the array to be interpretted
		 * @return string
		 */
		static public function col_implode( $array )
		{
			$string = "";
			if ( is_array( $array ) )
			{
				$keys = array( "PRI" => "PRIMARY KEY" );
		
				$string = $array[ 'Field' ] . " " . $array[ 'Type' ] . ( $array[ 'Null' ] ? " NOT NULL" : " " )
						. ( $array[ 'Default' ] ? " DEFAULT '" . $array[ 'Default' ] . "'" : " " )
						. ( $array[ 'Extra' ] ? " " . $array[ 'Extra' ] . " " : " " ) 
						. ( $array[ 'Key' ] ? " " . $keys[ $array[ 'Key' ] ] : " " );
			}
		
			return $string;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * Is Published
		#--------------------------------------------------------------------------------------------------
		/*static public function row_is_published( $table, $prefix, $id )
		{
			$published = false;
			$data = Database::fetch( $table, $prefix . "enabled, " . $prefix . "deleted", $prefix . "id = '{$id}'" );
			
			if ( $data )
			{
				if ( $i = strpos( $prefix, "." ) )
					$prefix = substr( $prefix, $i + 1 );
				$published 	= $data[ $prefix . "enabled" ] && !$data[ $prefix . "deleted" ];
				$mods 		= Database::select( Database::MODERATORS, "*", "moderator_access = '" . $table . "'" );
				$r_approval	= array();
				$approval = false;
				
				// check to see if a moderator has approved it first
				if ( $published && $mods )
				{
					foreach( $mods as $m )
					{
						$a = Database::fetch( Database::APPROVALS, "*", "approval_moderator = '" . $m[ 'moderator_id' ] . "' AND approval_row = '" . $id . "'" );
						$a_approval = isset( $a[ 'approval_approved' ] ) && $a[ 'approval_approved' ];
						
						if ( $a_approval ) $approval = true;
						if ( $m[ 'moderator_required' ] ) $r_approval[] = $a_approval;
					}
					
					$published = $approval && !in_array( false, $r_approval );
				}
			}

			return $published;
		}*/
		
		/**
		 * This puts the result set of any given query into a nice array.
		 *
		 * @param string name of the table to be interpretted.
		 * @param string fields to be returned in the result set. (Default = "[asterisk]")
		 * @param string any additional WHERE statement to add to the query. (Default = "1")
		 * @uses Database::select_query
		 * @uses Database::is_connected
		 * @return Array
		 */
		static public function select( $table, $fields = "*", $clause = "1" )
		{
			$sql 	= "SELECT {$fields} FROM {$table} WHERE {$clause}";
			$list 	= false;
			
			if ( !isset( $this ) )
				$list = Database::select_query( $sql );
			else if ( $this->is_connected() )
				$list = $this->select_query( $sql );
			
			return $list;
		}
		
		/**
		 * This puts the result set of any given query into a nice array and eliminates anything unpublished.
		 *
		 * @todo Needs to actually eliminate unpublished rows. Right now, it doesn't.
		 * @param string name of the table to be interpretted.
		 * @param string fields to be returned in the result set. (Default = "[asterisk]")
		 * @param string any additional WHERE statement to add to the query. (Default = "1")
		 * @param string prefix of columns. (Default = "")
		 * @uses Database::select
		 * @return Array
		 */
		static public function select_published( $table, $fields = "*", $clause = "1", $prefix = "" )
		{
			
			$list 	= Database::select( $table, $fields, $clause );
			/*$n		= count( $list );
			$del	= array();
			
			for( $i = 0; $i < $n; $i++ )
			{
				if ( ( $j = strpos( $prefix, "." ) ) !== false )
					$p = substr( $prefix, ( $j + 1 ) );
				else
					$p = $prefix;
				
				if ( !Database::row_is_published( $table, $prefix, $list[ $i ][ $p . 'id' ] ) )
					$del[] = $i;
			}
			
			foreach( $del as $d )
				unset( $list[ $d ] );
			
			if ( is_array( $list ) )
				sort( $list );*/
			
			
			
			return $list;
		}
		
		/**
		 * This puts the result set of any given query into a nice array. 
		 *
		 * @param string the query to gather an array from
		 * @uses Database::query
		 * @uses Database::is_connected
		 * @return Array
		 */
		static public function select_query( $query )
		{
			$rows = !isset( $this ) || $this->is_connected() ? array() : false;
			
			if ( $rows !== false )
			{
				if ( ( $result = isset( $this ) ? $this->query( $query ) : mysql_query( $query ) ) === false )
					$rows = false;
				else
					while( $row = mysql_fetch_array( $result, MYSQL_ASSOC ) )
						$rows[] = $row;
			}
			
			return $rows;
		}
		
		/**
		 * Checks to see if a table exists in the selected database.
		 *
		 * @param string name of the table to be interpretted.
		 * @uses Database::query
		 * @return bool
		 */
		static public function table_exists( $table )
		{
			$sql 	= "SELECT * FROM `{$table}` LIMIT 0";
			$result = isset( $this ) ? $this->query( $sql ) : mysql_query( $sql );
			
			return $result ? true : false;
		}
		
		/**
		 * Looks up the given table in the database and returns the field type.
		 *
		 * @param string name of the table to be interpretted.
		 * @param string name of the field to be interpretted.
		 * @uses Database::query
		 * @return mixed
		 */
		static public function type( $table, $field )
		{
			$sql 	= "SHOW COLUMNS FROM {$table} WHERE field = '{$field}'";
			$result = isset( $this ) ? $this->query( $sql ) : mysql_query( $sql );
			$row 	= mysql_fetch_row( $result );
			
			return $row[ 1 ] ;
		}	
	}
}
?>