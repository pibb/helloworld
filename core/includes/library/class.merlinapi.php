<?php
#============================================================================================================
# ** Merlin API Class
#============================================================================================================
namespace Core;

class MerlinAPI
{
	const COOKIEJAR = "_merlinapi_session.txt";
	
	const URL_LOGIN_CHECK = "http://admin.merlin.pbs.org/django-admin/core/create/";
	const URL_LOGIN = "https://account.pbs.org/accounts/login/"; //"https://login.pbs.org/login/";
	const URL_LOGIN2 = "https://account.pbs.org/accounts/openid/login/"; //"http://merlin.pbs.org/uua/login/";
	const URL_LOGIN3 = "http://admin.merlin.pbs.org/openid/login/?next=/django-admin/core/create/"; //"http://merlin.pbs.org/uua/login/";
	const URL_LOGIN4 = "http://admin.merlin.pbs.org/openid/login/?next=/django-admin/videoingester/"; //"http://merlin.pbs.org/uua/login/";
	const URL_OPENID = "https://account.pbs.org/cranky/endpoint/";
	const LOGIN_TIMEOUT = 10;
	const URL_NEW_ASSET = "http://admin.merlin.pbs.org/django-admin/videoingester/videoasset/add/";
	const URL_ASSET = "http://admin.merlin.pbs.org/django-admin/videoingester/videoasset/{ID}/";
	const URL_CHAPTER = "http://admin.merlin.pbs.org/django-admin/videoingester/chapter/add/";
	const URL_CHAPTER_DELETE = "http://admin.merlin.pbs.org/django-admin/videoingester/chapter/?video_asset={ID}";
	

	const MAX_CURL_REQUESTS = 300;
	
	const STATUS_EMPTY = "empty";
	const STATUS_PENDING = "pending";
	const STATUS_UPLOADING = "uploading";
	const STATUS_ERROR = "error";
	const STATUS_COMPLETE = "complete";
	const STATUS_READY_TO_TRANSCODE = "transcode";
	const STATUS_NEED_CAPTION = "caption";
	
	const STATUS_TO_SUBMIT = "to-submit";
	const STATUS_TO_DELETE_AND_SUBMIT = "to-delete-submit";
	const STATUS_TO_DELETE = "to-delete";
	
	static private $s_instance = NULL;
	static public $cache_only = FALSE;
	public $logged_in_pbs = FALSE;
	public $logged_in_merlin = FALSE;
	public $status = "";
	
	
	#--------------------------------------------------------------------------------------------------
	# * ___constructor
	#--------------------------------------------------------------------------------------------------
	private function __construct() {
		
		
		
		

		for ( $i = 0; $i < 2; $i++ ) {
			//Check merlin.pbs.org status
			if ( self::_get_cache( 'MerlinSession' ) ) {
				$this->logged_in_merlin = true;
			} else if ( !self::$cache_only ) {
				$this->logged_in_merlin = self::_is_logged_in_merlin();
				self::_set_cache( 'MerlinSession', $this->logged_in_merlin );
			}
			
			
			
			//Check login.pbs.org status
			if ( self::_get_cache( 'PbsOrgSession' ) ) {
				$this->logged_in_pbs = true;
			} else if ( !self::$cache_only ) {
				$this->logged_in_pbs = $this->logged_in_merlin ? true : self::_is_logged_in_pbs() ;
				self::_set_cache( 'PbsOrgSession', $this->logged_in_pbs );
			}
			
			
			//Login If needed
			if ( !$this->logged_in_pbs && !self::$cache_only  ) 
				if ( $_POST[ 'username' ] && $_POST[ 'password'] && 
					self::_log_in_pbs( $_POST[ 'username' ] , $_POST[ 'password'] )) 
							$this->logged_in_pbs = true;
			
			
			//If still not logged in something went wrong
			if ( !$this->logged_in_pbs && !self::$cache_only ) return  $this->status = "Not Logged Into login.pbs.org";
			
			//a final chance to log into merlin
			if ( !$this->logged_in_merlin && !self::$cache_only  ) {
				$this->logged_in_merlin = self::_log_in_merlin();
				self::_set_cache( 'MerlinSession', $this->logged_in_merlin );
			}
			
			
			if ( self::$cache_only && !$this->logged_in_merlin ) {
				system( "/opt/php54/bin/php /home/wnittv/public_html/wizard_cron.php refresh > /home/wnittv/public_html/test.txt" );

				continue;
			} 
			break;
		}
		
		if ( $this->logged_in_merlin ) {
			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		
		
	}
	
	#--------------------------------------------------------------------------------------------------
	# * self
	# return self
	#--------------------------------------------------------------------------------------------------
	static public function self( $force = false ) {
		if ( !self::$s_instance || $force ) {
				self::$s_instance = new MerlinAPI();
		}
		return self::$s_instance;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * _get_cache
	#--------------------------------------------------------------------------------------------------
	private static function _get_cache( $page, $expired_minutes = 30 ) {
		$result = false;
		
		$page = preg_replace( '/\W*/', '', $page );
		$data = Database::select( Database::CACHE, "*", Database::CACHE_PRE . "video_id = '$page'" ); 
		if ( count ( $data ) ) {
			$data = $data[0];
			if ( time() - $data[ Database::CACHE_PRE . 'time' ] < $expired_minutes * 60 ) {
				$result = (int)$data[ Database::CACHE_PRE . 'available' ];
			}
		}
		return $result;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * _get_cache_time
	#--------------------------------------------------------------------------------------------------
	private static function _get_cache_time( $page ) {
		$time = false;
		
		$page = preg_replace( '/\W*/', '', $page );
		$data = Database::select( Database::CACHE, "*", Database::CACHE_PRE . "video_id = '$page'" ); 
		if ( count ( $data ) ) {
			$data = $data[0];
			$time = (int)$data[ Database::CACHE_PRE . 'time' ];
		}
		return $time;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * _set_cache
	#--------------------------------------------------------------------------------------------------
	private static function _set_cache( $page, $available, $time = false ) {
		$result = false;
		$available = (int)$available;
		$page = preg_replace( '/\W*/', '', $page );
		$data = Database::select( Database::CACHE, "*", Database::CACHE_PRE . "video_id = '$page'" ); 
		
		if ( $time === false ) {
			$time = time();	
		}
		
		if ( !$data )  {
			$query = sprintf( "INSERT INTO %s ( %svideo_id, %savailable, %stime  ) VALUES ('$page',$available,%d) ",
								Database::CACHE,
								Database::CACHE_PRE,
								Database::CACHE_PRE,
								Database::CACHE_PRE,
								$time
			);	
			Database::query( $query );
		} else {
			$data = Database::select( Database::CACHE, "*", Database::CACHE_PRE . "video_id = '$page'" ); 
			if ( count( $data ) ) {
				$data = $data[0];
				
				$query = sprintf( "UPDATE %s SET %savailable = $available, %stime = %d WHERE %sid = %d ",
									Database::CACHE,
									Database::CACHE_PRE,
									Database::CACHE_PRE,
									$time,
									Database::CACHE_PRE,
									$data[Database::CACHE_PRE.'id']
				);	
				Database::query( $query );	
			}
		}

		return true;
	}
	
	
	#--------------------------------------------------------------------------------------------------
	# * merlin_log
	#--------------------------------------------------------------------------------------------------
	static function merlin_log( $msg ) {
		
		$msg = addslashes( $msg );
		$query = sprintf("INSERT INTO %s ( %smsg, %stime ) VALUES ( '%s', %d  )",
							Database::MERLIN_LOG,
							DatabasE::MERLIN_LOG_PRE,
							Database::MERLIN_LOG_PRE,
							$msg,
							time());
		
		Database::query( $query );
		
		
		return;
	}
	
	
	#--------------------------------------------------------------------------------------------------
	# * _tickapi
	#--------------------------------------------------------------------------------------------------
	private static function _tickapi() {
		
		$ticks = (int)self::_get_cache( "MerlinAPITicks", 1440 /*1440 Minutes in a day*/ );
		if ( $ticks ) 
			$ticks_timeout = (int)self::_get_cache_time(  "MerlinAPITicks" );
		else
			$ticks_timeout = time();

		$ticks = $ticks + 1;
		self::_set_cache( "MerlinAPITicks", $ticks, $ticks_timeout );

		return $ticks;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * _tickapi
	#--------------------------------------------------------------------------------------------------
	public static function getTicks() {
		$ticks = (int)self::_get_cache( "MerlinAPITicks", 1440 /*1440 Minutes in a day*/ );
		return $ticks;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * reset_ticks
	#--------------------------------------------------------------------------------------------------
	public static function reset_ticks() 
	{
		self::_set_cache( "MerlinAPITicks", 0, time() );
		return 0;
	}
	
	
	#--------------------------------------------------------------------------------------------------
	# * curl
	#--------------------------------------------------------------------------------------------------
	private static function _curl( $url, $data = false, $follow = true, $referer = false ) {
		
		$ticks = self::_tickapi();
		if ( $ticks > self::MAX_CURL_REQUESTS ) {
			self::merlin_log( "Error: Max curl requests reached for the day" );	
			die("Error: Max curl requests reached for the day. To reset it <a href=\"?curlreset=1\">click here</a>");
		}
		
		
		self::merlin_log( "MerlinAPI ($ticks): $url - " . (is_array( $data ) ? print_r( $data ) : $data ) );	
		
		

		$s = curl_init(); 
		
		if ( $referer ) curl_setopt($s, CURLOPT_REFERER, $referer );

		curl_setopt($s,CURLOPT_URL, $url ); 
		curl_setopt ($s, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt ($s, CURLOPT_CONNECTTIMEOUT, self::LOGIN_TIMEOUT); 
		curl_setopt ($s, CURLOPT_FOLLOWLOCATION, $follow ? 1 : 0 ); 
		curl_setopt($s, CURLOPT_COOKIEJAR, self::COOKIEJAR );
		curl_setopt($s, CURLOPT_COOKIEFILE, self::COOKIEJAR );
		curl_setopt($s, CURLINFO_HEADER_OUT, 1 );
		curl_setopt($s, CURLOPT_HEADER, 1 );
		curl_setopt ($s, CURLOPT_SSL_VERIFYPEER, 0); 
		if ( $data ) {
			curl_setopt($s, CURLOPT_POST, TRUE);
			curl_setopt($s, CURLOPT_POSTFIELDS, $data );
		}
		list( $response, $html ) = explode( "\r\n\r\n", curl_exec( $s ), 2 );
		$request = curl_getinfo( $s, CURLINFO_HEADER_OUT );
		curl_close( $s );
		

		return array( $request, $response, $html);
	}	
	
	#--------------------------------------------------------------------------------------------------
	# * html_contains_strings
	#--------------------------------------------------------------------------------------------------
	private  static function _html_contains_strings( $html, $strings  ) {
		$result = true;
		
		if ( is_array( $strings ) ) {
			foreach ( $strings as &$s ) {
				if ( strpos( $html, $s ) === false ) {
					$result = false;	
				}
			}
		} else {
			$result = (bool)strpos( $html, $strings );
		}
		
		return $result;
	}
	
	
	#--------------------------------------------------------------------------------------------------
	# * finds the strpos of $string not larger than the given $pos
	#--------------------------------------------------------------------------------------------------
	private static function _reverse_strpos( &$html, $string, $pos = 0 ) {
		$pos1 = 0;
		$last_find = false;
		while ( $pos1 !== false ) {
			$pos1 = strpos( $html, $string, $pos1 );
			if ( $pos1 !== false ) {
				if ( $pos1 < $pos ) {
					$last_find = $pos1;
					$pos1++;	//no infinite loops
				} else {
					break;	
				}
			}
		}
		
		return  $last_find;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * html_extract
	#	$find_html - html to parse/search
	#	$pattern - array of patterns
	#	$base_html - html to extract from (optional)
	#
	#	 parses html with a supplied pattern to extract data
	# 	e.g. $pattern = array( array( '+', '<td>' ), array( '-', '</td>' ) )
	#
    # 	@,str pos1 = search for str from index 0
    # 	+,str pos1 = search for str from index pos1
    # 	++,str pos1 = search + len(str)
    # 	p,num adds num to pos1
    # 	m,num subjects num from pos1
	# 	r,str reverse strpos
    # 	-,str pos2 = search for str, $result[] = substr( pos1:pos2 )
    # 	$,str same as +, start of loop until not found
    # 	$+,str same as ++, start of loop until not found
    # 	%     repeat loop
    # 	array, num 	sets results to multi array result
	# 	set, num		sets the index of the result in case of array
	#------------------------------------------------------------------------------
	private static function _html_extract( $find_html, array $pattern, $base_html = NULL  ) {
		
		//Init variables
		$data = array();
		
		$pos1 = 0;
		$pos2 = 0;
		
		$start_loop = false;
		$array_result = false;
		
		if ( $base_html === NULL ) $base_html = &$find_html;
		
		foreach ( $pattern as $k => &$f ) {
			if ( $f[0] == '$' || $f[0] == '$+' ) $start_loop = $k;
			if ( $f[0] == 'array' ) {
				$array_result = true;
				$data = array();
				for ( $i = 0; $i < (int)$f[1]; $i++ ) {
					$data[] = array();
				}	
			}	
		}
		
		$step = 0;
		$loop = 0;
		$array_idx = 0;
		
		while( $step < count( $pattern) && $pos1 !== false ) {
			$action = $pattern[$step][0];
			$string = $pattern[$step][1];
			$error  = isset($pattern[$step][2]) ? $pattern[$step][2] : '';
			if ( $loop++ > 5000 ) throw new \Exception("Infinate Loop"); 
			
			switch( $action ) {
				case '@':
					
					if ( ($pos1 = strpos( $find_html, $string )) === false ) 
						switch ( $error ) {
							case '?': break;
							case '!': return false; break;
							default: throw new \Exception("Unable to find string \"$string\" "); break;
						}
				break;
				case '+':
					if ( ($pos1 = strpos( $find_html, $string, $pos1 )) === false )
						switch ( $error ) {
							case '?': break;
							case '!': return false; break;
							default: throw new \Exception("Unable to find string \"$string\" starting at $pos1 \"" . substr( $find_html, $pos1, 55 )  . "...\"" );  break;
						}
						
				break;
				case '++':
					if ( ($pos1 = strpos( $find_html, $string, $pos1 )) === false ) 
						switch ( $error ) {
							case '?': break;
							case '!': return false; break;
							default: throw new \Exception("Unable to find string \"$string\" starting at $pos1 \"" . substr( $find_html, $pos1, 55 )  . "...\"" );   break;
						}
						
					$pos1 = $pos1 + strlen( $string );
				break;
				case 'p':
					$pos1 = $pos1 + (int)$string;
				break;
				case 'm':
					$pos1 = $pos1 - (int)$string;
				break;
				case '-':
					if  ( ($pos2 = strpos( $find_html, $string, $pos1 )) === false ) 
						switch ( $error ) {
							case '?': break;
							case '!': return false;
							default: throw new \Exception("Unable to find string \"$string\" starting at $pos1 \"" . substr( $find_html, $pos1, 55 )  . "\"" );    break;
						}
						
					if ( $pos2 != false ) {
						if ( $array_result ) {
							$data[$array_idx][] = substr( $base_html, $pos1, $pos2 - $pos1 );
						} else {
							$data[] = substr( $base_html, $pos1, $pos2 - $pos1 );	
						}
						
						$pos1 = $pos2;
					}
				break;
				case 'r':
					if ( ($pos1 = self::_reverse_strpos( $find_html, $string, $pos1 )) === false )
						switch ( $error ) {
							case '?': break;
							case '!': return false;
							default: throw new \Exception("Unable to reverse find string \"$string\" starting at $pos1 \"" . substr( $find_html, $pos1-25, 55 )  . "...\"" );    break;
						}
						
				break;
					
					
				break;
				case '$':
					$pos1 = strpos( $find_html, $string, $pos1 ); 
				break;
				case '$+':
					$pos1 = strpos( $find_html, $string, $pos1 );
					if ( $pos1 !== false ) $pos1 = $pos1 + strlen( $string );
				break;
				case '%':
					if ( $start_loop !== false ) $step = $start_loop;
					$step--;
				break;
				case 'set':
					if ( $array_result ) $array_idx = (int)$string;
				break;
			}

			$step++;
		}
		
		
		
		return $data;
	}
	
	
	#--------------------------------------------------------------------------------------------------
	# * _get_table_data
	#--------------------------------------------------------------------------------------------------
	static private function _get_table_data( $id, $html ) {
		$results = array(); 
		
		$table = self::_html_extract( $html, array(
			array( '+', $id, '!' ),
			array( '-', '<div class="module">' ),
		));
		
		


		if  ( $table === false ) {
			return array();
		}
		
		
		$table = self::_html_extract( $table[0], array(
			array( '+', $id, '!' ),
			array( '+', '</thead', '!' ),
			array( '-', '</table', '!' ),
		));
		
		if  ( $table === false ) {
			return array();
		}
		

		$rows = self::_html_extract( $table[0], array(
			array( '$', '<tr' ),
			array( '++', '>' ),
			array( '-', '</tr' ),
			array( '%', '' )
		));
		


		foreach ( $rows as &$r ) {
			
			$results[] = self::_html_extract( $r, array(
				array( '$', '<td' ),
				array( '++', '>' ),
				array( '-', '</td' ),
				array( '%', '' )
			));
		}
		
		return $results;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * _is_logged_in_pbs
	#--------------------------------------------------------------------------------------------------
	private static function _is_logged_in_pbs() {
			list( $request, $response, $html ) = self::_curl( self::URL_LOGIN  );
			//var_dump( $html );
			return !(bool)strpos( $html, 'Returning Guests' );
	}
	
	#--------------------------------------------------------------------------------------------------
	# * _is_logged_in_pbs
	#--------------------------------------------------------------------------------------------------
	private static function _is_logged_in_merlin() {
			//merlin.pbs.org 
			list( $request, $response, $html ) = self::_curl( self::URL_NEW_ASSET  );
			
			return !(bool)strpos( $html, 'OpenID transaction in progress' );
	}
	
	#--------------------------------------------------------------------------------------------------
	# * _is_logged_in_pbs
	#--------------------------------------------------------------------------------------------------
	private static function _log_in_pbs( $username, $password ) {
			
			unlink( self::COOKIEJAR );
			list( $request, $response, $html ) = self::_curl( self::URL_LOGIN  );
			
			$token = self::_html_extract( $html, array(
				array( '+', 'csrfmiddlewaretoken' ),
				array( '++', "value='" ),
				array( '-', "'" ),
			))[0];
			
			$username = urlencode( $username );
			$password = urlencode( $password );
			$data = "csrfmiddlewaretoken=$token&username=$username&password=$password&remember_user=on&next=";
			list( $request, $response, $html ) = self::_curl( self::URL_LOGIN, $data  );
			if ( strpos( $html, 'Please enter a correct email and password' ) ) return FALSE;
			
			
			
			return TRUE;
	}
	
	
	
	private static function dump_request( $url, $data, $request, $response, $html ) {
		var_dump( "url<textarea>$url</textarea><br />" );
		var_dump( "data<textarea>".$data."</textarea><br />" );
		var_dump( "request<textarea>$request</textarea><br />" );
		var_dump( "response<textarea>$response</textarea><br />" );
		var_dump( "html<textarea>$html</textarea><br /><br />" );
		var_dump( "<hr />" );
	}
	
	#--------------------------------------------------------------------------------------------------
	# * _log_in_merlin
	#--------------------------------------------------------------------------------------------------
	private static function _log_in_merlin() {
			$debug = false;
			

			//Check if logged in to merlin
			list( $request, $response, $html ) = self::_curl( self::URL_LOGIN4 );
			if ( $debug ) self::dump_request( self::URL_LOGIN4, "", $request, $response, $html );
			
			if ( !strpos( $html, 'OpenID transaction in progress' ) ) return FALSE;
			
			if ( !$_POST['username'] || !$_POST['password'] ) {
				return false;
			}
			
			//Extract form elements to post
			list( $name, $value ) = self::_html_extract( $html, array(
				array( 'array', '2' ),
				array( '$+', 'name="' ),
				array( 'set', '0' ),
				array( '-', '"' ),
				array( '++', 'value="' ),
				array( 'set', '1' ),
				array( '-', '"' ),
				array( '%', '' ),
			));
			
			
			$n = count( $name );
			$data = "";
			for ( $i = 0; $i < $n; $i++ ) $data .= $name[$i] . '=' . urlencode( $value[$i] ) . ( $i+1 < $n ? '&' : '' );	
			list( $request, $response, $html ) = self::_curl( self::URL_OPENID, $data );
			if ( $debug ) self::dump_request( self::URL_OPENID, $data, $request, $response, $html );
			
			if ( strpos( $html, "You are already logged in" ) !== false ) {
				//already logged in PBS so skip
				$location = "https://account.pbs.org/accounts/openid/allow/";
			} else {
				//relog
				$token = self::_html_extract( $html, array(
					array( '+', 'csrfmiddlewaretoken' ),
					array( '++', "value='" ),
					array( '-', "'" )
				))[0];
				$username = $_POST['username'];
				$password = $_POST['password'];
				$data = "email=$username&password=$password&csrfmiddlewaretoken=$token&keep_logged_in=on";
				list( $request, $response, $html ) = self::_curl( self::URL_LOGIN2 , $data, false, "https://account.pbs.org/accounts/openid/login/" );
				if ( $debug ) self::dump_request( self::URL_LOGIN2, $data, $request, $response, $html );
				
				$location = self::_html_extract( $response, array(
					array( '++', 'Location: ' ),
					array( '-', "\n" ),
				))[0];
			}
			
			

			
			list( $request, $response, $html ) = self::_curl( $location , "", false );
			if ( $debug ) self::dump_request( $location, "", $request, $response, $html );
			$location = self::_html_extract( $response, array(
				array( '++', 'Location: ' ),
				array( '-', "\n" ),
			))[0];
			list( $request, $response, $html ) = self::_curl( $location , "", false );
			if ( $debug ) self::dump_request( $location, "", $request, $response, $html );
			$location = self::_html_extract( $response, array(
				array( '++', 'Location: ' ),
				array( '-', "\n" ),
			))[0];
			
			$location = str_replace( "\r", "", $location );
			$location = str_replace( "\n", "", $location );
			$location = str_replace( "&amp;", "&", $location );

			list( $request, $response, $html ) = self::_curl( $location  );
			if ( $debug ) self::dump_request( $location, "", $request, $response, $html );

			return TRUE;
	}

	
	#--------------------------------------------------------------------------------------------------
	# * new_asset
	#--------------------------------------------------------------------------------------------------
	public function new_asset( $title, $channel, $slug_prefix = '', $slug_suffix = 'wnit', $content_type = "0" ) {
			$asset = NULL;
			
			if ( $this->logged_in_merlin ) {
				
				if ( $slug_prefix ) $slug_prefix = $slug_prefix . '-';
				$slug = $slug_prefix . $title . '-' . $slug_suffix;
				
				$slug = str_replace( ' ', '-', $slug );
				$slug = preg_replace( '/[^\w-]*/', '', $slug );
				$slug = strtolower( $slug );
				
				
				list( $request, $response, $html ) = self::_curl( self::URL_NEW_ASSET );
				$csrf = self::_html_extract( $html, array(
					array( "@", "csrfmiddlewaretoken" ),	
					array( "++", "value='" ),	
					array( "-", "'" ),	
				))[0];
				

				$title = urlencode( $title );
				$channel = urlencode( $channel );
				$slug = urlencode( $slug );
				$content_type = urlencode( $content_type );
				$data = "csrfmiddlewaretoken=$csrf&title=$title&contentchannel=$channel&slug=$slug&submit-button=";
				
				list( $request, $response, $html ) = self::_curl( self::URL_NEW_ASSET, $data );
				
;
				
				$pos1 = strpos( $response, 'Location: http://admin.merlin.pbs.org/django-admin/videoingester/videoasset/' );
				if ( !$pos1 ) {
					$this->status = "An unknown error occured adding the asset.";
					return FALSE;
				};
				

				$pos1 = $pos1 + strlen( 'Location: http://admin.merlin.pbs.org/django-admin/videoingester/videoasset/' );
				$pos2 = strpos( $response, '/', $pos1 );
				$asset = (int)substr( $response, $pos1, $pos2 - $pos1 );
				

				
				$this->status = "good";
			
			} else {
				$this->status = "Not Logged into merlin.pbs.org";	
			}
			
			return $asset;
	}	
	
	#--------------------------------------------------------------------------------------------------
	# * new_chapter
	#--------------------------------------------------------------------------------------------------
	public function new_chapter( $asset, $title, $start, $length,  $image, $short = "", $long = "", $tags = "" ) {
			
			if ( $this->logged_in_merlin ) {
				
				$start = (int)$start;
				$length = (int)$length;
				$assset = (int)$asset;
				
				
				$asset_data = $this->get_asset_data( $asset );
				
				$url = self::URL_CHAPTER . "?video_asset=" . (int)$asset;
				list( $request, $response, $html ) = self::_curl( $url );
				
				$token = self::_html_extract( $html, array(
					array( "@", "csrfmiddlewaretoken" ),	
					array( "++", "value='" ),	
					array( "-", "'" ),	
				))[0];
				


				$data = array( "_save" => "Save",
							   "video_asset" => $asset,
							   "title" =>  html_entity_decode( $title ),
							   "short_description" => $short,
							   "long_description" => $long,
							   "tags" => $tags,
							   'csrfmiddlewaretoken' => $token
							   );
			
				
				/*
					POST = 
						video_asset=
						title=
						short_description=
						long_description=
						tags=
						start_time_0=
						start_time_1=
						start_time_2=
						start_time_3=
						end_time_0=
						end_time_1=
						end_time_2=
						end_time_3=
						_save=Save
						image={IMGPOST}
				*/
				
				$end = $start + $length;
				
				$hours = (int)($start / 3600);
				$start -= $hours * 3600;
				$minutes = (int)($start / 60);
				$start -= $minutes * 60;
				$seconds = $start;
				$ms = 0;
				
				$data["start_time_0"] = $hours;
				$data["start_time_1"] = $minutes;
				$data["start_time_2"] = $seconds;
				$data["start_time_3"] = $ms;
				

				$hours = (int)($end / 3600);
				$end -= $hours * 3600;
				$minutes = (int)($end / 60);
				$end -= $minutes * 60;
				$seconds = $end;
				$ms = 0;
				
				$data["end_time_0"] = $hours;
				$data["end_time_1"] = $minutes;
				$data["end_time_2"] = $seconds;
				$data["end_time_3"] = $ms;
				

				if ( !file_exists( $image ) ) {
					$this->status = "The given file ($image) does not exist.";
					return NULL;
				}
				
				$data['image'] = '@'  . $image;
				

				list( $request, $response, $html ) = self::_curl( $url, $data );
				
				if ( !strpos( $html, "window.parent.close_dialog();" ) ) {
					if ( strpos( $html, "End can't be greater than the video asset's saved length" ) ) {
						$this->status = "End can't be greater than the video asset's saved length";
						return;
					}
					
					$this->status = "An error occured submitting the form.";
					return;
				}
			
			} else {
				$this->status = "Not Logged into merlin.pbs.org";	
			}
			
			return $asset;
	}	
	
	
	
	#--------------------------------------------------------------------------------------------------
	# * new_asset
	#--------------------------------------------------------------------------------------------------
	public function edit_asset( $asset, $data ) {
			
			if ( $this->logged_in_merlin ) {
				
			
				$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET );
				$formdata = $this->get_asset_data( $asset );

				
				
				if ( is_array( $data ) ) {
					foreach( $data as $name => $value ) {
						$formdata[ $name ] = $value;
					}
					$data =  "";
					foreach( $formdata as $name=>$value ) {
						$data .=  $name . '='  . urlencode( $value ) . '&';
					}
					$data .= "add_video_assets=&save_btn=Save";
				} else {
					if ( $data ) $data .= "&";
					foreach( $formdata as $name=>$value ) {
						$data .=  $name . '='  . urlencode( $value ) . '&';
					}
					$data .= "add_video_assets=&save_btn=Save";
				}
				

				list( $request, $response, $html ) = self::_curl( $url , $data );
				
				$errors = self::_get_form_errors( $html );
				

				if ( $errors ) {
					$this->status = implode( ", ", $errors );
				} else {
					$this->status = "good";
				}

			
			} else {
				$this->status = "Not Logged into merlin.pbs.org";	
			}
			
			return TRUE;
	}	
	
	
	
	#--------------------------------------------------------------------------------------------------
	# * get_asset_data
	#--------------------------------------------------------------------------------------------------
	public function get_asset_video( $asset ) {
		$result = 0;

		if ( $this->logged_in_merlin ) {
		
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET );
			
			list( $request, $response, $html ) = self::_curl( $url );
			
			$result = (int)self::_html_extract( $html, array(
				array( '@', 'TP Media ID' ),
				array( '++', "name=\"actual_tp_media_id\"" ),
				array( '++', '>' ),
				array( '-', '</' ),
			))[0];
			
			$this->status = "good";

		
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
			
		return $result;
	}
	#--------------------------------------------------------------------------------------------------
	# * _get_form_data
	#--------------------------------------------------------------------------------------------------
	private static function _get_form_errors( $html ) {
		$result = array();
		
		try{
			$error_html = self::_html_extract( $html, array( 
				array( '@', '"save-error"' ),
				array( '+', '<ul>' ) ,
				array( '-', '</ul>' ) 
			))[0];
			
			$result = self::_html_extract( $error_html, array( 
				array( '$+', '<li>' ),
				array( '-', '</li>' ) ,
				array( '%', '' )
			));
			
		}catch( \Exception $e ) {}
		
		return $result;
	}
	#--------------------------------------------------------------------------------------------------
	# * _get_form_data
	#--------------------------------------------------------------------------------------------------
	private static function _get_form_data( $html, $filter_buttons = true ) {
		$result = array();
		
		$fixed_html = strtolower( str_replace( "'", "\"", $html ) );
		
		$tags = self::_html_extract( $fixed_html, array(
			array( "$", 'name=' ),
			array( "r", '<' ),
			array( "-", '>' ),
			array( "%", '>' ),
		), $html);


		foreach( $tags as $tag ) {
			$fixed_tag = strtolower( str_replace( "'", "\"", $tag ) );
			
			$tagname = strtolower( self::_html_extract( $tag, array( array( "p", 1 ), array( "-", " " ) ), $tag )[0] );
			$name = NULL;
			try{
				$name = strtolower( self::_html_extract( $fixed_tag, array( array( "++", 'name="' ), array( "-", "\"" ) ), $tag )[0] );
			}catch ( \Exception $e ) {}
			
			
			if ( !$name ) continue;

			$value = NULL;
			switch( $tagname ) {
				case 'select':
					try{
						$select = self::_html_extract( $fixed_html, array( array( "++", $tag ), array( "-", '</select>' ) ), $html )[0];
						
						$value = self::_html_extract( $select, array(
							array( "@", 'selected' ),
							array( "r", '<' ),
							array( "++", 'value="' ),
							array( "-", '"' ),
						))[0];
					} catch ( \Exception $e ) {}
				break;
				
				case 'span':
				case 'textarea':
					try{
						$value = self::_html_extract( $fixed_html, array( array( "++", $tag ), array( "p", 1 ),  array( "-", "</$tagname" ) ), $html )[0];
						$value = html_entity_decode( $value );
					} catch ( \Exception $e ) {}
				break;
				case 'button':break;
				case 'input':
					$type = NULL;
					try{
						$type = self::_html_extract( $fixed_tag, array( array( "++", 'type="' ), array( "-", '"' ) ), $tag )[0];
					}catch( \Exception $e ) {}

					if ( $filter_buttons == false || ( $filter_buttons && $type != 'submit' ) ) {
						try{
							$value = self::_html_extract( $fixed_tag, array( array( "++", 'value="' ), array( "-", '"' ) ), $tag )[0];
						}catch( \Exception $e ) {}
					}
				break;
				
				default:
					try{
						$value = self::_html_extract( $fixed_tag, array( array( "++", 'value="' ), array( "-", '"' ) ), $tag )[0];
					}catch( \Exception $e ) {}
				break;
			}
			
			if ( $value !== NULL ) $result[ $name ] = $value;
		}
		

		
		return $result;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * get_asset_data
	#--------------------------------------------------------------------------------------------------
	public function get_asset_data( $asset ) {
			$result = array();

			if ( $this->logged_in_merlin ) {
			
				$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET );
				list( $request, $response, $html ) = self::_curl( $url );
				
				$result = self::_get_form_data( $html );
				$result['sync_web_videos'] = "on";
				$result['sync_mobile_videos'] = "on";
				$result['relation_type'] = "1";
				$result['embed-player-size'] = "512x288";
				
				$result['short_description'] = html_entity_decode( $result['short_description'] );
				$result['long_description'] = html_entity_decode( $result['long_description'] );
							
				$result['short_description']= preg_replace('/&#(\d+);/me',"chr(\\1)",$result['short_description']); #decimal notation
				$result['short_description']= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$result['short_description']);  #hex notation
				
				$result['long_description']= preg_replace('/&#(\d+);/me',"chr(\\1)",$result['long_description']); #decimal notation
				$result['long_description']= preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)",$result['long_description']);  #hex notation
							
				list( $request, $response, $html ) = self::_curl( $url , $data );


				$this->status = "good";
				
				
			
			} else {
				$this->status = "Not Logged into merlin.pbs.org";	
			}
			
			return $result;
	}	
	

	
	#--------------------------------------------------------------------------------------------------
	# * set_episode_url
	#--------------------------------------------------------------------------------------------------
	public function set_episode_url( $asset, $episodeurl ) {
		if ( $this->logged_in_merlin ) {
			
			
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET . "additional/" );
			list( $request, $response, $html ) = self::_curl( $url );
			

			$token = self::_html_extract( $html, array(
				array( '+', 'csrfmiddlewaretoken' ),
				array( '++', "value='" ),
				array( '-', "'" ),
			))[0];
			
			
			$data = array();
			$data[ "geoprofile_policy" ] 	= "1";
			$data[ "tv_rating" ] 			= "6";
			$data[ "csrfmiddlewaretoken" ] 	= $token;
			$data[ "episode_url" ]			= $episodeurl;
			$data_full = array();
			foreach( $data as $k => $v ) {
				$data_full[] = "$k=$v";
			}
			$data = implode( "&", $data_full );

			
			list( $request, $response, $html ) = self::_curl( $url , $data );

			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * get_video_status
	#--------------------------------------------------------------------------------------------------
	public function get_video_status( $asset ) {
		if ( $this->logged_in_merlin ) {
			
			
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET . "videos/" );
			list( $request, $response, $html ) = self::_curl( $url );


			if ( strpos( $html, "video-status-" ) !== false ) {
				$status = trim( self::_html_extract( $html, array(
					array( '+', 'video-status-' ),
					array( '++', '>' ),
					array( '-', '</' ),
				))[0] );
				switch ( $status ) {
					case "Ingestion Complete":
						return self::STATUS_COMPLETE;
					case "Ingestion in Progress":
					case "Protecting URLs":
					case "Delete in progress":
						return self::STATUS_PENDING;
					break;
					default:
						if ( strpos( $status, "Transcoding" ) !== false ) 
							return self::STATUS_PENDING;
						if ( strpos( $status, "Downloading" ) !== false ) 
							return self::STATUS_UPLOADING;
						if ( strpos( $status, "Uploading" ) !== false ) 
							return self::STATUS_UPLOADING;
						return self::STATUS_ERROR;
					break;
				}
			} else {
				return self::STATUS_EMPTY;
			}

			

			
			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * get_image_status
	#--------------------------------------------------------------------------------------------------
	public function get_image_status( $asset ) {
		
		
		if ( $this->logged_in_merlin ) {
			$this->status = "good";
			
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET . "images" );
			list( $request, $response, $html ) = self::_curl( $url );
			
			if ( strpos( $html, "delete-image" ) !== false ) {
				return self::STATUS_COMPLETE;
			} else {
				return self::STATUS_EMPTY;
			}
			

		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		
	}
	
	#--------------------------------------------------------------------------------------------------
	# * get_image
	#--------------------------------------------------------------------------------------------------
	public function get_image( $asset ) {
		$result = null;
		
		if ( $this->logged_in_merlin ) {
			$this->status = "good";
			
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET . "images" );
			list( $request, $response, $html ) = self::_curl( $url );
			
			$result = self::_html_extract( $html, array(
				array( '+', 'image-container' ),
				array( '+', "<img" ),
				array( "++", "src=\"" ),
				array( "-", "\"" )
			));
			

		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return $result;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * is_published
	#--------------------------------------------------------------------------------------------------
	public function is_published( $asset ) {
		$result = null;
		
		if ( $this->logged_in_merlin ) {
			$this->status = "good";
			
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET . "" );
			list( $request, $response, $html ) = self::_curl( $url );
			$data = strtolower( trim( self::_html_extract( $html, array(
				array( '++', 'videoasset-is-published' ),
				array( '++', ">" ),
				array( "-", "</" )
			))[0]) );
			$result = $data == "yes";
		
			

		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return $result;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * remove_all_videos
	#--------------------------------------------------------------------------------------------------
	public function remove_all_videos( $asset ) {
		if ( $this->logged_in_merlin ) {
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET ) . "videos/";
			list( $request, $response, $html ) = self::_curl( $url );
			
			$formdata = $this->get_asset_data( $asset );
			$vidurl = urlencode( $vidurl );
			$profile = $widescreen ? 1 : 5;
			
			$data = "csrfmiddlewaretoken=" . $formdata[ 'csrfmiddlewaretoken' ];
			$data .= "&caption_location=&submit=&video_delete=on&video_location=&caption_justification=" . urlencode( "Not required for this program, and none are available." ) ;
			list( $request, $response, $html ) = self::_curl( $url , $data );
			
			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	
	
	#--------------------------------------------------------------------------------------------------
	# * remove_all_images
	#--------------------------------------------------------------------------------------------------
	public function remove_all_images( $asset ) {
		if ( $this->logged_in_merlin ) {
			

			
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET ) . "images/";
			list( $request, $response, $html ) = self::_curl( $url );
			$formdata = $this->get_asset_data( $asset );
			
			if ( strpos( $html, "image_files-0-id" ) !== false ) {
				$id = self::_html_extract( $html, array(
					array( '+', 'name="image_files-0-id"' ),
					array( '++', "value=\"" ),
					array( '-', "\"" ),
				))[0];
	
				$data = "csrfmiddlewaretoken=" . $formdata[ 'csrfmiddlewaretoken' ];
				$data .= "&image_files-TOTAL_FORMS=2&image_files-INITIAL_FORMS=1&image_files-MAX_NUM_FORMS=1000&image_files-0-id=$id&image_files-0-DELETE=on&image_files-1-original_url=&image_files-1-profile=&submit-button=";
				list( $request, $response, $html ) = self::_curl( $url , $data );
			}
			

			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	
	
	
	

	#--------------------------------------------------------------------------------------------------
	# * new_image
	#--------------------------------------------------------------------------------------------------
	public function new_image( $asset, $imgurl ) {
		if ( $this->logged_in_merlin ) {
			
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET ) . "images/";
			list( $request, $response, $html ) = self::_curl( $url );
			
			
			$formdata = $this->get_asset_data( $asset );
			$imgurl = urlencode( $imgurl );
			$data = "csrfmiddlewaretoken=" . $formdata[ 'csrfmiddlewaretoken' ];
			$data .= "&image_files-TOTAL_FORMS=1&image_files-INITIAL_FORMS=0&image_files-MAX_NUM_FORMS=1000&image_files-0-original_url=$imgurl&image_files-0-profile=9&submit-button=";
			
			list( $request, $response, $html ) = self::_curl( $url , $data );
			
			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	
	
	#--------------------------------------------------------------------------------------------------
	# * asset_overview
	#--------------------------------------------------------------------------------------------------
	public function asset_overview( $html ) {
		$result = array();
		
		$result['publishable'] = false;
		$result['videos'] = array();
		$result['any_raw_video'] = false;
		$result['video_raw_status'] = '';
		$result['video_all_ingested'] = false;
		
		$result['images'] = array();
		$result['image_raw_status'] = false;
		
		$form = self::_get_form_data( $html, false );
		
		//var_dump( $result );
		
		
		return $result;
		
	}
	
	#--------------------------------------------------------------------------------------------------
	# * get_chapter_info
	#--------------------------------------------------------------------------------------------------
	public function get_chapter_info( $asset )
	{
		$result = null;

		if ( $this->logged_in_merlin ) {
			
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET ) . "chapters/";
			list( $request, $response, $html ) = self::_curl( $url );
			
			
			$chapters_raw = self::_html_extract( $html, array(
				array( '$', 'edit-chapter' ),
				array( '++', ">" ),
				array( '-', "</a" ),
				array( "++", "<img" ),
				array( "++", "src=\"" ),
				array( "-", "\"" ),
				array( "++", "<td>" ),
				array( "++", "<td>" ),
				array( "-", "</td>" ),
				array( "%", "" ),
			));
			
			
			$chapter_count = count ( $chapters_raw ) / 3;
			$chapters = array();
			for ( $i = 0; $i < $chapter_count; $i++ )
			{
				$chapters[] = array( "title" => $chapters_raw[ ($i*3)+0 ],
								  "image" => $chapters_raw[ ($i*3)+1 ],
								  "length" => $chapters_raw[ ($i*3)+2 ]) ;
			}

			
			$result = &$chapters;
			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return $result;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * get_chapter_info
	#--------------------------------------------------------------------------------------------------
	public function get_video_info( $asset )
	{
		$result = null;

		if ( $this->logged_in_merlin ) {
			
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET ) . "videos/";
			list( $request, $response, $html ) = self::_curl( $url );
			
			
			
			$videos = array();
			try{
				$video_table = self::_html_extract( $html, array(
					array( '+', "output-video-files" ),
					array( '-', '</table' ),
				))[0];
				$raw_videos = self::_html_extract( $video_table, array(
					array( '$', "data-row" ),
					array( '+', 'Download' ),
					array( 'r', '<' ),
					array( '++', "href=\"" ),
					array( '-', "\"" ),
					array( '++', "<td>" ),
					array( '-', "</td" ),
					array( '%', "" ),
				));
			}catch( \Exception $e ) {}
			
		
			
			for ( $i = 0; $i < count( $raw_videos )/2; $i++ )
			{
				$video = array();
				$video[ $raw_videos[ $i * 2 + 1 ] ] = $raw_videos[ $i * 2 ];
				$videos[] = $video;
			}
			
			$result = &$videos;
			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return $result;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * new_video
	#--------------------------------------------------------------------------------------------------
	public function new_video( $asset, $vidurl, $widescreen = TRUE ) {

		if ( $this->logged_in_merlin ) {
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET ) . "videos/";
			
			
			$formdata = $this->get_asset_data( $asset );
			$vidurl = urlencode( $vidurl );
			$profile = $widescreen ? 1 : 5;
			
			$data = "csrfmiddlewaretoken=" . $formdata[ 'csrfmiddlewaretoken' ];
			$data .= "&caption_location=&submit=&video_location=$vidurl&video_profile=$profile&caption_justification=" . urlencode( "Not required for this program, and none are available." ) ;
			list( $request, $response, $html ) = self::_curl( $url , $data );
			

		
			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	

	#--------------------------------------------------------------------------------------------------
	# * video_transcode
	#--------------------------------------------------------------------------------------------------
	public function video_transcode(  $asset, $caption_justify = false ) {
		if ( $this->logged_in_merlin ) {
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET );
			
			$formdata = $this->get_asset_data( $asset );
			
			if ( $caption_justify ) {
				$formdata['caption_justification'] = $caption_justify ;	
			}
			
			
			$data =  "";
			foreach( $formdata as $name=>$value ) {
				$data .=  $name . '='  . urlencode( $value ) . '&';
			}
			$data .= "add_video_assets=&transcode_btn=Transcode";
			list( $request, $response, $html ) = self::_curl( $url , $data );
			
			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * delete_all_chapters
	#--------------------------------------------------------------------------------------------------
	public function delete_all_chapters( $asset ) {

		if ( $this->logged_in_merlin ) {

			$url = str_replace( "{ID}", (int)$asset, self::URL_CHAPTER_DELETE );
			list( $request, $response, $html ) = self::_curl( $url );
			

			$token = self::_html_extract( $html, array(
				array( '+', 'csrfmiddlewaretoken' ),
				array( '++', "value='" ),
				array( '-', "'" ),
			))[0];
			
			$chapters = self::_html_extract( $html, array(
				array( '$', '_selected_action' ),
				array( 'r', "<" ),
				array( '++', "value=\"" ),
				array( '-', "\"" ),
				array( "++", ">" ),
				array( "%", "" ),
			));
			

			
			$data_array = array();
			$data_array[] = "csrfmiddlewaretoken=$token";
			$data_array[] = "action=delete_selected";
			$data_array[] = "post=yes";
			foreach ( $chapters as $c ) {
				$data_array[] = "_selected_action=$c";
			}
			$data = implode( "&", $data_array );
			

			
			list( $request, $response, $html ) = self::_curl( $url, $data );
	
			
			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	
	
	#--------------------------------------------------------------------------------------------------
	# * publish_asset
	#--------------------------------------------------------------------------------------------------
	public function publish_asset(  $asset ) {

		if ( $this->logged_in_merlin ) {
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET . "publish/" );
			
			$formdata = $this->get_asset_data( $asset );
			$csr = $formdata[ 'csrfmiddlewaretoken' ];
			$data = "csrfmiddlewaretoken=$csr&published=on&submit-button=publish";
			
			list( $request, $response, $html ) = self::_curl( $url , $data );

			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	
	#--------------------------------------------------------------------------------------------------
	# * unpublish_asset
	#--------------------------------------------------------------------------------------------------
	public function unpublish_asset(  $asset ) {

		if ( $this->logged_in_merlin ) {
			$url = str_replace( "{ID}", (int)$asset, self::URL_ASSET . "publish/" );
			
			$formdata = $this->get_asset_data( $asset );
			$csr = $formdata[ 'csrfmiddlewaretoken' ];
			$data = "csrfmiddlewaretoken=$csr&submit-button=unpublish";
			list( $request, $response, $html ) = self::_curl( $url , $data );

			$this->status = "good";
		} else {
			$this->status = "Not Logged into merlin.pbs.org";	
		}
		return NULL;
	}
	
	
	#--------------------------------------------------------------------------------------------------
	# * _find_atom
	#--------------------------------------------------------------------------------------------------
	static private function _find_atom( $file_handle, $atom_type, $max_length )
	{
		
		$bytes_read = 0;
		while ( TRUE ) 
		{
			if ( $bytes_read+4 > $max_length ) return false;
			$atom_len = fread( $file_handle, 4 );
			$bytes_read += 4;
			$atom_len = unpack(  "N", $atom_len )[1];
			
			
			
			if ( $atom_len == 0 ) return false;
			if ( $atom_len == 1 ) return false;
			
			if ( $bytes_read+4 > $max_length ) return false;
			$a = fread( $file_handle, 4 );
			$bytes_read += 4;
			$a = unpack(  "N", $a )[1];
			if ( $a == $atom_type ) 
				return $atom_len;
			else 
			{

				if ( ($bytes_read + $atom_len - 8) > $max_length ) return false;
				fseek( $file_handle, $atom_len-8, SEEK_CUR );
				$$bytes_read += $atom_len-8; 
			}	
		}
	}
	
	#--------------------------------------------------------------------------------------------------
	# * getAspectRatio
	#--------------------------------------------------------------------------------------------------
	static public function getAspectRatio( $movie_path )
	{
		$ratio = false;
		

		$max_size = filesize( $movie_path );
		$f =  fopen( $movie_path , "r" );
		
		$moov = unpack(  "N", "moov" )[1];
		$tkhd = unpack(  "N", "tkhd" )[1];
		$trak = unpack(  "N", "trak" )[1];
		


		if ( $atom_size = self::_find_atom( $f, $moov, $max_size ) ) 
			if ( $atom_size = self::_find_atom( $f, $trak, $atom_size ) ) 
				if ( $atom_size = self::_find_atom( $f, $tkhd, $atom_size ) ) 
				{
					
					fseek( $f, 0x4A, SEEK_CUR );
					$width = fread( $f, 4 );
					$width = unpack(  "N", $width )[1];
					
					$height = fread( $f, 4 );
					$height = unpack(  "N", $height )[1];
					
					return $width / $height;
				}
		
		
		
		fclose( $f );
		
		return $ratio;
	}


}



?>