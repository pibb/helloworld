<?php
#============================================================================================================
# ** Episode Class
#
# Version:   1.0
# Author:    Michael Shelton
#============================================================================================================
namespace Core;
if ( !defined( "D_CLASS_EPISODE" ) )
{
	define( "D_CLASS_EPISODE", true );
	
	require( __DIR__ . "/class.data.php" );
	require( __DIR__ . "/class.segment.php" );
	require( __DIR__ . "/../class.coveapi.php" );
	
	class Episode extends ArticleData
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		protected $num 				= NULL;
		protected $cove 			= NULL;
		protected $cove_use 		= NULL;
		protected $short 			= NULL;
		protected $airdate 			= NULL;
		protected $reair	 		= NULL;
		protected $enabled_video 	= NULL;
		protected $prime 			= NULL;
		protected $program 			= 0;

		protected $href 			= "";
		protected $program_name 	= "";
		protected $thumb 			= "";
		
		protected $segments			= array();
		protected $template_parse	= "episodes_view.html";
		protected $template_seg 	= "episodes_segments.html";
		
		const TABLE					= Database::EPISODES;
		const PREFIX				= Database::EPISODES_PRE;
		
		#----------------------------------------------------------------------------------------------------
		# * Parse
		# Added by Travis
		#----------------------------------------------------------------------------------------------------
		/*public function parse( $complete = true )
		{
			$html = "";
			
			if ( !$complete ) 
				$html = $this->get_episode_html();
			else
			{
				$template = new \Core\Template( $this->webpage, $this->template_parse );
				$template->add_func( "EPISODE_HTML", array( $this, get_episode_html ) );
				$template->add_url( "EPISODES", $this->webpage->anchor( SHOW_EPISODES, array() ) );
				$html = $template->parse();
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Convert To String
		#----------------------------------------------------------------------------------------------------
		public function __toString()
		{
			return $this->parse();
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Convert To String
		#----------------------------------------------------------------------------------------------------
		public function parse()
		{
			$html = new Template( $this->webpage, $this->template_parse );
			$long = $this->get_long_description();
			$short = $this->get_short_description();
			$desc = ( $long ? $long : $short ) . $this->show_cove_link();
				
			$html->add_var( "THUMB", $this->prime && $this->prime->thumb ? '<img src="' . $this->prime->thumb[ 'photo_url' ] . '" alt="' . $this->prime->thumb[ 'photo_alt' ] . '" />' : "" );
			$html->add_var( "DESCRIPTION", $desc );
			$html->add_url( "THIS", $this->webpage->anchor( SHOW_EPISODES, array( 'slug' => $this->slug ) ) );
			$html->add_url( "FACEBOOK", $this->webpage->anchor( SHOW_EPISODES, array( 'slug' => $this->webpage->slug ) ) );
			$html->add_func( "SEGMENTS", array( $this, show_segments ) );
			$html->add_func( "RESOURCES", array( $this, show_resources ) );
			$html->add_func( "UNDERWRITERS", array( $this, show_underwriters ) );
			$html = $html->parse();
			
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * List Segments
		#----------------------------------------------------------------------------------------------------
		public function list_segments( $id )
		{
			$html = "";
			$this->populate( 'segments', '\Core\Segment' );
			
			// now, show them
			foreach( $this->segments as $s )
				$html .= "<li" . ( $s->id == $id ? ' class="sel"' : '' ) . ">" . $s->parse( false ) . "</li>\n";
			
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Show Resources
		#----------------------------------------------------------------------------------------------------
		public function show_resources()
		{
			$html = "";
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Show Segments
		#----------------------------------------------------------------------------------------------------
		public function show_segments( $id )
		{
			// initialize variable
			$html = "";
			
			// if there are segments available to show, list them
			if ( $this->segments )
			{
				$html = new Template( $this->webpage, $this->template_seg );
				$html->add_var( "HEADER", $id ? "<h3>More From This Episode</h3>\n" : "<h2>Segments</h2>\n" );
				$html->add_func( "LIST_SEGMENTS", array( $this, "list_segments" ), $id );
				$html = $html->parse();
			}
			
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Show Underwriters
		#----------------------------------------------------------------------------------------------------
		public function show_underwriters()
		{
			$html = "";
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Show COVE Link
		#----------------------------------------------------------------------------------------------------
		public function show_cove_link()
		{
			// initialize variables
			$html = "";
			$cove = new \Core\COVE_API;
			
			// we check with the api in order to see if the video is actually available on cove first
			if ( $this->cove->value && ( $this->enabled_video->value || $cove->get_video( $this->cove->value ) ) )
				$html = " <a href=\"http://video.wnit.org/video/" . $this->cove->value . "/\" target=\"_blank\"><strong>(See the full episode.)</strong></a>";
			
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Base Slug
		#----------------------------------------------------------------------------------------------------
		public function base_slug()			
		{ 
			// initialize variables
			$slug = $this->slug;
			$program = new Program( $this->program );
			
			// remove the program handle from the slug
			if ( $program && $program->handle->value )
				$slug = str_replace( $program->handle->value . "-", "", $slug );
		
			return $slug;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Get Long Description
		#----------------------------------------------------------------------------------------------------
		public function get_long_description()
		{
			$description = $this->content->value;
			if ( $this->cove->value && $this->cove_use->value )
			{
				$description = (new COVE_API)->get_video( $this->cove->value )->long_description;
			}
			return $description;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Get Short Description
		#----------------------------------------------------------------------------------------------------
		public function get_short_description()
		{
			$description = $this->short->value;
			if ( $this->cove->value && $this->cove_use->value )
			{
				$description = (new COVE_API)->get_video( $this->cove->value )->short_description;
			}
			return $description;
		}
		
		/*
		#----------------------------------------------------------------------------------------------------
		# * Parse
		# Added by Travis
		#----------------------------------------------------------------------------------------------------
		public function get_episode_html(  )
		{
			$html .= "<span class=\"airdate\">Air date</span>: " .  date( "m/d/y", $this->airdate ) . "<br />";
			if ( $this->reairdate ) 
			{
				$reairdate = date( "m/d/y", $this->reairdate );
				$html .= "<span class=\"airdate\">Re-air date</span>: " . $reairdate . "<br />";
			}
			
			// segment list
			if ( count( $this->segments ) == 0 )
				$html .= "<p>No segments found.</p>";
			else
			{
				$html .= "<ul id=\"segment-list\">";
				foreach( $this->segments as $s ) 
				{
					$html .= "<li>" . $s . "</li>";				
				}
				$html .= "</ul>";
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Static Getters
		#----------------------------------------------------------------------------------------------------
		static public function geta( $amend = "", WebPage &$webpage = NULL ) 									{ return parent::getx( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		static public function geta_many( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 				{ return parent::getx_many( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		static public function geta_many_published( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 	{ return parent::getx_many_published( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		static public function geta_published( $amend = "", WebPage &$webpage = NULL ) 							{ return parent::getx_published( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		
		#----------------------------------------------------------------------------------------------------
		# * Initializers
		#----------------------------------------------------------------------------------------------------
		protected function init_table() 	{ return self::TABLE; }
		protected function init_prefix()	{ return self::PREFIX; }
		protected function init_classname()	{ return __CLASS__; }
		
		#----------------------------------------------------------------------------------------------------
		# * Setup
		#----------------------------------------------------------------------------------------------------
		protected function setup( $data = Array() )
		{
			$not_recorded = array( "href", "program_name", "thumb" );
			foreach( $not_recorded as $r )
				$this->not_recorded[] = $r;
				
		
			$data = parent::setup( $data );
			$this->def_col( 'num', $data );
			$this->def_col( 'short', $data );
			$this->def_col( 'cove', $data );
			$this->def_col( 'cove_use', $data );
			$this->def_col( 'enabled_video', $data );
			$this->def_col( 'airdate', $data );
			$this->def_col( 'reair', $data );
			$this->def_col( 'prime', $data );
			

			// create columns
			/*$this->num				= new IntColumn( self::TABLE, self::PREFIX, "num" );
			$this->short 			= new Column( self::TABLE, self::PREFIX, "short" ); 
			$this->cove 			= new Column( self::TABLE, self::PREFIX, "cove" ); 
			$this->cove_use 		= new MultiColumn( self::TABLE, self::PREFIX, "cove_use" ); 
			$this->enabled_video 	= new MultiColumn( self::TABLE, self::PREFIX, "enabled_video" ); 
			$this->airdate 			= new DateColumn( self::TABLE, self::PREFIX, "airdate" ); 
			$this->reair	 		= new DateColumn( self::TABLE, self::PREFIX, "reair" ); 
			$this->prime			= new IntColumn( self::TABLE, self::PREFIX, "prime_segment" );
			
			// make adjustments
			$this->num->min = 100;
			$this->short->max = 90;
			$this->enabled_video->options = array( 1 => "Yes", 0 => "No" );
			$this->cove_use->options = array( 1 => "Yes", 0 => "No" );
			
			// add columns
			$this->add_col( 'num', true, true );
			$this->add_col( 'short' );
			$this->add_col( 'cove' );
			$this->add_col( 'cove_use' );
			$this->add_col( 'video' );
			$this->add_col( 'airdate', true, true );
			$this->add_col( 'reair' );
			$this->add_col( 'prime' );
			$this->add_col( 'enabled_video' );*/
			
			if ( $data )
			{
				/*$this->set_num( $data[ $this->prefix . 'num' ] );
				$this->set_cove( $data[ $this->prefix . 'cove' ] );
				$this->set_cove_use( $data[ $this->prefix . 'cove_use' ] );
				$this->set_short( $data[ $this->prefix . 'short' ] );
				$this->set_airdate( $data[ $this->prefix . 'airdate' ] );
				$this->set_reair( $data[ $this->prefix . 'reair' ] );
				$this->set_enabled_video( $data[ $this->prefix . 'enabled_video' ] );
				$this->set_program( $data[ $this->prefix . 'program' ] );*/
				
				// find segments
				$segments = Database::select( Database::SEGMENTS, "segment_id", "segment_episode = '" . $this->id . "' AND segment_enabled != '0' AND segment_deleted = '0' ORDER BY segment_num" );
				foreach( $segments as $s )
					$this->segments[] = $s[ 'segment_id' ];
				
				$this->set_prime( $data[ $this->prefix . 'prime_segment' ] );
				
				if ( $this->webpage ) {
					$slug = $this->slug->value;
					
					$handle = $this->webpage->db->fetch_cell( Database::PROGRAMS, Database::PROGRAMS_PRE . "handle", Database::PROGRAMS_PRE . "id = " . (int)$data[ $this->prefix . "program" ] );
					$this->program_name = $this->webpage->db->fetch_cell( Database::PROGRAMS, Database::PROGRAMS_PRE . "name", Database::PROGRAMS_PRE . "id = " . (int)$data[ $this->prefix . "program" ] );
					
					$photo_id = $this->webpage->db->fetch_cell( Database::SEGMENTS, Database::SEGMENTS_PRE . "thumb", Database::SEGMENTS_PRE . "id = " . (int)$data[ $this->prefix . "prime_segment" ] );
					$this->thumb = $this->webpage->db->fetch_cell( Database::PHOTOS, Database::PHOTOS_PRE . "url", Database::PHOTOS_PRE . "id = " . (int)$photo_id );
					
					if ( strpos( $slug, $handle . "-", 0 ) == 0 )
						$slug = substr( $slug, strlen( $handle . "-") );
					
					$this->href = $this->webpage->site->url . $handle . "/e/" . $slug . ".html";
				}
				
			}
			
			return $data;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Setters
		#----------------------------------------------------------------------------------------------------
		/*protected function set_num( $a )			{ return $this->num->value = (int)$a; }
		protected function set_cove( $a )			{ return $this->cove->value = (int)$a; }
		protected function set_cove_use( $a )		{ return $this->cove_use->value = (bool)$a; }
		protected function set_short( $a )			{ return $this->short->value = trim( stripslashes( $a ) ); }
		protected function set_airdate( $a )		{ return $this->airdate->value = trim( stripslashes( $a ) ); }
		protected function set_reair( $a )			{ return $this->reair->value = trim( stripslashes( $a ) ); }
		protected function set_enabled_video( $a )	{ return $this->enabled_video->value = (bool)$a; }
		protected function set_prime( $a )			{ return $this->prime->value = (int)$a; }
		protected function set_program( $a )		{ return $this->program = (int)$a; }*/
	}
}
?>
