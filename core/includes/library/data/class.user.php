<?php
namespace Core;

if ( !defined( "D_CLASS_USER" ) )
{
	define( "D_CLASS_USER", true );
	require( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.user.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class User extends Data
	{
		protected $email 			= NULL;
		protected $entry 			= NULL;
		protected $password 		= NULL;
		protected $name 			= NULL;
		protected $title 			= NULL;
		protected $fname 			= NULL;
		protected $lname 			= NULL;
		protected $group 			= NULL;
		protected $groups			= array();
		protected $level			= 0;
		protected $salt				= "";
		protected $timezone			= "";
		protected $changed_password = false;
		protected $change_account 	= false;
		//protected $recover_secret	= "";*/
		protected $email_html		= true;
		//protected $visits			= 0;
		
		const TABLE				= Database::USERS;
		const PREFIX			= Database::USERS_PRE;
		const ANONYMOUS			= 0;
		const REG				= 1;
		const MOD				= 2;
		const ADMIN				= 3;
		const ALLOW_NONE 		= 0;
		const ALLOW_VIEW 		= 1;
		const ALLOW_EDIT 		= 2;
		const ALLOW_ADD 		= 4;
		const ALLOW_DELETE 		= 8;
		const ALLOW_UNDELETE 	= 16;
		const ALLOW_FORGET 		= 32;
		const ALLOW_ENABLE		= 64;
		const ALLOW_DISABLE		= 128;
		const ALLOW_ALL			= 255;
		const QUERY				= "1";
		const PUBQUERY			= "AND user_enabled != '0' AND user_deleted = '0' AND user_status = '1'";
		
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
		static public function get_published( $amend = "%s", WebPage $webpage = NULL, $flag_first = true ) 
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
			$rows = Database::select( self::TABLE, "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
			$data = self::getx_data( __CLASS__, $rows, $flag_first, "id", $webpage );
			
			return $data;
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
			$rows = Database::select( self::TABLE, "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
			$data = self::getx_array_data( $rows, $flag_first );
			
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
		 * @uses User::$password
		 * @uses User::$groups
		 * @uses User::set_password
		 * @uses User::set_level
		 * @uses User::set_salt
		 * @uses User::set_changed_password
		 * @uses User::get_group_parents
		 * @uses Base::$webpage
		 * @uses WebPage::$mode
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @uses Data::$not_recorded
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			// extra properties that don't go into the db
			$this->not_recorded = array_merge( $this->not_recorded, array( 'groups' ) );
			
			// define columns
			$this->def_col( 'email', $data, "EmailColumn", true, true );
			$this->def_col( 'entry', $data, "Many2OneColumn", false, array( Database::ENTRIES, Database::ENTRIES_PRE, "name" ) );
			$this->def_col( 'name', $data, "Column", true, true );
			$this->def_col( 'title', $data );
			$this->def_col( 'fname', $data );
			$this->def_col( 'lname', $data );
			$this->def_col( 'timezone', $data, "Column", true );
			$this->def_col( 'email_html', $data, "MultiColumn", true );
			$this->def_col( 'change_account', $data, "MultiColumn" );
			$this->def_col( 'group', $data, "Many2OneColumn", false, array( Database::GROUPS, Database::GROUPS_PRE, "name" ) );
			$this->def_col( 'timezone', $data, "Column" );
			
			// create columns
			$this->password		= new PassColumn( self::TABLE, self::PREFIX, "password", true, true );
			
			// make adjustments
			//$this->name->min = 3;
			//$this->fname->min = 3;
			//$this->lname->min = 3;
			//$this->email->unique = true;
			//$this->email_html->options = array( 1 => "No", 2 => "Yes" );
			//$this->email_html->value = 2;
			//$this->change_account->options = array( 1 => "No", 2 => "Yes" );
			//$this->change_account->value = 2;
			//$this->timezone->value = "-5.0";
			
			// add columns
			$this->add_col( 'password', ( $this->webpage && $this->webpage->mode == Website::MODE_NEW ) );
			
			if ( $data )
			{
				$this->set_password( $data[ $this->prefix . 'password' ] );
				$this->set_level( $data[ $this->prefix . 'level' ] );
				$this->set_salt( $data[ $this->prefix . 'salt' ] );
				$this->set_changed_password( $data[ $this->prefix . 'changed_password' ] );
				
				if ( $this->group && $this->group->value )
					$this->groups = $this->get_group_parents( $this->group->value );
			}
			
			// record page visit
			//$this->record_page_visit();
			
			return $data;
		}
		
		/**
		 * Sets the value of User::password. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses User::$password
		 * @param string
		 * @return string
		 */
		protected function set_password( $a ) 			
		{ 
			return $this->password->value = $a; 
		}
		
		/**
		 * Sets the value of User::level. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses User::$level
		 * @param int
		 * @return int
		 */
		protected function set_level( $a )				
		{ 
			return $this->level = (int)$a; 
		}
		
		/**
		 * Sets the value of User::salt. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses User::$salt
		 * @param string
		 * @return string
		 */
		protected function set_salt( $a )				
		{ 
			return $this->salt = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of User::changed_password. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses User::$changed_password
		 * @param string
		 * @return string
		 */
		protected function set_changed_password( $a )	
		{ 
			return $this->changed_password = (bool)$a; 
		}
		
		/**
		 * Determines whether the user's level is at least self::ADMIN.
		 *
		 * @uses User::$level
		 * @return bool
		 */
		public function is_admin()
		{
			return $this->level >= self::ADMIN;
		}
		
		/**
		 * Get the ancestor groups of the current group ID recursively.
		 *
		 * @uses Database::fetch_cell
		 * @uses User::get_group_parents
		 * @return Array
		 */
		protected function get_group_parents( $id )
		{
			if ( !( $parent = Database::fetch_cell( Database::GROUPS, "group_parent", "group_id = '{$id}' AND group_enabled != '0' AND group_deleted = '0'" ) ) )
				$groups = array( $id );
			else
			{
				$groups = array( $id );
				$groups = array_merge( $groups, $this->get_group_parents( $parent ) );
			}
			
			return $groups;
		}
		
		/**
		 * This function checks to see if the user has permission to perform the given act.
		 *
		 * This function checks to see if the user has permission to perform the given act.
		 * <code>
		 * $main->user->may('view', 'users'); // would return 'true' or 'false'
		 * </code>
		 *
		 * @param mixed the name of the act or the value as represented by the ALLOW_* constants.
		 * @param string the name of the table or area that needs to be compared.
		 * @pararm int check a specific row instead of the table. (Default = NULL)
		 * @uses Database::get_prefix
		 * @uses Database::fetch_cell
		 * @return bool
		 */
		public function may( $act, $access, $row = NULL )
		{
			if ( $this->level >= User::ADMIN )
				$may = true;
			else
			{
				// initialize variables
				$may 	= false;
				$access = trim( strtolower( $access ) );
				$act 	= trim( strtolower( $act ) );
				$prefix = Database::get_prefix( $access );
				$prefix .= $prefix ? "_" : ""; 
				
				// translate english into values
				switch( $act )
				{
					case Website::ACTION_VIEW: 		$act = self::ALLOW_VIEW; break;
					case Website::ACTION_EDIT: 		$act = self::ALLOW_EDIT; break;
					case Website::ACTION_ADD: 		$act = self::ALLOW_ADD; break;
					case Website::ACTION_DELETE: 	$act = self::ALLOW_DELETE; break;
					case Website::ACTION_UNDELETE: 	$act = self::ALLOW_UNDELETE; break;
					case Website::ACTION_FORGET: 	$act = self::ALLOW_FORGET; break;
					case Website::ACTION_ENABLE: 	$act = self::ALLOW_ENABLE; break;
					case Website::ACTION_DISABLE: 	$act = self::ALLOW_DISABLE; break;
					default:						$act = (int)$act;
				}
				
				// check against database
				foreach( $this->groups as $g )
				{
					$result = mysql_query( "SELECT perm_value, perm_rows, perm_personal FROM " . Database::PERM. " WHERE perm_access = '{$access}' AND perm_group = '{$g}'" );
					if ( $perm_row = mysql_fetch_array( $result, MYSQL_ASSOC ) )
					{
						// check for author's rights, before checking general rights
						if ( $perm_row[ 'perm_personal' ] ) 
						{
							if ( !$row && ( $act != self::ALLOW_FORGET ) )
							{
								$may = true;
								break;
							}
							else if ( $works = Database::fetch_cell( $access, "{$prefix}id", "{$prefix}author = '{$this->id}'" ) )
							{
								if ( !$row || ( $works == $row ) )
								{
									$may = true;
									break;
								}
							}
						}
						
						// if whole table isn't accessible, try the specific row
						if ( $act & $perm_row[ 'perm_value' ] )
						{
							$may = true;
							break;
						}
						else if ( $perm_row[ 'perm_rows' ] )
						{
							$perm = explode( ";", substr( $perm_row[ 'perm_rows' ], 0, ( strlen( $perm_row[ 'perm_rows' ] ) - 1 ) ) );
		
							// the formula is {id:access;id2:access}
							// if the user can {$act} a specific row but not the whole table, access is granted, so
							// being specific with each row is essential.
							foreach( $perm as $p )
							{
								$p = explode( ":", $p );
								if ( ( $row && ( $p[ 0 ] == $row ) && ( $act & $p[ 1 ] ) ) || ( is_null( $row ) && $act & $p[ 1 ] ) )
								{
									$may = true;
									break;
								}
							}
						}
					}
				}
			}
			
			return $may;
		}
		
		/**
		 * Whether or not the current user can log in even if the website is disabled.
		 *
		 * @uses User::$level
		 * @uses User::$groups
		 * @return bool
		 */
		public function may_always_login()
		{
			// administrators may log in
			$may = $this->level >= self::ADMIN ? true : false;
			
			// check for permissions within the user group (start with the current)
			if ( !$may )
			{
				$groups = array_reverse( $this->groups );
				foreach( $this->groups as $g )
				{
					$result = mysql_query( "SELECT * FROM " . Database::GROUPS . " WHERE group_id = '{$g}' LIMIT 1" );
					if ( $pass = Database::fetch_cell( Database::GROUPS, "group_may_always_login", "group_id = '{$g}'" ) )
					{
						if ( $pass == -1 )
							break;
						else if ( $pass == 1 )
						{
							$may = true; 
							break;
						}
					}
				}
			}
			
			return $may;
		}
		
		/*
		 * Check to see if user is a moderator of the given table
		 *
		 * @author Travis Shelton <tshelton@wnit.org>
		 * 
		 * @param string The name of the table.
		 * @param Webpage Instance of a webpage object.
		 * @uses Database::fetch_cell
		 * @return bool
		 */
		public function is_moderator_of( $table, Webpage &$webpage = null )
		{
			$result = false;
			
			if ( !$webpage )
				$webpage = &$this->webpage;
			
			if ( $webpage && $webpage->db->fetch_cell( Database::MODERATORS, Database::MODERATORS_PRE . "id", Database::MODERATORS_PRE . "user = " . (int)$this->id ) )
				$result = true;
				
			$result |= $this->is_admin();

			return $result;
		}
		
		
		#--------------------------------------------------------------------------------------------------
		# * Notify
		#--------------------------------------------------------------------------------------------------
		/*public function notify( $msg, $user_id = false, $page = 0, $table = "", $index = "", $id = 0, $query = array() )
		{
			// initialize variables
			$user_id = $user_id ? $user_id : $this->id;
			$msg = addslashes( $msg );
			
			return (bool)mysql_query( "INSERT INTO " . Database::NOTICES . " (notice_user, notice_time, notice_msg, notice_ref, notice_page, notice_table, notice_table_index, notice_query) VALUES ('" . $user_id . "','" . time() . "','{$msg}','{$id}','{$page}','{$table}','{$index}','" . serialize( $query ) . "')" );
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# * Parse Activity
		#--------------------------------------------------------------------------------------------------
		/*public function parse_activity( $row, &$site )
		{
			return $this->parse_note( $row, 'activity', $site );
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# * Parse Notice
		#--------------------------------------------------------------------------------------------------
		/*public function parse_notice( $row, &$site )
		{
			return $this->parse_note( $row, 'notice', $site );
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# *Parse Note
		#--------------------------------------------------------------------------------------------------
		/*public function parse_note( $row, $prefix, &$site )
		{
			// initialize variables
			$ref = $page = NULL;
			$tag = "{CELL:";
			$text = "test";
			// make sure to include user data
			if ( !isset( $row[ 'user_id' ] ) )
			{
				$row2 	= Database::fetch( Database::USERS, "*", "user_id = '" . $row[ $prefix . '_user' ] . "' AND user_deleted = '0'" );
				$row	= array_merge( $row, $row2 );
			}
			
			// look up any reference to another table
			if ( $row[ $prefix . '_ref' ] && $row[ $prefix . '_table' ] && $row[ $prefix . '_table_index' ] )
				$ref = Database::fetch( $row[ $prefix . '_table' ], "*", $row[ $prefix . '_table_index' ] . " = '" . $row[ $prefix . '_ref' ] . "'" );
			
			// parse variables
			$text = $row[ $prefix . '_msg' ];
			
			$prof = "#";
			$text = str_replace( "{EMAIL}", "<a href=\"{$prof}\" class=\"act-username\" tabindex=\"" . $_tab++ . "\">" . ( $row[ 'user_id' ] == $this->id ? "You" : $row[ 'user_email' ] ) . "</a>", $text );
			$text = str_replace( "{FIRSTNAME}", "<a href=\"{$prof}\" class=\"act-firstname\" tabindex=\"" . $_tab++ . "\">" . $row[ 'user_fname' ] . "</a>", $text );
			$text = str_replace( "{LASTNAME}", "<a href=\"{$prof}\" class=\"act-lastname\" tabindex=\"" . $_tab++ . "\">" . $row[ 'user_lname' ] . "</a>", $text );
			$text = str_replace( "{NAME}", "<a href=\"{$prof}\" class=\"act-firstname\" tabindex=\"" . $_tab++ . "\">" . ( $row[ 'user_fname' ] . ( $row[ 'user_lname' ] ? " " . $row[ 'user_lname' ] : "" ) ) . "</a>", $text );
			if ( $this->id == $row[ 'user_id' ] )
				$text = str_replace( "his or her", "your", $text );
			
			// define page link
			if ( !isset( $site->urls[ $row[ $prefix . '_page' ] ] ) )
				$text = str_replace( "{PAGE}", ( $row[ $prefix . '_table' ] ?  $row[ $prefix . '_table' ] : "an unknown page (" . $row[ $prefix . '_page' ] . ")" ), $text );
			else
			{
				// find the title of the show, if it's available
				$pages 		= $show_id ? array( ADMIN_EPISODES => "Episodes", ADMIN_GUESTS => "Guests", ADMIN_SEGMENTS => "Segments", ADMIN_COMPANIES => "Companies" ) : array();
				$title		= $row[ $prefix . '_table' ]; //$_title[ $row[ $prefix . '_page' ] ];
				
				foreach( $pages as $p => $title )
				{
					if ( ( $row[ $prefix . '_page' ] == $p ) && isset( $this->show_id ) && $this->show_id )
					{
						if ( $result = stripslashes( Database::fetch_cell( Database::PROGRAMS, "program_title", "program_id = '{$this->show_id}'" ) ) )
						{
							$title = "{$result} {$title}";
							break;
						}
					}
				}
				
				// show what page was involved.
				$page = $row[ $prefix . '_page' ];
				$query = $row[ $prefix . '_query' ] ? unserialize( $row[ $prefix . '_query' ] ) : array();
				if ( $this->may( 'view', $row[ $prefix . '_table' ] ) )
					$text = str_replace( "{PAGE}", "<a href=\"" . $site->anchor( $page, $query ) . "\" class=\"act-page\" tabindex=\"" . $this->tab++ . "\">" . $title . "</a>", $text );
				else
					$text = str_replace( "{PAGE}", $title, $text );
			}
			
			// check for "CELL" reference
			if ( $open = strpos( $text, $tag ) )
			{
				$close 	= strpos( $text, "}", $open );
				$len	= strlen( $tag );
				$start	= $open + $len;
				$end	= $close - $start;
				$col 	= substr( $text, $start, $end ); 
				
				// check for references to another table
				if ( $col )
				{
					$text2	= substr( $text, 0, $open );
					
					// if the row exists, create a link; otherwise, describe the row by its index
					if ( !$ref )
						$text2 .= "row #" . $row[ $prefix . '_ref' ];
					else
					{
						$query = array_merge( unserialize( $row[ $prefix . '_query' ] ), array( 'mode' => Website::MODE_EDIT, 'id' => $row[ $prefix . '_ref' ] ) );
						if ( $this->may( 'edit', $row[ $prefix . '_table' ], $perm_row ) )
							$text2 .= "<a href=\"" . ( $page ? $site->anchor( $page, $query ) : "#") . "\" class=\"act-cell\" tabindex=\"" . $this->tab++ . "\">" . ( isset( $ref[ $col ] ) ? $ref[ $col ] : "row #" . $row[ $prefix . '_ref' ] ) . "</a>";
						else
							$text2 .= $ref[ $col ];
					}
					
					$text2 	.= substr( $text, $close + 1 );
					$text	= $text2;
				}
			}
			
			return $text;
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# * Log Activity
		#--------------------------------------------------------------------------------------------------
		/*public function log_activity( $msg, $page = 0, $table = "", $index = "", $id = 0, $query = array() )
		{
			$msg = addslashes( $msg );
			if ( mysql_query( "INSERT INTO " . Database::ACTIVITY . " (activity_user, activity_time, activity_msg, activity_ref, activity_page, activity_table, activity_table_index, activity_query) VALUES ('" . $this->id . "','" . time() . "','{$msg}','{$id}','{$page}','{$table}','{$index}','" . serialize( $query ) . "')" ) === false )
				return false;
		
			return true;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Record Page Visit
		#----------------------------------------------------------------------------------------------------
		/*protected function record_page_visit()
		{
			// set page visit
			if ( $this->webpage->page_id && $this->id > 0 )
			{
				// initialize
				$mode 		= Globals::get( 'mode' );
				$show_id 	= (int)Globals::get( 'show' );
				$row_id 	= addslashes( Globals::get( 'id' ) );
				$condition 	= "user_id = '" . $this->id . "' AND page_id = '" . $this->webpage->page_id . "' AND page_mode = '" . addslashes( $mode ) . "' AND page_show = '" . $show_id . "' AND page_row = '" . $row_id . "'";
				$visits 	= $this->webpage->db->fetch_cell( Database::USERS2PAGES, "page_visits", $condition );
				
				// view is the default, and i don't want duplicates, so let's just cut it out
				if ( $mode == "view" )
					$mode = "";
				
				// are we updating an old visit or creating a new record?
				if ( $visits === NULL )
					$this->webpage->db->query( "INSERT INTO " . Database::USERS2PAGES . " (user_id, page_id, page_mode, page_show, page_row, page_visits, page_lastvisit) VALUES ( '" . $this->id . "', '" . $this->webpage->page_id . "', '" . $mode . "','" . $show_id . "','" . $row_id . "', '1', '" . time() . "' )" );
				else
					$this->webpage->db->query( "UPDATE " . Database::USERS2PAGES . " SET page_visits = '" . ( $visits + 1 ) . "', page_lastvisit = '" . time() . "' WHERE " . $condition );
			}
			
			// get page visits
			$this->visits = $this->webpage->db->select( Database::USERS2PAGES, "*", "user_id = '" . $this->id . "'" );
		}*/
	}
}
?>