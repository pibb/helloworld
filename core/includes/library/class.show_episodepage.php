<?php
namespace Core;
if ( !defined( "D_CLASS_SHOWEPISODEPAGE" ) )
{
	define( "D_CLASS_SHOWEPISODEPAGE", true );
	class ShowEpisodePage extends Template
	{
		const COVE_HEIGHT			= 300;
		const COVE_WIDTH			= 500;
		#--------------------------------------------------------------------------------------------------
		# * Constructor
		#--------------------------------------------------------------------------------------------------
		public function __construct( WebPage &$webpage )
		{	
		
			if ( $webpage->episode ) {
				parent::__construct( $webpage, "show_episode.html" );
				
				//get embed html
				$embed_html = null;
				if ( $this->webpage->episode->cove->value ) {
					$coveapi = new COVE_API(  );
					if ( $code = $coveapi->get_video( $this->webpage->episode->cove->value ) ) {
						$embed_html = $code->partner_player;
					}
				}
				
				//add vars
				$this->add_vars( array
				(
					"V_EPISODENUM" 	=> $this->webpage->episode->num->value,
					"V_DESCRIPTION" => $this->webpage->episode->content->value,
					"V_AIRDATE" 	=> date( "F d, Y", $this->webpage->episode->airdate->value ),
					"V_EMBED_HTML" 	=> $embed_html,
					"V_ETITLE" 	=> $this->webpage->episode->name->value,
				) );
				
				

				
				$this->add_var( "V_EP_TITLE", 	$this->webpage->episode->name);
				$this->add_var( "V_EP_URL", 	$this->webpage->anchor( $this->webpage->page_id, array( "slug" => $this->webpage->slug ) ));
				$this->add_var( "V_EP_TIME", 	date( "m/d/Y" ) );
				
				
				
				//calculate segment vars
				$segments = array();
				$extra_segments = array();
				$resources = array();
				
				foreach( $this->webpage->episode->segments as $s ) {
					$s = new Segment( (int)$s );
					
					$resources = array_merge( $resources, $this->get_resources( $s ) );
					
					
					//init html
					$guests_plain_list = array();
					$guests_plain_html = "";

					if ( !$s->guests ) 
						$guests_html = "No guest information was found.";
					else
						$guests_html = "<ol>";
					
					$photo_html = "<div class=\"photos\"><ul>";
					
					//guest html
					foreach( $s->guests as $guest ) {
						$guest = new Guest( (int)$guest );
						$guests_html .= $this->guests_html( $guest );
						$guests_plain_list []= $guest->name->value;
					}
					
					$guests_plain_html = implode( ", ", $guests_plain_list );
					
					//photo html
					foreach( $s->photos as $photo ) {
						if ( $photo == $s->thumb->value ) continue;
						$photo = new Photo( (int)$photo );
						$photo_html .= $this->photo_html( $photo );
					}
					
					//close off html
					if ( $s->guests ) 
						$guests_html .= "</ol>";
						
					$photo_html .= "</ul></div>";
					

					$category = new SegmentCategory( (int)$s->category->value );

					if ( (int)$category->extra->value ) {
						$extra_segments[] = array(
							"TITLE" => $s->name->value,
							"THUMB_URL" => $s->thumb->value ? (new Photo( (int)$s->thumb->value ))->url->value : $this->webpage->site->url . "themes/" . $this->webpage->theme . "/images/nothumb.jpg",
						);
					} else {
						$segments[] = array(
							"TITLE" => $s->name->value,
							"description" => $s->content,
							"full" => false,
							"id" => $s->id,
							"num" => $s->num->value,
							"descriptiontrim" => substr( $s->content, 0, 100 ) . "...",
							"guestsplain" => $guests_plain_html,
							"guest_html" => $guests_html,
							"photo_html" => $photo_html,
							"THUMB_URL" => $s->thumb->value ? (new Photo( (int)$s->thumb->value ))->url->value : $this->webpage->site->url . "themes/" . $this->webpage->theme . "/images/nothumb.jpg",
						);
					}
					
				}
				
				
				if ( isset($_GET['segment']) ) {
					$s = new Segment( (int)$_GET['segment'] );
					
					$this->add_var( "EMBED", isset($_GET['embed']) ? $_GET['embed'] : 0 );
					$this->add_var( "CODE", isset($_GET['code']) ? $_GET['code'] : 0 );
					$this->add_var( "V_HEIGHT", self::COVE_HEIGHT );
					$this->add_var( "V_WIDTH", self::COVE_WIDTH );
					$this->add_var( "V_COVE_ID", $s->cove->value );
					$this->add_var( "V_CHAPTER", $s->num->value );
				}
				
				
				
				$this->add_var( "V_STARTCLASS", count( $this->webpage->episode->segments ) > 1 ? "summary" : "full" );
				$this->add_var( "V_SHOWACTIONS", count( $this->webpage->episode->segments ) > 1 ? "1" : "0" );
				$this->add_var( "V_SEGMENTS", $segments );
				$this->add_var( "V_EXTRASEGMENTS", $extra_segments );
				$this->add_var( "V_RESOURCES", $resources );
				
				
				$this->add_var( "V_SHOW_HANDLE", 	strtoupper( $webpage->show->handle->value) );
			
			} else {
			
				//full episodes page
				parent::__construct( $webpage, "show_full_episodes.html" );
				
				if ( isset( $_GET['ajaxcall'] ) ) $this->add_var( "AJAXCALL", 1 );
				
				$seasons = array();
				foreach( $this->webpage->show->episodes as $episode ) {
					$enabled = $this->webpage->db->fetch_cell( Database::EPISODES, Database::EPISODES_PRE . "enabled", Database::EPISODES_PRE . "id = " . (int)$episode );
					
					if ( $enabled )
						$seasons[ $this->get_episode_season( $episode ) ] = true;
				}
				
				$seasons_list = array();
				$last_season_value = null;
				$season_highest = 0;
				foreach( $seasons as $season => $s ) {
					$href = $this->webpage->anchor( SHOW_EPISODES, array( "season" => $season ) );
					
					$seasons_list[] = array( "name" => "Season " . $season, "value" => $season, "href" => $href );
					if ( (int)$season  > $season_highest ) $season_highest = $season;
				}
				
				$this->add_var( 'U_AJAXCALLBACK', $this->webpage->anchor( $this->webpage->page_id, array( "naked" => 1, "ajaxcall" => 1 ) ) );
				
				
				
				$this->add_var( "V_SEASONS", $seasons_list );
				$season = isset( $_GET['season'] ) ? $_GET['season']  : $season_highest;
				$this->add_var( "V_EPISODES", $this->get_episodes_html( $season ) );
				
			}
			
		}

		#--------------------------------------------------------------------------------------------------
		# * get_episode_season
		#--------------------------------------------------------------------------------------------------
		public function get_episodes_html( $season ) {
		
			$episodes = array();

			if ( $season ) {
				
				foreach( $this->webpage->show->episodes as $episode ) {
					if ( $season == $this->get_episode_season( $episode ) ) {
						
						$episodes[] = $episode;
					}
				}
				
			}
			
			
			$episodes_html = "";
			
			$episode_list = array();
			foreach( $episodes as &$episode ) {
				$e = new Episode( (int)$episode );
				if ( !$e->enabled ) continue;
				
				
				
				if ( !$e->prime->value && $e->segments  )
					$prime = new Segment( $e->segments[0] );
				else
					$prime = new Segment( (int)$e->prime->value );
				
						
				if ( $prime->thumb->value ) {
					$photo = new Photo( (int)$prime->thumb->value );
					if ( !$img_url = $photo->url->value ) {
						$img_url = $this->webpage->site->url . "themes/" . $this->webpage->theme . "/images/nothumb.jpg";
					}
				} else {
					$img_url = $this->webpage->site->url . "themes/" . $this->webpage->theme . "/images/nothumb.jpg";
				}
				
				

				$slug = str_replace( $this->webpage->show->handle->value . "-", "", $e->slug );
				
				$href = $this->webpage->anchor( SHOW_EPISODES, array( "slug" => $slug ) );
				
				
				$name = strlen( $e->name ) > 25 ? substr( $e->name, 0, 22 ) . "..." : $e->name;
				$episodes_html .= "<a href=\"$href\"><li>#"  . $e->num->value . "<img src=\"$img_url\">". $name . "<br />" .  date( "F d, Y", $e->airdate->value ) . "</li></a>";
			}
			
			
			return $episodes_html;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * get_episode_season
		#--------------------------------------------------------------------------------------------------
		public function get_episode_season( $id ) {
			
			$season = "";
			
			$num = (string)(int)$this->webpage->db->fetch_cell( Database::EPISODES, Database::EPISODES_PRE . "num", Database::EPISODES_PRE . "id = " . (int)$id );
			if ( strlen( $num ) >= 3 ) {
				$season = "";
				for ( $i = strlen( $num ); $i >= 3; $i-- ) {
					$season .= $num[ strlen($num) - $i ];
				}
			}
			
			return (int)$season;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * get_resources
		#--------------------------------------------------------------------------------------------------
		public function get_resources( Segment &$s ) 
		{
			//init vars
			$resources = array();
			
			foreach( $s->resources as $resource ) {
			
				list( $anchor, $resource_type ) = array( $resource["id"], $resource["type"] );
				$resource = new Anchor( (int)$anchor );
				
				$resources[] = array( "NAME" => $resource->name->value, "URL" => $resource->url->value );
				
			}
			
			return $resources;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * guests_html
		#--------------------------------------------------------------------------------------------------
		public function guests_html( Guest $guest )
		{
			$html = "<li><span class=\"name\">" . $guest->name->value . "</span>" . ($guest->title->value ? ", <span class=\"title\">" . $guest->title->value . "</span>" : "") . "</li>";
			
			return $html;
		}
		#--------------------------------------------------------------------------------------------------
		# * photo_html
		#--------------------------------------------------------------------------------------------------
		public function photo_html( Photo &$photo )
		{
			if ( $photo->url->value ) 
				return "<li><img src=\"" . $photo->url->value . "\"></li>";
			
			return "";
			
		}
		
		
	}
	
}