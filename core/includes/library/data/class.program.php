<?php
namespace Core;

if ( !defined( "D_CLASS_PROGRAM" ) )
{
	define( "D_CLASS_PROGRAM", true );
	require( __DIR__ . "/class.data.php" );
	require( __DIR__ . "/class.episode.php" );
	//require( __DIR__ . "/class.program_category.php" );
	//require( __DIR__ . "/class.resourcetype.php" );
	
	/**
 	 * File: class.PROGRAM.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Program extends Data
	{
		protected $name 			= "";
		//protected $img 			= "";
		protected $description 	= "";
		protected $handle 		= "";
		protected $hashtag 		= "";
		protected $twitter 		= "";
		//protected $theme 		= "";
		
		protected $category 	= 0;
		protected $guests 		= false;
		protected $chaptering 	= false;
		protected $segments 	= false;
		protected $featured 	= false;
		protected $resources 	= false;
		protected $catagorize_type 	= false;
		//protected $href			= array();
		protected $episodes		= array();
		
		protected $coveid 		= false;
		protected $airtime 		= false;
		protected $reairtime 	= false;
		protected $titleformat 	= false;
		//protected $catagorize_type 	= false;
		protected $default_catagory = false;
		//protected $chaptering 		= false;
		protected $episodelength 	= false;
		protected $meta 			= false;
		
		const TABLE			= Database::PROGRAMS;
		const PREFIX		= Database::PROGRAMS_PRE;
		
		/**
		 * Get a specific field from a given row.
		 *
		 * @param string the name of the column/field.
		 * @param mixed the row identifier.
		 * @param string the column identifier. (Default = "id")
		 * @uses Database::fetch_cell
		 * @return mixed
		 */
		static public function get_field( $col, $id, $identifier = "id" ) 
		{ 
			return Database::fetch_cell( self::TABLE, self::PREFIX . $col, self::PREFIX . $identifier . " = '" . $id . "'" ); 
		}
		
		/**
		 * Get published rows.
		 *
		 * @param string amendment for the MySQL query in sprintf format. (Default = "%s")
		 * @param WebPage the current webpage if it's being passed on. (Default = NULL)
		 * @param bool whether or not to flag the first result. (Default = true)
		 * @uses Data::getx_published
		 * @return Array of Ad objects.
		 */
		static public function get_published( $amend = "%s", WebPage &$webpage = NULL, $flag_first = true ) 
		{ 
			return parent::getx_published( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage, $flag_first ); 
		}
		
		/**
		 * Get published rows using arrays.
		 *
		 * @param string the name of the MySQL table.
		 * @param string the prefix for the column names.
		 * @param string amendment for the MySQL query in sprintf format. (Default = "%s")
		 * @uses Database::get_published_data
		 * @uses Article::append_data_array
		 * @return Array keys are column names.
		 */
		static public function get_published_array( $amend = "%s", WebPage &$webpage = NULL, $flag_first = true ) 
		{ 
			return parent::getx_published_array( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage, $flag_first ); 
		}
		
		/**
		 * Initialize MySQL table name using class constant.
		 */
		protected function init_table() 	
		{ 
			return self::TABLE; 
		}
		
		/**
		 * Initialize MySQL column prefix using class constant.
		 */
		protected function init_prefix()
		{ 
			return self::PREFIX; 
		}
		
		/**
		 * Initialize class name.
		 */
		protected function init_classname()	
		{ 
			return __CLASS__;
		}
		
		/**
		 * Initializing method that takes given row information and puts them into properties.
		 *
		 * @param mixed if an array is passed, it fills the properties; otherwise, it will attempt to get an array using it as an id.
		 * @uses Program::$name
		 * @uses Program::$description
		 * @uses Program::$handle
		 * @uses Program::$hashtag
		 * @uses Program::$twitter
		 * @uses Program::$episodes
		 * @uses Program::set_name
		 * @uses Program::set_description
		 * @uses Program::set_handle
		 * @uses Program::set_hashtag
		 * @uses Program::set_twitter
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @uses Database::select_published
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			// extra properties
			$this->not_recorded = array_merge( $this->not_recorded, array( 'resources' ) );
			
			
			$this->def_col( 'name', $data, "Column", false, true );
			$this->def_col( 'description', $data );
			$this->def_col( 'handle', $data );
			$this->def_col( 'hashtag', $data );
			$this->def_col( 'twitter', $data );
			$this->def_col( 'coveid', $data );
			$this->def_col( 'airtime', $data );
			$this->def_col( 'reairtime', $data );
			$this->def_col( 'titleformat', $data );
			$this->def_col( 'episodelength', $data );
			$this->def_col( 'meta', $data );
			$this->def_col( 'category', $data, "Many2OneColumn", false, array( Database::PROGCATS, Database::PROGCATS_PRE, "name"  ) );
			$this->def_col( 'default_catagory', $data, "Many2OneColumn", false, array( Database::SEGCATS, Database::SEGCATS_PRE, "name"  ) );
			
			
			$this->def_col( 'featured', $data, "MultiColumn" );
			$this->def_col( 'segments', $data, "MultiColumn" );
			$this->def_col( 'guests', $data, "MultiColumn" );
			$this->def_col( 'chaptering', $data, "MultiColumn" );
			$this->def_col( 'catagorize_type', $data, "MultiColumn" );
			$this->def_col( 'twitter', $data, "MultiColumn" );
			
			// create columns
			///$this->name 			= new Column( self::TABLE, self::PREFIX, "name" );
			//$this->category	 	= new Many2OneColumn( self::TABLE, self::PREFIX, "category", Database::PROGCATS, Database::PROGCATS_PRE, "name" );
			//$this->img 			= new ImageColumn( self::TABLE, self::PREFIX, "img", $this->webpage->site->local_upload . "programs" . DIRECTORY_SEPARATOR, $this->webpage->site->url_upload . "programs/", ImageColumn::IMAGE_TYPES );
			///$this->description 	= new Column( self::TABLE, self::PREFIX, "description" );
			///$this->handle 		= new Column( self::TABLE, self::PREFIX, "handle" );
			///$this->hashtag 		= new Column( self::TABLE, self::PREFIX, "hashtag" );
			///$this->twitter 		= new Column( self::TABLE, self::PREFIX, "twitter" );
			//$this->theme 		= new Column( self::TABLE, self::PREFIX, "theme" );
			//$this->featured 	= new MultiColumn( self::TABLE, self::PREFIX, "featured" );
			//$this->segments 	= new MultiColumn( self::TABLE, self::PREFIX, "segments" );
			//$this->guests 		= new MultiColumn( self::TABLE, self::PREFIX, "guests" );
			//$this->meta 		= new MultiColumn( self::TABLE, self::PREFIX, "meta" );
		
			//$this->coveid 		= new Column( self::TABLE, self::PREFIX, "coveid" );
			//$this->airtime 		= new Column( self::TABLE, self::PREFIX, "airtime" );
			//$this->reairtime 		= new Column( self::TABLE, self::PREFIX, "reairtime" );
			//$this->titleformat 		= new Column( self::TABLE, self::PREFIX, "titleformat" );
			//$this->catagorize_type 	= new MultiColumn( self::TABLE, self::PREFIX, "catagorize_type" );
			//$this->default_catagory = new Many2OneColumn( self::TABLE, self::PREFIX, "default_catagory", Database::SEGCATS, Database::SEGCATS_PRE, "name" );
			//$this->chaptering 		= new MultiColumn( self::TABLE, self::PREFIX, "chaptering" );
			//$this->episodelength 	= new Column( self::TABLE, self::PREFIX, "episodelength" );
			//$this->meta 			= new Column( self::TABLE, self::PREFIX, "meta" );
		
			// make adjustments
			$options = array( 1 => "Yes", 0 => "No" );
			//$this->name->min = 3;
			//$this->name->max = 64;
			//$this->description->min = 3;
			//$this->description->max = 2000;
			//$this->handle->unique = true;
			$this->featured->options = $options;
			$this->segments->options = $options;
			$this->guests->options = $options;
			$this->chaptering->options = $options;
			$this->catagorize_type->options = $options;
			
			// add columns
			//$this->add_col( 'name', true, true );
			//$this->add_col( 'category', true, array( Database::PROGCATS, Database::PROGCATS_PRE, "name" ) );
			//$this->add_col( 'img' );
			//$this->add_col( 'description' );
			//$this->add_col( 'handle', true );
			//$this->add_col( 'hashtag' );
			//$this->add_col( 'twitter' );
			//$this->add_col( 'theme' );
			//$this->add_col( 'featured' );
			//$this->add_col( 'segments' );
			//$this->add_col( 'guests' );
			
			//$this->add_col( 'coveid' );
			//$this->add_col( 'airtime' );
			//$this->add_col( 'reairtime' );
			//$this->add_col( 'titleformat' );
			//$this->add_col( 'catagorize_type' );
			//$this->add_col( 'default_catagory' );
			//$this->add_col( 'chaptering' );
			//$this->add_col( 'episodelength' );
			//$this->add_col( 'meta' );
			
			if ( $data )
			{
				///$this->set_name( $data[ $this->prefix . 'name' ] );
				//$this->set_img( $data[ $this->prefix . 'img' ] );
				///$this->set_description( $data[ $this->prefix . 'description' ] );
				///$this->set_handle( $data[ $this->prefix . 'handle' ] );
				///$this->set_hashtag( $data[ $this->prefix . 'hashtag' ] );
				///$this->set_twitter( $data[ $this->prefix . 'twitter' ] );
				//$this->set_theme( $data[ $this->prefix . 'theme' ] );
				//$this->set_category( $data[ $this->prefix . 'category' ] );
				//$this->set_guests( $data[ $this->prefix . 'guests' ] );
				//$this->set_segments( $data[ $this->prefix . 'segments' ] );
				//$this->set_featured( $data[ $this->prefix . 'featured' ] );
				//$this->set_featured( $data[ $this->prefix . 'featured' ] );
				
				//$this->set_coveid( $data[ $this->prefix . 'coveid' ] );
				//$this->set_airtime( $data[ $this->prefix . 'airtime' ] );
				//$this->set_reairtime( $data[ $this->prefix . 'reairtime' ] );
				//$this->set_titleformat( $data[ $this->prefix . 'titleformat' ] );
				//$this->set_catagorize_type( $data[ $this->prefix . 'catagorize_type' ] );
				//$this->set_default_catagory( $data[ $this->prefix . 'default_catagory' ] );
				//$this->set_chaptering( $data[ $this->prefix . 'chaptering' ] );
				//$this->set_episodelength( $data[ $this->prefix . 'episodelength' ] );
				//$this->set_meta( $data[ $this->prefix . 'meta' ] );
				
				//$resources = Database::select( Database::P2RESOURCES, "*", Database::PROGRAMS_PRE . "id  = " . $this->id );
				//$this->resources = array();
				/*foreach( $resources as $r ) 
				{
					$this->resources[] = array( "name" => 			$r[ Database::P2RESOURCES_PRE . 'name' ],
												"resourcetype" => 	$r[ Database::RESOURCETYPES_PRE . 'id' ],
												"required" => 		$r[ Database::P2RESOURCES_PRE . 'required' ]);
				}*/
				
				// set external links
				//$this->href[ 'site' ]	 	= $data[ $this->prefix . 'site' ];
				//$this->href[ 'episodes' ] 	= $data[ $this->prefix . 'episodes' ];
				//$this->href[ 'schedule' ] 	= $data[ $this->prefix . 'schedule' ];
				
				// setup episodes
				$episodes 	= Database::select_published( Database::EPISODES, "episode_id, episode_reair, episode_airdate", "episode_program = '" . $this->id . "' AND episode_deleted = '0' ORDER BY episode_reair DESC, episode_airdate DESC", "episode_" );
				$now 		= time();
				$deck 		= array();
				
				// since episodes could be scheduled far in advance, we have to check dates against today.
				foreach( $episodes as $e )
				{
					if ( $e[ 'episode_reair' ] && $now >= $e[ 'episode_reair' ] )
						$deck[ $e[ 'episode_reair' ] ] = $e[ 'episode_id' ];
					else if ( !$e[ 'episode_reair' ] && $e[ 'episode_airdate' ] && $now >= $e[ 'episode_airdate' ] )
						$deck[ $e[ 'episode_airdate' ] ] = $e[ 'episode_id' ];
				}
						
				// sorts the deck from the most recent episode to the oldest
				krsort( $deck );
				
				// remember the id's in the order they were sorted
				foreach( $deck as $time => $id )
					if ( !in_array( $id, $this->episodes ) )
						$this->episodes[] = $id;
				
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Program::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Program::$name
		 * @param string
		 * @return string
		 */
		/*protected function set_name( $a )			
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}*/
		
		/**
		 * Sets the value of Program::description. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Program::$description
		 * @param string
		 * @return string
		 */
		/*protected function set_description( $a )	
		{ 
			return $this->description->value = trim( stripslashes( $a ) ); 
		}*/
		
		/**
		 * Sets the value of Program::handle. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Program::$handle
		 * @param string
		 * @return string
		 */
		/*protected function set_handle( $a )			
		{ 
			return $this->handle->value = trim( stripslashes( $a ) ); 
		}*/
		
		/**
		 * Sets the value of Program::hashtag. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Program::$hashtag
		 * @param string
		 * @return string
		 */
		/*protected function set_hashtag( $a )		
		{ 
			return $this->hashtag->value = trim( stripslashes( $a ) ); 
		}*/
		
		/**
		 * Sets the value of Program::twitter. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Program::$twitter
		 * @param string
		 * @return string
		 */
		/*protected function set_twitter( $a )		
		{ 
			return $this->twitter->value = trim( stripslashes( $a ) ); 
		}*/
		
		//protected function set_img( $a )			{ return $this->img->value = trim( stripslashes( $a ) ); }
		//protected function set_theme( $a )			{ return $this->theme->value = trim( stripslashes( $a ) ); }
		//protected function set_category( $a )		{ return $this->category->value = (int)$a; }
		//protected function set_guests( $a )			{ return $this->guests->value = (bool)$a; }
		//protected function set_segments( $a )		{ return $this->segments->value = (bool)$a; }
		//protected function set_featured( $a )		{ return $this->featured->value = (bool)$a; }
		
		//protected function set_coveid( $a )			{ return $this->coveid->value = (int)$a; }
		//protected function set_airtime( $a )		{ return $this->airtime->value = trim( stripslashes( $a ) ); }
		//protected function set_reairtime( $a )		{ return $this->reairtime->value = trim( stripslashes( $a ) ); }
		//protected function set_titleformat( $a )	{ return $this->titleformat->value = trim( stripslashes( $a ) );  }
		//protected function set_catagorize_type( $a )		{ return $this->catagorize_type->value = (int)$a; }
		//protected function set_default_catagory( $a )		{ return $this->default_catagory->value = (int)$a; }
		//protected function set_chaptering( $a )				{ return $this->chaptering->value = (int)$a; }
		//protected function set_episodelength( $a )			{ return $this->episodelength->value = trim( stripslashes( $a ) );  }
		//protected function set_meta( $a )					{ return $this->meta->value = trim( stripslashes( $a ) );  }
		
		#----------------------------------------------------------------------------------------------------
		# * Get Article
		#----------------------------------------------------------------------------------------------------
		/*public function get_article( $slug )
		{
			if ( $article = $this->webpage->db->fetch( \Core\Database::ARTICLES, "*", "article_slug = '" . $this->webpage->show->handle . "-" . $slug . "' AND article_enabled != '0' AND article_deleted = '0'" ) )
				if ( !Database::row_is_published( \Core\Database::ARTICLES, "article_", $article[ 'article_id' ] ) )
					$article = NULL;
		
			return $article;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Get Episode
		#----------------------------------------------------------------------------------------------------
		/*public function get_episode( $num, $program_id = NULL )
		{
			// initialize variables
			$episode 	= NULL;
			
			
			if ( $program_id === NULL ) {
				// look it up
				if ( $id = Database::select_published( Database::EPISODES, "episode_id", "episode_num = '" . (int)$num . "'", "episode_" ) )
					$episode = new Episode( $id[ 0 ][ 'episode_id' ], $this );
			} else {
				if ( $id = Database::select_published( Database::EPISODES, "episode_id", "episode_num = '" . (int)$num . "' AND episode_program = '" . (int)$program_id . "'", "episode_" ) )
					$episode = new Episode( $id[ 0 ][ 'episode_id' ], $this );
			}
		
			return $episode;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Get Episode By date
		#----------------------------------------------------------------------------------------------------
		/*public function get_episode_date( $date )
		{
			// initialize variables
			$episode 	= NULL;
			$n 			= count( $this->episodes );
			
			// find the episode
			for( $i = 0; $i < $n; $i++ )
			{
				if ( in_array( $date, array( $this->episodes[ $i ]->airdate, $this->episodes[ $i ]->reairdate ) ) )
				{																			 
					$episode = &$this->episodes[ $i ];
					break;
				}
			}
		
			return $episode;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Get Segments By Topics
		#----------------------------------------------------------------------------------------------------
		/*public function get_segments_topic( $topics = array() )
		{
			// initialize variables
			$segments = array();
			$episodes = array();
			
			// order the episodes from the most recent to the oldest
			foreach( $this->episodes as $i => $e )
			{
				if ( $e->reairdate )
					$episodes[ $e->reairdate ] = &$this->episodes[ $i ];
				else
					$episodes[ $e->airdate ] = &$this->episodes[ $i ];
			}
			
			krsort( $episodes );
			
			foreach( $episodes as $e )
				$segments = array_merge_recursive( $segments, $e->get_segments_topic( $limit, $topics ) );
				
			ksort( $segments );
			
			return $segments;
			
		}*/
		#----------------------------------------------------------------------------------------------------
		# * Get most recent segments of given category
		# Added By Travis
		#----------------------------------------------------------------------------------------------------
		/*public function get_recent_segments_by_category( $category = '', $limit = 5 )
		{
			$topic = addslashes($topic);
			$limit = (int)$limit;
			return \Core\Segment::geta_published( \Core\Database::SEGMENTS_PRE . "category = '$category' LIMIT $limit" );
		}*/
		
		/**
		 * Get next episode.
		 *
		 * @todo needs finishing if it's still used. Right now, it just returns false.
		 * @return bool false.
		 */
		public function get_next_episode() 
		{
			/*// initialize variables
			$pre = Database::EPISODES_PRE;
			$now = time();
			$next = Episode::geta_published( "{$pre}program = '" . $this->id . "' AND ( {$pre}airdate > '{$now}' OR {$pre}reair > '{$now}' ) ORDER BY {$pre}reair, {$pre}airdate ASC LIMIT 1" );
			
			return $next ? $next[ 0 ] : false;*/
			return false;
		}
		
		/**
		 * Retrieves episode/segment related to the given slug.
		 *
		 * @param string the slug to lookup.
		 * @uses Database::select_published
		 * @return Array array( $episode, $segment )
		 */
		public function get_slug( $slug )
		{
			// initialize variables
			$segment = NULL;
			$episode = NULL;
			
			if ( $episodes = Database::select_published( Database::EPISODES . " as e LEFT JOIN " . Database::ARTICLES . " as a ON a.article_id = e.episode_article", "e.episode_id", "a.article_slug = '" . $this->handle . "-" . $slug . "'", "e.episode_" ) )
			{
				$episode = new Episode( (int)$episodes[ 0 ][ 'episode_id' ] );
			}
			else if ( $segments = Database::select_published( Database::SEGMENTS . " as s LEFT JOIN " . Database::ARTICLES . " as a ON a.article_id = s.segment_article", "s.*", "a.article_slug = '" . $this->handle . "-" . $slug . "'", "s.segment_" ) )
			{
				$segment = new Segment( (int)$segments[ 0 ][ 'segment_id' ] );
				$episode = new Episode((int)$segments[ 0 ][ 'segment_episode' ] );
			}
			
			return array( $episode, $segment );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * List Episode By Season
		#----------------------------------------------------------------------------------------------------
		/*public function list_episodes_by_season( $clear = 0 )
		{
			// initialize variables
			$html 	= "";
			$words	= array( 	1 => "One", 2 => "Two", 3 => "Three", 4 => "Four", 5 => "Five", 6 => "Six", 7 => "Seven", 
								8 => "Eight", 9 => "Nine", 10 => "Ten", 11 => "Eleven", 12 => "Twelve", 13 => "Thirteen", 
								14 => "Fourteen", 15 => "Fifteen", 16 => "Sixteen", 17 => "Seventeen" );
			$episodes = Database::select_published( Database::EPISODES . " as e LEFT JOIN " . Database::ARTICLES . " as a ON a.article_id = e.episode_article", "*", "e.episode_id IN(" . implode( ",", $this->episodes ) . ") ORDER BY e.episode_airdate", "e.episode_" );
			$n = count( $episodes );
			
			// get the schedule
			for( $i = 0; $i < $n; $i++ )
			{
				$season = floor( $episodes[ $i ][ 'episode_num' ] / 100 );
				$schedule[ $season ][] = &$episodes[ $i ];
			}
			krsort( $schedule );
				
			// list episodes by season
			foreach( $schedule as $season => $eps )
			{
				$i = 0;
				$n = count( $eps );
				$per_list = (int)floor( $n / $clear );
				$html .= "<li id=\"season_" . $season ."\" class=\"season" . ( !$selected ? ' sel' : '' ) . "\"><h2>Season " . $season . "</h2>\n<ul class=\"group\">\n";
				$season_mod = $season * 100;
				
				foreach( $eps as $e )
				{
					if ( !( $i++ % $per_list ) )
						$html .= "</ul>\n<ul class=\"group\">\n";
					
					$num = $e[ 'episode_num' ] - $season_mod;
					$html .= "<li><a href=\"" . $this->webpage->site->anchor( SHOW_EPISODES, ( $e[ 'article_slug' ] ? array( 'slug' => str_replace( $this->handle . "-", "", $e[ 'article_slug' ] ) ) : array( 'id' => $e[ 'episode_id' ] ) ) ) . "\">" . $num . ". " . $e[ 'article_name' ] . "</a></li>\n";
				}
				
				$html .= "</ul>\n";
				$selected = true;
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * List Episodes By Date
		#----------------------------------------------------------------------------------------------------
		/*public function list_episodes_by_date( $clear = 0 )
		{
			// initialize variables
			$episodes = Database::select_published( Database::EPISODES . " as e LEFT JOIN " . Database::ARTICLES . " as a ON a.article_id = e.episode_article", "*", "e.episode_id IN(" . implode( ",", $this->episodes ) . ") ORDER BY e.episode_airdate", "e.episode_" );
			$n = count( $episodes );
			$schedule = array();
			$selected = false;
			
			// get the schedule
			for( $i = 0; $i < $n; $i++ )
			{
				$year 	= date( "Y", $episodes[ $i ][ 'episode_airdate' ] );
				$month 	= date( "F", $episodes[ $i ][ 'episode_airdate' ] );
				$schedule[ $year ][ $month ][] = &$episodes[ $i ];
			}
			ksort( $schedule );
			$schedule = array_reverse( $schedule, true );
			
			//Reverse to show most recent months first
			$schedule[ $year ] = array_reverse( $schedule[ $year ] );
			
			// list episodes by date
			foreach( $schedule as $year => $month )
			{
				$i = 0;
				$html .= "<li id=\"year_" . $year ."\" class=\"year" . ( !$selected ? ' sel' : '' ) . "\"><h2>" . $year . "</h2>\n<ul class=\"months\">\n";
				foreach( $month as $m => $eps )
				{
					$j = 1;
					$html .= "<li class=\"month group" . ( $clear && !( $i++ % $clear ) ? ' row' : '' ) . "\"><h3>" . $m . "</h3>\n<ul class=\"episodes\">\n";
					foreach( $eps as $e )
						$html .= "<li class=\"episode\"><a href=\"" . $this->webpage->site->anchor( SHOW_EPISODES, ( $e[ 'article_slug' ] ? array( 'slug' => str_replace( $this->handle . "-", "", $e[ 'article_slug' ] ) ) : array( 'id' => $e[ 'episode_id' ] ) ) ) . "\">" . $j++ . ". " . $e[ 'article_name' ] . "</a>";
					$html .= "</ul>\n</li>\n";
				}
				$html .= "</ul>\n</li>\n";
				$selected = true;
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Latest Episode
		#----------------------------------------------------------------------------------------------------
		/*public function list_episodes_lite( array $param ) 
		{
			// initialize
			list( $limit, $reverse ) = $param;
			$html = "";
			$episodes = $reverse ? array_reverse( $this->episodes ) : $this->episodes;
			
			foreach( $episodes as $e ) 
			{
				if ( $limit && $i >= $limit )
					break;
				else 
				{
					$e = new Episode( $e );
					if ( $e->prime && $prime = new Segment( $e->prime->value ) ) 
					{
						if ( $thumb = $prime->get_primary_thumb() ) 
						{
							$show_video = $e->cove->value && $e->enabled_video;
							$output = new Template( $this->webpage, "episode.html" );
							
							$output->add_var( "TITLE",			$e->name->value ? ( "<h3>" . $e->name->value . "</h3>" ) : "" );
							$output->add_var( "DESCRIPTION", 	$e->content->value ? ( "<p>" . $e->content->value . "</p>" ) : "" );
							$output->add_var( "THUMB", 			( $show_video ? "<a href=\"http://video.wnit.org/video/" . $e->cove->value  . "/\" target=\"_blank\" >" : "" ) . "<img src=\"" . $thumb->url->value . "\" alt=\"" . $thumb->alt->value . "\">" . ( $show_video ? "</a>" : "" ) );
							
							$html .= "<li>\n" . $output->parse() . "</li>\n";
							$i++;
						}
					}

				}
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Lists the most recent episodes (up to $limit results)
		# ~ Travis was here
		#----------------------------------------------------------------------------------------------------
		/*public function list_episodes_recent( $limit = 5 ) 
		{
			// initialize variables
			$html 	= "";
			$limit 	= (int)$limit;
			$flip	= $this->episodes;
			$i		= 0;
			
			foreach( $flip as $e ) 
			{
				if ( $i >= $limit )
					break;
				else 
				{
					$e = new Episode( $e );
					if ( $e->prime && $prime = new Segment( $e->prime->value ) ) 
					{
						$thumb = $prime->get_primary_thumb();
						if ( $thumb && Generic::url_exists( $thumb->url->value ) ) 
						{
							$html .= "<li><a href=\"" . $this->webpage->site->anchor( SHOW_EPISODES, ( array( 'slug' => str_replace( $this->handle . "-", "", $e->slug ) ) ) )  . "\" ><img src=\"" . $thumb->url->value . "\" alt=\"" . $thumb->alt->value . "\"><br /><span>" . $e->name->value . "</span></a>" . ( $e->content->value ? "<p>" . $e->content->value . "</p>" : "" ) . "</li>";
							$i++;
						}
					}

				}
			}
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Lists the most recent episodes (up to $limit results) from COVE
		#----------------------------------------------------------------------------------------------------
		/*public function list_episodes_recent_cove( $program_id = 374302, $limit = 5 ) 
		{
			// initialize variables
			$html 	= "";
			$limit 	= (int)$limit;
			$i		= 0;
			$cove 	= new COVE_API;
			$recent = array();
			
			$request = COVE_API::ENDPOINT . "?filter_program=" . $program_id . "&filter_availability_status=Available&filter_type=Episode&fields=associated_images&order_by=-airdate=&limit_stop=" . $limit;
			$videos = json_decode( $cove->make_request( $request ) );
			
			foreach( $videos->results as $video )
				$html .= "<li><a href=\"" . $video->episode_url . "\" ><img src=\"" . $video->associated_images[ 3 ]->url . "\" alt=\"Episode Thumbnail\"><br /><span>" . $video->title . "</span></a></li>";
			
			return $html;
		}*/
		
		#----------------------------------------------------------------------------------------------------
		# * Lists the most recent resources (up to $limit results)
		#----------------------------------------------------------------------------------------------------
		/*public function list_resources( $limit = 3 ) 
		{
			// initialize variables
			$html 	= "";
			$res_n 	= 0;
			$lang	= array( 'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth' );
			
			$links 	= Database::select_published( Database::SEGMENTS . " as s, " . Database::S2SITES . " as x LEFT JOIN " . Database::SITES . " as w ON w.website_id = x.website_id", "*", "x.segment_id = s.segment_id AND s.segment_program = '" . $this->id . "' ORDER BY segment_episode DESC LIMIT {$limit}", "w.website_" );
			
			// show the links
			foreach( $links as $link )
			{
				$html .= "<li" . ( in_array( $res_n, array_keys( $lang ) ) ? ' class="' . $lang[ $res_n ] . '"' : "" ) . "><a href=\"" . $link[ 'website_url' ] . "\">" . ( $link[ 'website_name' ] ? $link[ 'website_name' ] : $link[ 'website_url' ] ) . "</a></li>\n";
				$res_n++;
			}
			
			return $html;
		}*/
	}
}
?>