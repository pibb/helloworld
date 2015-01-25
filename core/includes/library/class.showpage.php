<?php
namespace Core;
if ( !defined( "D_CLASS_SHOWPAGE" ) )
{
	define( "D_CLASS_SHOWPAGE", true );
	require_once( __DIR__ . '/class.webpage.php' );
	require_once( __DIR__ . '/data/class.program.php' );
	require_once( __DIR__ . '/class.show_homepage.php' );
	require_once( __DIR__ . '/class.show_episodepage.php' );
	//require_once( __DIR__ . '/class.show_episodespage.php' );
	//require_once( __DIR__ . '/class.show_searchpage.php' );
	//require_once( __DIR__ . '/class.show_article.php' );

	abstract class ShowWebPage extends WebPage
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		public $show 		= NULL;
		public $slug		= NULL;
		public $episode		= NULL;
		public $segment		= NULL;
		public $breadcrumb	= NULL;
		public $facebook	= "";
		public $twitter		= "";
		public $times		= array();
		public $byseason	= true;
		public $host		= false;
		
		const VIDEO_HEIGHT	= 288;
		const VIDEO_WIDTH	= 512;

		#----------------------------------------------------------------------------------------------------
		# * Constructor
		#----------------------------------------------------------------------------------------------------
		public function __construct( $program_id, $css = array(), $js = array(), $auto_header = false )
		{
			// load web page object
			
			$js[] = "fullepisodes.js";
			// Put css argument 2nd so show specific css will be last
			$css = array_merge( array( "show.css" ), $css );
			$body_class = array( 'show' );
			parent::__construct( false, $auto_header, $css, $js, $body_class );
			
			
			// define the show id "constant"
			define( "SHOW_ID", $program_id );
			
			// set up the show object
			$this->show  = new Program( $program_id, $this );
			
			$handle 	 = strtoupper( $this->show->handle->value );
			$title		 = $thumb = $description = "";
			
			// change templates if there's an original theme
			/*if ( $this->show->theme->value )
			{
				$this->title					= $this->get_title( $this->page_id );
				$this->theme 					= defined( "WNIT_THEME" ) && $this->user->is_admin() ? WNIT_THEME : $this->show->theme->value;
				$this->site->local_templates	= $this->site->local . "themes" . DIRECTORY_SEPARATOR . $this->theme . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR;
				$css							= array_merge( $css, array( $this->site->url . $this->show->handle . "/themes/" . $this->theme . "/css/show.css" ) );
				$this->head						= new WebHeader( $this, $css, $js );
				$this->foot						= new WebFooter( $this );
			}*/
			
			// page id's vary, so attach handle
			$this->constants( strtoupper( $this->show->handle->value ) );
			$this->identify_slug( addslashes( Globals::get( 'slug' ) ) );
			
			
			/*if ( $this->page_id == SHOW_EPISODES )
			{	
				$title = $this->episode->name->value;
				$thumb = $this->episode->prime ? $this->db->fetch_cell( Database::SEGMENTS . " as s, " . Database::PHOTOS . " as p", "p.photo_url", "s.segment_thumb = p.photo_id AND s.segment_id = '" . $this->episode->prime . "'" ) : "";
				$description = strip_tags( str_replace( '"', "'", $this->episode->content->value ) );
				$video = "";
				if ( $this->episode->enabled_video->value )
				{
					if ( $this->episode->cove->value )
					{
						$video = ( new COVE_API )->get_video( $this->episode->cove->value, false, true, false, false );
						if ( $video )
						{
							if ( $video->partner_player )
							{
								$start = strpos( $video->partner_player, "src" ) + 5;
								$end = strpos( $video->partner_player, "'></iframe>" ) - $start;
								$video = str_replace( "pbs.org", "wnit.org", substr( $video->partner_player, $start, $end ) );
							}
						}
					}
				}
			}*/
			
			/*else if ( $this->page_id == SHOW_SEGMENTS )
			{
				// initialize variables
				$num 	= (int)Globals::get( 'num', 0 );
				$share 	= (bool)Globals::get( 'share', false );
				$id 	= $this->db->fetch_cell( Database::SEGMENTS, "segment_id", "segment_num = '" . (int)$num . "' AND segment_episode = '" . $this->episode->id . "'" );
				$query 	= $this->episode ? array( 'slug' => $this->strip_handle( $this->episode->slug->value ) ) : "#";
				$hash	= $this->segment ? "#segment=" . $this->segment->num->value : "#";
				
				// set title
				$title = $this->segment->name->value;
				$thumb = $this->segment->thumb ? $this->segment->thumb->url->value : "";
				
				if ( !$id )
					$this->redirect( $this->anchor( SHOW_EPISODES, $query ) . $hash );
				else if ( $share )
				{
					$embed_html = new Template( $this, 	"show_episode_cove.html" );
					$embed_html->add_var( "COVE_ID", 	$this->segment->cove->value );
					$embed_html->add_var( "HEIGHT", 	self::VIDEO_HEIGHT );
					$embed_html->add_var( "WIDTH", 		self::VIDEO_WIDTH );
					$embed_html->add_var( "CHAPTER", 	$num );
					$embed_html->add_var( "START", 		0 );
					$embed_html->add_var( "END", 		0 );
					
					$html = new Template( $this, "show_share.html" );
					$html->add_url( "URL", $this->anchor( SHOW_SEGMENTS, array( 'slug' => $this->strip_handle( $this->episode->slug->value ) ) ) . $hash );
					$html->add_var( "EMBED", $embed_html . $test );
					echo $html;
				}
				else
				{
					$s = new Segment( $id, $this );
					echo (bool)Globals::get( 'embed' ) ? $s->embed() : $s->parse( (bool)Globals::get( 'min' ), false, count( $this->episode->segments ) );
				}
			}*/
			
			// set title
			$this->head->add_var( "V_TITLE", 			$this->show->name . ( $title ? ( " - " . $title ) : "" ) );
			
			/*$this->head->add_var( "V_SUBTITLE",			$title );
			$this->head->add_var( "V_QUERY",			htmlspecialchars( Globals::get( 'query', '' ) ) );
			$this->head->add_var( "U_HOME",				$this->anchor( MAIN_INDEX ) );
			$this->head->add_var( "U_LOCAL",			$this->anchor( MAIN_LOCAL ) );
			$this->head->add_var( "U_SHOW_ABOUT", 		$this->anchor( SHOW_ABOUT ) );
			$this->head->add_var( "U_SEARCH", 			$this->anchor( SHOW_SEARCH ) );
			$this->head->add_var( "U_DONATE",			$this->anchor( SUPPORT_PLEDGE ) );
			$this->head->add_var( "U_PBS",				"http://www.pbs.org/" );
			$this->head->add_func( "F_MENU_EPISODES", 	array( $this, "menu_episodes" ) );
			$this->head->add_func( "F_MENU_RESOURCES", 	array( $this, "menu_resources" ) );
			$this->head->add_func( "BREADCRUMBS",		array( $this, "breadcrumbs" ) );*/
			
			$submenu = array();
			if ( $this->host ) $submenu[] = array( 'NAME' => "About The Host", 'CLASS' => "", 'HREF' => $this->anchor( SHOW_HOST ) );
			$submenu[] = array( 'NAME' => "Full Episodes", 'CLASS' => "", 'HREF' => $this->anchor( SHOW_EPISODES ) );
			//$submenu[] = array( 'NAME' => "Contact Us", 'CLASS' => "", 'HREF' => $this->anchor( SHOW_CONTACT ) );
			
			$this->head->add_vars( array
			(
				"U_SHOW_INDEX" =>	$this->anchor( SHOW_INDEX ),
				"V_SHOW_NAME" =>	$this->show->name,
				"V_SUBMENU" => 		$submenu
			) );
			
			// change thumb from default if there is a primary segment thumbnail to use
			//if ( $thumb )
			//	$this->head->add_url( "THUMB",			$thumb );
			//if ( $description )
			//	$this->head->add_var( "DESCRIPTION",	$description );
			//if ( $video )
			//	$this->head->add_url( "VIDEO",	$video );
			
			// show the header if it is called
			//if (  defined( "_NOAUTOHEADER" ) == false || $auto_header ) 
			//{
			//	echo $this->head;
				//if ( $this->page_id != SHOW_INDEX ) echo $this->breadcrumb;
			//}
			
			
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Identify Slug
		#----------------------------------------------------------------------------------------------------
		protected function constants( $handle )
		{
			define( "SHOW_INDEX", 		"{$handle}_INDEX" );
			define( "SHOW_EPISODES", 	"{$handle}_EPISODES" );
			define( "SHOW_SEGMENTS", 	"{$handle}_SEGMENTS" );
			define( "SHOW_ARTICLES", 	"{$handle}_ARTICLES" );
			define( "SHOW_ABOUT", 		"{$handle}_ABOUT" );
			define( "SHOW_CONTACT", 	"{$handle}_CONTACT" );
			define( "SHOW_HOST", 		"{$handle}_HOST" );
			define( "SHOW_RESOURCES", 	"{$handle}_RESOURCES" );
			define( "SHOW_SEARCH", 		"{$handle}_SEARCH" );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Identify Slug
		#----------------------------------------------------------------------------------------------------
		protected function identify_slug( $slug )
		{
			// initialize variables
			$this->slug = $slug;
			$found = false;
			
			if ( $this->slug ) 	
			{
				list( $this->episode, $this->segment ) = $this->show->get_slug( $this->slug );
				
				if ( $this->segment )
				{
					//$this->adopt( 'segment' );
					$this->id = $this->segment->id;
					$found = true;
				}
				else if ( $this->episode )
				{
					//$this->adopt( 'episode' );
					$this->id = $this->episode->num;
					$found = true;
				}
			}
			
			return $found;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * get_links
		#----------------------------------------------------------------------------------------------------
		public function get_links() 
		{
			return array();
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Breadcrumbs
		#----------------------------------------------------------------------------------------------------
		/*public function breadcrumbs()
		{
			// find all the crumbs
			$crumbs = array();
			switch( $this->page_id )
			{
				case SHOW_INDEX:		break;
				case SHOW_RESOURCES: 	$crumbs[] = array( 'url' => $this->anchor( SHOW_RESOURCES ), 'title' => $this->get_title( $this->page_id ) ); 
										if ( $this->mode )
											$crumbs[] = array( 'url' => $this->anchor( SHOW_RESOURCES, array( 'mode' => $this->mode ) ), 'title' => $this->get_title( $this->page_id, $this->mode ) ); 
										break; 
										
				case SHOW_EPISODES: 	$crumbs[] = array( 'url' => $this->anchor( SHOW_EPISODES ), 'title' => "Episodes" ); 
										if ( $this->slug )
										{
											$season = floor( $this->episode->num->value / 100 );
											$crumbs[] = array( 'url' => $this->anchor( SHOW_EPISODES ) . "#season=" . $season, 'title' => "Season " . $season ); 
											$crumbs[] = array( 'url' => $this->anchor( SHOW_EPISODES, array( 'slug' => $this->slug ) ), 'title' => $this->episode->name ); 
										}
										break; 
										
				case SHOW_SEGMENTS: 	$crumbs[] = array( 'url' => $this->anchor( SHOW_EPISODES ), 'title' => "Episodes" ); 
										if ( $this->slug )
										{
											$season = floor( $this->episode->num->value / 100 );
											$crumbs[] = array( 'url' => $this->anchor( SHOW_EPISODES ) . "#season=" . $season, 'title' => "Season " . $season ); 
											$crumbs[] = array( 'url' => $this->anchor( SHOW_EPISODES, array( 'slug' => $this->strip_handle( $this->episode->slug ) ) ), 'title' => $this->episode->name ); 
											$crumbs[] = array( 'url' => $this->anchor( SHOW_SEGMENTS, array( 'slug' => $this->slug ) ), 'title' => $this->segment->name ); 
										}
										break; 
				default: 				$crumbs[] = array( 'url' => $this->anchor( $this->page_id ), 'title' => $this->get_title( $this->page_id ) ); break; 
			}
			
			// add them to the list
			$html = "";
			foreach( $crumbs as $e )
				$html .= '<li><a href="' . $e[ 'url' ] . '">' . $e[ 'title' ] . '</a></li>';
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Strip Handle
		#----------------------------------------------------------------------------------------------------
		/*public function strip_handle( $slug )
		{
			return str_replace( $this->show->handle . "-", "", $slug );
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Menu Episodes
		#----------------------------------------------------------------------------------------------------
		/*public function menu_episodes()
		{
			$html = "";
			
			// only show episode guide if we have some recorded
			if ( $this->show->episodes )
			{
				// initialize list
				$episodes = array();
				$html = '<li id="show-episodes" class="sub"><a href="' . $this->anchor( SHOW_EPISODES ) . '">Full Episodes</a><ul>';
				
				if ( $this->byseason )
				{
					// instead of loading Episode objects, we're going load data the long-handed way, organizing the episodes as we go by season
					foreach( $this->show->episodes as $e )
					{
						$episode = $this->db->fetch( Database::EPISODES . " as e, " . Database::ARTICLES . " as a", "*", "e.episode_article = a.article_id AND e.episode_id = '" . $e . "'" );
						$season = floor( $episode[ 'episode_num' ] / 100 );
						$id = (int)( $episode[ 'episode_num' ] - ( $season * 100 ) );
						$episodes[ $season ][ $id ] = $episode;
					}
					
					// once they're organized, list them out
					foreach( $episodes as $season => $eps )
					{
						ksort( $eps );
						$html .= '<li class="sub"><a href="' . $this->anchor( SHOW_EPISODES ) . '#order=seasons&amp;season=' . $season . '">Season ' . $season . '</a><ul>';
						foreach( $eps as $id => $e )
						{
							$id .= ". " . stripslashes( $e[ 'article_name' ] );
							$slug = substr( $e[ 'article_slug' ], ( strpos( $e[ 'article_slug' ], "-" ) + 1 ) );
							$html .= '<li><a href="' . $this->anchor( SHOW_EPISODES, array( 'slug' => $slug ) ) . '">' . $id . '</a></li>';
						}
						$html .= '</ul></li>';
					}
				}
				else
				{
					$month_to_string = array( 	1 => "January", 2 => "February", 3 => "March", 4 => "April", 5 => "May", 6 => "June", 
											 	7 => "July", 8 => "August", 9 => "September", 10 => "October", 11 => "November", 12 => "December" );
					
					// instead of loading Episode objects, we're going load data the long-handed way, organizing the episodes as we go by season
					foreach( $this->show->episodes as $e )
					{
						$episode 	= $this->db->fetch( Database::EPISODES . " as e, " . Database::ARTICLES . " as a", "*", "e.episode_article = a.article_id AND e.episode_id = '" . $e . "'" );
						$year 		= date( "Y", $episode[ 'episode_airdate' ] );
						$month 		= date( "n", $episode[ 'episode_airdate' ] );
						$episodes[ $year ][ $month ][] = $episode;
					}
					
					// once they're organized, list them out
					foreach( $episodes as $year => $months )
					{
						ksort( $eps );
						$html .= '<li class="sub"><a href="' . $this->anchor( SHOW_EPISODES ) . '#order=date&amp;year=' . $year . '">Year ' . $year . '</a><ul>';
						foreach( $months as $month => $eps )
						{
							$html .= '<li class="sub"><a href="' . $this->anchor( SHOW_EPISODES ) . '#order=date&amp;year=' . $year . '">' . $month_to_string[ $month ] . '</a><ul>';
							foreach( $eps as $e )
							{
								$slug = substr( $e[ 'article_slug' ], ( strpos( $e[ 'article_slug' ], "-" ) + 1 ) );
								$html .= '<li><a href="' . $this->anchor( SHOW_EPISODES, array( 'slug' => $slug ) ) . '">' . $e[ 'article_name' ] . '</a></li>';
							}
							$html .= '</ul></li>';
						}
						$html .= '</ul></li>';
					}
				}
				
				$html .= '</ul></li>';
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Menu Resources
		#----------------------------------------------------------------------------------------------------
		/*public function menu_resources()
		{
			$html = "";
			
			if ( $this->resources )
			{
				$html = '<li id="show-resources" class="sub"><a href="' . $this->anchor( SHOW_RESOURCES ) . '">Resources</a><ul>';
				
				foreach( $this->resources as $r )
					$html .= '<li><a href="' . $this->anchor( SHOW_RESOURCES, array( 'mode' => $r[ 'mode' ] ) ) . '">' . $r[ 'title' ] . '</a></li>';
				
				$html .= '</ul></li>';
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * This week's episode html
		#----------------------------------------------------------------------------------------------------
		/*public function this_week() 
		{
			// initialize variables
			$html 			= "";
			$episode_idx 	= $this->latest_episode();
			
			// show the latest episode
			if ( isset( $this->show->episodes[ $episode_idx ] ) )
			{
				$latest		= $this->show->episodes[ $episode_idx ];
				$airdate 	= date( "m/d/y", $latest->airdate );
				$latest_n	= count( $latest->segments );
				
				// airdate
				$html .= "<span class=\"airdate\">Air date</span>: " . $airdate . "<br />";
				if ( $latest->reairdate ) 
				{
					$reairdate = date( "m/d/y", $latest->reairdate );
					$html .= "<span class=\"airdate\">Re-air date</span>: " . $reairdate . "<br />";
				}
				
				// segment list
				if ( $latest_n == 0 )
					$html .= "<p>No segments found.</p>";
				else
				{
					$html .= "<ul id=\"segment-list\">";
					foreach( $latest->segments as $s ) 
						$html .= "<li>" . $s . "</li>";
					$html .= "</ul>";
				}
				
				// show previous episode, if there was one
				if ( $episode_idx > 0 ) 
				{
					$previous = &$this->show->episodes[ $episode_idx - 1 ];
					$html .= "<a href=\"" . $this->anchor( SHOW_EPISODES, array( 'slug' => $previous->slug ) ) . "\">Previously - " . $previous->name . "</a>\n";
				}
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Latest Episode
		#----------------------------------------------------------------------------------------------------
		/*protected function latest_episode() 
		{
			// initialize
			$i = count( $this->show->episodes ) - 1;
			
			// find the latest episode that has valid segments
			while( $i >= 0 && !$this->show->episodes[ $i ]->segments ) 
				$i--;
				
			return $i;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Guest List html
		#----------------------------------------------------------------------------------------------------
		/*protected function get_guest_list( $guests ) 
		{
			// initialize variable
			$html = "";
			
			if ( $guests )
			{
				$html .= "<h4>Guests</h4>\n<ul id=\"guest-list\">\n";
				foreach( $guests as $g ) 
					$html .= "<li>" . $g . "</li>";
				$html .= "</ul>\n";
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Resource list html
		#----------------------------------------------------------------------------------------------------
		/*protected function get_resources_list( array $resources ) 
		{
			// initialize variable
			$html = "";
			
			if ( $resources )
			{
				$html .= "<h4>Resources</h4>\n<ul id=\"resource-list\">\n";
				foreach( $resources as $r ) 
					$html .= "<li><a href=\"" . $r->url . "\">" . $r->name . "</a></li>";
				$html .= "</ul>\n";
			}
				
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Ohter photos html
		#----------------------------------------------------------------------------------------------------
		/*protected function get_other_photo_list( array $photos, $photo_exclude_id = 0 ) 
		{
			// initialize variable
			$html = "";
			$n = count( $photos );
			
			//if there is only 1 photo and it is exluded return nothing
			if ( $n > 1 || ( $n == 1 && $photos[ 0 ]->id != $photo_exclude_id ) ) 
			{
				$html .= "<ul id=\"photo-list\">\n";
				foreach( $photos as $p ) 
					if ( $p->id != $photo_exclude_id ) 
						$html .= "<li><img src=\"" . $p->url . "\" alt=\"" . $p->alt . "\"></li>\n";
				$html .= "</ul>\n";
			}
			
			return $html;
		}*/
	}
}
?>
