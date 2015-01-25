<?php
namespace Core;

if ( !defined( "D_CLASS_ENTRY" ) )
{
	define( "D_CLASS_ENTRY", true );
	require_once( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.entry.php
	 *
	 * Represents an contestant entry for Rising Star
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Entry extends Data
	{
		protected $name 			= NULL;
		protected $cname 			= NULL;
		protected $handle 			= NULL;
		protected $demo 			= NULL;
		protected $type		 		= NULL;
		protected $city 			= NULL; 
		protected $state 			= NULL; 
		protected $region 			= NULL;
		protected $ages 			= NULL;
		protected $youth 			= NULL;
		protected $pinfo 			= NULL;
		protected $num 				= NULL;
		protected $instruments 		= NULL;
		protected $video 			= NULL;
		protected $video_pass 		= NULL;
		protected $video_explicit 	= NULL;
		protected $email 			= NULL;
		protected $twitter 			= NULL;
		protected $facebook 		= NULL;
		protected $website	 		= NULL;
		protected $dayphone	 		= NULL;
		protected $evephone	 		= NULL;
		protected $organization	 	= NULL;
		protected $comments	 		= NULL; 
		protected $bio				= NULL;
		protected $scores			= array();
		protected $videos			= array();
		
		const TABLE		= Database::ENTRIES;
		const PREFIX	= Database::ENTRIES_PRE;
		const QUERY		= "ORDER BY e.entry_id DESC";
		const IGNORE	= "and the this";
		const VIDEO_EXPLICIT_NO = 1;
		const VIDEO_EXPLICIT_YES = 2;
		const DEMO_ONLINE = 1;
		const DEMO_MAIL = 2;
		
		/**
		 * Get a specific field from a given row.
		 *
		 * @param string the name of the column/field.
		 * @param mixed the row identifier.
		 * @param string the column identifier. (Default = "id")
		 * @uses Database::fetch_cell
		 * @return mixed
		 */
		static public function get_field( $col, $id ) 
		{ 
			return Database::fetch_cell( self::TABLE, self::PREFIX . $col, self::PREFIX . "id = '" . (int)$id . "'" ); 
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
		static public function get_published_array( $amend = "%s", WebPage &$webpage = NULL, $flag_first = true ) { return parent::getx_published_array( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage, $flag_first ); }
		
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
			$rows = Database::select( Database::ENTRIES . " as e, " . Database::ENTRIES2COMPS . " as c", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
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
			$rows = Database::select( Database::ENTRIES . " as e, " . Database::ENTRIES2COMPS . " as c", "*", str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
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
		 * @uses Entry::$name
		 * @uses Entry::$cname
		 * @uses Entry::$demo
		 * @uses Entry::$type
		 * @uses Entry::$city
		 * @uses Entry::$state
		 * @uses Entry::$region
		 * @uses Entry::$ages
		 * @uses Entry::$youth
		 * @uses Entry::$pinfo
		 * @uses Entry::$num
		 * @uses Entry::$instruments
		 * @uses Entry::$video
		 * @uses Entry::$video_pass
		 * @uses Entry::$email
		 * @uses Entry::$twitter
		 * @uses Entry::$facebook
		 * @uses Entry::$website
		 * @uses Entry::$dayphone
		 * @uses Entry::$evephone
		 * @uses Entry::$organization
		 * @uses Entry::$comments
		 * @uses Entry::$bio
		 * @uses Entry::$scores
		 * @uses Entry::$videos
		 * @uses Entry::$video_explicit
		 * @uses Entry::set_name
		 * @uses Entry::set_cname
		 * @uses Entry::set_handle
		 * @uses Entry::set_demo
		 * @uses Entry::set_type
		 * @uses Entry::set_city
		 * @uses Entry::set_state
		 * @uses Entry::set_region
		 * @uses Entry::set_ages
		 * @uses Entry::set_youth
		 * @uses Entry::set_pinfo
		 * @uses Entry::set_num
		 * @uses Entry::set_instruments
		 * @uses Entry::set_video
		 * @uses Entry::set_video_pass
		 * @uses Entry::set_email
		 * @uses Entry::set_twitter
		 * @uses Entry::set_facebook
		 * @uses Entry::set_website
		 * @uses Entry::set_dayphone
		 * @uses Entry::set_evephone
		 * @uses Entry::set_organization
		 * @uses Entry::set_comments
		 * @uses Entry::set_bio
		 * @uses Entry::set_video_explicit
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$id
		 * @uses Data::$prefix
		 * @uses Data::$not_recorded
		 * @uses Database::select_query
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			// extra properties
			$this->not_recorded 	= array_merge( $this->not_recorded, array( 'scores', 'videos' ) );
			
			// create columns
			$this->name 			= new Column( self::TABLE, self::PREFIX, "name" );
			$this->cname 			= new Column( self::TABLE, self::PREFIX, "cname" );
			$this->demo 			= new MultiColumn( self::TABLE, self::PREFIX, "demo" );
			$this->type 			= new Column( self::TABLE, self::PREFIX, "type" );
			$this->city 			= new Column( self::TABLE, self::PREFIX, "city" );
			$this->state 			= new Column( self::TABLE, self::PREFIX, "state" );
			$this->region 			= new Column( self::TABLE, self::PREFIX, "region" );
			$this->ages 			= new Column( self::TABLE, self::PREFIX, "ages" );
			$this->youth 			= new IntColumn( self::TABLE, self::PREFIX, "youth" );
			$this->pinfo 			= new Column( self::TABLE, self::PREFIX, "pinfo" );
			$this->num 				= new Column( self::TABLE, self::PREFIX, "num" );
			$this->instruments 		= new Column( self::TABLE, self::PREFIX, "instruments" );
			$this->video 			= new URLColumn( self::TABLE, self::PREFIX, "video" );
			$this->video_pass 		= new Column( self::TABLE, self::PREFIX, "video_pass" );
			$this->email 			= new EmailColumn( self::TABLE, self::PREFIX, "email" );
			$this->twitter 			= new HandleColumn( self::TABLE, self::PREFIX, "twitter" );
			$this->facebook 		= new URLColumn( self::TABLE, self::PREFIX, "facebook" );
			$this->website 			= new URLColumn( self::TABLE, self::PREFIX, "website" );
			$this->dayphone 		= new PhoneColumn( self::TABLE, self::PREFIX, "dayphone" );
			$this->evephone 		= new PhoneColumn( self::TABLE, self::PREFIX, "evephone" );
			$this->organization 	= new Column( self::TABLE, self::PREFIX, "organization" );
			$this->comments 		= new Column( self::TABLE, self::PREFIX, "comments" );
			$this->bio	 			= new Column( self::TABLE, self::PREFIX, "bio" );
			$this->video_explicit 	= new MultiColumn( self::TABLE, self::PREFIX, "video_explicit" );
			 
			// make adjustments
			$this->twitter->min = 3;
			$this->twitter->max = 16;
			$this->email->unique = true;
			$this->video_explicit->options = array
			( 
				self::VIDEO_EXPLICIT_NO => "No", 
				self::VIDEO_EXPLICIT_YES => "Yes"
			);
			$this->video_explicit->value = self::VIDEO_EXPLICIT_NO;
			$this->demo->options = array
			( 
				self::DEMO_ONLINE => "Online Video", 
				self::DEMO_MAIL => "Mail a DVD"
			);
			$this->demo->value = self::DEMO_ONLINE;
			 
			// add columns
			$this->add_col( 'name', true, true );
			$this->add_col( 'cname', true );
			$this->add_col( 'demo' );
			$this->add_col( 'type', true );
			$this->add_col( 'city', true, true );
			$this->add_col( 'state', true );
			$this->add_col( 'region' );
			$this->add_col( 'ages', true );
			$this->add_col( 'youth' );
			$this->add_col( 'pinfo' );
			$this->add_col( 'num', true );
			$this->add_col( 'instruments' );
			$this->add_col( 'video' );
			$this->add_col( 'video_pass' );
			$this->add_col( 'video_explicit' );
			$this->add_col( 'email', true, true );
			$this->add_col( 'twitter' );
			$this->add_col( 'dayphone', true );
			$this->add_col( 'evephone', true );
			$this->add_col( 'organization' );
			$this->add_col( 'comments' );
			$this->add_col( 'bio' );
			
			if ( $data )
			{
				$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_cname( $data[ $this->prefix . 'cname' ] );
				$this->set_handle( $data[ $this->prefix . 'handle' ] );
				$this->set_demo( $data[ $this->prefix . 'demo' ] );
				$this->set_type( $data[ $this->prefix . 'type' ] );
				$this->set_city( $data[ $this->prefix . 'city' ] );
				$this->set_state( $data[ $this->prefix . 'state' ] );
				$this->set_region( $data[ $this->prefix . 'region' ] );
				$this->set_ages( $data[ $this->prefix . 'ages' ] );
				$this->set_youth( $data[ $this->prefix . 'youth' ] );
				$this->set_pinfo( $data[ $this->prefix . 'pinfo' ] );
				$this->set_num( $data[ $this->prefix . 'num' ] );
				$this->set_instruments( $data[ $this->prefix . 'instruments' ] );
				$this->set_video( $data[ $this->prefix . 'video' ] );
				$this->set_video_pass( $data[ $this->prefix . 'video_pass' ] );
				$this->set_video_explicit( $data[ $this->prefix . 'video_explicit' ] );
				$this->set_email( $data[ $this->prefix . 'email' ] );
				$this->set_twitter( $data[ $this->prefix . 'twitter' ] );
				$this->set_facebook( $data[ $this->prefix . 'facebook' ] );
				$this->set_website( $data[ $this->prefix . 'website' ] );
				$this->set_dayphone( $data[ $this->prefix . 'dayphone' ] );
				$this->set_evephone( $data[ $this->prefix . 'evephone' ] );
				$this->set_organization( $data[ $this->prefix . 'organization' ] );
				$this->set_comments( $data[ $this->prefix . 'comments' ] );
				$this->set_bio( $data[ $this->prefix . 'bio' ] );
				
				$scores = Database::select_query( "SELECT * FROM " . Database::SCORES . " WHERE entry_id = '" . (int)$this->id . "'" );
				foreach( $scores as $s )
					$this->scores[ $s[ 'competition_id' ] ][ $s[ 'round_id' ] ][] = array( 'judge' => $s[ 'judge_id' ], 'score' => $s[ 'score' ] );
					
				$this->videos = Database::select_query( "SELECT * FROM " . Database::PERFORMANCES . " WHERE performance_entry = '" . (int)$this->id . "'" );
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Entry::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$name
		 * @param string
		 * @return string
		 */
		protected function set_name( $a )			
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::cname. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$cname
		 * @param string
		 * @return string
		 */
		protected function set_cname( $a )			
		{ 
			return $this->cname->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::handle. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$handle
		 * @param string
		 * @return string
		 */
		protected function set_handle( $a )			
		{ 
			return $this->handle = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::demo. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$demo
		 * @param string
		 * @return string
		 */
		protected function set_demo( $a )			
		{ 
			return $this->demo->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Entry::type. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$type
		 * @param string
		 * @return string
		 */
		protected function set_type( $a )			
		{ 
			return $this->type->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::city. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$city
		 * @param string
		 * @return string
		 */
		protected function set_city( $a )			
		{ 
			return $this->city->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::state. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$state
		 * @param string
		 * @return string
		 */
		protected function set_state( $a )			
		{ 
			return $this->state->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::region. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$region
		 * @param string
		 * @return string
		 */
		protected function set_region( $a )			
		{ 
			return $this->region->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::ages. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$ages
		 * @param string
		 * @return string
		 */
		protected function set_ages( $a )			
		{ 
			return $this->ages->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::youth. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$youth
		 * @param string
		 * @return string
		 */
		protected function set_youth( $a )			
		{ 
			return $this->youth->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Entry::pinfo. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$pinfo
		 * @param string
		 * @return string
		 */
		protected function set_pinfo( $a )			
		{ 
			return $this->pinfo->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::num. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$num
		 * @param string
		 * @return string
		 */
		protected function set_num( $a )			
		{ 
			return $this->num->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Entry::instruments. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$instruments
		 * @param string
		 * @return string
		 */
		protected function set_instruments( $a )	
		{ 
			return $this->instruments->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::video. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$video
		 * @param string
		 * @return string
		 */
		protected function set_video( $a )			
		{ 
			return $this->video->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::video_pass. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$video_pass
		 * @param string
		 * @return string
		 */
		protected function set_video_pass( $a )		
		{ 
			return $this->video_pass->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::video_explicit. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$video_explicit
		 * @param int
		 * @return int
		 */
		protected function set_video_explicit( $a )	
		{ 
			return $this->video_explicit->value = (int)$a; 
		}
		
		/**
		 * Sets the value of Entry::email. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$email
		 * @param string
		 * @return string
		 */
		protected function set_email( $a )			
		{ 
			return $this->email->value = strtolower( trim( stripslashes( $a ) ) ); 
		}
		
		/**
		 * Sets the value of Entry::twitter. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$twitter
		 * @param string
		 * @return string
		 */
		protected function set_twitter( $a )		
		{ 
			return $this->twitter->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::facebook. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$facebook
		 * @param string
		 * @return string
		 */
		protected function set_facebook( $a )		
		{ 
			return $this->facebook->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::website. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$website
		 * @param string
		 * @return string
		 */
		protected function set_website( $a )		
		{ 
			return $this->website->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::dayphone. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$dayphone
		 * @param string
		 * @return string
		 */
		protected function set_dayphone( $a )		
		{ 
			return $this->dayphone->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::evephone. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$evephone
		 * @param string
		 * @return string
		 */
		protected function set_evephone( $a )		
		{ 
			return $this->evephone->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::organization. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$organization
		 * @param string
		 * @return string
		 */
		protected function set_organization( $a )	
		{ 
			return $this->organization->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::comments. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$comments
		 * @param string
		 * @return string
		 */
		protected function set_comments( $a )		
		{ 
			return $this->comments->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Entry::bio. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Entry::$bio
		 * @param string
		 * @return string
		 */
		protected function set_bio( $a )			
		{ 
			return $this->bio->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Inserts this object into the database as a new row.
		 *
		 * @param int User ID of the author. (Default = self::ANONYMOUS)
		 * @param bool Automatically enable the row? (Default = false)
		 * @uses Entry::gen_handle
		 * @uses Entry::$name
		 * @uses Entry::$handle
		 * @uses Data::insert
		 * @uses Data::$notes
		 * @uses Generic::random_string
		 * @return int of the new row; false if it failed.
		 */
		public function insert( $user_id = self::ANONYMOUS, $auto_enable = false )
		{
			$this->handle = $this->gen_handle( $this->name->value );
			$this->notes = Generic::random_string( 16 );
			return parent::insert( $user_id, $auto_enable );
		}
		
		/**
		 * Updates this object in the database.
		 *
		 * @param int User ID of the updater. (Default = self::ANONYMOUS)
		 * @uses Entry::gen_handle
		 * @uses Entry::$name
		 * @uses Entry::$handle
		 * @throws Exception if there was a MySQL error.
		 * @return int of the new row; false if it failed.
		 */
		public function update( $user_id = self::ANONYMOUS, $override_class = null, $id = null )
		{
			$this->handle = $this->gen_handle( $this->name->value );
			return parent::update( $user_id, $override_class, $id );
		}
		
		/**
		 * Generates a handle for the entrant based on the given string.
		 *
		 * @param string usually the name of the entrant.
		 * @return string
		 */
		public function gen_handle( $a )
		{
			$handle = explode( " ", strtolower( $a ) );
			$ignore = explode( " ", self::IGNORE );
			
			foreach( $ignore as $word )
				if ( $key = array_search( $word, $handle ) )
					$handle[ $key ] = "";
					
			$n = count( $handle );
			foreach( $handle as $index => $word )
				if ( $index < ( $n - 1 ) )
					$handle[ $index ] = substr( $word, 0, 1 );
			return preg_replace( "/[^a-z]+/", "", implode( " ", $handle ) );
		}
	}
}
?>