<?php
namespace Core;

if ( !defined( "D_CLASS_WEBPAGE" ) )
{
	define( "D_CLASS_WEBPAGE", true );
	require( 'class.webpagelite.php' );
	require( 'class.template.php' );
	require( 'class.webheader.php' );
	require( 'class.webfooter.php' );
	require( 'data/class.competition.php' );
	
	/**
 	 * File: class.webpage.php
	 *
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class WebPage extends WebPageLite
	{	
		public $msg				= "";
		public $body_class		= array();
		//public $tab_menu		= array();
		public $tab				= 1;
		public $tab_nav			= 1000;
		//public $focus 			= "";
		public $auto_header		= true;
		//public $ad_prepend		= "";
		
		public $head 			= NULL;
		public $foot 			= NULL;
		
		protected $id			= NULL;
		protected $mode			= NULL;
		protected $action		= NULL;
		protected $query		= NULL;
		
		//protected $gzip			= false;
		protected $naked		= false;
		protected $on_page_index = false;
		//protected $is_compressed= false;
		
		protected $_getters 	= array( 	'db', 'debug', 'session', 'site', 'id', 'mode', 'action', 'query', 'gzip', 'lite', 
											'temp_urls', 'naked', 'is_compressed', 'page_id', 'temp_vars', 'temp_funcs',
											'page_start', 'page_stop', 'page_loadtime', 'on_page_index' );
		
		const FCC_URL 			= "https://stations.fcc.gov/station-profile/wnit";
		const FACEBOOK_URL 		= "https://www.facebook.com/wnitpublictv";
		const TWITTER_URL 		= "https://www.twitter.com/wnitpublictv";
		const PINTEREST_URL 	= "https://www.pinterest.com/wnitpublictv";
		const GOOGLE_URL 		= "https://plus.google.com/+WnitOrgPBS";
		const YOUTUBE_URL 		= "https://www.youtube.com/user/WNITpublicTelevision?";
		const VIDEOS_URL 		= "http://videos.wnit.org";
		const LOCALPROD_ID 		= 1;	
		const RISINGSTAR_ID		= 9;
		
		/**
		 * Class constructor. Sets up most of the general, header, and footer template work.
		 * 
		 * @todo Is the $user property still being used? It's referenced here.
		 * @throws Exception if the website is not enabled and user doesn't have permission to view it.
		 * @param string the title of the page if caller doesn't want to have it looked up. (Default = false)
		 * @param bool whether or not to parse the header at end.
		 * @param Array an array of page-specific CSS files to include.
		 * @param Array an array of page-specific JavaScript files to include.
		 * @param Array an array of classes to add to the body tag.
		 * @uses WebPageLite::__construct
		 * @uses WebPage::$body_class
		 * @uses WebPage::$auto_header
		 * @uses WebPage::$query
		 * @uses WebPage::$action
		 * @uses WebPage::$mode
		 * @uses WebPage::$msg
		 * @uses WebPage::$naked
		 * @uses WebPage::$id
		 * @uses WebPage::$body_class
		 * @uses WebPage::$on_page_index
		 * @uses WebPage::$head
		 * @uses WebPage::$foot
		 * @uses WebPage::get_title
		 * @uses WebPage::get_styles
		 * @uses WebPage::get_scripts
		 * @uses WebPage::anchor
		 * @uses WebPageLite::$lite
		 * @uses WebPageLite::$title
		 * @uses WebPageLite::$meta
		 * @uses WebPageLite::$page_id
		 * @uses WebPageLite::$session
		 * @uses WebPageLite::$temp_vars
		 * @uses WebPageLite::$temp_funcs
		 * @uses WebPageLite::stop_timer
		 * @uses WebPageLite::kill
		 * @uses Website::$owner_email
		 * @uses Website::$copyright
		 * @uses Website::$title
		 * @uses Website::$company_street
		 * @uses Website::$company_phone
		 * @uses Website::$company_email
		 * @uses Website::$site_enabled
		 * @uses Session::$logged_in
		 * @uses User::is_admin
		 * @uses Generic::nicenum to round social media counters
		 * @uses Globals::get to get general variables
		 */
		public function __construct( $title = false, $auto_header = false, $css = array(), $js = array(), $body_class = array() )
		{
			try
			{
				parent::__construct();
				
				$css[] = "fancybox.css";
				
				// set common variables
				//$this->gzip			= $this->gzip_begin();
				$this->body_class	= $body_class;
				$this->auto_header	= $auto_header;
				$this->query 		= trim( strtolower( Globals::get( 'query' ) ) );
				$this->action   	= Globals::get( 'action' );
				$this->mode     	= Globals::get( 'mode' );
				$this->msg			= Globals::get( 'msg' );
				$this->naked		= (bool)Globals::get( 'naked' );
				$this->id       	= Globals::get( 'id' );
				$this->title 		= $title ? $title : $this->get_title();
				
				// check for a lite layout
				if ( $this->lite || Globals::get( 'lite' ) )
					$this->body_class[] = "lite";
					
				// setup account menu
				$in_account = strpos( $this->page_id, "ACCOUNT_" ) !== false;
				$account = array();
				$account[ 'admin' ] = array();
				$account[ 'local' ] = array();
				
				if ( $in_account && $this->user instanceof User && $this->session->logged_in )
				{
					if ( $this->user->may( 'view', Database::USERS ) ) 	
						$account[ 'admin' ][] = array( 'id' => 'users', 'href' => $this->anchor( ACCOUNT_USERS ), 'name' => "Users", 'class' => ( $this->page_id == ACCOUNT_USERS ? "sel" : "" ) );
					if ( $this->user->may( 'view', Database::GROUPS ) ) 
						$account[ 'admin' ][] = array( 'id' => 'groups', 'href' => $this->anchor( ACCOUNT_GROUPS ), 'name' => "User Groups", 'class' => ( $this->page_id == ACCOUNT_GROUPS ? "sel" : "" ) );
					if ( $this->user->may( 'view', Database::PROGRAMS ) ) 
						$account[ 'admin' ][] = array( 'id' => 'programs', 'href' => $this->anchor( ACCOUNT_PROGRAMS ), 'name' => "Local Programs", 'class' => ( $this->page_id == ACCOUNT_PROGRAMS ? "sel" : "" ) );
					if ( $this->user->may( 'view', Database::NEWSLETTERS ) ) 	
						$account[ 'admin' ][] = array( 'id' => 'newsletters', 'href' => $this->anchor( ACCOUNT_NEWSLETTERS ), 'name' => "Newsletters", 'class' => ( $this->page_id == ACCOUNT_NEWSLETTERS ? "sel" : "" ) );
					if ( $this->user->may( 'view', Database::CONFIG ) ) 	
						$account[ 'admin' ][] = array( 'id' => 'config', 'href' => $this->anchor( ACCOUNT_CONFIG ), 'name' => "Config", 'class' => ( $this->page_id == ACCOUNT_CONFIG ? "sel" : "" ) );
					if ( $this->user->may( 'view', Database::ADS ) ) 	
						$account[ 'admin' ][] = array( 'id' => 'ads', 'href' => $this->anchor( ACCOUNT_ADVERTISEMENTS ), 'name' => "Ads", 'class' => ( $this->page_id == ACCOUNT_ADVERTISEMENTS ? "sel" : "" ) );
					if ( $this->user->may( 'view', Database::PROGCATS ) ) 	
						$account[ 'admin' ][] = array( 'id' => 'progcats', 'href' => $this->anchor( ACCOUNT_PROGRAM_CATEGORIES ), 'name' => "Program Categories", 'class' => ( $this->page_id == ACCOUNT_PROGRAM_CATEGORIES ? "sel" : "" ) );
					if ( $this->user->may( 'view', Database::SEGCATS ) ) 	
						$account[ 'admin' ][] = array( 'id' => 'segcats', 'href' => $this->anchor( ACCOUNT_SEGMENT_CATEGORIES ), 'name' => "Segment Categories", 'class' => ( $this->page_id == ACCOUNT_SEGMENT_CATEGORIES ? "sel" : "" ) );
					if ( $this->user->may( 'view', Database::PHOTOS ) ) 	
						$account[ 'admin' ][] = array( 'id' => 'photos', 'href' => $this->anchor( ACCOUNT_PHOTOS ), 'name' => "Photos", 'class' => ( $this->page_id == ACCOUNT_PHOTOS ? "sel" : "" ) );

					// list the local progarms
					$local_programs = Database::select( Database::PROGRAMS, "*", "program_category = '" . self::LOCALPROD_ID . "' AND program_deleted = '0' ORDER BY program_name" );
					foreach( $local_programs as $index => $p )
					{
						//if ( $this->user->may( 'view', Database::PROGRAMS, $p[ 'program_id' ] ) ) 	
						//{
							// show links
							$account[ 'local' ][ $index ][ 'name' ] = $p[ 'program_hashtag' ];
							$account[ 'local' ][ $index ][ 'links' ][] = array( 'id' => 'program-' . $p[ 'program_id' ], 'href' => $this->anchor( ACCOUNT_PROGRAMS, array( 'id' => $p[ 'program_id' ] ) ), 'name' => "Edit Program", 'class' => ( $this->page_id == ACCOUNT_PROGRAMS ? "sel" : "" ) );
							$account[ 'local' ][ $index ][ 'links' ][] = array( 'id' => 'programsite-' . $p[ 'program_id' ], 'href' => $p[ 'program_site' ], 'name' => "Visit Site" );
							$account[ 'local' ][ $index ][ 'links' ][] = array( 'id' => 'articles-' . $p[ 'program_id' ], 'href' => $this->anchor( ACCOUNT_ARTICLES, array( 'show' => $p[ 'program_id' ] ) ), 'name' => "Articles/Resources", 'class' => ( $this->page_id == ACCOUNT_ARTICLES ? "sel" : "" ) );
							
							// list episodes
							if ( $p[ 'program_episodes' ] )
								$account[ 'local' ][ $index ][ 'links' ][] = array( 'id' => 'episodes-' . $p[ 'program_id' ], 'href' => $this->anchor( ACCOUNT_EPISODES, array( 'show' => $p[ 'program_id' ] ) ), 'name' => "Episodes", 'class' => ( $this->page_id == ACCOUNT_EPISODES ? "sel" : "" ) );
							
							// list segments
							if ( $p[ 'program_segments' ] )
								$account[ 'local' ][ $index ][ 'links' ][] = array( 'id' => 'segments-' . $p[ 'program_id' ], 'href' => $this->anchor( ACCOUNT_SEGMENTS, array( 'show' => $p[ 'program_id' ] ) ), 'name' => "Segments", 'class' => ( $this->page_id == ACCOUNT_SEGMENTS ? "sel" : "" ) );
							
							// list guests
							if ( $p[ 'program_guests' ] )
							{
								$account[ 'local' ][ $index ][ 'links' ][] = array( 'id' => 'guests-' . $p[ 'program_id' ], 'href' => $this->anchor( ACCOUNT_GUESTS, array( 'show' => $p[ 'program_id' ] ) ), 'name' => "Guests", 'class' => ( $this->page_id == ACCOUNT_GUESTS ? "sel" : "" ) );
								$account[ 'local' ][ $index ][ 'links' ][] = array( 'id' => 'companies-' . $p[ 'program_id' ], 'href' => $this->anchor( ACCOUNT_COMPANIES, array( 'show' => $p[ 'program_id' ] ) ), 'name' => "Guests' Companies", 'class' => ( $this->page_id == ACCOUNT_COMPANIES ? "sel" : "" ) );
							}
							
							// rising star has unique pages
							if ( $p[ 'program_id' ] == self::RISINGSTAR_ID )
							{
								if ( $this->user->may( 'view', Database::COMPETITIONS ) )
								{
									$comps = Competition::get_published_array();
									$account[ 'local' ][ $index ][ 'links' ][] = array( 'id' => 'competitions-' . $p[ 'program_id' ], 'href' => $this->anchor( ACCOUNT_COMPETITIONS ), 'name' => "Competitions", 'class' => ( $this->page_id == ACCOUNT_COMPETITIONS ? "sel" : "" ) );
								}
								
								// list out the entries by competition year
								if ( $this->user->may( 'view', Database::ENTRIES ) )
									foreach( $comps as $c )
										$account[ 'local' ][ $index ][ 'links' ][] = array( 'id' => 'entries-' . $p[ 'program_id' ], 'href' => $this->anchor( ACCOUNT_ENTRIES, array( 'competition' => $c[ 'id' ] ) ), 'name' => $c[ 'name' ] . " Entries", 'class' => ( $this->page_id == ACCOUNT_ENTRIES ? "sel" : "" ) );
							}
						//}
					}
				}
				
				// setup base template variables
				$this->temp_vars = array
				(
					'V_YEAR' => date( "Y" ),
					'V_MODE' => $this->mode,
					'V_ACTION' => $this->action,
					'V_MSG' => $this->msg,
					'V_QUERY' => $this->query,
					'V_ID' => $this->id,
					'V_OWNER_EMAIL' => $this->site->owner_email,
					'V_COPYRIGHT' => $this->site->copyright,
					'V_TITLE' => $this->title,
					'V_COMPANY_NAME' => $this->site->title,
					'V_COMPANY_STREET' => $this->site->company_street,
					'V_COMPANY_PHONE' => $this->site->company_phone,
					'V_COMPANY_EMAIL' => $this->site->company_email,
					'V_META_DESCRIPTION' => $this->meta[ 'description' ],
					'V_META_KEYWORDS' => $this->meta[ 'keywords' ],
					'V_META_AUTHOR' => $this->meta[ 'author' ],
					'V_THEME' => $this->theme,
					'V_STYLES' => $this->get_styles( $css ),
					'V_SCRIPTS' => $this->get_scripts( $js ),
					'V_SEARCH' => htmlspecialchars( $this->query ),
					'V_FBLIKES' => Generic::nicenum( $this->count_fb ),
					'V_TWITTERFOLLOWERS' => Generic::nicenum( $this->count_twitter ),
					'V_GOOGLELIKES' => Generic::nicenum( $this->count_google ),
					'V_PAGE_ID'	=> $this->page_id,
					'V_LOGGED_IN' => $this->session && $this->session->logged_in,
					'V_IN_ACCOUNT' => $in_account,
					'V_ACCOUNT_ADMIN' => $account[ 'admin' ],
					'V_LOCAL_ADMIN' => $account[ 'local' ],
					'V_SUBMENU' => array(),
					'U_SHOW_INDEX' => "",
					'U_THIS' => $this->anchor( $this->page_id, $_GET ),
					'U_QUICKSEARCH' => $this->anchor( MAIN_SEARCH, array( 'mode' => 'quick' ) ),
					'U_URL' => $this->site->url,
					'U_LOCAL' => $this->site->local,
					'U_PROFILE' => $this->anchor( ACCOUNT_PROFILE ),
					'U_SETTINGS' => $this->anchor( ACCOUNT_SETTINGS ),
					'U_DASHBOARD' => $this->anchor( ACCOUNT_INDEX ),
					'U_LOGOUT' => $this->anchor( ACCOUNT_LOGIN, array( 'action' => 'logout' ) ),
					'U_USERS' => $this->anchor( ACCOUNT_USERS ),
					'U_ENTRIES' => $this->anchor( ACCOUNT_ENTRIES ),
					'U_AWARDS' => $this->anchor( ABOUT_AWARDS ),
					'U_BOARD' => $this->anchor( ABOUT_BOARD ),
					'U_MEETINGS' => $this->anchor( ABOUT_BOARD, array( 'mode' => 'meetings' ) ),
					'U_CAC' => $this->anchor( ABOUT_CAC ),
					'U_CC' => $this->anchor( ABOUT_CLOSEDCAPTIONING ),
					'U_ABOUT' => $this->anchor( ABOUT_INDEX ),
					'U_JOBS' => $this->anchor( ABOUT_JOBS ),
					'U_TERMS' => $this->anchor( ABOUT_LEGAL ),
					'U_OURWORK' => $this->anchor( ABOUT_OURWORK ),
					'U_HIRE' => $this->anchor( ABOUT_OURWORK, array( 'mode' => 'apply' ) ),
					'U_STAFF' => $this->anchor( ABOUT_STAFF ),
					'U_CAPITALCAMPAIGN' => $this->anchor( SUPPORT_CAPITALCAMPAIGNDONORS ),
					'U_CORPORATE' => $this->anchor( SUPPORT_CORPORATE ),
					'U_CORPORATEMATCHING' => $this->anchor( SUPPORT_CORPORATEMATCHING ),
					'U_DC' => $this->anchor( SUPPORT_DC ),
					'U_EVENTS' => $this->anchor( SUPPORT_EVENTS ),
					'U_SUPPORT' => $this->anchor( SUPPORT_INDEX ),
					'U_KIDSCLUB' => $this->anchor( SUPPORT_KIDSCLUB ),
					'U_MEMBERSHIP' => $this->anchor( SUPPORT_MEMBERSHIP ),
					'U_PARTNERS' => $this->anchor( SUPPORT_PARTNERS ),
					'U_SCHEDULE' => $this->anchor( PROGRAMS_SCHEDULE ),
					'U_PLANNEDGIVING' => $this->anchor( SUPPORT_PLANNEDGIVING ),
					'U_DONATE' => $this->anchor( SUPPORT_PLEDGE ),
					'U_UNDERWRITING' => $this->anchor( SUPPORT_UNDERWRITING ),
					'U_UNDERWRITE_WEBSITE' => $this->anchor( SUPPORT_UNDERWRITING, array( 'mode' => 'website' ) ),
					'U_VOLUNTEER' => $this->anchor( SUPPORT_VOLUNTEER ),
					'U_WHY' => $this->anchor( SUPPORT_WHY ),
					'U_CHANNELS' => $this->anchor( PROGRAMS_CHANNELS ),
					'U_HIGHLIGHTS' => $this->anchor( PROGRAMS_HIGHLIGHTS ),
					'U_PROGRAMS' => $this->anchor( PROGRAMS_INDEX ),
					'U_KIDSPROGRAMMING' => $this->anchor( KIDS_INDEX ),
					'U_LOCAL' => $this->anchor( PROGRAMS_LOCAL ),
					'U_ENGAGE' => $this->anchor( ENGAGE_INDEX ),
					'U_CONTACT' => $this->anchor( ENGAGE_INDEX ),
					'U_MOBILE' => $this->anchor( ENGAGE_MOBILE ),
					'U_NEWSLETTER' => $this->anchor( ENGAGE_NEWSLETTER ),
					'U_ARTICLES' => $this->anchor( MAIN_ARTICLES ),
					'U_SEARCH' => $this->anchor( MAIN_SEARCH ),
					'U_HOME' => $this->anchor( MAIN_INDEX ),
					'U_BECOMEGUEST' => $this->anchor( ENGAGE_INDEX, array( 'mode' => 'guest' ) ),
					'U_FCC' => self::FCC_URL,
					'U_FACEBOOK' => self::FACEBOOK_URL,
					'U_TWITTER' => self::TWITTER_URL,
					'U_GOOGLE' => self::GOOGLE_URL,
					'U_PINTEREST' => self::PINTEREST_URL,
					'U_YOUTUBE' => self::YOUTUBE_URL,
					'U_VIDEOS' => self::VIDEOS_URL,
					'U_ASK' => $this->anchor( ASKA_INDEX ),
					'U_BQ' => $this->anchor( BIGQUESTIONS_INDEX ),
					'U_OE' => $this->anchor( OUTDOORELEMENTS_INDEX ),
					'U_EO' => $this->anchor( ECONOMICOUTLOOK_INDEX ),
					'U_PS' => $this->anchor( POLITICALLYSPEAKING_INDEX ),
					'U_MRS' => $this->anchor( RISINGSTAR_INDEX ),
					'U_EM' => $this->anchor( EXPMICHIANA_INDEX ),
					'U_DB' => $this->anchor( DINNERANDABOOK_INDEX ),
					'U_HLTV' => $this->anchor( HARBORLIGHTSTV_INDEX )
				);
				
				// check home page
				if ( $this->page_id != MAIN_INDEX )
				{
					$this->temp_vars[ 'IS_HOME' ] = false;
					$this->on_page_index = false;
				}
				else
				{
					$this->on_page_index = true;
					$this->temp_vars[ 'IS_HOME' ] = true;
				}
				
				// set default template variables
				//$this->temp_vars[ 'NAKED' ]		= $this->naked ? "module" : "";
				//$this->temp_vars[ 'CONTENT' ] 	= $this->naked ? "" : "content";
				$this->temp_funcs[ 'TAB' ] 		= array( 'name' => array( $this, "get_tab" ) );
				$this->temp_funcs[ 'TAB_NAV' ] 	= array( 'name' => array( $this, "get_tab_nav" ) );
				//$this->temp_funcs[ 'MASTER' ] 	= array( 'name' => array( $this, print_masterbar ) );
				
				// run actions
				//$this->process_actions();
				
				// setup html
				$this->head	= new WebHeader( $this );
				$this->foot = new WebFooter( $this );
			
				// Check for disabled site
				
				global $alpha_switch;
				//2014-10-24: Whitelist IBS/WNIT IP blocks
				//WNIT: 199.8.48.0/24
				//IBS : 50.247.178.136/29
				
				if ( 0 && $_SERVER['REMOTE_ADDR'] != "50.247.178.142" && $_SERVER['REMOTE_ADDR'] != "199.8.48.226" && !$alpha_switch ) {
					if ( ( Website::BETA || !$this->site->enabled ) && ( !$this->user || !$this->user->may_always_login() ) && ( Globals::get( 'masterkey' ) != Website::MASTERKEY ) )
					{
						if ( strpos( $this->page_id, "RISINGSTAR_" ) === false && $this->page_id != ACCOUNT_LOGIN )
							throw new \Exception( "<p>This website is currently unavailable due to maintenance. Please try again later.</p>" );
						else
						{
							$this->lite = true;
							$this->body_class[] = "lite";
						}
					}
				}
				
				//if ( $auto_header ) echo $this->head;
				
				$this->stop_timer();
			}
			catch( \Exception $e )
			{
				$this->kill( $e );
			}
		}
		
		/**
		 * Takes a list of CSS files and creates HTML link tags using the nearest version.
		 *
		 * @param Array of CSS file names (i.e., Array('core.css','supplemental.css')).
		 * @uses WebPageLite::$theme
		 * @uses Website::$url
		 * @return string HTML
		 */
		public function get_styles( $css )
		{
			// initialize variables
			$html 	= "";
			$open	= '<link type="text/css" rel="stylesheet" media="all" href="';
			$close	= "\" />\n";
			$local	= 'themes/' . $this->theme . '/css/';
			$main	= $this->site->url . $local;
			$dir 	= "http://" . $_SERVER[ 'SERVER_NAME' ] . str_replace( basename( $_SERVER[ 'SCRIPT_NAME' ] ), "", $_SERVER[ 'SCRIPT_NAME' ] );
			
			// it's complicated, but in case the "beta" files are served to the live version of the site, this flag tells us to override the search to the css file
			if ( Website::BETA && defined( "OVERRIDE_ROOT" ) )
				$main = Website::BETA . substr( str_replace( basename( $_SERVER[ 'SCRIPT_NAME' ] ), "", $_SERVER[ 'SCRIPT_NAME' ] ), 1 ) . $local;
			
			
			
			// make sure all files actually exist before printing them
			foreach( $css as $f )
			{
				if ( strpos( $f, "http" ) !== false )
					$file_name = $f;
				else {
					//its even more complicated with alpha switch
					global $alpha_switch;
					if ( $alpha_switch ) {
						$subdir = strtolower( explode( "_", $this->page_id)[0] );
						
						if ( file_exists( $this->site->local . $subdir . "/" . $local . $f ) )
							$file_name = $this->site->url . "alpha/" . $subdir . "/" . $local . $f ;
						else
							$file_name = $this->site->url . $local . $f ;
						
					} else {
						$file_name = file_exists( $f ) ? $f : (file_exists( $local . $f ) ? ( $dir . $local . $f ) : ( $main . basename( $f ) ) );
					}
				}
				
				$html .= $open . $file_name . $close;
			}
			
			
			
			return $html;
		}
		
		/**
		 * Takes a list of JavaScript files and creates HTML script tags using the nearest version.
		 *
		 * @js Array of JavaScript file names (i.e., Array('general.js','supplemental.js')).
		 * @uses Website::$url
		 * @return string HTML
		 */
		public function get_scripts( $js )
		{
			$html 	= "";
			
			$url = $this->site->url;
			global $alpha_switch;
			if ( $alpha_switch ) {
				$url = "http://alpha.wnit.us/";
			}
			
			foreach( $js as $f )
				$html .= '<script type="text/javascript" src="' . ( strpos( $f, 'http' ) === false ? $url . 'includes/js/' : '' ) . $f . "\"></script>\n";
			
			return $html;
		}
		
		/**
		 * Tries to find a title of the current (or given) page.
		 *
		 * @param string specified page; otherwise it will assume $this->page_id. (Default = false)
		 * @param string the current page mode (used in the INI file). (Default = "")
		 * @param int the ID of the given show if we're on a show page. (Default = 0)
		 * @param int the ID of the row if we're in an admin area. (Default = 0)
		 * @uses Database::fetch_cell
		 * @uses Globals::get to find current mode or show id.
		 * @uses WebPageLite::$page_id
		 * @uses Website::$titles to look for preconfigured title listings.
		 * @uses Website::get_abbr title
		 * @return string the title of the page; "Unknown Page" if not found.
		 */
		public function get_title( $page_id = false, $mode = "", $show_id = 0, $row_id = 0 )
		{
			// initialize variables
			$title = "Unknown Page";
			$show_name = "";
			
			if ( !$page_id )
			{
				$page_id 	= $this->page_id;
				$mode 		= Globals::get( 'mode' );
				$show_id 	= (int)Globals::get( 'show' );
			}
			
			// look in the config file for page titles
			if ( isset( $this->site->titles[ $page_id ] ) )
			{
				if ( isset( $this->site->titles[ $page_id ][ $mode ] ) )
					$title = $this->site->titles[ $page_id ][ $mode ];
				else
					$title = $this->site->titles[ $page_id ][ 0 ];
					
				// get the show name if we're talking about it
				if ( $show_id )
					$title = $this->get_abbr_title( $this->db->fetch_cell( Database::PROGRAMS, "program_name", "program_id = '{$show_id}'" ) ) . " " . $title;
					
				if ( $row_id )
					$title .= " #{$row_id}";
			}
			
			return $title;
		}
		
		/**
		 * Get Abbriviated title.
		 *
		 * @param string Title to shortern
		 *
		 * @return string the shortened title
		 */
		public function get_abbr_title( $title )
		{
			$title = preg_replace( "/((&amp;|a)\s)+/i", "", $title );
			$words = explode( " ", $title );
			foreach( $words as $i => $w )
				$words[ $i ] = substr( $w, 0, 1 );
				
			$title = implode( ".", $words ) . ".";
			
			return $title;
		}
		
		/**
		 * Class destructor. It will automatically parse the footer if the header has already been parsed.
		 *
		 * @uses Template::$parsed
		 * @uses Template::parse
		 * @uses WebPageLite::__destruct
		 * @uses WebPage::$foot
		 * @uses WebPage::$head
		 */
		public function __destruct()
		{
			parent::__destruct();
			if ( $this->foot instanceof WebFooter && $this->head->parsed )
				$this->foot->parse();
				
			//if ( $this->is_compressed )
			//	$this->gzip_end();
		}
		
		/**
		 * A shortcut to Website::anchor.
		 *
		 * A shortcut to Website::anchor.
		 * <code>
		 * // the two statements below do the same thing.
		 * $webpage->anchor( MAIN_INDEX );
		 * $webpage->site->anchor( MAIN_INDEX );
		 * </code>
		 *
		 * @param string Page ID
		 * @param array GET arguments in an array form. (Default = array())
		 * @param bool whether or not to use html char for &. (Default = false)
		 * @param bool assume whether or not to revert to file reference instead of URL's if non_html_amp. (Default = true)
		 * @uses Website::anchor
		 * @return string URL
		 */
		public function anchor( $id, array $query = array(), $non_html_amp = false, $assume = true )
		{
			return $this->site->anchor( $id, $query, $non_html_amp, $assume );
		}
		
		/**
		 * A shortcut to Website::anchor_file.
		 *
		 * A shortcut to Website::anchor_file.
		 * <code>
		 * // the two statements below do the same thing.
		 * $webpage->anchor_file( MAIN_INDEX );
		 * $webpage->site->anchor_file( MAIN_INDEX );
		 * </code>
		 *
		 * @param string Page ID
		 * @param array GET arguments in an array form. (Default = array())
		 * @uses Website::anchor_file
		 * @return string URL
		 */
		public function anchor_file( $page, $query = array() )
		{
			return $this->site->anchor_file( $page, $query );
		}
		
		/**
		 * Increments $this->tab as we parse a template.
		 *
		 * @uses WebPage::$tab
		 * @return int the current tab index
		 */
		public function get_tab() 		{ return $this->tab++; }
		
		/**
		 * Increments $this->tab_nav as we parse a template.
		 *
		 * @uses WebPage::$tab_nav
		 * @return int the current tab_nav index
		 */
		public function get_tab_nav() 	{ return $this->tab_nav++; }
		
		#--------------------------------------------------------------------------------------------------
		# * Mail
		#--------------------------------------------------------------------------------------------------
		/*public function mail( $to, $subject, $msg, $name = "", $use_html = true, $has_html = false )
		{
			// initialize variables
			$sent 		= false;
			$headers	= "";
			$html 		= new Template( $this, "email." . ( $use_html ? 'html' : 'txt' ) );
			
			// set headers
			if ( !$use_html )
				$msg = strip_tags( $msg );
			else
			{
				$headers .= 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
				if ( !$has_html ) $msg = "<p>" . str_replace( "\n\n", "</p><p>", $msg ) . "</p>";
			}
			$headers .= "From: " . $this->site->company_email . "\r\n";
			
			// change template
			$html->add_var( "GREETING", $name ? "Dear {$name}" : "Hello" );
			$html->add_var( "SUBJECT", 	$subject );
			$html->add_var( "MESSAGE", 	$msg );
			
			return mail( $to, $subject, $html->parse(), $headers );
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# * GZip Begin
		#--------------------------------------------------------------------------------------------------
		# Description: 	This function ultimately buffers the page until the script has ended. It checked 
		#				PHP versions and looks for the appropriate extensions first.
		#--------------------------------------------------------------------------------------------------
		/*protected function gzip_begin()
		{
			ob_start();
			ob_implicit_flush( 0 );
		
			header( 'Content-Encoding: gzip' );
		
			return $this->is_compressed = true;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * GZip End
		#--------------------------------------------------------------------------------------------------
		# Description: 	This function flushes the buffer and GZIP's the contents of the page.
		#--------------------------------------------------------------------------------------------------
		protected function gzip_end()
		{
			$_gzip_contents = ob_get_contents();
			ob_end_clean();
		
			$_gzip_size 		= strlen( $_gzip_contents );
			$_gzip_crc 			= crc32( $_gzip_contents );
			$_gzip_contents 	= gzcompress( $_gzip_contents, 9 );
			$_gzip_contents 	= substr ($_gzip_contents, 0, strlen( $_gzip_contents ) - 4 );
		
			echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
			echo $_gzip_contents;
			echo pack( 'V', $_gzip_crc );
			echo pack( 'V', $_gzip_size );
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# * Process Actions
		#--------------------------------------------------------------------------------------------------
		# Description: 	This function processes any common actions related to the application.
		#--------------------------------------------------------------------------------------------------
		/*protected function process_actions()
		{
			try
			{
				if ( $this->action == Website::ACTION_LOGOUT )
				{
					$this->session->logout();
					$this->redirect( $this->anchor_file( ADMIN_INDEX ) );
				}
			}
			catch( \Exception $e )
			{
				$this->debug->add( E_USER_WARNING, $e->getMessage() );
			}
		}*/
	}
}
?>
