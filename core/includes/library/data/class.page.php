<?php
namespace Core;

if ( !defined( "D_CLASS_PAGE" ) )
{
	define( "D_CLASS_PAGE", true );
	require( __DIR__ . "/class.data.php" );
	
	/**
 	 * File: class.page.php
	 *
 	 * @package Library/Data
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Page extends Data
	{
		protected $keywords = NULL;
		protected $category = NULL;
		protected $name 	= "";
		protected $href 	= "";
		protected $content 	= "";
		protected $thumb 	= "";
		protected $auto_id	= false;
		
		const TABLE			= Database::SEARCH;
		const PREFIX		= Database::SEARCH_PRE;
		
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
		 * @uses Page::$name
		 * @uses Page::$keywords
		 * @uses Page::$category
		 * @uses Page::$href
		 * @uses Page::$thumb
		 * @uses Page::$content
		 * @uses Page::set_name
		 * @uses Page::set_keywords
		 * @uses Page::set_category
		 * @uses Base::$webpage
		 * @uses Data::add_col
		 * @uses Data::setup
		 * @uses Data::$prefix
		 * @uses Data::$not_recorded
		 * @uses WebPage::anchor
		 * @uses Website::$titles
		 * @return Array
		 */
		protected function setup( $data = array() )
		{
			$this->not_recorded[]  = 'name';
			$this->not_recorded[]  = 'href';
			$this->not_recorded[]  = 'content';
			$this->not_recorded[]  = 'thumb';
			$data = parent::setup( $data );
			
			// create columns
			$this->keywords = new Column( self::TABLE, self::PREFIX, "keywords" );
			$this->category = new Column( self::TABLE, self::PREFIX, "category" );
			
			// add columns
			$this->add_col( 'category', true, true );
			$this->add_col( 'keywords', true, true );
			
			if ( $data )
			{
				$this->set_category( $data[ $this->prefix . 'category' ] );
				$this->set_keywords( $data[ $this->prefix . 'keywords' ] );
				
				if ( $this->webpage )
				{
					$id = $data[ $this->prefix . 'id' ];
					$isset = isset( $this->webpage->site->titles[ $id ] );
					$has_title = $isset && isset( $this->webpage->site->titles[ $id ][ 0 ] );
					$has_desc = $isset && isset( $this->webpage->site->titles[ $id ][ 'description' ] );
					$has_thumb = $isset && isset( $this->webpage->site->titles[ $id ][ 'thumb' ] );
					$this->href = $this->webpage->anchor( $id );
					$this->name = $has_title ? $this->webpage->site->titles[ $id ][ 0 ] : "Untitled Page";
					$this->content = $has_desc ? $this->webpage->site->titles[ $id ][ 'description' ] : "No description.";
					$this->thumb = $has_thumb ? $this->webpage->site->titles[ $id ][ 'thumb' ] : "";
				}
			}
			
			return $data;
		}
		
		/**
		 * Sets the value of Page::category. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Page::$category
		 * @param string
		 * @return string
		 */
		protected function set_category( $a )	
		{ 
			return $this->category->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Page::keywords. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Page::$keywords
		 * @param string
		 * @return string
		 */
		protected function set_keywords( $a )	
		{ 
			return $this->keywords->value = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Page::href. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Page::$href
		 * @param string
		 * @return string
		 */
		protected function set_href( $a )		
		{ 
			return $this->href = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Page::content. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Page::$content
		 * @param string
		 * @return string
		 */
		protected function set_content( $a )	
		{ 
			return $this->content = trim( stripslashes( $a ) ); 
		}
		
		/**
		 * Sets the value of Page::thumb. Called automatically by __set.
		 *
		 * @uses Base::__set by being called.
		 * @uses Page::$thumb
		 * @param string
		 * @return string
		 */
		protected function set_thumb( $a )		
		{ 
			return $this->thumb = trim( stripslashes( $a ) ); 
		}
	}
}
?>