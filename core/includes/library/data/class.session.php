<?php
namespace Core;

if ( !defined( "D_CLASS_SESSION" ) )
{
	define( "D_CLASS_SESSION", true );
	require( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.session.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Session extends Data
	{
		public $activation 			= false;
		public $login_reset 		= 3;
		
		protected $user 			= 0;
		protected $last_attempt 	= 0;
		protected $login_attempts 	= 0;
		protected $start 			= 0;
		protected $time 			= 0;
		protected $page 			= 0;
		protected $ip 				= "";
		protected $logged_in 		= false;
		protected $user_agent		= NULL;
		
		protected $cname			= "";
		protected $cexpire			= 0;
		
		const CNAME					= "account";
		const CPATH					= "/beta/";
		const TABLE					= Database::SESSIONS;
		const PREFIX				= Database::SESSIONS_PRE;
		
		/**
		 * Get a specific field from a given row.
		 *
		 * @param string the name of the column/field.
		 * @param mixed the row identifier.
		 * @param string the column identifier. (Default = "id")
		 * @uses Database::fetch_cell
		 * @return mixed
		 */
		static public function get_field( $col, $id, $identifier = "id" ) { return Database::fetch_cell( self::TABLE, self::PREFIX . $col, self::PREFIX . $identifier . " = '" . $id . "'" ); }
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
		 * Class constructor
		 *
		 * @param mixed The indentifier to create the object from. This can be something to match against the identifier, an array of row info, or NULL for a blank object.
		 * @param WebPage Current WebPage object to add to object. (Default = NULL)
		 * @param string The column name to compare $id to. (Default = "id")
		 * @uses Data::__construct
		 * @uses Session::get_sid_from_cookie
		 */
		public function __construct( $id, WebPage &$webpage = NULL, $identifier = "id" )
		{
			if( session_status() != PHP_SESSION_ACTIVE )
				session_start();
			
			$sid = $id ? $id : self::get_sid_from_cookie();
			parent::__construct( $sid, $webpage, $identifier );
		}
		
		/**
		 * Initializing method that takes given row information and puts them into properties.
		 *
		 * @param mixed if an array is passed, it fills the properties; otherwise, it will attempt to get an array using it as an id.
		 * @uses Session::$ip
		 * @uses Session::$cname
		 * @uses Session::$cexpire
		 * @uses Session::$user_agent
		 * @uses Session::$start
		 * @uses Session::$time
		 * @uses Session::$page
		 * @uses Session::set_user
		 * @uses Session::set_last_attempt
		 * @uses Session::set_login_attempts
		 * @uses Session::set_start
		 * @uses Session::set_time
		 * @uses Session::set_page
		 * @uses Session::set_ip
		 * @uses Session::set_logged_in
		 * @uses Base::$webpage
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @uses Data::$not_recorded
		 * @uses WebPageLite::$session_expire
		 * @uses WebPageLite::$page_id
		 * @throws Exception if there isn't a database connection.
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$this->not_recorded[] = 'activation';
			$this->not_recorded[] = 'login_reset';
			$this->not_recorded[] = 'cname';
			$this->not_recorded[] = 'cexpire';
			$this->not_recorded[] = 'author';
			$this->not_recorded[] = 'created';
			$this->not_recorded[] = 'modified';
			$this->not_recorded[] = 'modified_by';
			$this->not_recorded[] = 'deleted';
			$this->not_recorded[] = 'deleted_by';
			$this->not_recorded[] = 'enabled';
			$this->not_recorded[] = 'enabled_by';
			$this->not_recorded[] = 'reviewed';
			$this->not_recorded[] = 'reviewed_by';
			$this->not_recorded[] = 'status';
			$this->not_recorded[] = 'notes';
			
			// check for database compatability first
			if ( @mysql_thread_id() === false )
				throw new \Exception( "A valid database connection is required." );
			else
			{
				$this->ip 			= $_SERVER[ 'REMOTE_ADDR' ];
				$this->cname 		= self::CNAME;
				$this->cexpire		= $this->webpage ? (int)$this->webpage->session_expire : WebPageLite::SESSION_LEN;
				$this->user_agent	= isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) ? md5( $_SERVER[ 'HTTP_USER_AGENT' ] ) : md5( "" );
				$this->start		= time();
				$this->time			= time();
				$this->page			= $this->webpage ? $this->webpage->page_id : "";
			}
			
			$data = parent::setup( $data );
			
			if ( $data )
			{
				$this->set_user( $data[ $this->prefix . 'user' ] );
				$this->set_last_attempt( $data[ $this->prefix . 'last_attempt' ] );
				$this->set_login_attempts( $data[ $this->prefix . 'login_attempts' ] );
				$this->set_start( $data[ $this->prefix . 'start' ] );
				$this->set_time( $data[ $this->prefix . 'time' ] );
				$this->set_page( $data[ $this->prefix . 'page' ] );
				$this->set_ip( $data[ $this->prefix . 'ip' ] );
				$this->set_logged_in( $data[ $this->prefix . 'logged_in' ] );
			}
			
			return $data;
		}
		
		
		/**
		 * Sets the value of Session::user. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Session::$user
		 * @param int
		 * @return int
		 */
		protected function set_user( $a )		 	
		{ 
			return $this->user->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Session::last_attempt. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Session::$last_attempt
		 * @param int
		 * @return int
		 */
		protected function set_last_attempt( $a ) 	
		{ 
			return $this->last_attempt->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Session::login_attempts. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Session::$login_attempts
		 * @param int
		 * @return int
		 */
		protected function set_login_attempts( $a ) 
		{ 
			return $this->login_attempts->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Session::start. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Session::$start
		 * @param int
		 * @return int
		 */
		protected function set_start( $a ) 			
		{ 
			return $this->start->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Session::time. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Session::$time
		 * @param int
		 * @return int
		 */
		protected function set_time( $a ) 			
		{ 
			return $this->time->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Session::page. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Session::$page
		 * @param int
		 * @return int
		 */
		protected function set_page( $a ) 			
		{ 
			return $this->page->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Session::ip. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Session::$ip
		 * @param string
		 * @return string
		 */
		protected function set_ip( $a ) 			
		{ 
			return $this->ip->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Session::logged_in. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Session::$logged_in
		 * @param bool
		 * @return bool
		 */
		protected function set_logged_in( $a ) 		
		{ 
			return $this->logged_in->value = (bool)$a; 
		}
		
		/**
		 * Sets the value of Session::id. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Session::$id
		 * @param string
		 * @return string
		 */
		protected function set_id( $a )
		{
			return $this->id = $a;
		}
		
		/**
		 * If there is a cookie set with the session id, change the current session to it.
		 *
		 * @param string the domain name for the cookie. (Default = self::CNAME)
		 * @return string session id
		 */
		static public function get_sid_from_cookie( $domain = self::CNAME )
		{
			// determine cookie name from domain name
			$cname = str_replace( ".", "_", $domain );
			
			// if the session has been stored, use the id
			if ( isset( $_COOKIE[ $cname ] ) )
				session_id( $_COOKIE[ $cname ] );
			
			return session_id();
		}
		
		/**
		 * Logs the user in if the given credentials are correct.
		 *
		 * Attempts to log the user into their account by accessing the assumed database connection and comparing 
		 * the user name and password. Then it sets a cookie, if the session is meant to last.
		 *
		 * @param string the username to compare.
		 * @param string the password associated with the account. Will be md5'd.
		 * @param bool will this session persist? (Default = true)
		 * @param string the column for the user name. (Sometimes we use the e-mail address.) (Default = 'user_name')
		 * @uses Session::$activation
		 * @uses Session::$last_attempt
		 * @uses Session::$login_reset
		 * @uses Session::$login_limit
		 * @uses Session::$login_attempts
		 * @uses Session::$logged_in
		 * @uses Session::$start
		 * @uses Session::$cexpire
		 * @uses Session::$cname
		 * @uses Session::log_activity
		 * @uses Session::salt
		 * @uses Generic::parse_seconds
		 * @uses WebPageLite::$user
		 * @uses Base::$webpage
		 * @uses Data::$id
		 * @throws Exception if it couldn't authenticate or if a MySQL error occurred.
		 * @return bool whether the user is now logged in.
		 */
		public function login( $username, $password, $remember = true, $fld = 'user_name' )
		{
			// initialize variables
			$username 		= addslashes( $username );
			$last_attempt	= time() - $this->last_attempt;
	
			// check log in information against database
			$result = mysql_query( "SELECT * FROM " . Database::USERS . " WHERE {$fld} = '{$username}' LIMIT 1" );
			if ( !( $row = mysql_fetch_array( $result, MYSQL_ASSOC ) ) )
				throw new \Exception( "The user '" . stripslashes( $username ) . "' does not exist." );
			else if ( $this->activation && (int)$row[ 'user_enabled' ] == 0 )
				throw new \Exception( "This account has not been activated yet." );
			else if ( $this->activation && (int)$row[ 'user_enabled' ] == -1 )
				throw new \Exception( "This account is no longer active." );
			else if ( $last_attempt < $this->login_reset && $this->login_attempts >= $this->login_limit )
				throw new \Exception( "You have exceeded the number of times you can attempt to log in. Please try again in " . Generic::parse_seconds( $this->login_reset - $last_attempt ) . "." );
			else if ( $row[ 'user_password' ] != md5( $password . ( isset( $row[ 'user_salt' ] ) ? $row[ 'user_salt' ] : "" ) ) )
			{
				if ( $last_attempt >= $this->login_reset )
					$this->login_attempts = 0;
				
				// update the user id in the current session
				if ( !mysql_query( "UPDATE " . self::TABLE . " SET session_login_attempts = '" . ++$this->login_attempts . "', session_last_attempt = '" . time() . "' WHERE session_id = '{$this->id}'" ) )
					throw new \Exception( mysql_error() );
				else
					throw new \Exception( "That password is not correct." );
			}
			else 
			{
				
				// take the data and store it in the object
				$this->webpage->user = new User( $row[ 'user_id' ], $this->webpage );
				
				// update the salt for security in the user's account
				if ( isset( $row[ 'user_salt' ] ) )
				{
					$salt = $this->salt();
					if ( !mysql_query( "UPDATE " . Database::USERS . " SET user_salt = '{$salt}', user_password = '" . md5( $password . $salt ) . "' WHERE user_id = '{$this->user}'" ) )
						throw new \Exception( mysql_error() );
				}
				
				// to guard against session fixation, regenerate the session id
				session_regenerate_id( true );
				$new_sid = session_id();
				$sql = "UPDATE " . self::TABLE . " SET session_logged_in = 1, session_login_attempts = 0, session_last_attempt = '" . time() . "', session_user = '" . $this->webpage->user->id . "', session_user_agent = '" . md5( $_SERVER[ 'HTTP_USER_AGENT' ] ) . "', session_id = '{$new_sid}' WHERE session_id = '{$this->id}'";

				// update the user id in the current session
				if ( !mysql_query( $sql ) )
					throw new \Exception( mysql_error() );
	
				// cookies will preserve sessions
				if ( $remember )
					setcookie( $this->cname, $new_sid, $this->start + $this->cexpire, self::CPATH );
				$this->id = $new_sid;
	
				// log the action
				if ( !$this->log_activity( "{EMAIL} logged in." ) )
					throw new \Exception( mysql_error() );
	
				return $this->logged_in = true;
			}
		}
		
		/**
		 * Logs the given message into the database.
		 *
		 * @param string the message to enter. Can use variables for the following parameters.
		 * @param string the page_id. (Default = "")
		 * @param string the MySQL table. (Default = "")
		 * @param string the table index. (Default = "")
		 * @param mixed the id of the row. (Default = 0)
		 * @param Array GET paramters for the page. Used for linking. (Default = array())
		 * @uses Data::$id
		 * @return bool whether is was logged or not.
		 */
		public function log_activity( $msg, $page = "", $table = "", $index = "", $id = 0, $query = array() )
		{
			$msg = addslashes( $msg );
			if ( mysql_query( "INSERT INTO " . Database::ACTIVITY . " (activity_user, activity_time, activity_msg, activity_ref, activity_page, activity_table, activity_table_index, activity_query) VALUES ('" . $this->id . "','" . time() . "','{$msg}','{$id}','{$page}','{$table}','{$index}','" . serialize( $query ) . "')" ) === false )
				return false;
		
			return true;
		}
	
		/**
		 * Updates the current session or creates a new one if one does not exist yet.
		 * 
		 * @uses Base::$webpage
		 * @uses Data::$id
		 * @uses Session::$time
		 * @uses Session::$start
		 * @uses Session::$logged_in
		 * @uses Session::$login_attempts
		 * @uses Session::$last_attempt
		 * @uses Session::$ip
		 * @uses Session::$cexpire
		 * @uses Session::start
		 * @uses Generic::encode_ip
		 * @uses WebPageLite::$user
		 * @uses WebPageLite::$page_id
		 * @return int user id; NULL if one isn't available.
		 */
		public function run()
		{
			// delete expired sessions while we're searching the table.
			$expired = time() - $this->cexpire;
			mysql_query( "DELETE FROM " . self::TABLE . " WHERE session_id != '{$this->id}' AND session_time <= {$expired}" );
	
			// find the current session
			$result = mysql_query( "SELECT * FROM " . self::TABLE . " WHERE session_id = '{$this->id}' LIMIT 1" );
			if ( !( $row = mysql_fetch_array( $result, MYSQL_ASSOC ) ) )
				$this->start();
			else
			{
				$this->time     			= $time = time();
				$this->start    			= $row[ 'session_start' ];
				$this->logged_in 			= $row[ 'session_logged_in' ];
				$this->login_attempts 		= $row[ 'session_login_attempts' ];
				$this->last_attempt 		= $row[ 'session_last_attempt' ];
				
				// update user id
				if ( !$this->webpage )
					$page = "";
				else 
				{
					if ( $row[ 'session_user' ] )
						$this->webpage->user = new User( (int)$row[ 'session_user' ], $this->webpage );
					$page = $this->webpage->page_id;
				}
	
				// update any pages and times associated with this session.
				mysql_query( "UPDATE " . self::TABLE . " SET session_time = '{$time}', session_page = '" . $page . "', session_ip = '" . Generic::encode_ip( $this->ip ) . "', session_user_agent = '" . $this->user_agent . "' WHERE session_id = '{$this->id}'" );
			}
			
			return isset( $row[ 'session_user' ] ) && $row[ 'session_user' ] ? (int)$row[ 'session_user' ] : NULL;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Encode I.P.
		#----------------------------------------------------------------------------------------------------
		# Description: 	Takes the IP and turns it into a hexadecimal value; simple obscurity for 
		#				prying eyes.
		#----------------------------------------------------------------------------------------------------
		/*protected function encode_ip()
		{
			$ip_sep = $this->ip;
			
			if ( $this->ip == Website::LOCALHOST )
				$ip_sep = "localhost";
			else
			{
				$ip_sep = explode( '.', $this->ip );
				$ip_sep = sprintf( '%02x%02x%02x%02x', $ip_sep[ 0 ], $ip_sep[ 1 ], $ip_sep[ 2 ], $ip_sep[ 3 ] );
			}
			
			return $ip_sep;
		}*/
		
		/**
		 * Generates a single character to 'season' a password.
		 *
		 * @param string the starting string.
		 * @uses Generic::random_string
		 * @return string
		 */
		public function salt( $a = "" )
		{
			return $a . Generic::random_string( 1 );
		}
	
		/**
		 * This method clears the session so the user is no longer logged in.
		 *
		 * @uses Session::$cname
		 * @uses Session::$id
		 * @uses Session::log_activity
		 */
		public function logout()
		{
			setcookie( $this->cname, $this->id, ( time() - 3600 ), self::CPATH );
	
			// for good measure, regenerate the session id
			session_regenerate_id();
			$new_sid = session_id();
	
			mysql_query( "UPDATE " . self::TABLE . " SET session_logged_in = 0, session_user = 0, session_last_attempt = 0, session_id = '{$new_sid}', session_user_agent = '' WHERE session_id = '{$this->id}'" );
	
			// reset properties
			$this->id = $new_sid;
			
			// log the action
			$this->log_activity( "{EMAIL} logged out." );
		}
	
		/**
		 * This method creates a new session.
		 *
		 * @uses Generic::encode_ip
		 * @uses Session::$start
		 * @uses Session::$time
		 * @uses Session::$user_agent
		 * @uses Data::$id
		 * @uses WebPageLite::$page_id
		 */
		protected function start()
		{
			$this->start = $this->time = $time = time();
			mysql_query( "INSERT INTO " . self::TABLE . " ( session_id, session_user, session_start, session_time, session_page, session_ip, session_logged_in, session_last_attempt, session_user_agent ) VALUES ( '{$this->id}', 0, '{$time}', '{$time}', '" . $this->webpage->page_id . "', '" . Generic::encode_ip( $this->ip ) . "', 0, 0, '" . $this->user_agent . "' )" );
		}
	}
}
?>