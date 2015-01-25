<?php
#============================================================================================================
# ** Segment Class
#
# Version:   1.0
# Author:    Michael Shelton
#============================================================================================================
namespace Core;
if ( !defined( "D_CLASS_SEGMENT" ) )
{
	define( "D_CLASS_SEGMENT", true );
	
	require( __DIR__ . "/class.data.php" );
	require( __DIR__ . "/class.articledata.php" );
	require( __DIR__ . "/class.guest.php" );
	require( __DIR__ . "/class.photo.php" );
	require( __DIR__ . "/class.anchor.php" );
	require( __DIR__ . "/class.segment_category.php" );
	
	class Segment extends ArticleData
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		protected $num 				= NULL;
		protected $video			= NULL;
		protected $cove				= NULL;
		protected $episode			= NULL;
		protected $category			= NULL;
		protected $company			= NULL;
		protected $thumb			= NULL;
		protected $metadata			= NULL;
		protected $length			= NULL;
		protected $program			= NULL;
		protected $_meta			= NULL;
		protected $photos			= array();
		protected $guests			= array();
		protected $resources		= array();
		protected $template_parse	= "show_segment_full.html";
		protected $template_parse_m	= "show_segment.html";
		protected $template_parse_s	= "show_segment_small.html";
		protected $template_cove	= "show_episode_cove2.html";
		protected $template_summary	= "segments_summary.html";
		protected $template_photos	= "segments_photos.html";
		protected $template_youtube	= "segments_youtube.html";
		protected $other_tables		= array( "Photo" => Database::S2PHOTOS, "Guest" => Database::G2SEGMENTS, "Anchor" => Database::S2SITES  );  
		
		const TABLE 				= Database::SEGMENTS;
		const PREFIX 				= Database::SEGMENTS_PRE;
		const COVE_HEIGHT			= 300;
		const COVE_WIDTH			= 500;
		#----------------------------------------------------------------------------------------------------
		# * Convert To String
		#----------------------------------------------------------------------------------------------------
		public function __toString()
		{
			return $this->parse();
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Parse
		#----------------------------------------------------------------------------------------------------
		public function parse( $min = false, $list = false, $num = 1 )
		{
			$nothumb = $this->webpage->site->url . "themes/" . $this->webpage->theme . "/images/nothumb.jpg";
			
			if ( !$min )
			{
				$html = new \Core\Template( $this->webpage, $this->template_parse );
				$thumb = $this->thumb ? $this->webpage->db->fetch_cell( Database::PHOTOS, "photo_url", "photo_id = '" . $this->thumb . "'" ) : $nothumb;
				if ( !$thumb || !Generic::url_exists( $thumb ) )
					$thumb = $nothumb;
				
				$html->add_var( "TITLE", $this->name );
				$html->add_var( "CATEGORY", ( new SegmentCategory( $this->category ) )->name->value );
				$html->add_var( "DESCRIPTION", $this->content->value ? $this->content->value : "No description available." );
				$html->add_url( "THUMB", $thumb );
				$html->add_func( "GUESTS", array( $this, "show_guests" ) );
				$html->add_func( "PHOTOS", array( $this, "show_photos" ) );
				
				$video 	= $this->cove->value ? ( new COVE_API )->get_video( $this->cove->value, false, true, false, true ) : false;
				$html = $html->parse();
				
				if ( $num > 1 )
				{
					$html .= "<div class=\"actions\">"
					. ( $video ? "<a class=\"play\" href=\"#action_play\" onclick=\"_gaq.push(['_trackEvent', 'segmentPlay', '" . $this->id . "']);\">Play This Segment</a>" : "" )
					. "<a class=\"share\" href=\"#action_share\" onclick=\"_gaq.push(['_trackEvent', 'segmentShare', '" . $this->id . "']);\">Share This Segment</a>"
					. ( $video ? "<a class=\"embed\" href=\"#action_embed\" onclick=\"_gaq.push(['_trackEvent', 'segmentEmbed', '" . $this->id . "']);\">Embed This Segment</a>" : "" )
					. "</div>";
				}
			}
			else
			{
				$html 		= new \Core\Template( $this->webpage, ( $list ? $this->template_parse_s : $this->template_parse_m ) );
				$thumb 		= $this->thumb ? $this->webpage->db->fetch_cell( Database::PHOTOS, "photo_url", "photo_id = '" . $this->thumb . "'" ) : $nothumb;
				if ( !$thumb || !Generic::url_exists( $thumb ) )
					$thumb = $nothumb;
				
				$html->add_var( "TITLE", 		$this->name->value );
				$html->add_var( "GUESTS", 		$this->list_guests() );
				$html->add_var( "NUM", 			$this->num->value );
				$html->add_url( "THUMB", 		$thumb );
				$html->add_url( "SEGMENT", 		$this->webpage->anchor( SHOW_SEGMENTS, array( "slug" => str_replace( $this->webpage->show->handle . "-", "", $this->slug->value ) ) ) );
				
				$html = $html->parse();
			}
			
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Embed
		#----------------------------------------------------------------------------------------------------
		public function embed()
		{
			$html = new \Core\Template( $this->webpage, $this->template_cove ); 
			$html->add_var( "HEIGHT", 	self::COVE_HEIGHT );
			$html->add_var( "WIDTH", 	self::COVE_WIDTH );
			$html->add_var( "COVE_ID", 	$this->cove->value );
			$html->add_var( "CHAPTER", 	$this->num->value );
			$html->add_var( "START", 	0 );
			$html->add_var( "END", 		0 );
			
			return $html;
		}
		
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
			$not_recorded = array( "_meta", "photos", "guests", "resources", "template_parse",
									"template_parse_m","template_parse_s","template_cove",
									"template_summary","template_photos","template_youtube" );
			foreach( $not_recorded as $r )
				$this->not_recorded[] = $r;
			
			

			$data = parent::setup( $data );
			
			$this->def_col( 'num', $data, "Column", true );
			$this->def_col( 'episode', $data );
			$this->def_col( 'video', $data );
			$this->def_col( 'cove', $data );
			$this->def_col( 'category', $data );
			$this->def_col( 'company', $data );
			$this->def_col( 'metadata', $data );
			$this->def_col( 'thumb', $data );
			$this->def_col( 'length', $data );
			$this->def_col( 'program', $data );

			
			
			if ( $data )
			{

				// get info from the database
				$guests = Database::select( Database::G2SEGMENTS, Database::GUESTS_PRE . "id", $this->prefix . "id = '" . (int)$this->id . "'" );
				foreach( $guests as $g )
					$this->guests[] = $g[ Database::GUESTS_PRE . 'id' ];
				$photos = Database::select( Database::S2PHOTOS, Database::PHOTOS_PRE . "id", $this->prefix . "id = '" . (int)$this->id . "'" );
				foreach( $photos as $g )
					$this->photos[] = $g[ Database::PHOTOS_PRE . 'id' ];
				$resources = Database::select( Database::S2SITES, "*", $this->prefix . "id = '" . (int)$this->id . "'" );
				

				foreach( $resources as $g )
					$this->resources[] = array( "id" => $g[ Database::SITES_PRE . 'id' ], "type" => $g[ Database::RESOURCETYPES_PRE . 'id' ] );

				
				$this->_meta = array();
				if ( $this->metadata->value ) {
					$program_meta = Database::fetch_cell( Database::PROGRAMS, Database::PROGRAMS_PRE . "meta", Database::PROGRAMS_PRE . "id = " . (int)$this->program->value );
					$meta_names = explode( "," , $program_meta );
					$metas = explode( "|", $this->metadata->value );	
					foreach ( $metas as $n => $m ) {
						$this->_meta[ $meta_names[ $n ] ] = $m;
					}
				}
		

				// add this object to children for reference
				//$this->adopt( 'guests' );
				//$this->adopt( 'photos' );
				//$this->adopt( 'resources' );
			}
			
			
			
			return $data;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Show Photos
		#----------------------------------------------------------------------------------------------------
		public function show_guests()
		{
			$html = "";
			
			if ( !$this->guests )
				$html = "<p>Guest information could not be found for this segment.</p>";
			else
			{
				$html .= "<h3>Guests</h3>\n<ol>\n";
				
				foreach( $this->guests as $g )
					$html .= "<li>" . $g . "</li>\n";
				
				$html .= "</ol>\n";
			}
			
			return $html;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * List Guests
		#--------------------------------------------------------------------------------------------------
		protected function list_guests()
		{
			$list = array();
			
			foreach( $this->guests as $g )
				$list[] = $g->name;
			
			return implode( ", ", $list );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Show Photos
		#----------------------------------------------------------------------------------------------------
		public function show_photos( $ignore_thumb = true )
		{
			if ( $this->photos )
			{
				$html .= "<h3>Photos</h3>\n<ul>\n";
				foreach( $this->photos as $p )
					if ( !$ignore_thumb || $p->id != $this->thumb )
						$html .= "<li><a href=\"" . $p->url->value . "\" class=\"box\"><img src=\"" . $p->url->value . "\" alt=\"" . $p->img->value . "\" /></a></li>\n";
				$html .= "</ul>\n";
			}
			
			return $html;
		}
		/*
		#----------------------------------------------------------------------------------------------------
		# * Show Resources
		#----------------------------------------------------------------------------------------------------
		public function show_resources()
		{
			$html = "<div class=\"resources\">\n";
			
			if ( $this->resources )
			{
				$html .= "<h3>Resources</h3>\n<ul>\n";
				foreach( $this->resources as $r )
				{
					$html .= "<li><a href=\"" . $r->url . "\" target=\"_blank\">" . $r->name . "</a></li>\n";
				}
				$html .= "</ul>\n";
			}
			
			$html .= "</div>";
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Show Segments
		# travis was here
		#----------------------------------------------------------------------------------------------------
		public function show_segments()
		{
			$html = "";
			
			if ( $e = $this->webpage->show->get_episode( $this->episode ) )
			{
				$html .= "<div id=\"more_segments\">\n";
				$html .= $e->show_segments( $this->id );
				$html .= "</div>\n";
			}
			
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Show Video
		# travis was here
		#----------------------------------------------------------------------------------------------------
		public function show_video()
		{
			$episode = new Episode( $this->episode->value );
			if ( !$episode->enabled_video->value || ( !$this->cove && !$this->video ) )
				$html = '<div class="nowatch">The video for this segment is unavailable at this time. Please try again later.</div>';
			else
			{
				// only template if there is a video set
				if ( $this->video->value || $this->cove->value ) 
				{
					$template = $this->cove->value ? $this->template_cove : $this->template_youtube; 
					$html = new Template( $this->webpage, $template );
					$youtube_id = "";
					
					if ( $this->video->value )
					{
						$youtube_id = $this->get_youtube_id( $this->video->value );
						$html->add_var( "YOUTUBE_ID", $youtube_id );
					}
					
					$html->add_var( "COVE_ID", $this->cove->value );
					$html->add_var( "COVE_CHAPTER", $this->num->value );
					$html->add_var( "HEIGHT", self::COVE_HEIGHT );
					$html->add_var( "WIDTH", self::COVE_WIDTH );
					$html = $html->parse();
				}
			}
			
			return $html; 
		}
		*/
		#----------------------------------------------------------------------------------------------------
		# * Get primary thumb
		# added by travis
		#----------------------------------------------------------------------------------------------------
		public function get_primary_thumb()
		{
			foreach ($this->photos as $p) {
				if ($this->thumb->value == (int)$p) {
					return $p;	
				}
			}
			
			return NULL;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Get COVE ID
		#----------------------------------------------------------------------------------------------------
		protected function get_cove_id( $link )
		{
			// initialize variables
			$id = false;
			
			if ( strpos( $link, "http" ) === false )
				$id = (int)$link;
			else if ( ( $v = strpos( $link, "/video/" ) ) !== false )
				$id = (int)substr( $link, ( $v + 7 ), 10 );
			
			return $id;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Get YouTube ID
		#----------------------------------------------------------------------------------------------------
		protected function get_youtube_id( $link )
		{
			// initialize variables
			$id = false;
			
			if ( strpos( $link, "http" ) === false )
				$id = $link;
			else if ( ( $v = strpos( $link, "v=" ) ) !== false )
				$id = substr( $link, ( $v + 2 ), 11 );
			
			return $id;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * File Add
		#----------------------------------------------------------------------------------------------------
		public function file_add()
		{
			$table = isset( $_GET['table'] ) ? $_GET['table'] : null;
			$is_callback = isset( $_POST['callback'] ) ? (bool)$_POST['callback'] : false;

			if ( $table == Database::PHOTOS )
			{
				$photo = new Photo( 0 );
				$photo_dir = "";
				$photo_url_dir = "";
				$local = "images/";
				$url = "images/";
				if ( $this->webpage ) $photo_dir = $this->webpage->site->local . $local;
				if ( $this->webpage ) $photo_url_dir = $this->webpage->site->url . $url;
				$photo_column = new ImageColumn( "", "", "photo", $photo_dir, $photo_url_dir, ImageColumn::IMAGE_TYPES );
				$photo_column->errors();
				$photo->name->value = $photo_column->file_name;
				$photo->url->value = $photo_url_dir . $photo_column->file_name;
				$photo->local->value = $photo_dir . $photo_column->file_name;
				$photo->enabled = true;
				$photo->insert();
				$sql = sprintf( "INSERT INTO %s ( %sid, %sid ) VALUES ( %d, %d )", Database::S2PHOTOS, Database::PHOTOS_PRE, Database::SEGMENTS_PRE, $photo->id, $this->id  );
				mysql_query( $sql );
				if ( $this->webpage && $is_callback )
				{
					$this->webpage->form->add_var( strtoupper( Database::PHOTOS . "_CALLBACK" ), 1 );
					$this->webpage->set_form_vars();
					return;
				}
			}
			

			else if ( $this->webpage ) 
				$this->webpage->redirect( $this->webpage->anchor( $this->webpage->page_id, array( "id" => $this->id, "mode" => $this->webpage->mode ) ) );
			else
				exit();
		}
		
		#----------------------------------------------------------------------------------------------------
		# * File Add
		#----------------------------------------------------------------------------------------------------
		public function file_delete()
		{
			$table = isset( $_GET['table'] ) ? $_GET['table'] : null;
			$is_callback = isset( $_POST['callback'] ) ? (bool)$_POST['callback'] : false;

			if ( $table == Database::PHOTOS )
			{
				$photo_id = isset( $_GET[ Database::PHOTOS_PRE. 'id'] ) ? $_GET[ Database::PHOTOS_PRE. 'id' ] : null;
				if ( $photo_id )
				{
					$photo = new Photo( $photo_id );
					$sql = sprintf( "DELETE FROM %s WHERE %sid = %d AND %sid = %d", Database::S2PHOTOS, Database::PHOTOS_PRE . "id", $photo->id, $this->prefix, $this->id );
					mysql_query( $sql );
					$photo->delete();
					if ( $this->webpage && $is_callback )
					{
						$this->webpage->form->add_var( strtoupper( Database::PHOTOS . "_CALLBACK" ), 1 );
						$this->webpage->set_form_vars();
						return;
					}
				}
			}
			

			else if ( $this->webpage ) 
				$this->webpage->redirect( $this->webpage->anchor( $this->webpage->page_id, array( "id" => $this->id, "mode" => $this->webpage->mode ) ) );
			else
				exit();
		}
		
		#----------------------------------------------------------------------------------------------------
		# * File Add
		#----------------------------------------------------------------------------------------------------
		public function file_arrange()
		{
			die("segments::file_arrange called");
		}
	}
}
?>