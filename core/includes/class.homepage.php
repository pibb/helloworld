<?php
#============================================================================================================
# ** HomePage Class
#============================================================================================================
namespace Core;
{
	require( 'library/class.webpage.php' );
	require( 'library/data/class.article.php' );
	require( 'library/data/class.banner.php' );
	require( 'library/data/class.episode.php' );
	require( 'library/class.tvssapi.php' );

	class HomePage extends WebPage
	{
		const FEATURED_LOCAL_PROGRAM = 1; // Experience Michiana
		const ARTICLE_NEWS_TYPE = 1;
		const TWITTER_CONSUMER_KEY = "JkUjODJPrf5fj3hVYulg";
		const TWITTER_CONSUMER_SECRET = "zoWwjzdAQkPecEC3qLTkvGDy8M8JIzh9hidgfhw90";
		const TWITTER_ACCESS_TOKEN = "133427478-oIYhTcFVgeZGAqAeziJ1p32aN0tZjvj0gQ7AsMLR";
		const TWITTER_ACCESS_TOKEN_SECRET = "hdiu0YSVRaoJM3kErAubsMYGTrn1cia5vEx0FOy0";
		const TWITTER_CHECK = 600; // 10 minutes
		#----------------------------------------------------------------------------------------------------
		# * Constructor
		#----------------------------------------------------------------------------------------------------
		public function __construct( $title, $auto_header = true, $css = array(), $js = array(), $body_class = array() )
		{
			// initialize
			$mod = isset( $_GET['style'] ) ? array( 'home.css', $_GET[ 'style'] ) : array( 'home.css' );
			parent::__construct( $title, $auto_header, $mod, array(), array( 'index' ) );
			$this->check_schedules();
			
			// get database items
			$news 		= Article::get_published_array( "%s AND article_type = '" . self::ARTICLE_NEWS_TYPE . "' ORDER BY article_created DESC", $this );
			$banners 	= Banner::get_published_array( "%s AND (banner_end = '' OR banner_end > '" . time() . "') ORDER BY banner_end ASC" );
			$ad 		= Ad::get_published_array( "%s AND x.page_id = '" . $this->page_id . "' ORDER BY RAND() LIMIT 1" );
			$ad 		= is_array( $ad ) && $ad ? $ad[ 0 ] : false;
			$national 	= $this->get_national_banner();
			
			$ubanners = array();
			foreach( $banners as $index => $banner )
				foreach( $banner as $key => $value )
					$ubanners[ $index ][ strtoupper( $key ) ] = $value;
			unset( $banners );
			
			// local news (get one from EM, then 3 from the other programs)
			$local = array();//Episode::get_published( "e.episode_program = '" . self::FEATURED_LOCAL_PROGRAM . "' AND e.episode_enabled_video = '1' %s LIMIT 1", $this );
			//$local = array_merge( $local, Episode::get_published( "e.episode_program != '" . self::FEATURED_LOCAL_PROGRAM . "' AND e.episode_enabled_video = '1' %s LIMIT 3", $this, false ) );
			
			
			
			// build body
			$body = new Template( $this, "home.html" );
			$body->add_vars( array
			(
				'V_NEWS' => $news,
				'V_BANNERS' => $ubanners,
				'V_LOCAL' => $local,
				'V_WHATSON_NOW' => $this->schedules[ 'now' ][ '34.1' ],
				'V_WHATSON_NOW2' => $this->schedules[ 'now' ][ '34.2' ],
				'V_WHATSON_TONIGHT' => $this->schedules[ 'tonight' ]['34.1'],
				'V_WHATSON_TONIGHT2' => $this->schedules[ 'tonight' ]['34.1'],
				'U_HOMEAD' => $ad ? $ad[ 'img' ] : "",
				'U_HOMEAD_HREF' => $ad ? $ad[ 'href' ] : "",
				'V_HOMEAD_NAME' => $ad ? $ad[ 'name' ] : "",
				'V_NATIONAL_BANNER' => $national ? $national[ 'img' ] : "",
				'V_NATIONAL_URL' => $national ? $national[ 'href' ] : "",
				'V_NATIONAL_NAME' => $national ? $national[ 'name' ] : "",
				'V_LOADTIME' => $this->stop_timer()
			) );
			
			echo $this->head . $body;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * Check Schedules
		#--------------------------------------------------------------------------------------------------
		protected function check_schedules()
		{
			
			// initialize variables
			$tvss = new TVSS_API;
			$tv_now		= $this->schedules[ 'now' ];
			$tv_tonight	= $this->schedules[ 'tonight' ];
			$tv_recheck	= time() - $this->schedules[ 'check' ];
			
			// see if we need to recheck the tv schedule
			if ( $tv_recheck >= 1800 )
				$tv_recheck = true;
			else
			{
				// if less than 30 minutes have passed, check to see if we passed the nearest thirty on the clock
				$old_min = (int)date( "i", $this->schedules[ 'check' ] ) > 30 ? 30 : 0;
				$new_min = (int)date( "i", time() ) > 30 ? 30 : 0;
				$tv_recheck = $old_min != $new_min ? true : false;
			}
			
			// check api
			if ( !$tv_now || !$tv_tonight || $tv_recheck )
			{
				$tv_now		= $tvss->schedule_now();
				$tv_tonight	= $tvss->schedule_tonight();
				$schedules = array( 'check' => time(), 'now' => $tv_now, 'tonight' => $tv_tonight );
				mysql_query( "UPDATE " . Database::CONFIG . " SET config_value = '" . addslashes( serialize( $schedules ) ) . "' WHERE config_id = 'schedules' LIMIT 1" );
				$this->schedules = array( 'check' => time(), 'now' => $tv_now, 'tonight' => $tv_tonight );
			}
		}
		
		#--------------------------------------------------------------------------------------------------
		# * Get National Banner
		#--------------------------------------------------------------------------------------------------
		protected function get_national_banner()
		{
			$national = array();
			$national[] = array( 'href' => 'http://pbskids.org/peg/', 'img' => 'peg_cat.jpg', 'name' => "Peg + Cat Ad" );
			$national[] = array( 'href' => 'http://www.pbs.org/program/genealogy-roadshow/', 'img' => 'genealogy_roadshow.jpg', 'name' => "Genealogy Roadshow" );
			if ( $national )
			{
				srand( time() );
				$national = $national[ rand( 0, ( count( $national ) - 1 ) ) ];
			}
			return $national;
		}
	}
}
?>
