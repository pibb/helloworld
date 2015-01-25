<?php
namespace Core;

if ( !defined( "D_CLASS_GENERIC" ) )
{
	define( "D_CLASS_GENERIC", true );
	
	/**
	 * File: class.generic.php
	 *
	 * @package Library/Static
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	abstract class Generic
	{
		/**
		 * Performs addslashes() on given array recursively.
		 *
		 * @param Array
		 * @return Array
		 */
		static public function addslashes_to_array( array $array ) 
		{	
			// initialize variables
			$new_array = array();
			
			if ( get_magic_quotes_gpc() != 1 && is_array( $array ) && count( $array ) > 0 ) 
			{
				foreach ( $array as $key => $a ) 
				{
					if ( !is_array( $a ) ) 
						$new_array[ $key ] = addslashes( $a );
					else
					{
						$a = self::addslashes_to_array( $a );
						$new_array[ $key ] = $a;
					} 
				}
			}  
			
			return $new_array;
		}
		
		/**
		 * Takes the given URL and appends a key/value pair to the end of it (ie. foo.bar?key=value). 
		 *
		 * @param string string to be appended. It doesn't necessarily have to be an absolute URL.
		 * @param string argument to be passed (ie. $_GET[ 'key' ] ).
		 * @param string value to set this argument to (ie. $_GET[ 'key' ] = 'value').
		 * @param bool setting this to true will cause variables to be deliminated by a literal "&", instead of the HTML special character.
		 */
		static public function append( $url, $key, $value, $non_html_amp = false )
		{
			if ( !empty( $value ) && !preg_match( "#$key=#", $url ) )
				$url .= ( ( strpos( $url, '?' ) != false ) ?  ( $non_html_amp ? '&' : '&amp;' ) : '?' ) . "{$key}={$value}";
		
			return $url;
		}
		
		/**
		 * This function unsets the node in the given array at the given index.
		 * 
		 * This function unsets the node in the given array at the given index. It also 
		 * reorganizes the array to prevent gaps in the indices. Like if I delete [1] in 
		 * Array( [0] => "Apple", [1] => "Orange", [2] =>"Banana" ), this function would take 
		 * index [2] (Banana) and move it to [1]. This does not work on associated arrays. It 
		 * returns the dropped element like array_shift does.
		 *
		 * @param Array the array to be adjusted.
		 * @param int the index to be removed.
		 * @return Array returns deleted element.
		 */
		static public function array_del( &$array, $index )
		{
			$n 		= count( $array );
			$old	= false;
			
			if ( $index > -1  && $index < $n )
			{
				$old = $array[ $index ];
				for ( $i = $index; $i < ( $n - 1 ); $i++ )
					$array[ $i ] = $array[ ( $i + 1 ) ];
				
				unset( $array[ ( $n - 1 ) ] );
			}
			
			return $old;
		}
		
		/**
		 * This function resets the keys of the array so they begin with 0 and increment by 1.
		 * 
		 * @param Array the array to be adjusted.
		 * @param Array
		 */
		static public function array_reset( array $array )
		{
			$new = array();
			foreach( $array as $a )
				if ( $a )
					$new[] = $a;
				
			return $new;
		}
						
		/**
		 * Takes the given U.R.L. and makes it uniform for the database.
		 *
		 * @param string the URL to be interpretted
		 * @return string
		 */
		static public function canonicalize( $a )
		{
			$a = str_replace( "http://", "", $a );
			$a = str_replace( "https://", "", $a );
			if ( ( $n = strrpos( $a, "/" ) ) == ( strlen( $a ) - 1 ) )
				$a = substr( $a, 0, $n );
		
			return $a;
		}
		
		/**
		 * Changes file names so they are safer in the browser by removing bad characters and spaces.
		 *
		 * @param string the string to be interpretted.
		 * @return string
		 */
		static public function clean( $a ) 
		{
			return preg_replace( "/[^\w\.-]/", "-", $a );	
		}
		
		/**
		 * Checks to see if the given strength is the correct length.
		 *
		 * @param string the string to be interpretted.
		 * @parram int minimum length.
		 * @param int maximum length. (Default = NULL)
		 * @return bool
		 */
		static public function correct_len( $str, $min, $max = NULL )
		{
			$valid = true;
		
			$len = strlen( $str );
			if ( $len < $min || ( $max && $len > $max ) )
				$valid = false;
		
			return $valid;
		}
		
		/**
		 * Gets the ISO 8601 date (i.e., 2004-02-12T15:19:21+00:00) from now plus the given number of hours.
		 *
		 * @param int number of hours after now.
		 * @return string
		 */
		static public function datetime( $hours )
		{
			return date( "c", strtotime( date( "m/d/Y" ) ) + ( $hours * 3600 ) );
		}
		
		/**
		 * Takes the given IP (which was turned into a HEX value), and returns it to its original value.
		 *
		 * @param string this should be an encrypted IP address.
		 * @return string encoded IP address
		 */
		static public function decode_ip( $int_ip ) 
		{
			$hexipbang = explode( '.', chunk_split( $int_ip, 2, '.' ) );
		  
			return hexdec( $hexipbang[ 0 ] ). '.' . hexdec( $hexipbang[ 1 ] ) 
				. '.' . hexdec( $hexipbang[ 2 ] ) . '.' . hexdec( $hexipbang[ 3 ] );
		}
		
		/**
		 * Takes the given IP and turns it into a hexadecimal value; simple obscurity for prying eyes.
		 *
		 * @param string this should be an IP address (ie 255.255.255.255).
		 * @return string IP Address
		 */
		static public function encode_ip( $dotquad_ip )
		{
			$ip_sep = explode( '.', $dotquad_ip );
		  
			return sprintf( '%02x%02x%02x%02x', $ip_sep[ 0 ], $ip_sep[ 1 ], $ip_sep[ 2 ], $ip_sep[ 3 ] );
		}
		
		/**
		 * Creates a well-formed URL by adding arguments from an array.
		 *
		 * @param string the starting URL.
		 * @param Array the GET parameters.
		 * @param bool whether or not to use the html entity for ampersands.
		 * @uses Generic::append
		 * @return string URL
		 */
		static public function form_url( $url, array $args = array(), $non_html_amp = false )
		{
			foreach( $args as $key => $value )
				if ( $key && $value )
					$url = Generic::append( $url, urlencode( $key ), urlencode( $value ), $non_html_amp );
				
			return $url;
		}
			
		/**
		 * Formats the given integer into a dollar amount.
		 *
		 * @param int the number of pennies.
		 * @return string the formatted number.
		 */
		static public function format_money( $n )
		{
			return sprintf( "$%01.2f", ( $n / 100 ) );
		}
			
		/**
		 * Formats the given string into a valid phone number: 555.555.5555.
		 *
		 * @param string the number without special characters (i.e., 5555555555).
		 * @param string any extension to add to the number.
		 * @return string
		 */
		static public function format_phone( $p, $ext = "" )
		{
			$text = substr( $p, 0, 3 ) . "." . substr( $p, 3, 3 ) . "." . substr( $p, 6 );
			if ( $ext )
				$text .= " ex.{$ext}";
				
			return $text;
		}
		
		/**
		 * Takes a given error and translates it into something more useful.
		 *
		 * @param Exception the error to be interpretted.
		 * @return string
		 */
		static public function get_errmsg( \Exception $e )
		{
			$msg = $e->getMessage();
			$msg .= " on line " . $e->getLine();
			$msg .= " in file " . $e->getFile() . ".";
			
			return $msg;
		}
		
		/**
		 * Gets the file extension from a given file name.
		 *
		 * @param string the file name.
		 * @return string
		 */
		static public function get_ext( $a )
		{
		  return substr( strrchr( $a, '.' ), 1 );
		}
		
		/**
		 * Returns the name of the specified month.
		 *
		 * @param int the index to be interpretted.
		 * @return string the name of the month.
		 */
		static public function get_month( $a ) 
		{
			$month = "Unknown";
			
			switch( $a )
			{
				case 0: $month = "January"; break;
				case 1: $month = "February"; break;
				case 2: $month = "March"; break;
				case 3: $month = "April"; break;
				case 4: $month = "May"; break;
				case 5: $month = "June"; break;
				case 6: $month = "July"; break;
				case 7: $month = "August"; break;
				case 8: $month = "September"; break;
				case 9: $month = "October"; break;
				case 10: $month = "November"; break;
				case 11: $month = "December"; break;
			}
			
			return $month;
		}
		
		/**
		 * Takes a serialized array of times and returns them in a readable string.
		 *
		 * @param string (serialized-array) - array([0]=>"1:00;24:59", [1] ... [6] => "" );
		 * @return string
		 */
		static public function get_schedule( $sched )
		{
			// initialize variables
			$full	= array();
			$sched 	= unserialize( $sched );
			$days 	= array( "Sundays", "Mondays", "Tuesdays", "Wednesdays", "Thursdays", "Fridays", "Saturdays" );
			
			foreach( $sched as $i => $hours )
			{
				if ( $hours )
				{
					// get all of the hours (in military time) and translate it
					$hours = explode( ";", $hours );
					foreach( $hours as $i => $h )
						$hours[ $i ] = date( "g:i A", strtotime( $h ) );
					
					$full[] = $days[ $i ] . " " . implode( ", ", $hours );
				}
			}
				
			return implode( "; ", $full );
		}
		
		/**
		 * Checks to see if the given file was included in the script.
		 *
		 * @param string the path of the file to be interpretted.
		 * @return bool
		 */
		static public function included( $file )
		{
			$files = get_included_files();
			
			foreach( $files as $f )
				if ( ( $f == $file ) || ( substr( $f, ( strrpos( $f, "/" ) + 1 ) ) == $file ) )
					return true;
			
			return false;
		}
		
		/**
		 * Checks to see if the give date is within the date boundaries.
		 *
		 * @param string/int the start date or timestamp.
		 * @param string/int the ending date or timestamp
		 * @return bool
		 */
		static public function is_happening( $start, $end )
		{
			//initialize variables
			$happening 	= true;
			$now 		= time();
			
			// check start time
			if ( $start )
			{
				if ( is_string( $start ) )
					$start = strtotime( $start );
					
				if ( $now < $start )
					$happening = false;
			}
			
			// check end time
			if ( $end )
			{
				if ( is_string( $end ) )
					$end = strtotime( $end );
				
				if ( $now >= $end )
					$happening = false;
			}
			
			return $happening;
		}
		
		/**
		 * This function takes a linear array and nests them recursively.
		 *
		 * This function takes a linear array and nests them. For example, it could take 
		 * Array( [0] => "Top", [1] => "Parent" => [2] => "Child" ) and turn it into
		 * Array( [Top] => Array( [Parent] => Array( [Child] => Array() ) ) ).
		 *
		 * @param Array the array to be interpretted
		 * @return Array
		 */
		static public function nest( array &$dir )
		{
			$nested = array();
			while( $dir )
				$nested[ array_shift( $dir ) ] = nest( $dir );
			return $nested;
		}
		
		/**
		 * Rounds big numbers and adds character suffixes like "k" for thousand.
		 *
		 * @param int the large number
		 * @return string
		 */
		static public function nicenum( $n )
		{
			if ( $n >= 1000 )
				$n = round( ( $n / 1000 ), 1 ) . "k";
			else if ( $n >= 1000000 )
				$n = round( ( $n / 1000 ), 1 ) . "m";
			
			return $n;
		}
		
		/**
		 * Shows the difference between given timestamp and now in plain English.
		 *
		 * This function takes the difference between the given timestamp and the current
		 * time and returns, in plain english, how long ago it was or how much time until it
		 * transpires.
		 *
		 * @param int the timestamp to compare to time()
		 * @return string
		 */
		static public function nicetime( $date )
		{
			if( empty( $date ) )
				return "No date provided";
		   
			$periods	= array( "second", "minute", "hour", "day", "week", "month", "year", "decade" );
			$lengths	= array( "60","60","24","7","4.35","12","10" );
		   
			$now		= time();
			$unix_date 	= strtotime( $date );
		   
			// check validity of date
			if ( empty( $unix_date ) )
				return "Bad Date";
		
			// is it future date or past date
			if ( $now > $unix_date ) 
			{   
				$difference	= $now - $unix_date;
				$tense		= "ago";
			} 
			else 
			{
				$difference	= $unix_date - $now;
				$tense		= "from now";
			}
		   
			for ( $j = 0; $difference >= $lengths[ $j ] && $j < count( $lengths ) - 1; $j++ )
				$difference /= $lengths[ $j ];
		   
			$difference = round( $difference );
		   
			if ( $difference != 1 )
				$periods[ $j ] .= "s";
		   
			return "$difference $periods[$j] {$tense}";
		}
		
		/**
		 * Forms an array for pagination.
		 *
		 * @param int the total number of items to be displayed.
		 * @param int how many items will be on each page.
		 * @return Array
		 */
		static public function paginate( $total, $per_page )
		{
			$page 				= array();
			$page[ 'count' ] 	= $total;
			$page[ 'id' ] 		= (int)Globals::get( 'page' ) ? (int)Globals::get( 'page' ) : 1;
			$page[ 'start' ]	= ( $page[ 'id' ] - 1 ) * $per_page;
			$page[ 'end' ]		= ( $page[ 'start' ] + $per_page ) <= $total ? ( $page[ 'start' ] + $per_page ) : $total;
			$page[ 'pages' ]	= ceil( $total / $per_page );
		
			return $page;
		}
		
		/**
		 * Puts the given number of seconds in plain English.
		 *
		 * @param int the number of seconds.
		 * @return string
		 */
		protected function parse_seconds( $a )
		{
			$minutes = floor( $a / 60 );
			
			return $minutes ? "{$minutes} minute" . ( $minutes != 1 ? "s" : "" ) : "{$a} second" . ( $seconds != 1 ? "s" : "" );
		}
		
		/**
		 * Removes the last array element and returns it. (Duplicates array_pop()?)
		 *
		 * @param Array
		 * @return Array
		 */
		static public function push( array &$a )
		{
			$x = NULL;
			if ( $n = count( $a ) )
			{
				$x = $a[ ( $n - 1 ) ];
				unset( $a[ ( $n - 1 ) ] );
			}
		
			return $x;
		}
		
		/**
		 * Creates a string of random numbers and letters.
		 *
		 * @param int the length of the string.
		 * @return string
		 */
		static public function random_string( $len, $use_letters = true ) 
		{
			$characters = "0123456789" . ( $use_letters ? "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" : "" );
			$n 			= strlen( $characters );
			$string 	= "";
		
			for ( $p = 0; $p < $len; $p++ )
				$string .= $characters[ mt_rand( 0, ( $n - 1 ) ) ];
		
			return $string;
		}
		
		/**
		 * Translates any number of seconds into hours, minutes, and seconds.
		 *
		 * @param int the number of seconds to be interpretted.
		 * @return string
		 */
		static public function str_from_seconds( $secs ) 
		{
			if ( $secs < 0 ) 
				return "";
			else
			{
				$m = (int)($secs / 60); 
				$s = $secs % 60;
				$h = (int)($m / 60); 
				$m = $m % 60;
				
				return "$h:$m:$s";
			}
		}
		
		/**
		 * Turns the byte abbreviation into the full number (i.e., 1MB = 1000000).
		 *
		 * @param string the string to be interpretted.
		 * @return int
		 */
		static public function strtobytes( $a )
		{
			if ( ( $m = strpos( $a, "G" ) ) !== false )
				$a = substr( $a, 0, $m ) . "000000000";
			if ( ( $m = strpos( $a, "M" ) ) !== false )
				$a = substr( $a, 0, $m ) . "000000";
			else if ( ( $m = strpos( $a, "K" ) ) !== false )
				$a = substr( $a, 0, $m ) . "000";
				
			return (int)$a;
		}
			
		/**
		 * Takes the given array and performs stripslashes() to every value in it recursively.
		 *
		 * This function takes the given array and performs stripslashes() to every value in 
		 * it. Since it works recursively, it can handle multi-dimensional arrays. This is 
		 * most typically used on the global, $_POST.
		 *
		 * @param Array the array of values needing to be unslashed.
		 * @return Array
		 */
		static public function stripslashes_from_array( array $array ) 
		{
			if ( !is_array( $array ) ) 
				return false;
			
			if ( count( $array ) <= 0 )
				return $array;
			else
			{
				foreach ( $array as $key=>$a ) 
				{
					if ( !is_array( $a ) ) 
						$new_array[ $key ] = stripslashes( $a );
					else
					{
						$a = self::stripslashes_from_array( $a );
						$new_array[ $key ] = $a;
					} 
				}
			}
			
			return $new_array;
		}
		
		/**
		 * Shortens a string and adds any necessary elipsis.
		 *
		 * @param string the string to be interpretted (usually a URL).
		 * @param int the maximum string length.
		 * @param bool show the end of the string? (Default = false)
		 * @param string
		 */
		static public function truncate( $a, $n, $show_end = false )
		{
			$len = strlen( $a );
			if ( $show_end )
				$a = ( $n < $len ? "..." : "" ) . substr( $a, ( $len - ( $len < $n ? $len : $n ) ) );
			else
				$a = substr( $a, 0, ( $len < $n ? $len : $n ) ) . ( $n < $len ? "..." : "" );
				
			return $a;
		}
		
		/**
		 * Checks to see if the given URL returns a 404 error.
		 *
		 * @param string URL to check.
		 * @return bool
		 */
		static public function url_exists( $a )
		{
			// initialize variables
			$exists = true;
			$headers = @get_headers( $a );
			
			if( $headers[ 0 ] == 'HTTP/1.1 404 Not Found' )
				$exists = false;
				
			return $exists;
		}
	}
}
?>