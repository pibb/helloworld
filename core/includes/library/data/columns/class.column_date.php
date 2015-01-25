<?php
namespace Core;

if ( !defined( "D_CLASS_COLDATE" ) )
{
	define( "D_CLASS_COLDATE", true );
	require_once( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_date.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class DateColumn extends Column
	{
		public $format = "m/d/Y";
		
		/**
		 * Turns the POST'd value into a timestamp.
		 *
		 * @uses Globals::post
		 * @uses Column::$name
		 * @return int
		 */
		public function clean_post()
		{
			return strtotime( Globals::post( $this->name ) );
		}
	}
}
?>