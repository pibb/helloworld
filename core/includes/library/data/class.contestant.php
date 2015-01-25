<?php
#============================================================================================================
# ** Contestant Class
#============================================================================================================
namespace Core;
{
	if ( !defined( "D_CLASS_CONTESTANT" ) )
	{
		define( "D_CLASS_CONTESTANT", true );
		require_once( __DIR__ . "/class.data.php" );
		
		class Contestant extends Data
		{
			#----------------------------------------------------------------------------------------------------
			# * Properties
			#----------------------------------------------------------------------------------------------------
			protected $competition		= NULL;
			protected $entry			= NULL;
			protected $textcode	 		= NULL;
			protected $mgive 			= NULL;
			protected $scores			= array();
			/*protected $place			= NULL;
			protected $score1_judge1	= NULL;
			protected $score2_judge1	= NULL;
			protected $score3_judge1	= NULL;
			protected $score4_judge1	= NULL;
			protected $score5_judge1	= NULL;
			protected $score6_judge1	= NULL;
			protected $score1_judge2	= NULL;
			protected $score2_judge2	= NULL;
			protected $score3_judge2	= NULL;
			protected $score4_judge2	= NULL;
			protected $score5_judge2	= NULL;
			protected $score6_judge2	= NULL;
			protected $score1_judge3	= NULL;
			protected $score2_judge3	= NULL;
			protected $score3_judge3	= NULL;
			protected $score4_judge3	= NULL;
			protected $score5_judge3	= NULL;
			protected $score6_judge3	= NULL;
			protected $score1_judge4	= NULL;
			protected $score2_judge4	= NULL;
			protected $score3_judge4	= NULL;
			protected $score4_judge4	= NULL;
			protected $score5_judge4	= NULL;
			protected $score6_judge4	= NULL;
			protected $score1_voters	= NULL;
			protected $score2_voters	= NULL;
			protected $score3_voters	= NULL;
			protected $score4_voters	= NULL;
			protected $score5_voters	= NULL;
			protected $score6_voters	= NULL;
			protected $round2			= NULL;
			protected $round3			= NULL;
			protected $round4			= NULL;
			protected $round5			= NULL;
			protected $round6			= NULL;
			protected $video_round1		= NULL;
			protected $video_round2		= NULL;
			protected $video_round3		= NULL;
			protected $video_round4		= NULL;
			protected $video_round5		= NULL;
			protected $video_round6		= NULL;
			protected $rounds			= array();*/
			
			const TABLE		= Database::CONTESTANTS;
			const PREFIX	= Database::CONTESTANTS_PRE;
			const QUERY		= "ORDER BY c.contestant_textcode DESC";
			const PUBQUERY	= "AND c.contestant_enabled != '0' AND c.contestant_deleted = '0' AND c.contestant_status = '1' AND e.entry_enabled != '0' AND e.entry_deleted = '0' AND e.entry_status = '1' GROUP BY c.contestant_id ORDER BY c.contestant_textcode ASC";
			#----------------------------------------------------------------------------------------------------
			# * Get Published
			#----------------------------------------------------------------------------------------------------
			static public function get_published( $amend = "%s", WebPage $webpage = NULL, $flag_first = true )
			{
				// initialize variables
				$rows = Database::select_query( "SELECT * FROM " . Database::CONTESTANTS . " as c LEFT JOIN " . Database::ENTRIES . " as e ON c.contestant_entry = e.entry_id WHERE " . str_replace( "#", "%", sprintf( $amend, self::PUBQUERY ) ) );
				$data = self::getx_data( __CLASS__, $rows, $flag_first, "id", $webpage );
				
				return $data;
			}
			
			#----------------------------------------------------------------------------------------------------
			# * Get Published Array
			#----------------------------------------------------------------------------------------------------
			static public function get_published_array( $amend = "%s", WebPage $webpage = NULL, $flag_first = true )
			{
				// initialize variables
				$rows = Database::select_query( "SELECT * FROM " . Database::CONTESTANTS . " as c LEFT JOIN " . Database::ENTRIES . " as e ON c.contestant_entry = e.entry_id WHERE " . str_replace( "#", "%", sprintf( $amend, self::PUBQUERY ) ) );
				$data = self::getx_array_data( $rows, $flag_first, self::PREFIX );
				
				return $data;
			}
			
			#----------------------------------------------------------------------------------------------------
			# * Get
			#----------------------------------------------------------------------------------------------------
			static public function get( $amend = "%s", WebPage $webpage = NULL, $flag_first = true )
			{
				// initialize variables
				$rows = Database::select_query( "SELECT * FROM " . Database::CONTESTANTS . " as c LEFT JOIN " . Database::ENTRIES . " as e ON c.contestant_entry = e.entry_id WHERE " . str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
				return self::getx_data( __CLASS__, $rows, $flag_first, "id", $webpage );
			}
			
			#----------------------------------------------------------------------------------------------------
			# * Get Array
			#----------------------------------------------------------------------------------------------------
			static public function get_array( $amend = "%s", WebPage $webpage = NULL, $flag_first = true )
			{
				// initialize variables
				$rows = Database::select_query( "SELECT * FROM " . Database::CONTESTANTS . " as c LEFT JOIN " . Database::ENTRIES . " as e ON c.contestant_entry = e.entry_id WHERE " . str_replace( "#", "%", sprintf( $amend, self::QUERY ) ) );
				return self::getx_array_data( $rows, $flag_first, self::PREFIX );
			}
			
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
				$data = parent::setup( $data );
				
				// extra properties
				$this->not_recorded 	= array_merge( $this->not_recorded, array( 'scores' ) );
				 
				// create columns
				$this->competition		= new Many2OneColumn( self::TABLE, self::PREFIX, "competition", Database::COMPETITIONS, Database::COMPETITIONS_PRE, "name" );
				$this->entry			= new Many2OneColumn( self::TABLE, self::PREFIX, "entry", Database::ENTRIES, Database::ENTRIES_PRE, "name" );
				$this->textcode 		= new Column( self::TABLE, self::PREFIX, "textcode" );
				$this->mgive 			= new Column( self::TABLE, self::PREFIX, "mgive" );
				/*$this->place 			= new Column( self::TABLE, self::PREFIX, "place" );
				$this->score1_judge1 	= new Column( self::TABLE, self::PREFIX, "score1_judge1" );
				$this->score2_judge1 	= new Column( self::TABLE, self::PREFIX, "score2_judge1" );
				$this->score3_judge1 	= new Column( self::TABLE, self::PREFIX, "score3_judge1" );
				$this->score4_judge1 	= new Column( self::TABLE, self::PREFIX, "score4_judge1" );
				$this->score5_judge1 	= new Column( self::TABLE, self::PREFIX, "score5_judge1" );
				$this->score6_judge1 	= new Column( self::TABLE, self::PREFIX, "score6_judge1" );
				$this->score1_judge2 	= new Column( self::TABLE, self::PREFIX, "score1_judge2" );
				$this->score2_judge2 	= new Column( self::TABLE, self::PREFIX, "score2_judge2" );
				$this->score3_judge2 	= new Column( self::TABLE, self::PREFIX, "score3_judge2" );
				$this->score4_judge2 	= new Column( self::TABLE, self::PREFIX, "score4_judge2" );
				$this->score5_judge2 	= new Column( self::TABLE, self::PREFIX, "score5_judge2" );
				$this->score6_judge2 	= new Column( self::TABLE, self::PREFIX, "score6_judge2" );
				$this->score1_judge3 	= new Column( self::TABLE, self::PREFIX, "score1_judge3" );
				$this->score2_judge3 	= new Column( self::TABLE, self::PREFIX, "score2_judge3" );
				$this->score3_judge3 	= new Column( self::TABLE, self::PREFIX, "score3_judge3" );
				$this->score4_judge3 	= new Column( self::TABLE, self::PREFIX, "score4_judge3" );
				$this->score5_judge3 	= new Column( self::TABLE, self::PREFIX, "score5_judge3" );
				$this->score6_judge3 	= new Column( self::TABLE, self::PREFIX, "score6_judge3" );
				$this->score1_judge4 	= new Column( self::TABLE, self::PREFIX, "score1_judge4" );
				$this->score2_judge4 	= new Column( self::TABLE, self::PREFIX, "score2_judge4" );
				$this->score3_judge4 	= new Column( self::TABLE, self::PREFIX, "score3_judge4" );
				$this->score4_judge4 	= new Column( self::TABLE, self::PREFIX, "score4_judge4" );
				$this->score5_judge4 	= new Column( self::TABLE, self::PREFIX, "score5_judge4" );
				$this->score6_judge4 	= new Column( self::TABLE, self::PREFIX, "score6_judge4" );
				$this->score1_voters 	= new Column( self::TABLE, self::PREFIX, "score1_voters" );
				$this->score2_voters 	= new Column( self::TABLE, self::PREFIX, "score2_voters" );
				$this->score3_voters 	= new Column( self::TABLE, self::PREFIX, "score3_voters" );
				$this->score4_voters 	= new Column( self::TABLE, self::PREFIX, "score4_voters" );
				$this->score5_voters 	= new Column( self::TABLE, self::PREFIX, "score5_voters" );
				$this->score6_voters 	= new Column( self::TABLE, self::PREFIX, "score6_voters" );
				$this->round2 			= new MultiColumn( self::TABLE, self::PREFIX, "round2" );
				$this->round3 			= new MultiColumn( self::TABLE, self::PREFIX, "round3" );
				$this->round4 			= new MultiColumn( self::TABLE, self::PREFIX, "round4" );
				$this->round5 			= new MultiColumn( self::TABLE, self::PREFIX, "round5" );
				$this->round6 			= new MultiColumn( self::TABLE, self::PREFIX, "round6" );
				$this->video_round1 	= new Column( self::TABLE, self::PREFIX, "video_round1" );
				$this->video_round2 	= new Column( self::TABLE, self::PREFIX, "video_round2" );
				$this->video_round3 	= new Column( self::TABLE, self::PREFIX, "video_round3" );
				$this->video_round4 	= new Column( self::TABLE, self::PREFIX, "video_round4" );
				$this->video_round5 	= new Column( self::TABLE, self::PREFIX, "video_round5" );
				$this->video_round6 	= new Column( self::TABLE, self::PREFIX, "video_round6" );*/
				 
				// make adjustments
				//$this->round1->options 	= array( 0 => "No", 1 => "Yes" );  
				//$this->round2->options 	= array( 0 => "No", 1 => "Yes" );  
				//$this->round3->options 	= array( 0 => "No", 1 => "Yes" );  
				//$this->round4->options 	= array( 0 => "No", 1 => "Yes" );  
				//$this->round5->options 	= array( 0 => "No", 1 => "Yes" );  
				//$this->round6->options 	= array( 0 => "No", 1 => "Yes" );  
				
				// add columns
				$this->add_col( 'competition', true, array( Database::COMPETITIONS, Database::COMPETITIONS_PRE, "name" ) );
				$this->add_col( 'entry', true, array( Database::ENTRIES, Database::ENTRIES_PRE, "name" ) );
				$this->add_col( 'textcode', true, true );
				$this->add_col( 'mgive', true );
				/*$this->add_col( 'place' );
				$this->add_col( 'score1_judge1' );
				$this->add_col( 'score2_judge1' );
				$this->add_col( 'score3_judge1' );
				$this->add_col( 'score4_judge1' );
				$this->add_col( 'score5_judge1' );
				$this->add_col( 'score6_judge1' );
				$this->add_col( 'score1_judge2' );
				$this->add_col( 'score2_judge2' );
				$this->add_col( 'score3_judge2' );
				$this->add_col( 'score4_judge2' );
				$this->add_col( 'score5_judge2' );
				$this->add_col( 'score6_judge2' );
				$this->add_col( 'score1_judge3' );
				$this->add_col( 'score2_judge3' );
				$this->add_col( 'score3_judge3' );
				$this->add_col( 'score4_judge3' );
				$this->add_col( 'score5_judge3' );
				$this->add_col( 'score6_judge3' );
				$this->add_col( 'score1_judge4' );
				$this->add_col( 'score2_judge4' );
				$this->add_col( 'score3_judge4' );
				$this->add_col( 'score4_judge4' );
				$this->add_col( 'score5_judge4' );
				$this->add_col( 'score6_judge4' );
				$this->add_col( 'score1_voters' );
				$this->add_col( 'score2_voters' );
				$this->add_col( 'score3_voters' );
				$this->add_col( 'score4_voters' );
				$this->add_col( 'score5_voters' );
				$this->add_col( 'score6_voters' );
				$this->add_col( 'round2' );
				$this->add_col( 'round3' );
				$this->add_col( 'round4' );
				$this->add_col( 'round5' );
				$this->add_col( 'round6' );
				$this->add_col( 'video_round1' );
				$this->add_col( 'video_round2' );
				$this->add_col( 'video_round3' );
				$this->add_col( 'video_round4' );
				$this->add_col( 'video_round5' );
				$this->add_col( 'video_round6' );*/
				
				if ( $data )
				{
					$this->set_competition( $data[ 'contestant_competition' ] );
					$this->set_entry( $data[ 'contestant_entry' ] );
					$this->set_textcode( $data[ 'contestant_textcode' ] );
					$this->set_mgive( $data[ 'contestant_mgive' ] );
					/*$this->set_place( $data[ 'contestant_place' ] );
					$this->set_score1_judge1( $data[ 'contestant_score1_judge1' ] );
					$this->set_score2_judge1( $data[ 'contestant_score2_judge1' ] );
					$this->set_score3_judge1( $data[ 'contestant_score3_judge1' ] );
					$this->set_score4_judge1( $data[ 'contestant_score4_judge1' ] );
					$this->set_score5_judge1( $data[ 'contestant_score5_judge1' ] );
					$this->set_score6_judge1( $data[ 'contestant_score6_judge1' ] );
					$this->set_score1_judge2( $data[ 'contestant_score1_judge2' ] );
					$this->set_score2_judge2( $data[ 'contestant_score2_judge2' ] );
					$this->set_score3_judge2( $data[ 'contestant_score3_judge2' ] );
					$this->set_score4_judge2( $data[ 'contestant_score4_judge2' ] );
					$this->set_score5_judge2( $data[ 'contestant_score5_judge2' ] );
					$this->set_score6_judge2( $data[ 'contestant_score6_judge2' ] );
					$this->set_score1_judge3( $data[ 'contestant_score1_judge3' ] );
					$this->set_score2_judge3( $data[ 'contestant_score2_judge3' ] );
					$this->set_score3_judge3( $data[ 'contestant_score3_judge3' ] );
					$this->set_score4_judge3( $data[ 'contestant_score4_judge3' ] );
					$this->set_score5_judge3( $data[ 'contestant_score5_judge3' ] );
					$this->set_score6_judge3( $data[ 'contestant_score6_judge3' ] );
					$this->set_score1_judge4( $data[ 'contestant_score1_judge4' ] );
					$this->set_score2_judge4( $data[ 'contestant_score2_judge4' ] );
					$this->set_score3_judge4( $data[ 'contestant_score3_judge4' ] );
					$this->set_score4_judge4( $data[ 'contestant_score4_judge4' ] );
					$this->set_score5_judge4( $data[ 'contestant_score5_judge4' ] );
					$this->set_score6_judge4( $data[ 'contestant_score6_judge4' ] );
					$this->set_score1_voters( $data[ 'contestant_score1_voters' ] );
					$this->set_score2_voters( $data[ 'contestant_score2_voters' ] );
					$this->set_score3_voters( $data[ 'contestant_score3_voters' ] );
					$this->set_score4_voters( $data[ 'contestant_score4_voters' ] );
					$this->set_score5_voters( $data[ 'contestant_score5_voters' ] );
					$this->set_score6_voters( $data[ 'contestant_score6_voters' ] );
					$this->set_round2( $data[ 'contestant_round2' ] );
					$this->set_round3( $data[ 'contestant_round3' ] );
					$this->set_round4( $data[ 'contestant_round4' ] );
					$this->set_round5( $data[ 'contestant_round5' ] );
					$this->set_round6( $data[ 'contestant_round6' ] );
					$this->set_video_round1( $data[ 'contestant_video_round1' ] );
					$this->set_video_round2( $data[ 'contestant_video_round2' ] );
					$this->set_video_round3( $data[ 'contestant_video_round3' ] );
					$this->set_video_round4( $data[ 'contestant_video_round4' ] );
					$this->set_video_round5( $data[ 'contestant_video_round5' ] );
					$this->set_video_round6( $data[ 'contestant_video_round6' ] );
					$this->set_round( 1, $data[ 'contestant_video_round1' ], $data[ 'contestant_round1' ], $data[ 'contestant_score1_judge1' ], $data[ 'contestant_score1_judge2' ], $data[ 'contestant_score1_judge3' ], $data[ 'contestant_score1_judge4' ], $data[ 'contestant_score1_voters' ] );
					$this->set_round( 2, $data[ 'contestant_video_round2' ], $data[ 'contestant_round2' ], $data[ 'contestant_score2_judge1' ], $data[ 'contestant_score2_judge2' ], $data[ 'contestant_score2_judge3' ], $data[ 'contestant_score2_judge4' ], $data[ 'contestant_score2_voters' ] );
					$this->set_round( 3, $data[ 'contestant_video_round3' ], $data[ 'contestant_round3' ], $data[ 'contestant_score3_judge1' ], $data[ 'contestant_score3_judge2' ], $data[ 'contestant_score3_judge3' ], $data[ 'contestant_score3_judge4' ], $data[ 'contestant_score3_voters' ] );
					$this->set_round( 4, $data[ 'contestant_video_round4' ], $data[ 'contestant_round4' ], $data[ 'contestant_score4_judge1' ], $data[ 'contestant_score4_judge2' ], $data[ 'contestant_score4_judge3' ], $data[ 'contestant_score4_judge4' ], $data[ 'contestant_score4_voters' ] );
					$this->set_round( 5, $data[ 'contestant_video_round5' ], $data[ 'contestant_round5' ], $data[ 'contestant_score5_judge1' ], $data[ 'contestant_score5_judge2' ], $data[ 'contestant_score5_judge3' ], $data[ 'contestant_score5_judge4' ], $data[ 'contestant_score5_voters' ] );
					$this->set_round( 6, $data[ 'contestant_video_round6' ], $data[ 'contestant_round6' ], $data[ 'contestant_score6_judge1' ], $data[ 'contestant_score6_judge2' ], $data[ 'contestant_score6_judge3' ], $data[ 'contestant_score6_judge4' ], $data[ 'contestant_score6_voters' ] );*/
					
					$this->scores = Database::select_query( "SELECT * FROM " . Database::SCORES . " WHERE contestant_id = '" . (int)$this->id . "'" );
				}
				 
				return $data;
			}
			
			#----------------------------------------------------------------------------------------------------
			# * Setters
			#----------------------------------------------------------------------------------------------------
			protected function set_competition( $a ) 		{ return $this->competition->value = (int)$a; }
			protected function set_entry( $a ) 				{ return $this->entry->value = (int)$a; }
			protected function set_textcode( $a )			{ return $this->textcode->value = trim( stripslashes( $a ) ); }
			protected function set_mgive( $a )				{ return $this->mgive->value = trim( stripslashes( $a ) ); }
			/*protected function set_place( $a )				{ return $this->place->value = trim( stripslashes( $a ) ); }
			protected function set_score1_judge1( $a )		{ return $this->score1_judge1->value = (float)$a; }
			protected function set_score2_judge1( $a )		{ return $this->score2_judge1->value = (float)$a; }
			protected function set_score3_judge1( $a )		{ return $this->score3_judge1->value = (float)$a; }
			protected function set_score4_judge1( $a )		{ return $this->score4_judge1->value = (float)$a; }
			protected function set_score5_judge1( $a )		{ return $this->score5_judge1->value = (float)$a; }
			protected function set_score6_judge1( $a )		{ return $this->score6_judge1->value = (float)$a; }
			protected function set_score1_judge2( $a )		{ return $this->score1_judge2->value = (float)$a; }
			protected function set_score2_judge2( $a )		{ return $this->score2_judge2->value = (float)$a; }
			protected function set_score3_judge2( $a )		{ return $this->score3_judge2->value = (float)$a; }
			protected function set_score4_judge2( $a )		{ return $this->score4_judge2->value = (float)$a; }
			protected function set_score5_judge2( $a )		{ return $this->score5_judge2->value = (float)$a; }
			protected function set_score6_judge2( $a )		{ return $this->score6_judge2->value = (float)$a; }
			protected function set_score1_judge3( $a )		{ return $this->score1_judge3->value = (float)$a; }
			protected function set_score2_judge3( $a )		{ return $this->score2_judge3->value = (float)$a; }
			protected function set_score3_judge3( $a )		{ return $this->score3_judge3->value = (float)$a; }
			protected function set_score4_judge3( $a )		{ return $this->score4_judge3->value = (float)$a; }
			protected function set_score5_judge3( $a )		{ return $this->score5_judge3->value = (float)$a; }
			protected function set_score6_judge3( $a )		{ return $this->score6_judge3->value = (float)$a; }
			protected function set_score1_judge4( $a )		{ return $this->score1_judge4->value = (float)$a; }
			protected function set_score2_judge4( $a )		{ return $this->score2_judge4->value = (float)$a; }
			protected function set_score3_judge4( $a )		{ return $this->score3_judge4->value = (float)$a; }
			protected function set_score4_judge4( $a )		{ return $this->score4_judge4->value = (float)$a; }
			protected function set_score5_judge4( $a )		{ return $this->score5_judge4->value = (float)$a; }
			protected function set_score6_judge4( $a )		{ return $this->score6_judge4->value = (float)$a; }
			protected function set_score1_voters( $a )		{ return $this->score1_voters->value = (float)$a; }
			protected function set_score2_voters( $a )		{ return $this->score2_voters->value = (float)$a; }
			protected function set_score3_voters( $a )		{ return $this->score3_voters->value = (float)$a; }
			protected function set_score4_voters( $a )		{ return $this->score4_voters->value = (float)$a; }
			protected function set_score5_voters( $a )		{ return $this->score5_voters->value = (float)$a; }
			protected function set_score6_voters( $a )		{ return $this->score6_voters->value = (float)$a; }
			protected function set_round2( $a ) 			{ return $this->round2->value = (bool)$a; }
			protected function set_round3( $a ) 			{ return $this->round3->value = (bool)$a; }
			protected function set_round4( $a ) 			{ return $this->round4->value = (bool)$a; }
			protected function set_round5( $a ) 			{ return $this->round5->value = (bool)$a; }
			protected function set_round6( $a ) 			{ return $this->round6->value = (bool)$a; }
			protected function set_video_round1( $a ) 		{ return $this->video_round1->value = trim( stripslashes( $a ) ); }
			protected function set_video_round2( $a ) 		{ return $this->video_round2->value = trim( stripslashes( $a ) ); }
			protected function set_video_round3( $a ) 		{ return $this->video_round3->value = trim( stripslashes( $a ) ); }
			protected function set_video_round4( $a ) 		{ return $this->video_round4->value = trim( stripslashes( $a ) ); }
			protected function set_video_round5( $a ) 		{ return $this->video_round5->value = trim( stripslashes( $a ) ); }
			protected function set_video_round6( $a ) 		{ return $this->video_round6->value = trim( stripslashes( $a ) ); }*/
			
			#----------------------------------------------------------------------------------------------------
			# * Set Round
			#----------------------------------------------------------------------------------------------------
			/*protected function set_round( $round, $video, $participated, $judge1, $judge2, $judge3, $judge4, $viewers )			
			{ 
				return $this->rounds[ $round ] = array( 'video' => trim( stripslashes( $video ) ),
														'participated' => (bool)$participated, 
														'judge1' => (float)$judge1,
														'judge2' => (float)$judge2,
														'judge3' => (float)$judge3,
														'judge4' => (float)$judge4,
														'viewers' => (float)$viewers );
			}
			
			#----------------------------------------------------------------------------------------------------
			# * Set Rounds
			#----------------------------------------------------------------------------------------------------
			protected function set_rounds( array $rounds )			
			{ 
				foreach( $rounds as $num => $v )
					$this->set_round( $num, $v[ 'video' ], $v[ 'participated' ], $v[ 'judge1' ], $v[ 'judge2' ], $v[ 'judge3' ], $v[ 'judge4' ], $v[ 'viewers' ] );
					
				return $this->rounds;
			}*/
		}
	}
}
?>