<?php
#============================================================================================================
# ** ProgramCategory Class
#
# Version:   1.0
# Author:    Michael Shelton
#============================================================================================================
namespace Core;
if ( !defined( "D_CLASS_PROGRAMCATEGORY" ) )
{
	define( "D_CLASS_PROGRAMCATEGORY", true );
	require( __DIR__ . "/class.data.php" );
	
	class ProgramCategory extends Data
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		protected $name 	= NULL;
		protected $category	= NULL;
		
		const TABLE			= Database::PROGCATS;
		const PREFIX		= Database::PROGCATS_PRE;
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
			// create columns
			parent::setup( $data );
			$this->def_col( 'name', $data, "Column", true, true );
			$this->def_col( 'category', $data );
			
			return $data;
		}
		
	}
}
?>