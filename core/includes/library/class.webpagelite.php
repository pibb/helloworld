<?php
namespace Core;

if ( !defined( "D_CLASS_WEBPAGELITE" ) )
{
	define( "D_CLASS_WEBPAGELITE", true );
	require( 'class.base.php' );
	require( 'class.website.php' );
	require( 'class.database.php' );
	require( "class.twitterapi.php" );
	require( 'data/class.session.php' );
	require( 'data/class.user.php' );
	
	/**
 	 * File: class.webpagelite.php
	 *
	 * @todo Utilize a Error Handler?
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class WebPageLite extends Base
	{	
		public $core_config 	= null;
		public $title			= "WNIT Public Television";
		public $theme			= "2014";
		public $refresh_cache	= true;
		public $tweet_check		= 0;
		public $tweet_recent	= "";
		public $per_page		= 20;
		public $session_expire	= self::SESSION_LEN;
		public $meta			= array( 'author' => "", 'keywords' => "", 'description' => "" );
		public $count_fb		= 0;
		public $count_twitter	= 0;
		public $count_google	= 0;
		public $count_recent	= 0;
		public $user			= NULL;
		public $schedules		= "";
		public $natprograms_check = 0;
		public $natprogram_list = "";
		public $kidsprogram_list = "";
		public $programlist_check = 0;
		public $lite			= false;
		public $temp_funcs		= array();
		//public $temp_urls		= array();
		public $temp_vars		= array();
		
		protected $page_id			= 0;
		protected $page_start		= 0;
		protected $page_stop		= 0;
		protected $page_loadtime	= 0;
		protected $session 		= NULL;
		protected $db 			= NULL;
		protected $site			= NULL;
		protected $_getters 	= array( 'db', 'session', 'user', 'site', 'page_start', 'page_stop', 'page_loadtime' );
		
		const SMCOUNT_CHECK 	= 43200; // 12 hours
		const SESSION_LEN		= 604800;
		
		/**
		 * Class constructor.
		 *
		 * @uses WebPageLite::$site
		 * @uses WebPageLite::$session
		 * @uses WebPageLite::$db
		 * @uses WebPageLite::set_cur_page
		 * @uses WebPageLite::ensure_security
		 * @uses WebPageLite::set_config
		 * @uses WebPageLite::start_timer
		 * @uses WebPageLite::kill
		 * @uses Session::run
		 */
		public function __construct()
		{
			
			try
			{
				global $_CORE_CONFIG;
				$this->core_config = $_CORE_CONFIG;
				
				// set up the website framework
				$this->site = new Website;
				
				// determine the current page
				$this->set_cur_page();
				
				// set objects
				if ( $this->core_config[ 'database_enabled' ] ) {
					$this->db		= new Database;
				}
				
				$this->session	= new Session( NULL, $this );
				$this->session->run();

				// final checks and settings
				$this->ensure_security();
				$this->set_config();
				
				$this->start_timer();
			}
			catch( \Exception $e )
			{
				$this->kill( $e );
			}
			
		}
		
		/**
		 * Class destructor.
		 *
		 * @uses WebPageLite::stop_timer
		 */
		public function __destruct()
		{
			$this->stop_timer();
		}
		
		/**
		 * Starts the page load timer.
		 *
		 * @uses WebPageLite::$page_start
		 * @uses WebPageLite::get_time
		 */
		public function start_timer()
		{
			$this->page_start = $this->get_time();
		}
		
		/**
		 * Stops the page load timer.
		 *
		 * @uses WebPageLite::$page_start
		 * @uses WebPageLite::$page_stop
		 * @uses WebPageLite::$page_loadtime
		 * @uses WebPageLite::get_time
		 * @return int
		 */
		public function stop_timer()
		{
			$this->page_stop = $this->get_time();
			return $this->page_loadtime = round( ( $this->page_stop - $this->page_start ), 4 );
		}
		
		/**
		 * Get the current microtime.
		 *
		 * @return int
		 */
		public function get_time()
		{
			$time = microtime();
			$time = explode( ' ', $time );
			return $time[ 1 ] + $time[ 0 ];
		}
		
		/**
		 * Ends the script and issues any error messages.
		 *
		 * @param string the error message to display.
		 * @uses WebPageLite::$lite
		 * @uses WebPageLite::$body_class
		 * @uses WebPageLite::$head
		 * @uses WebPageLite::$foot
		 * @uses WebPageLite::$title
		 * @uses WebPageLite::$head
		 * @uses WebPageLite::$page_id
		 * @uses WebPageLite::redirect
		 * @uses Website::$enabled
		 * @uses Website::anchor_file
		 * @uses Session::$level
		 */
		public function kill( $msg )
		{
			// initialize variables
			$this->lite 		= true;
			$this->body_class	= array( 'lite' );
			$this->head 		= new WebHeader( $this, array( 'lite.css' ) );
			$this->foot 		= new WebFooter( $this );
			$this->title		= $this->site->enabled ? "Oops!" : "We're Sorry!";
			
			// if the website isn't even enabled, just show a generic message
			if ( Website::BETA || !$this->site->enabled )
			{
				if ( $this->page_id != MAIN_INDEX )
					$this->redirect( $this->site->anchor_file( MAIN_INDEX ) );
				else
				{
					if ( $this->head ) echo $this->head;
					echo $msg->getMessage();
				}
			}
			else if ( $this->session->level >= User::ADMIN )
				var_dump( $msg );
			else
				echo $msg->getMessage();
			
			// end the script
			exit;
		}
		
		/**
		 * Sends the appropriate headers to the User Agent to redirect the page to the given location.
		 *
		 * @param string url to redirect to.
		 * @uses Website::$url to prepend the website URL if protocol wasn't include.
		 * @uses Template::add_var to add the URL to the template redirection page.
		 */
		public function redirect( $url ) 
		{
			// initialize variables
			$first_char = substr( $url, 0, 1 );
			$url 		= str_replace( "\\", "/", substr( $url, ( $first_char == DIRECTORY_SEPARATOR ? 1 : 0 ) ) );
			
			// fix url if it was relative 
			if ( strpos( $url, "http://" ) === false )
				$url = $this->site->url . $url;
			
			// kick out newlines and returns
			if ( strstr( urldecode( $url ), "\n") || strstr( urldecode( $url ), "\r" ) )
				trigger_error( "Tried to redirect to potentially insecure url.", E_USER_ERROR );
			else
			{
				// redirect or make an exception for browsers that need meta redirection
				if ( !@preg_match( '/Microsoft|WebSTAR|Xitami/', getenv( 'SERVER_SOFTWARE' ) ) )
					header( "Location: {$url}" );
				else
				{
					header( "Refresh: 0; URL=" . $url );
					$html = new Template( $this, "redirect.html" );
					$html->add_var( "U_REDIRECT", $url );
					echo $html;
				}
				
				// end the script
				exit;
			}
		}
		
		/**
		 * Does basic security checks against website attacks.
		 *
		 * This important function should be called at the beginning of each script for some 
		 * security checks. Some clever attacks will be guarded against here.
		 *
		 * @throws Eception if an attack is detected
		 * @uses Generic::addslashes_to_array on PHP's global arrays.
		 * @uses Database::is_connected
		 * @uses Database::fetch_cell
		 * @uses Session::$id
		 * @uses Session::$logged_in
		 * @uses Session::logout
		 * @uses User::$changed_password
		 * @uses Globals::get
		 * @uses WebPageLite::$page_id
		 * @uses Website::anchor_file
		 */
		protected function ensure_security()
		{
			// Protects against GLOBALS tricks
			if ( isset( $_POST[ 'GLOBALS' ] ) || isset( $FILES[ 'GLOBALS' ] ) || isset( $_GET[ 'GLOBALS' ] ) || isset( $_COOKIE[ 'GLOBALS' ] ) )
				throw new \Exception( "Suspicious global variable activity detected." );
			
			// Protect against SESSION tricks
			if ( isset( $_SESSION ) && !is_array( $_SESSION ) )
				throw new \Exception( "Suspicious session activity detected." );
				
			// Protect against SQL statement breaks. These functions will work recursively so it reaches any depth.
			$_POST 		= Generic::addslashes_to_array( $_POST );
			$_GET 		= Generic::addslashes_to_array( $_GET );
			$_COOKIE 	= Generic::addslashes_to_array( $_COOKIE );
			
			// if the database was connected successfully, do some more perimeter checks
			if ( $this->db->is_connected() )
			{
				// Check User-Agent/Session. If the user-agent is not the same during original log in, make user log in again.
				$agent = $this->db->fetch_cell( Database::SESSIONS, "session_user_agent", "session_id = '" . $this->session->id . "' LIMIT 1" );
				if ( $this->session->logged_in && ( $agent != md5( $_SERVER[ 'HTTP_USER_AGENT' ] ) ) )
					$this->session->logout();
				
				// Check user permissions
				if ( strpos( $this->page_id, "ACCOUNT_" ) !== false )
				{
					$mode 	= Globals::get( 'mode' );
					$action = Globals::get( 'action' );
					
					if ( $action != Website::ACTION_LOGOUT && $this->session->logged_in && !$this->user->changed_password && ( $this->page_id != ACCOUNT_INDEX || ( $this->page_id == ACCOUNT_INDEX && $mode != "password" ) ) )
						$this->redirect( $this->site->anchor_file( ACCOUNT_INDEX, array( 'mode' => "password" ) ) );
					else if ( !$this->session->logged_in && $this->page_id != ACCOUNT_LOGIN )
						$this->redirect( $this->site->anchor_file( ACCOUNT_LOGIN ) );
				}
			}
		}
		
		/**
		 * Puts configuration table into an array.
		 *
		 * @uses Database::is_connected
		 * @uses Database::query
		 * @return Array of config variables
		 */
		public function get_config()
		{
			$config = array();
			
			// get configuration data
			if ( $this->db->is_connected() )
			{
				$result = $this->db->query( "SELECT * FROM " . Database::CONFIG );
				while( $row = mysql_fetch_array( $result, MYSQL_ASSOC ) )
					$config[ $row[ 'config_id' ] ] = $row[ 'config_value' ];
			}
			
			return $config;
		}
		
		/**
		 * Initializes constants based on how previous actions turned out.
		 *
		 * @todo Timezones from user accounts.
		 * @uses TwitterAPIExchange::num_followers
		 * @uses User::is_admin
		 * @uses WebPageLite::get_config
		 * @uses WebPageLite::$meta
		 * @uses WebPageLite::$theme
		 * @uses WebPageLite::$per_page
		 * @uses WebPageLite::$session_expire
		 * @uses WebPageLite::$refresh_cache
		 * @uses WebPageLite::$tweet_check
		 * @uses WebPageLite::$tweet_recent
		 * @uses WebPageLite::$count_fb
		 * @uses WebPageLite::$count_twitter
		 * @uses WebPageLite::$count_google
		 * @uses WebPageLite::$count_recent
		 * @uses WebPageLite::$schedules
		 * @uses WebPageLite::$natprograms_check
		 * @uses WebPageLite::$natprogram_list
		 * @uses WebPageLite::$kidsprogram_list
		 * @uses WebPageLite::$programlist_check
		 * @uses WebPageLite::$temp_vars
		 * @uses Website::$title
		 * @uses Website::$local_templates
		 * @uses Website::$owner_email
		 * @uses Website::$memory_alloc
		 * @uses Website::$enabled
		 * @uses Website::$register_enabled
		 * @uses Website::$must_change_password
		 * @uses Website::$copyright
		 * @uses Website::$title
		 * @uses Website::$url
		 * @uses Website::$urls
		 * @uses Website::anchor
		 */
		protected function set_config()
		{
			// load configuration data
			$config = $this->get_config();
			
			// update configs
			$this->meta[ 'author' ] 		= isset( $config[ 'meta_author' ] ) ? $config[ 'meta_author' ] : $this->meta[ 'author' ];
			$this->meta[ 'keywords' ] 		= isset( $config[ 'meta_keywords' ] ) ? $config[ 'meta_keywords' ] : $this->meta[ 'keywords' ];
			$this->meta[ 'description' ]	= isset( $config[ 'meta_description' ] ) ? $config[ 'meta_description' ] : $this->meta[ 'description' ];
			$this->theme 					= defined( "WNIT_THEME" ) && $this->user->is_admin() ? WNIT_THEME : ( isset( $config[ 'site_theme' ] ) && file_exists( $this->site->local . "themes" . DIRECTORY_SEPARATOR . $config[ 'site_theme' ] . DIRECTORY_SEPARATOR ) ? $config[ 'site_theme' ] : $this->theme );
			$this->per_page 				= isset( $config[ 'site_perpage' ] ) ? $config[ 'site_perpage' ] : $this->per_page;
			$this->session_expire			= isset( $config[ 'session_expire' ] ) ? $config[ 'session_expire' ] : $this->session_expire;
			$this->refresh_cache			= isset( $config[ 'refresh_cache' ] ) ? $config[ 'refresh_cache' ] : $this->refresh_cache;
			
			$this->site->title 				= isset( $config[ 'site_title' ] ) ? $config[ 'site_title' ] : $this->title;
			$this->site->local_templates	= $this->site->local . "themes" . DIRECTORY_SEPARATOR . $this->theme . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
			$this->site->owner_email		= isset( $config[ 'site_email' ] ) ? $config[ 'site_email' ] : $this->site->owner_email;
			$this->site->memory_alloc		= isset( $config[ 'server_memory' ] ) ? $config[ 'server_memory' ] : $this->site->memory_alloc;
			$this->site->enabled 			= isset( $config[ 'site_enabled' ] ) ? $config[ 'site_enabled' ] : $this->site->enabled;
			$this->site->register_enabled	= isset( $config[ 'register_enabled' ] ) ? $config[ 'register_enabled' ] : $this->site->register_enabled;
			$this->site->must_change_password	= isset( $config[ 'must_change_password' ] ) ? $config[ 'must_change_password' ] : $this->site->must_change_password;
			$this->site->copyright			= isset( $config[ 'site_copyright' ] ) ? $config[ 'site_copyright' ] : $this->site->copyright;
			$this->tweet_check				= isset( $config[ 'tweet_check' ] ) ? $config[ 'tweet_check' ] : $this->tweet_check;
			$this->tweet_recent				= isset( $config[ 'tweet_recent' ] ) ? $config[ 'tweet_recent' ] : $this->tweet_recent;
			$this->count_fb					= isset( $config[ 'count_fb' ] ) ? (int)$config[ 'count_fb' ] : $this->count_fb;
			$this->count_twitter			= isset( $config[ 'count_twitter' ] ) ? (int)$config[ 'count_twitter' ] : $this->count_twitter;
			$this->count_google				= isset( $config[ 'count_google' ] ) ? (int)$config[ 'count_google' ] : $this->count_google;
			$this->count_recent				= isset( $config[ 'count_recent' ] ) ? (int)$config[ 'count_recent' ] : $this->count_recent;
			$this->schedules				= isset( $config[ 'schedules' ] ) ? unserialize( $config[ 'schedules' ] ) : $this->schedules;
			$this->natprograms_check		= isset( $config[ 'natprograms_check' ] ) ? (int)$config[ 'natprograms_check' ] : $this->natprograms_check;
			$this->natprogram_list			= isset( $config[ 'natprogram_list' ] ) ? $config[ 'natprogram_list' ] : $this->natprogram_list;
			$this->kidsprogram_list			= isset( $config[ 'kidsprogram_list' ] ) ? $config[ 'kidsprogram_list' ] : $this->kidsprogram_list;
			$this->programlist_check		= isset( $config[ 'programlist_check' ] ) ? (int)$config[ 'programlist_check' ] : $this->programlist_check;
			
			// if it's been awhile, check again
			if ( time() - $this->count_recent >= self::SMCOUNT_CHECK )
			{
				// get last tweet
				if ( $num = (int)( new TwitterAPIExchange )->num_followers() )
				{
					$this->count_twitter = $num;
					mysql_query( "UPDATE " . Database::CONFIG . " SET config_value = '" . $num . "' WHERE config_id = 'count_twitter'" );
				}
				
				// get fb likes
				$fb = json_decode( file_get_contents( "http://graph.facebook.com/?id=https://www.facebook.com/wnitpublictv" ) );
				$fb2 = json_decode( file_get_contents( "http://graph.facebook.com/?id=http://www.wnit.org" ) );
				if ( $fb && $fb2 )
				{
					$this->count_fb = (int)$fb->likes + (int)$fb2->shares;
					mysql_query( "UPDATE " . Database::CONFIG . " SET config_value = '" . $this->count_fb . "' WHERE config_id = 'count_fb'" );
				}
			
				// google plus+++
				$google = json_decode( file_get_contents( "https://www.googleapis.com/plus/v1/people/109075826416540477450?key=AIzaSyASNsadUJU5WOk1sJ_PS5TgKXQwY7pSQ60" ) );
				if ( $google )
				{
					$this->count_google = (int)$google->plusOneCount + (int)$google->circledByCount;
					mysql_query( "UPDATE " . Database::CONFIG . " SET config_value = '" . $this->count_google . "' WHERE config_id = 'count_google'" );
				}
			
				// update time if it was successful
				if ( $num || $fb || $google )
				{
					$this->count_recent = time();
					mysql_query( "UPDATE " . Database::CONFIG . " SET config_value = '" . $this->count_recent . "' WHERE config_id = 'count_recent'" );
				}
			}
				
			// set common template variables
			$this->temp_vars[ 'THEME' ]			= $this->theme;
			$this->temp_vars[ 'TITLE' ]			= $this->title;
			$this->temp_vars[ 'FANCYCONFIG' ]	= isset( $config[ 'fancy_config' ] ) ? $config[ 'fancy_config' ] : "'overlayColor':'#333','height':'auto','autoDimensions':false";
			$this->temp_vars[ 'FBLIKE' ]		= isset( $config[ 'site_fblike' ] ) ? $config[ 'site_fblike' ] : "";
			
			// ini modifications
			$zone = "America/New_York";
			/*switch( $this->session->timezone )
			{
				case -12.0: $zone = "Kwajalein"; break;				//Eniwetok, Kwajalein
				case -11.0: $zone = "Pacific/Midway"; break; 		//Midway Island, Samoa
				case -10.0: $zone = "Pacific/Honolulu"; break;		//Hawaii
				case -9.0: $zone = "America/Anchorage"; break;		//Alaska
				case -8.0: $zone = "America/Los_Angeles"; break;	//Pacific Time (US &amp; Canada)
				case -7.0: $zone = "America/Denver"; break;			//Mountain Time (US &amp; Canada)
				case -6.0: $zone = "America/Chicago"; break;		//Central Time (US &amp; Canada), Mexico City
				default:
				case -5.0: $zone = "America/New_York"; break;		//Eastern Time (US &amp; Canada), Bogota, Lima
				case -4.0: $zone = "America/Halifax"; break;		//Atlantic Time (Canada), Caracas, La Paz
				case -3.5: $zone = "America/St_Johns"; break;		//Newfoundland
				case -3.0: $zone = "America/Sao_Paulo"; break;		//Brazil, Buenos Aires, Georgetown
				case -2.0: $zone = "Atlantic/South_Georgia"; break;	//Mid-Atlantic
				case -1.0: $zone = "Atlantic/Azores"; break;		//Azores, Cape Verde Islands
				case 0.0: $zone = "Europe/London"; break;			//Western Europe Time, London, Lisbon, Casablanca
				case 1.0: $zone = "Europe/Brussels"; break;			//Brussels, Copenhagen, Madrid, Paris
				case 2.0: $zone = "Africa/Cairo"; break;			//Kaliningrad, South Africa
				case 3.0: $zone = "Asia/Baghdad"; break;			//Baghdad, Riyadh, Moscow, St. Petersburg
				case 3.5: $zone = "Asia/Tehran"; break;				//Tehran
				case 4.0: $zone = "Asia/Muscat"; break;				//Abu Dhabi, Muscat, Baku, Tbilisi
				case 5.0: $zone = "Asia/Yekaterinburg"; break;		//Ekaterinburg, Islamabad, Karachi, Tashkent
				case 5.5: $zone = "Asia/Kolkata"; break;			//Bombay, Calcutta, Madras, New Delhi
				case 5.7: $zone = "Asia/Katmandu"; break;			//Kathmandu
				case 6.0: $zone = "Asia/Almaty"; break;				//Almaty, Dhaka, Colombo
				case 7.0: $zone = "Asia/Bangkok"; break;			//Bangkok, Hanoi, Jakarta
				case 8.0: $zone = "Asia/Brunei"; break;				//Beijing, Perth, Singapore, Hong Kong
				case 9.0: $zone = "Asia/Tokyo"; break;				//Tokyo, Seoul, Osaka, Sapporo, Yakutsk
				case 9.5: $zone = "Australia/Adelaide"; break;		//Adelaide, Darwin
				case 10.0: $zone = "Australia/Sydney"; break;		//Eastern Australia, Guam, Vladivostok
				case 11.0: $zone = "Asia/Magadan"; break;			//Magadan, Solomon Islands, New Caledonia
				case 12.0: $zone = "Pacific/Auckland"; break;		//Auckland, Wellington, Fiji, Kamchatka
			}*/
				
			// make ini file adjustments
			if ( defined( "MEMORY_ALLOCATION" ) )
				ini_set( "memory_limit", $this->site->memory_alloc );
			ini_set( "date.timezone", $zone );
				
			// more template stuff
			$this->temp_vars[ 'URL' ] 		= $this->site->url;
			$this->temp_vars[ 'THROBBER' ] 	= $this->site->url . "themes/" . $this->theme . "/images/loading.gif";
			
			if ( isset( $this->site->urls[ $this->page_id ] ) )
				$this->temp_vars[ 'THIS' ] = $this->site->anchor( $this->page_id, $_GET );
		}
		
		/**
		 * Sets the current Page ID depending upon what file this is.
		 *
		 * @uses WebPageLite::$page_id
		 * @uses Website::$files
		 */
		protected function set_cur_page()
		{
			// set default page
			$this->page_id = Website::ERROR_404;
			
			// determine what page we're on
			foreach( $this->site->files as $i => $file )
				if ( $_SERVER[ 'PHP_SELF' ] == ( "/" . Website::HOME_DIR . str_replace( DIRECTORY_SEPARATOR, "/", $file ) . Website::REAL_EXT ) )
					$this->page_id = $i;
		}
		

		

	}
}
?>