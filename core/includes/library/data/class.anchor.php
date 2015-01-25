<?php
#============================================================================================================
# ** Anchor Class
#
# Version:   1.0
# Author:    Michael Shelton
#============================================================================================================
namespace Core;
if ( !defined( "D_CLASS_ANCHOR" ) )
{
	define( "D_CLASS_ANCHOR", true );
	
	require_once( __DIR__ . "/class.data.php" );
	
	class Anchor extends Data
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		protected $name 	= "";
		protected $url 		= "";
		protected $publish 	= false;
		
		const TABLE			= Database::SITES;
		const PREFIX		= Database::SITES_PRE;
		#----------------------------------------------------------------------------------------------------
		# * Convert To String
		#----------------------------------------------------------------------------------------------------
		public function __toString()
		{
			return "<span class=\"website\">Website: <a href=\"" . $this->url . "\" target=\"_blank\">" . ( $this->name ? $this->name : $this->url ) . "</a></span>";
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
		protected function setup( $data = Array())
		{
			$data = parent::setup( $data );
				
				
			$this->def_col( 'name', $data );
			$this->def_col( 'url', $data );
			$this->def_col( 'publish', $data );
			
			return $data;
		}
		

	}
}
?>