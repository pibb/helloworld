<?php
namespace Core;

if ( !defined( "D_CLASS_TVSSAPI" ) )
{
	define( "D_CLASS_TVSSAPI", true );
	require( "class.pbsapi.php" );

	/**
 	 * File: class.tvssapi.php
	 *
 	 * @package Library/API's
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedules+Version+2
	 * @version 1.0.0
	 */
	class TVSS_API extends PBS_API
	{
		public $channels = array( "34.1", "34.2" );
		
		const ENDPOINT 		= "http://services.pbs.org/tvss/";
		const API_ID 		= "wnit-d7cee944a6a85440324bcf6cf02fb24020b8fc0d396df7e60a877541";
		const PROGRAMS_CHECK= 604800; // 1 week
		const NOW_WINDOW	= 600; // 3 hours
		const TONIGHT		= 1900; // 7 oclock
		const IGNORE		= "experience-michiana,economic-outlook,harbor-lights-tv,outdoor-elements,dinner-and-a-book,ask-an-expert,michianas-rising-star,big-questions,politically-speaking";
		const DATE			= "Ymd";
		
		/**
		 * Class constructor.
		 *
		 * @uses TVSS_API::$api_id
		 */
		public function __construct()
		{
			$this->api_id = self::API_ID;
		}
		
		/**
		 * Turns "YYYYmmdd" into a timestamp.
		 * 
		 * @param string the date to be interpretted.
		 * @uses TVSS_API::extract_date
		 * @return int the timestamp.
		 */
		public function datetotime( $date )
		{
			list( $month, $day, $year ) = $this->extract_date( $date );
			return strtotime( "{$month}/{$day}/{$year}" );
		}
		
		/**
		 * Gets the date (self::DATE) before the given date.
		 * 
		 * @param string the date to be interpretted.
		 * @uses TVSS_API::move_date
		 * @return int the timestamp.
		 */
		public function yesterday( $date )
		{
			return $this->move_date( $date, -1 );
		}
		
		/**
		 * Gets the date (self::DATE) the number of days before or after the given date.
		 * 
		 * @param string the date to be interpretted.
		 * @param int the number of days to move. Negative numbers go into the past.
		 * @uses TVSS_API::datetotime
		 * @return string the date in the form: self::DATE.
		 */
		public function move_date( $date, $days )
		{
			$date = $this->datetotime( $date ) + ( $days * Website::SECS_IN_DAY );
			return date( self::DATE, $date );
		}
		
		/**
		 * Gets the date (self::DATE) after the given date.
		 * 
		 * @param string the date to be interpretted.
		 * @uses TVSS_API::move_date
		 * @return int the timestamp.
		 */
		public function tomorrow( $date )
		{
			return $this->move_date( $date, 1 );
		}
		
		/**
		 * Turns the given date (YYYYmmdd) and turns it into an array( month, day, year ).
		 * 
		 * @param string the date to be interpretted.
		 * @return Array array( $month, $day, $year )
		 */
		public function extract_date( $date )
		{
			$year = substr( $date, 0, 4 );
			$month = substr( $date, 4, 2 );
			$day = substr( $date, 6 );
			return array( $month, $day, $year );
		}
		
		/**
		 * Get the programming schedule for the given date and time.
		 * 
		 * @param string the date to be interpretted.
		 * @param int the start time (HHMM). (Default = 0)
		 * @param int the end time (HHMM). (Default = 2400)
		 * @uses PBS_API::make_request
		 * @uses TVSS_API::extract_date
		 * @uses TVSS_API::$channels to filter the digital channel feed.
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-FullListingsforaDay
		 * @return Array JSON result from request.
		 */
		public function schedule( $date, $start = 0, $end = 2400, $limit = false )
		{
			// initialize variables
			$a = array();
			$results = json_decode( $this->make_request( self::ENDPOINT . "wnit/day/{$date}/", true ) );
			if ( $results )
			{
				list( $month, $day, $year ) = $this->extract_date( $date );
				foreach( $results->feeds as $feed )
				{
					if ( in_array( $feed->digital_channel, $this->channels ) )
					{
						$a[ $feed->digital_channel ] = array();
						foreach( $feed->listings as $show )
						{
							if ( $show->start_time >= $start && $show->start_time < $end )
							{
								$hour = (int)substr( $show->start_time, 0, 2 );
								$s = array();
								$s[ 'name' ] 				= isset( $show->title ) ? $show->title : "";
								$s[ 'description' ] 		= isset( $show->description ) ? $show->description : "";
								$s[ 'id' ] 					= isset( $show->program_id ) ? $show->program_id : "";
								$s[ 'mhour' ]				= $hour;
								$s[ 'hour' ]				= $hour > 12 ? ( $hour - 12 ) : ( $hour == 0 ? 12 : $hour );
								$s[ 'minute' ]				= substr( $show->start_time, 2, 2 );
								$s[ 'pm' ]					= $hour >= 12 ? "PM" : "AM";
								$s[ 'cc' ]					= isset( $show->closed_captions ) ? $show->closed_captions : false;
								$s[ 'hd' ]					= isset( $show->hd ) ? $show->hd : false;
								$s[ 'animated' ]			= isset( $show->animated ) ? $show->animated : false;
								$s[ 'stereo' ]				= isset( $show->stereo ) ? $show->stereo : false;
								$s[ 'special_warnings' ]	= isset( $show->special_warnings ) ? $show->special_warnings : "";
								$s[ 'minutes' ]				= isset( $show->minutes ) ? $show->minutes : "";
								$s[ 'episode_title' ]		= isset( $show->episode_title ) ? $show->episode_title : "";
								$s[ 'episode_description' ]	= isset( $show->episode_description ) ? $show->episode_description : "";
								$s[ 'show_id' ]				= isset( $show->show_id ) ? $show->show_id : "";
								$s[ 'program_id' ]			= isset( $show->program_id ) ? $show->program_id : "";
								$s[ 'stamp' ]				= strtotime( "{$month}/{$day}/{$year} " . $s[ 'hour' ] . ":" . $s[ 'minute' ] . $s[ 'pm' ] );
								$a[ $feed->digital_channel ][] = $s;
								if ( $limit && count( $a[ $feed->digital_channel ] ) >= $limit ) break;
							}
						}
					}
				}
			}
			return $a;
		}
		
		/**
		 * Gets the current programming schedule in the self::NOW_WINDOW.
		 * 
		 * @uses TVSS_API::schedule
		 * @return Array JSON result from request.
		 */
		public function schedule_now()
		{
			// determine the relative present (rounding down for show currently on)
			$now_hour = date( "G" );
			$now_min = (int)date( "i" ) >= 30 ? "30" : "00";
			$now = $now_hour . $now_min;
			
			// initialize other variables
			$a = array();
			$today_str = date( self::DATE );
			$tomorrow_str = date( self::DATE, time() + Website::SECS_IN_DAY );
			$late = $now + self::NOW_WINDOW - 2400;
			$late_night = $late > 0;
			$tonight = $now + self::NOW_WINDOW;
			
			// midnight will reset the clock
			if ( $tonight >= 2400 )
				$tonight = 0;
			
			// get today's schedule and get upcoming shows
			$a = $this->schedule( $today_str, $now, $tonight, 6 );
			
			// if our schedule also mentions late night, then grab tomorrow's schedule, too
			if ( $late_night )
				$a = array_merge( $a, $this->schedule( $tomorrow_str, 0, $late, 6 ) );
			
			
			// sort and return results
			ksort( $a );

			return $a;
		}
		
		/**
		 * Gets the programming schedule for self::TONIGHT to midnight.
		 * 
		 * @uses TVSS_API::schedule
		 * @return Array JSON result from request.
		 */
		public function schedule_tonight()
		{
			// initialize variables
			$a = $this->schedule( date( self::DATE ), self::TONIGHT, 2399 );
			
			// sort and return results
			ksort( $a );
			return $a;
		}
		
		/**
		 * Gets a current list of PBS programs known to the scheduler.
		 * 
		 * @uses PBS_API::make_request
		 * @return Array JSON result from request.
		 */
		public function list_programs()
		{
			return json_decode( $this->make_request( self::ENDPOINT . "programs/" ) );
		}
		
		/**
		 * Tries to find the given program by its ID.
		 * 
		 * @param int the program ID.
		 * @uses PBS_API::make_request
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-ProgramInformation
		 * @return Array JSON result from request.
		 */
		public function get_program( $id )
		{
			return json_decode( $this->make_request( self::ENDPOINT . "wnit/upcoming/program/{$id}/", true ) );
		}
		
		/**
		 * Tries to find the given program by its name (full or partial).
		 * 
		 * @param string terms in the program name.
		 * @uses TVSS_API::search
		 * @return Array JSON result from request.
		 */
		public function search_program( $name )
		{
			$result = NULL;
			$name = trim( $name );
			
			if ( $name )
			{
				// 2 letter or 1 letter words aren't permitted in the search
				$terms = explode( " ", $name );
				$new_terms = array();
				foreach( $terms as $index => $token )
					if ( strlen( $token ) >= 3 )
						$new_terms[] = $token;
				$terms = implode( " ", $new_terms );
				$name = str_replace( "%20", " ", $name );
				
				if ( $programs = $this->search( $terms ) )
					foreach( $programs->program_results as $index => $p )
						if ( strtolower( $p->title ) == strtolower( $name ) )
							$result = $programs->program_results[ $index ];
			}
			
			return $result;
		}
		
		/**
		 * Get shows that match the given search terms.
		 * 
		 * @param string terms in the show.
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-ListingSearch
		 * @return Array JSON result from request.
		 */
		public function search( $terms )
		{
			return json_decode( file_get_contents( self::ENDPOINT . "wnit/search/{$terms}/" ) );
		}
		
		/**
		 * Get the list of channels available to us.
		 * 
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-ChannelLookup
		 * @return Array JSON result from request.
		 */
		public function get_channels()
		{
			return json_decode( file_get_contents( self::ENDPOINT . "wnit/channels/" ) );
		}
		
		/**
		 * Get the list of channels available to a given zipcode.
		 * 
		 * @param string the zipcode to match.
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-ChannelLookupbyZipcode
		 * @return Array JSON result from request.
		 */
		public function get_channels_by_zip( $zip )
		{
			return json_decode( file_get_contents( self::ENDPOINT . "wnit/channels/zip/{$zip}/" ) );
		}
		
		/**
		 * Get the PBS Kids programs that will be airing today.
		 * 
		 * @uses PBS_API::make_request
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-KIDSEndpoints
		 * @return Array JSON result from request.
		 */
		public function kids_today()
		{
			return json_decode( $this->make_request( self::ENDPOINT . "wnit/today/kids/", true ) );
		}
		
		/**
		 * Get the PBS Kids programs that will be airing on the given date.
		 * 
		 * @uses PBS_API::make_request
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-KIDSEndpoints
		 * @return Array JSON result from request.
		 */
		public function kids_date( $date )
		{
			return json_decode( $this->make_request( self::ENDPOINT . "wnit/day/{$date}/kids/", true ) );
		}
		
		/**
		 * Get the PBS Kids programs that match the given terms.
		 * 
		 * @param string the terms to match against.
		 * @uses PBS_API::make_request
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-KIDSEndpoints
		 * @return Array JSON result from request.
		 */
		public function search_kids( $terms )
		{
			return json_decode( $this->make_request( self::ENDPOINT . "wnit/search-kids/{$terms}/", true ) );
		}
		
		/**
		 * Get the show by ID.
		 * 
		 * @param int the ID to match against.
		 * @uses PBS_API::make_request
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-ShowInformation
		 * @return Array JSON result from request.
		 */
		public function get_show( $id )
		{
			return json_decode( $this->make_request( self::ENDPOINT . "wnit/upcoming/show/{$id}/", true ) );
		}
		
		/**
		 * Updates the database with an a list of current programs on the schedule. Results are cached.
		 * 
		 * @param int the timestamp of the last check. Leave empty to force a recheck. (Default = 0/)
		 * @uses PBS_API::make_request
		 * @uses TVSS_API::$channels
		 * @uses Database::get_total
		 * @see https://projects.pbs.org/confluence/display/tvsapi/TV+Schedule+Methods#TVScheduleMethods-FullListingsforaDay
		 */
		public function update_programs( $last_check = 0 )
		{
			$now = time();
			if ( $now - $last_check >= self::PROGRAMS_CHECK )
			{
				@mysql_query( "DELETE FROM " . Database::NATPROGRAMS . " WHERE natprogram_kids = '0'" );
				$programs = array();
				$days = array();
				$days[ 0 ] = date( self::DATE );
				$days[ 1 ] = date( self::DATE, ( strtotime( $days[ 0 ] ) + Website::SECS_IN_DAY ) );
				$days[ 2 ] = date( self::DATE, ( strtotime( $days[ 1 ] ) + Website::SECS_IN_DAY ) );
				$days[ 3 ] = date( self::DATE, ( strtotime( $days[ 2 ] ) + Website::SECS_IN_DAY ) );
				$days[ 4 ] = date( self::DATE, ( strtotime( $days[ 3 ] ) + Website::SECS_IN_DAY ) );
				$days[ 5 ] = date( self::DATE, ( strtotime( $days[ 4 ] ) + Website::SECS_IN_DAY ) );
				$days[ 6 ] = date( self::DATE, ( strtotime( $days[ 5 ] ) + Website::SECS_IN_DAY ) );
				$ignore = explode( ",", self::IGNORE );
				
				// we check a week's worth because each day is different
				foreach( $days as $day )
				{
					$results = json_decode( $this->make_request( self::ENDPOINT . "wnit/day/{$day}/", true ) );;
					// the only way to get a list of kids programs is to check today's schedule and add any new records if we find them
					foreach( $results->feeds as $feed )
					{
						// for some reason, there's an extra channel we don't need to look at (34)
						if ( in_array( $feed->digital_channel, $this->channels ) )
						{
							foreach( $feed->listings as $item )
							{
								$exists = Database::get_total( Database::NATPROGRAMS, "WHERE natprogram_pid = '" . (int)$item->program_id . "'" );
								if ( !$exists && $item->program_id && !in_array( $item->program_id, $programs ) )
									$programs[] = $item->program_id;
							}
						}
					}
				}
				
				sort( $programs );
				$columns = array();
				$columns[] = array( 'title', 'name' );
				$columns[] = array( 'description', 'description' );
				$columns[] = array( 'program_id', 'pid' );
				
				foreach( $programs as $index )
				{
					$p = $this->get_program( $index );
					
					// get valid column info
					$cols = $values = array();
					foreach( $columns as $c )
					{
						list( $property, $column ) = $c;
						if ( isset( $p->$property ) )
						{
							$cols[] = 'natprogram_' . $column;
							$values[] = "'" . addslashes( $p->$property ) . "'";
						}
					}
					
					// create a slug
					$slug = strtolower( trim( preg_replace( '/\W/',' ', str_replace( "'", "", str_replace( '"', "", $p->title ) ) ) ) );
					$slug = preg_replace( '/\s+/','-', $slug );
					if ( !in_array( $slug, $ignore ) )
					{
						// some of the info is hidden in any upcoming episodes
						$nola = "";
						if ( isset( $p->upcoming_episodes ) && $p->upcoming_episodes[ 0 ] )
						{
							$episode = &$p->upcoming_episodes[ 0 ];
							$nola = isset( $episode->nola_root ) ? $episode->nola_root : "";
						}
						
						// record time
						$cols[] = "natprogram_slug";
						$cols[] = "natprogram_nola";
						$cols[] = "natprogram_created";
						$cols[] = "natprogram_enabled";
						$values[] = "'" . addslashes( $slug ) . "'";
						$values[] = "'" . addslashes( $nola ) . "'";
						$values[] = "'" . addslashes( $now ) . "'";
						$values[] = "'" . addslashes( $now ) . "'";
						
						// insert new row
						@mysql_query( "INSERT INTO " . Database::NATPROGRAMS . " (" . implode( ", ", $cols ) . ") VALUES (" . implode( ", ", $values ) . ")" );
					}
				}
			}
		}
		
		/**
		 * Updates the database with an a list of current PBS Kids programs on the schedule. Results are cached.
		 * 
		 * @param int the timestamp of the last check. Leave empty to force a recheck. (Default = 0/)
		 * @uses TVSS_API::kids_date
		 * @uses TVSS_API::$channels
		 * @uses Database::get_total
		 */
		public function update_kids( $last_check = 0 )
		{
			$now = time();
			if ( $now - $last_check >= self::PROGRAMS_CHECK )
			{
				@mysql_query( "DELETE FROM " . Database::NATPROGRAMS . " WHERE natprogram_kids = '1'" );
				@mysql_query( "ALTER TABLE " . Database::NATPROGRAMS . " AUTO_INCREMENT = 1" );
				$newprograms = array();
				$days = array();
				$days[ 0 ] = date( self::DATE );
				$days[ 1 ] = date( self::DATE, ( strtotime( $days[ 0 ] ) + Website::SECS_IN_DAY ) );
				$days[ 2 ] = date( self::DATE, ( strtotime( $days[ 1 ] ) + Website::SECS_IN_DAY ) );
				$days[ 3 ] = date( self::DATE, ( strtotime( $days[ 2 ] ) + Website::SECS_IN_DAY ) );
				$days[ 4 ] = date( self::DATE, ( strtotime( $days[ 3 ] ) + Website::SECS_IN_DAY ) );
				$days[ 5 ] = date( self::DATE, ( strtotime( $days[ 4 ] ) + Website::SECS_IN_DAY ) );
				$days[ 6 ] = date( self::DATE, ( strtotime( $days[ 5 ] ) + Website::SECS_IN_DAY ) );
				
				// we check a week's worth because each day is different
				foreach( $days as $day )
				{
					$results = $this->kids_date( $day );
					// the only way to get a list of kids programs is to check today's schedule and add any new records if we find them
					foreach( $results->feeds as $feed )
					{
						// for some reason, there's an extra channel we don't need to look at (34)
						if ( in_array( $feed->digital_channel, $this->channels ) )
						{
							foreach( $feed->listings as $item )
							{
								// see if we already have a record of this show; all we need is the id
								if ( !Database::get_total( Database::NATPROGRAMS, "WHERE natprogram_pid = '" . (int)$item->program_id . "'" ) )
								{
									$newprograms[] = $item->program_id;
								}
							}
						}
					}
				}
					
				$columns = array();
				$columns[] = array( 'title', 'name' );
				$columns[] = array( 'description', 'description' );
				$columns[] = array( 'program_id', 'pid' );
				foreach( $newprograms as $index )
				{
					$p = $this->get_program( $index );
					
					// get valid column info
					$cols = $values = array();
					foreach( $columns as $c )
					{
						list( $property, $column ) = $c;
						if ( isset( $p->$property ) )
						{
							$cols[] = 'natprogram_' . $column;
							$values[] = "'" . addslashes( $p->$property ) . "'";
						}
					}
					
					// create a slug
					$slug = strtolower( trim( preg_replace( '/\W/',' ', str_replace( "'", "", str_replace( '"', "", $p->title ) ) ) ) );
					$slug = preg_replace( '/\s+/','-', $slug );
					
					// some of the info is hidden in any upcoming episodes
					$nola = "";
					if ( isset( $p->upcoming_episodes ) && $p->upcoming_episodes[ 0 ] )
					{
						$episode = &$p->upcoming_episodes[ 0 ];
						$nola = isset( $episode->nola_root ) ? $episode->nola_root : "";
					}
					
					// record time
					$cols[] = "natprogram_slug";
					$cols[] = "natprogram_nola";
					$cols[] = "natprogram_created";
					$cols[] = "natprogram_enabled";
					$cols[] = "natprogram_kids";
					$values[] = "'" . addslashes( $slug ) . "'";
					$values[] = "'" . addslashes( $nola ) . "'";
					$values[] = "'" . addslashes( $now ) . "'";
					$values[] = "'" . addslashes( $now ) . "'";
					$values[] = "'1'";
					
					// insert new row
					@mysql_query( "INSERT INTO " . Database::NATPROGRAMS . " (" . implode( ", ", $cols ) . ") VALUES (" . implode( ", ", $values ) . ")" );
				}
			}
		}
	}
}
?>