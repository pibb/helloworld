<?php
namespace Core;

if ( !defined( "D_CLASS_PPREVIEW" ) )
{
	define( "D_CLASS_PPREVIEW", true );
	require( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.competition.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class ProgramPreview extends Data
	{
		protected $name 	= "";
		protected $video 	= "";
		protected $pslug 	= "";
		protected $pcove 	= "";
		protected $ptvss 	= "";
		protected $cache 	= "";
		protected $end 		= "";
		protected $time 	= "";
		protected $length 	= 0;
		protected $filler 	= false;
		
		const TABLE			= Database::PREVIEWS;
		const PREFIX		= Database::PREVIEWS_PRE;
		
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
		 * @uses ProgramPreview::$name
		 * @uses ProgramPreview::$video
		 * @uses ProgramPreview::$pslug
		 * @uses ProgramPreview::$pcove
		 * @uses ProgramPreview::$ptvss
		 * @uses ProgramPreview::$filler
		 * @uses ProgramPreview::$length
		 * @uses ProgramPreview::$end
		 * @uses ProgramPreview::$time
		 * @uses ProgramPreview::set_name
		 * @uses ProgramPreview::set_video
		 * @uses ProgramPreview::set_pslug
		 * @uses ProgramPreview::set_pcove
		 * @uses ProgramPreview::set_ptvss
		 * @uses ProgramPreview::set_filler
		 * @uses ProgramPreview::set_length
		 * @uses ProgramPreview::set_end
		 * @uses ProgramPreview::set_time
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			// create columns
			$this->name 	= new Column( self::TABLE, self::PREFIX, "name" );
			$this->video 	= new Column( self::TABLE, self::PREFIX, "video" ); 
			$this->pslug 	= new Column( self::TABLE, self::PREFIX, "pslug" ); 
			$this->pcove 	= new Column( self::TABLE, self::PREFIX, "pcove" ); 
			$this->ptvss 	= new Column( self::TABLE, self::PREFIX, "ptvss" ); 
			$this->filler 	= new MultiColumn( self::TABLE, self::PREFIX, "filler" ); 
			$this->length 	= new IntColumn( self::TABLE, self::PREFIX, "length" ); 
			$this->end 		= new DateColumn( self::TABLE, self::PREFIX, "end" ); 
			$this->time 	= new Column( self::TABLE, self::PREFIX, "time" ); 
			
			// make adjustments
			$this->name->min = 3;
			$this->name->max = 255;
			$this->filler->options = array( 1 => "Yes", 0 => "No" );
			
			// add columns
			$this->add_col( 'name', true, true );
			$this->add_col( 'video', true );
			$this->add_col( 'pslug' );
			$this->add_col( 'pcove' );
			$this->add_col( 'ptvss' );
			$this->add_col( 'filler', true );
			$this->add_col( 'length', true );
			$this->add_col( 'end' );
			$this->add_col( 'time', true, true );
			
			if ( $data )
			{
				$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_video( $data[ $this->prefix . 'video' ] );
				$this->set_pslug( $data[ $this->prefix . 'pslug' ] );
				$this->set_pcove( $data[ $this->prefix . 'pcove' ] );
				$this->set_ptvss( $data[ $this->prefix . 'ptvss' ] );
				$this->set_cache( $data[ $this->prefix . 'cache' ] );
				$this->set_end( $data[ $this->prefix . 'end' ] );
				$this->set_time( $data[ $this->prefix . 'time' ] );
				$this->set_length( $data[ $this->prefix . 'length' ] );
				$this->set_filler( $data[ $this->prefix . 'filler' ] );
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of ProgramPreview::name. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$name
		 * @param string
		 * @return string
		 */
		protected function set_name( $a )	
		{ 
			return $this->name->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of ProgramPreview::video. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$video
		 * @param string
		 * @return string
		 */
		protected function set_video( $a )	
		{ 
			return $this->video->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of ProgramPreview::pslug. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$pslug
		 * @param string
		 * @return string
		 */
		protected function set_pslug( $a )	
		{ 
			return $this->pslug->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of ProgramPreview::pcove. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$pcove
		 * @param int
		 * @return int
		 */
		protected function set_pcove( $a )	
		{ 
			return $this->pcove->value = (int)$a; 
		}
		
		/**
		 * Sets the value of ProgramPreview::ptvss. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$ptvss
		 * @param int
		 * @return int
		 */
		protected function set_ptvss( $a )	
		{ 
			return $this->ptvss->value = (int)$a; 
		}
		
		/**
		 * Sets the value of ProgramPreview::cache. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$cache
		 * @param string
		 * @return string
		 */
		protected function set_cache( $a )	
		{ 
			return $this->cache = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of ProgramPreview::end. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$end
		 * @param string
		 * @return string
		 */
		protected function set_end( $a )	
		{ 
			return $this->end->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of ProgramPreview::time. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$time
		 * @param string
		 * @return string
		 */
		protected function set_time( $a )	
		{ 
			return $this->time->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of ProgramPreview::length. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$length
		 * @param int
		 * @return int
		 */
		protected function set_length( $a )	
		{ 
			return $this->length->value = (int)$a; 
		}
		
		/**
		 * Sets the value of ProgramPreview::filler. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses ProgramPreview::$filler
		 * @param bool
		 * @return bool
		 */
		protected function set_filler( $a )	
		{ 
			return $this->filler->value = (bool)$a; 
		}
	}
}
?>