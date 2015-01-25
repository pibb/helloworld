<?php 
namespace Core;

if ( !defined( "D_CLASS_COVEAPI" ) )
{
	/**
	 * require_once is too slow. Using constants to keep record of definitions.
	 */
	define( "D_CLASS_COVEAPI", true );
	require( "class.pbsapi.php" );
	require( "class.tvssapi.php" );
	
	/**
 	 * File: class.coveapi.php
	 *
 	 * @package Library/API's
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @see https://projects.pbs.org/confluence/display/coveapi/Welcome
	 * @todo Recreate the video cache methods if they are still needed.
	 * @todo Is the method 'get_previewsfdays' needed? (It's currently commented out.)
	 * @version 1.0.1
	 */
	class COVE_API extends PBS_API
	{
		public $webpage		= NULL;
		
		const API_ID 		= "WNIT-99cc531d-ec35-4a41-a027-18dc3cc59e2f";
		const API_SECRET 	= "c28973a0-c60b-4333-a5cf-62421ccebe9a";
		const ENDPOINT		= "http://api.pbs.org/cove/v1/videos/";
		const CATS_ENDPOINT	= "http://api.pbs.org/cove/v1/categories/";
		const PROG_ENDPOINT	= "http://api.pbs.org/cove/v1/programs/";
		const IGNORE		= "chuck-vanderchuck,design-squad-nation,dragonflytv,fizzys-lunch-lab,mama-mirabelles-home-movies,postcards-buster,greens,wilson-ditch-digging-america,maya-miguel,lomax-hound-music,its-my-life,woodrights-shop";
		const CACHE_EXPIRE_MINUTES = 5;
		const RESULT_CAP	= 200;
		const CACHE_PROGS	= 2592000; // 30 days
		
		/**
		 * Class constructor
		 * 
		 * @uses PBS_API::$api_id
		 * @uses PBS_API::$api_secret
		 */
		public function __construct()
		{
			$this->api_id = self::API_ID;
			$this->api_secret = self::API_SECRET;
		}
		
		/**
		 * Looks for a video on COVE using a tp_media_id
		 * 
		 * @param int The TP Media ID of the Video (i.e., 2365191592)
		 * @param bool Include images in the result? (Default = true)
		 * @param bool Include videos in the result? (Default = true)
		 * @param bool Include closed caption files in the result? (Default = true)
		 * @param bool Only if the video is available? (Default = true)
		 * @see https://projects.pbs.org/confluence/display/coveapi/Videos
		 * @uses PBS_API::make_request
		 * @return Array The JSON result set of the video; false if not found.
		 */
		public function get_video( $id, $images = true, $media = true, $captions = true, $available = true )
		{
			// initialize variables
			$video 	= false;
			$fields = array();
			
			// determine field info to include
			if ( $images ) 		$fields[] = "associated_images";
			if ( $media ) 		$fields[] = "mediafiles";
			if ( $captions ) 	$fields[] = "captions";
			
			// get results
			$code = $this->make_request( self::ENDPOINT . "?filter_tp_media_object_id=" . $id . "&fields=" . implode( ",", $fields ) . ( $available ? "&filter_availability_status=Available" : "" ) . "&order_by=-airdate&limit_stop=1" );
			
			if ( $code )
			{
				$result = json_decode( $code );
				
				// the returned set will be an array, so we only want the first result
				if ( $result && $result->count )
					$video = $result->results[ 0 ];
			}
			
			return $video;
		}
		
		/**
		 * Searches for videos matching given title. Attaches: associated_images, mediafiles, and captions.
		 * 
		 * @param string Full or partial title of the video to be found.
		 * @param string Orders results by 'title' by detault; Others (encore_datetime,airdate,title,available_datetime,expire_datetime,record_last_updated_datetime,type)
		 * @see https://projects.pbs.org/confluence/display/coveapi/Videos
		 * @uses PBS_API::make_request
		 * @return array The JSON result set of the videos; false if not found.
		 */
		public function search_video( $name, $order_by = "title" )
		{
			// initialize variables
			$result = false;
			$fields = array();
			
			// determine field info to include
			$fields[] = "associated_images";
			$fields[] = "mediafiles";
			$fields[] = "captions";
			
			// get results
			$code = $this->make_request( self::ENDPOINT . "?filter_title=" . $name . "&fields=" . implode( ",", $fields ) . "&filter_availability_status=Available&order_by={$order_by}" );
			if ( $code )
				$result = json_decode( $code );
			
			return $result;
		}
		
		/**
		 * Searches for videos 'Episodes' for the given $program_id. 
		 *
		 * Returns video 'Episode' assets that belong to the given $program_id. 
		 * Attaches: associated_images, tags, categories, geo_profile, and producer.
		 * 
		 * @param int Program ID of the program. This will be different from the Program ID for the TVSS_API
		 * @param string Orders results by 'title' by detault; Others (nola_root)
		 * @see https://projects.pbs.org/confluence/display/coveapi/Programs
		 * @uses PBS_API::make_request
		 * @return array The JSON result set of the videos; false if not found.
		 */
		public function search_program( $program_id, $order_by = "title" )
		{
			// initialize variables
			$result = false;
			
			// get results
			$code = $this->make_request( self::ENDPOINT . "?filter_program=" . $program_id . "&filter_type=Episode&fields=associated_images,tags,categories,geo_profile,producer&filter_availability_status=Available&order_by={$order_by}" );
			if ( $code )
				$result = json_decode( $code );
			
			return $result;
		}
		
		/**
		 * Searches for videos 'Promotions' for the given $program_id. 
		 * 
		 * Returns video 'Promotion' assets that belong to the given $program_id. 
		 * Attaches: associated_images, tags, categories, geo_profile, and producer.
		 *
		 * @param int Program ID of the program. This will be different from the Program ID for the TVSS_API
		 * @param string Orders results by 'title' by detault; Others (nola_root)
		 * @see https://projects.pbs.org/confluence/display/coveapi/Videos
		 * @uses PBS_API::make_request
		 * @return array The JSON result set of the videos; false if not found.
		 */
		public function search_program_previews( $program_id, $order_by = "title" )
		{
			// initialize variables
			$result = false;
			
			// get results
			$code = $this->make_request( self::ENDPOINT . "?filter_program=" . $program_id . "&filter_type=Promotion&fields=associated_images,tags,categories,geo_profile,producer&filter_availability_status=Available&order_by={$order_by}" );
			if ( $code )
				$result = json_decode( $code );
			
			return $result;
		}
		
		/*
		 * Returns any available 'Promotion' videos for the next number of $days. 
		 *
		 * @param int Number of days from now to look for promotions.
		 * @see https://projects.pbs.org/confluence/display/coveapi/Videos
		 * @uses PBS_API::make_request
		 * @return array The JSON result set of the videos; false if not found.
		 *
		public function get_previewsfdays( $days )
		{
			// initialize variables
			$result = false;
			$later 	= time() + ( Website::SECS_IN_DAY * $days );
			$later 	= date( "Y-m-d", $later );
			$now 	= date( "Y-m-d" );
			
			// get results
			$code = $this->make_request( self::ENDPOINT . "?filter_type=Promotion&filter_available_datetime__gt={$now}" );
			if ( $code )
				$result = json_decode( $code );
			
			return $result;
		}*/
		
		/**
		 * Searches for program by given $title and return first result.
		 *
		 * Attaches: associated_images, tags, categories, geo_profile, and producer.
		 * 
		 * @param string Full or partial title of the program.
		 * @see https://projects.pbs.org/confluence/display/coveapi/Programs
		 * @uses PBS_API::make_request
		 * @return array The JSON result set of the videos; false if not found.
		 */
		public function get_program( $title )
		{
			$program = false;
			
			// get results
			$code = $this->make_request( self::PROG_ENDPOINT . "?filter_title=" . urlencode( $title ) . "&fields=associated_images,tags,categories,geo_profile,producer&limit_stop=1" );
			if ( $code )
			{
				$result = json_decode( $code );
			
				// the returned set will be an array, so we only want the first result
				if ( $result && $result->count )
					$program = $result->results[ 0 ];
			}
			
			return $program;
		}
		
		/**
		 * Searches for any videos that belong to a PBS Kids program.
		 * 
		 * @see https://projects.pbs.org/confluence/display/coveapi/Videos
		 * @uses PBS_API::make_request
		 * @return array The JSON result set of the videos; false if not found.
		 */
		public function get_kids()
		{
			return json_decode( $this->make_request( self::PROG_ENDPOINT . "?filter_producer__name=KIDS" ) );
		}
		
		/**
		 * Gathers information about all of the available programs and records it in the Database.
		 * 
		 * @see https://projects.pbs.org/confluence/display/coveapi/Programs
		 * @uses PBS_API::make_request
		 * @uses Database::get_total
		 * @param int Timestamp of the last check; by default it ignores the cache stamp.
		 */
		public function update_programs( $last_check = 0 )
		{
			$now = time();
			if ( $now - $last_check >= self::CACHE_PROGS )
			{
				// initialize variables
				$list = array();
				$start = $remaining = $end = 0;
				$columns = array();
				$columns[] = array( 'title', 'name' );
				$columns[] = array( 'long_description', 'description' );
				$columns[] = array( 'short_description', 'short' );
				$columns[] = array( 'slug', 'slug' );
				$columns[] = array( 'nola_root', 'nola' );
				$columns[] = array( 'resource_uri', 'resource_uri' );
				$columns[] = array( 'itunes_url', 'itunes' );
				$columns[] = array( 'shop_url', 'shop' );
				$columns[] = array( 'underwriting', 'underwriting' );
				$columns[] = array( 'cast', 'cast' );
				$columns[] = array( 'website', 'website' );
				$ignore = explode( ",", self::IGNORE );
				$kids = $this->get_kids();
				
				// get all the results since the API only returns 200 results at a time
				do
				{
					$progs = json_decode( $this->make_request( self::PROG_ENDPOINT . "?fields=associated_images" . ( $end ? "&limit_start={$start}&limit_stop={$end}" : "" ) ) );
					$list = array_merge( $list, $progs->results );
					if ( $progs->count > self::RESULT_CAP )
					{
						$start += self::RESULT_CAP;
						$remaining = $progs->count - $start;
						$end = $start + ( $remaining < self::RESULT_CAP ? $remaining : self::RESULT_CAP );
						$start += 1;
					}
				}
				while( $progs->results && $start < $progs->count );
				
				// dump results into the database
				foreach( $list as $index => $p )
				{
					// make sure this isn't a kids program because we track that in the TVSS_API
					$break = false;
					foreach( $kids->results as $kid )
					{
						if ( isset( $kid->title ) && $kid->title && $kids->title == $p->title )
						{
							$break = true;
							break;
						}
					}
					
					// get out since it's a kids program
					if ( $break )
						break;
						
					// we're only storing programs that have NOLA code for national programming. the rest is usually irrelevant
					if ( !in_array( $p->slug, $ignore ) && trim( $p->nola_root ) && ( $p->short_description || $p->long_description ) )
					{
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
						
						// go through images
						foreach( $p->associated_images as $img )
						{
							switch( $img->type->eeid )
							{
								case "iPad-Small": 				if ( !in_array( 'natprogram_img_ipad_small', $cols ) ) { $cols[] = 'natprogram_img_ipad_small'; $values[] = "'" . $img->url . "'"; } break;
								case "iPhone-Medium": 			if ( !in_array( 'natprogram_img_iphone_med', $cols ) ) { $cols[] = 'natprogram_img_iphone_med'; $values[] = "'" . $img->url . "'"; } break;
								case "iPhone-Small":		 	if ( !in_array( 'natprogram_img_iphone_small', $cols ) ) { $cols[] = 'natprogram_img_iphone_small'; $values[] = "'" . $img->url . "'"; } break;
								case "program-logo-wide": 		if ( !in_array( 'natprogram_img_logo', $cols ) ) { $cols[] = 'natprogram_img_logo'; $values[] = "'" . $img->url . "'"; } break;
								case "program-mezzanine-16x9": 	if ( !in_array( 'natprogram_img_mezzanine', $cols ) ) { $cols[] = 'natprogram_img_mezzanine'; $values[] = "'" . $img->url . "'"; } break;
							}
						}
						
						// record time
						$cols[] = "natprogram_created";
						$cols[] = "natprogram_enabled";
						$values[] = $now;
						$values[] = $now;
						
						// insert new row
						if ( !Database::get_total( Database::NATPROGRAMS, "WHERE natprogram_name = '" . addslashes( $p->title ) . "'" ) )
							@mysql_query( "INSERT INTO " . Database::NATPROGRAMS . " (" . implode( ", ", $cols ) . ") VALUES (" . implode( ", ", $values ) . ")" );
						else
						{
							$props = array();
							foreach( $cols as $index => $col )
								$props[] .= "{$col} = " . $values[ $index ];
							@mysql_query( "UPDATE " . Database::NATPROGRAMS . " SET " . implode( ", ", $props ) . " WHERE natprogram = '" . addslashes( $p->title ) . "' LIMIT 1" );
						}
					}
				}
				
				// update kids since flags aren't returned in the full results
				foreach( $kids->results as $p )
					if ( isset( $p->slug ) && $p->slug )
						@mysql_query( "UPDATE " . Database::NATPROGRAMS . " SET natprogram_kids = '1' WHERE natprogram_slug = '" . addslashes( $p->slug ) . "' LIMIT 1" );
						
				// update check time
				@mysql_query( "UPDATE " . Database::CONFIG . " SET config_value = '{$now}' WHERE config_id = 'natprograms_check'" );
			}
		}
		
		public function cache( $id )
		{
			$id = addslashes( $id );
			$now = time();
			$result = false;
			if ( $this->webpage instanceof Webpage )
			{
				$last_cache = $this->webpage->db->fetch( Database::CACHE, '*', "cache_video_id =  '{$id}'" );
				if ( $last_cache ) {
					
					$since_last_check = $now - $last_cache[ 'cache_time' ];

					if ( $since_last_check > self::CACHE_EXPIRE_MINUTES * 60 ) {
						//expired cache	
						$data = $this->get_video( $id );
						$result = $found = (bool)$data;
						$player_html = $found ? addslashes( $data->partner_player ) : '';
						$sql = 'UPDATE ' . Database::CACHE . " SET cache_time = $now, video_player_html = '$player_html', cache_available = " . (int)$result . " WHERE cache_video_id =  '{$id}'";
	
						@$this->webpage->db->query( $sql );
					} else {
						//cache found
						$result = (bool)$last_cache[ 'cache_available' ];
					}
					
				} else {
					$data = $this->get_video( $id );
					$result = $found = (bool)$data;
					$player_html = $found ? addslashes( $data->partner_player ) : '';
					$sql = 'INSERT INTO ' . Database::CACHE . " (cache_video_id, cache_available, cache_time, video_player_html) VALUES ('$id'," . (int)$found . ",$now, '$player_html')";

					@$this->webpage->db->query( $sql );
				}
			}
			
			return $result;
		}
		
		/*public function video_player_cache( $id )
		{
			$result = null;
			if ( $this->cache( $id ) ) {
				$result  =  $this->webpage->db->fetch_cell( Database::CACHE, 'video_player_html', "cache_video_id =  '{$id}'" );
			}
			return $result;
		}*/
	}
}
?>