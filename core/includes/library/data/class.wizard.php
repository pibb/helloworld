<?php
#============================================================================================================
# ** Wizard Class
#
# Version:   1.0
# Author:    Michael Shelton
#============================================================================================================
namespace Core;
{
	require_once( __DIR__ . "/class.data.php" );
	
	class Wizard extends Data
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		protected $show    = NULL;
		protected $episode = NULL;
		protected $publish_time = NULL;
		protected $image_status = NULL;
		protected $video_status = NULL;
		protected $auto_publish = NULL;
		protected $auto_thumbnail = NULL;
		protected $auto_chapter = NULL;
		protected $video_error_count = NULL;
		protected $image_error_count = NULL;
		protected $chapter_info = NULL;
		protected $video_info = NULL;
		protected $asset = NULL;
		
		
		const TABLE			= Database::WIZARD;
		const PREFIX		= Database::WIZARD_PRE;
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
		protected function setup( $data = array() )
		{
		
			/*$mem = memory_get_usage();
			$last_mem = $mem;
			
			$this->show = new Column( self::TABLE, self::PREFIX, 'show', Database::PROGRAMS, Database::PROGRAMS_PRE );
			
			$this->add_col( 'show', true, array( Database::PROGRAMS, Database::PROGRAMS_PRE, 'name' ) );
			//unset( $this->show );
			
			echo "mem: " . ($mem=memory_get_usage()) . " (+". ( $mem-$last_mem ) .")<br />";*/
		
			// create columns
			
			$this->show = new Many2OneColumn( self::TABLE, self::PREFIX, 'show', Database::PROGRAMS, Database::PROGRAMS_PRE, "id" );
			$this->episode = new Many2OneColumn( self::TABLE, self::PREFIX, 'episode', Database::EPISODES, Database::EPISODES_PRE, "id" );

			
			
			
			$this->publish_time = new Column( self::TABLE, self::PREFIX, "publish_time" );
			$this->image_status = new Column( self::TABLE, self::PREFIX, "image_status" );
			$this->video_status = new Column( self::TABLE, self::PREFIX, "video_status" );
			$this->chapter_info = new Column( self::TABLE, self::PREFIX, "chapter_info" );
			$this->video_info 	= new Column( self::TABLE, self::PREFIX, "video_info" );
			
			$this->auto_publish = new Column( self::TABLE, self::PREFIX, "auto_publish" );
			$this->auto_thumbnail = new Column( self::TABLE, self::PREFIX, "auto_thumbnail" );
			$this->auto_chapter = new Column( self::TABLE, self::PREFIX, "auto_chapter" );
			$this->video_error_count = new Column( self::TABLE, self::PREFIX, "video_error_count" );
			$this->image_error_count = new Column( self::TABLE, self::PREFIX, "image_error_count" );
			
			$this->asset = new Column( self::TABLE, self::PREFIX, "asset" );
			

			
			
			
			// add the columns to the list

			$this->add_col( 'show', true, array( Database::PROGRAMS, Database::PROGRAMS_PRE, 'name' ) );
			$this->add_col( 'episode', true, array( Database::EPISODES, Database::EPISODES_PRE, 'num' ) );

			$this->add_col( 'publish_time', false );
			$this->add_col( 'image_status', false );
			$this->add_col( 'video_status', false );
			$this->add_col( 'chapter_info', false );
			$this->add_col( 'video_info', false );
			
			$this->add_col( 'auto_publish', false );
			$this->add_col( 'auto_thumbnail', false );
			$this->add_col( 'auto_chapter', false );
			$this->add_col( 'video_error_count', false );
			$this->add_col( 'image_error_count', false );
			
			$this->add_col( 'asset', false );
				

			if ( $data = parent::setup() )
			{
				
				$this->set_show( $data[ $this->prefix . 'show' ] );
				$this->set_episode( $data[ $this->prefix . 'episode' ] );
				$this->set_publish_time( $data[ $this->prefix . 'publish_time' ] );
				$this->set_image_status( $data[ $this->prefix . 'image_status' ] );
				$this->set_video_status( $data[ $this->prefix . 'video_status' ] );
				$this->set_chapter_info( $data[ $this->prefix . 'chapter_info' ] );
				$this->set_video_info( $data[ $this->prefix . 'video_info' ] );
				
				$this->set_auto_publish( $data[ $this->prefix . 'auto_publish' ] );
				$this->set_auto_thumbnail( $data[ $this->prefix . 'auto_thumbnail' ] );
				$this->set_auto_chapter( $data[ $this->prefix . 'auto_chapter' ] );
				$this->set_video_error_count( $data[ $this->prefix . 'video_error_count' ] );
				$this->set_image_error_count( $data[ $this->prefix . 'image_error_count' ] );
				
				$this->set_asset( $data[ $this->prefix . 'asset' ] );

			}
			
			return $data;
		}
		

		
		#----------------------------------------------------------------------------------------------------
		# * Setters
		#----------------------------------------------------------------------------------------------------
		protected function set_show( $a ) { return $this->show->value = (int)$a; }
		protected function set_episode( $a ) { return $this->episode->value = (int)$a; }
		protected function set_publish_time( $a ) { return $this->publish_time->value = (int)$a; }
		protected function set_image_status( $a ) { return $this->image_status->value = $a; }
		protected function set_video_status( $a ) { return $this->video_status->value = $a; }
		protected function set_chapter_info( $a ) { return $this->chapter_info->value = $a; }
		protected function set_video_info( $a ) { return $this->video_info->value = $a; }
		
		protected function set_auto_publish( $a ) { return $this->auto_publish->value = $a; }
		protected function set_auto_thumbnail( $a ) { return $this->auto_thumbnail->value = $a; }
		protected function set_auto_chapter( $a ) { return $this->auto_chapter->value = $a; }
		protected function set_video_error_count( $a ) { return $this->video_error_count->value = $a; }
		protected function set_image_error_count( $a ) { return $this->image_error_count->value = $a; }
		
		protected function set_asset( $a ) { return $this->asset->value = $a; }
	}
}
?>