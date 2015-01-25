<?php
namespace Core;
if ( !defined( "D_CLASS_SHOWHOMEPAGE" ) )
{
	define( "D_CLASS_SHOWHOMEPAGE", true );
	class ShowHomePage extends Template
	{
		const VIDEO_LIMIT 	= 4;
		//const MODE_LOAD		= "loadvideos";
		#--------------------------------------------------------------------------------------------------
		# * Constructor
		#--------------------------------------------------------------------------------------------------
		public function __construct( WebPage &$webpage )
		{	
			parent::__construct( $webpage, "show_home.html" );
			
			$images = $this->webpage->site->url . "themes/" . $this->webpage->theme . "/images/";
			$thumb = "themes" . DIRECTORY_SEPARATOR . $webpage->theme . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR . "banner.png";
			
			$this->add_vars( array
			(
				"V_SHOW_TITLE" 	=> $this->webpage->show->name,
				"V_DESCRIPTION" => $this->webpage->show->description,
				"V_TWITTER" 	=> $this->webpage->show->twitter,
				"V_SKIP" 		=> self::VIDEO_LIMIT,
				"U_THUMB" 		=> file_exists( $thumb ) ? $thumb : $webpage->site->url . $thumb,
				"U_WNIT_LOGO" 	=> $images . "logo_local.png",
				"U_BROWSE" 		=> $this->webpage->anchor( SHOW_EPISODES  ),
				"V_SHOWTIMES" 	=> $this->webpage->times,
				"V_VIDEO_LIST" 	=> $this->video_list( $this->webpage ),
				"V_LOADMORE_LIST" 	=> $this->load_more( $this->webpage ),
				"U_MORE_VIDEOS" 	=> $webpage->anchor( $webpage->page_id, array( "naked" => 1 ) ),
				"V_NAKED" => $webpage->naked
			) );
			
			
			
			$this->add_var( "U_FB", 				$this->webpage->facebook );
			$this->add_var( "U_FB_IMG", 			$this->webpage->site->url . "images/facebook.png" );
			$this->add_var( "U_TWITTER", 			$this->webpage->twitter );
			$this->add_var( "U_TWITTER_IMG", 		$this->webpage->site->url . "images/twitter.png" );
			$this->add_var( "LINKS", 				$this->webpage->get_links() );
			
			
			$newest_episode = false;
			$newest_episode_instance = false;
			$newest_reepisode = false;
			$newest_reepisode_instance = false;
			if ( $new_episodes = $this->webpage->db->fetch( Database::EPISODES, Database::EPISODES_PRE . "id", Database::EPISODES_PRE . "program = " . SHOW_ID . " AND " .Database::EPISODES_PRE . "enabled > 0 AND " . Database::EPISODES_PRE . "airdate > " . time()  ) ) {					
				foreach( $new_episodes as $e ) {
					$e = new Episode( (int)$e );
					if ( $newest_episode === false || $e->airdate->value < $newest_episode ) {
						$newest_episode = $e->airdate->value;
						$newest_episode_instance = $e;
					}
				}
			}
			
			if ( $reair_episodes = $this->webpage->db->fetch( Database::EPISODES, Database::EPISODES_PRE . "id", Database::EPISODES_PRE . "program = " . SHOW_ID . " AND " . Database::EPISODES_PRE . "enabled > 0 AND " . Database::EPISODES_PRE . "reair > " . time()  ) ) {
				foreach( $reair_episodes as $e ) {
					$e = new Episode( (int)$e );

					if ( $newest_reepisode === false || $e->reair->value < $newest_reepisode ) {
						$newest_reepisode = $e->reair->value;
						$newest_reepisode_instance = $e;
					}	
				}
			}
			
			
			
			
			
			
			if ( $newest_episode || $newest_reepisode ) {
				$is_new = false;
				$e = $newest_reepisode_instance;
				$airdate = $newest_reepisode;
				if ( $newest_episode && (!$newest_reepisode ||  $newest_episode < $newest_reepisode) ) {
					$is_new = true;
					$e = $newest_episode_instance;
					$airdate = $newest_episode;
				}
				
				
				$this->add_var( "V_EP_NEXT_TITLE", 	$e->name );
				$this->add_var( "V_EP_NEXT_TIME", 	date( "m/d/Y", $airdate + 3600*24 ) );
				$this->add_var( "V_NEW", $is_new ? "NEW!" : "" );
			}
				

			
			if ( $next = $this->webpage->show->get_next_episode() )
			{
				$time = $next->reair->value > $next->airdate->value ? $next->reair->value : $next->airdate->value;
				$this->add_var( "EP_TITLE", $next->name->value );
				$this->add_var( "EP_TIME", 	date( "m/d/Y", $time ) );
				$this->add_var( "NEW", 		$next->reair->value ? "" : "<sup>New!</sup>" );
			}
			
			$this->add_var( "V_SHOW_HANDLE", 	strtoupper( $webpage->show->handle->value) );
		}
		
		#--------------------------------------------------------------------------------------------------
		# * Load More
		#--------------------------------------------------------------------------------------------------
		static public function load_more( WebPage &$webpage )
		{
			$skip = (int)Globals::get( 'skip' );
			$next = self::VIDEO_LIMIT;
			$html = self::video_list( $webpage, $skip, $next, true );
			
			return $html;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * Video List
		#--------------------------------------------------------------------------------------------------
		static public function video_list( WebPage $webpage, $skip = 0, $limit = self::VIDEO_LIMIT, $li_only = false )
		{
			if ( !$li_only )  $html = "<p>Oops! We couldn't find any videos for this show. Please check back again later.</p>\n";
			
			if ( $webpage->show->episodes )
			{
				// initialize
				$i = $skip;
				if ( !$li_only ) $html = "<ul id=\"videos\">\n";
				
				for ( $i = $skip; $i < $skip+$limit; $i++ )
				{
					if ( isset( $webpage->show->episodes[ $i ] ) )
					{
						
						// initialize variables
						$default= $webpage->site->url . "themes/" . $webpage->theme . "/images/nothumb.jpg";
						$e 		= new Episode( (int)$webpage->show->episodes[ $i ] );
						$s 		= $e->prime->value ? new Segment( (int)$e->prime->value ) : NULL;
						$li 	= new Template( $webpage, "show_home_episode.html" );
						$slug 	= substr( $e->slug->value, ( strpos( $e->slug->value, "-" ) + 1 ) );
						$thumb 	= ( $s && $s->thumb->value ) ? ( new Photo( (int)$s->thumb->value ) )->url->value : $default;
						
						if ( !$e->enabled ) {
							$limit++;
							continue;
						}
						
						
						// add vars
						$li->add_var( "V_TITLE", 		$e->name->value );
						$li->add_var( "V_NUM", 			$e->num->value );
						$li->add_var( "V_DESCRIPTION", 	$e->short->value ? $e->short->value : "No description." );
						$li->add_var( "V_AIRDATE", 		date( "m/d/y", ( $e->reair->value ? $e->reair->value : $e->airdate->value ) ) );
						$li->add_var( "U_EPISODE", 		$webpage->anchor( SHOW_EPISODES, array( 'slug' => $slug ) ) );
						$li->add_var( "U_THUMB", 			$thumb );
						
						$html .= $li->parse( false );
					}
				}
				
				// end the list
				if ( !$li_only )  $html .= "</ul>\n";
				
				// look for more
				if ( count( $webpage->show->episodes ) > $i )
					if ( !$li_only )  $html .= '<div class="more_videos" class="more"><a id="more_button" href="#" onclick="return load_more()">Load more videos.</a></div>';
			}
			
			return $html;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * Ads
		#--------------------------------------------------------------------------------------------------
		/*public function ads()
		{
			$html = "";
			$valid_ads 	= array();
			$now 		= time();
			$ads		= Ad::geta_page_published( $this->webpage->page_id ); 
			
			if ( $ads || $this->webpage->ad_prepend  )
			{
				$ads_end = $ads_none = array();
				foreach( $ads as $a ) 
				{
					if ( $a->start->value ) 
					{
						$start_stamp 	= strtotime( $a->start->value );
						$end_stamp 		= $start_stamp + $a->length->value * Website::SECS_IN_DAY;
						if ( $now > $start_stamp && $now < $end_stamp ) 
							$ads_end[ $start_stamp ] = $a;			
					} 
					else $ads_none[] = $a;
				}
				
				foreach( $ads_end as $a )
					$valid_ads[] = $a;
				foreach( $ads_none as $a )
					$valid_ads[] = $a;
			}
			

			if ( $this->webpage->ad_prepend || $valid_ads ) 
			{
				$html .= "<ul id=\"ads\">\n" . $this->webpage->ad_prepend;

				foreach ( $valid_ads as $a ) 
					$html .= $a->parse( $this->webpage, $increment );
				$html .= "</ul>\n";
			}
			
			return $html;
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# * Next Episode
		#--------------------------------------------------------------------------------------------------
		/*public function next_episode()
		{
			$html = "";
			
			if ( $next = $this->webpage->show->get_next_episode() )
			{
				$time = $next->reair->value > $next->airdate->value ? $next->reair->value : $next->airdate->value;
				$html = new Template( $this->webpage, "show_home_next.html" );
				
				$html->add_var( "EP_TITLE", $next->name->value );
				$html->add_var( "EP_TIME", 	date( "m/d/Y", $time ) );
				$html->add_var( "NEW", 		$next->reair->value ? "" : "<sup>New!</sup>" );
			}
			
			return $html;
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# * Host
		#--------------------------------------------------------------------------------------------------
		/*public function host()
		{
			// initialize variables
			$html 		= "";
			$portrait 	= $this->webpage->site->url . $this->webpage->show->handle->value . "/themes/" . $this->webpage->theme . "/images/host.png";
			
			if ( SHOW_HOST && Generic::url_exists( $portrait ) )
				$html = '<a id="show-host" href="' . $this->webpage->anchor( SHOW_HOST ) . '"><img src="' . $portrait . '" alt="Host Portrait" /></a>';
			
			return $html;
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# * Social Media
		#--------------------------------------------------------------------------------------------------
		/*public function social()
		{
			$html = "";
			$images = $this->webpage->site->url . 'themes/' . $this->webpage->theme . '/images/';
			
			if ( $this->webpage->facebook || $this->webpage->twitter )
			{
				$html = "<ul id=\"show-social\">\n";
				if ( $this->webpage->facebook )
					$html .= '<li><a id="show-fb" href="' . $this->webpage->facebook . '" target="_blank"><img src="' . $images . 'facebook.png" alt="Find us on Facebook." /></a></li>';
				if ( $this->webpage->twitter )
					$html .= '<li><a id="show-twitter" href="' . $this->webpage->twitter . '" target="_blank"><img src="' . $images . 'twitter.png" alt="Follow us on Twitter." /></a></li>';
				$html .= "</ul>\n";
			}
			
			return $html;
		}*/
		
		#--------------------------------------------------------------------------------------------------
		# * Resources
		#--------------------------------------------------------------------------------------------------
		/*public function resources()
		{
			$html = "";
			
			if ( $this->webpage->resources )
			{
				$images = $this->webpage->site->url . $this->webpage->show->handle->value . '/themes/' . $this->webpage->theme . '/images/';
				$html 	= "<ul id=\"show-resources\">\n";
				
				foreach( $this->webpage->resources as $r )
					$html .= "<li><a href=\"" . $this->webpage->anchor( SHOW_RESOURCES, array( 'mode' => $r[ 'mode' ] ) ) . "\"><img src=\"" . $images . $r[ 'img' ] . "\" alt=\"" . $r[ 'title' ] . "\" /></a></li>\n";
				
				$html .= "</ul>\n";
			}
			
			return $html;
		}*/
	}
}
?>