<?php
namespace Core;

if ( !defined( "D_CLASS_COLIMAGE" ) )
{
	define( "D_CLASS_COLIMAGE", true );
	require_once( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_img.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class ImageColumn extends FileColumn
	{
		public $dimensions = "";
	}
}