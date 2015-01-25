<?php
namespace Core;

if ( !defined( "D_CLASS_WEBHEADER" ) )
{
	define( "D_CLASS_WEBHEADER", true );
	
	/**
 	 * File: class.webheader.php
	 *
	 * @todo Only one variable here. Should it be moved to the Webpage or should others move here?
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class WebHeader extends Template
	{
		public $css = array();
		public $js	= array();

		/**
		 * Class constructor.
		 *
		 * @param WebPage the current webpage.
		 * @uses WebPage::$naked
		 * @uses Template::__construct
		 */
		public function __construct( WebPage &$webpage )
		{	
			if ( !$webpage->naked )
			{
				parent::__construct( $webpage, "header.html" );
			}
		}
		
		/**
		 * Parses the template file and caches results.
		 *
		 * @param bool eval() or not? (Default = true)
		 * @uses Template::$webpage
		 * @uses Template::add_var
		 * @uses Template::parse
		 * @uses WebPage::$body_class
		 * @return string usually HTML from the file; nothing if eval()'d
		 */
		public function parse( $eval = true )
		{
			if ( $this->webpage )
			{
				// put together body class
				$body_class = $this->webpage->body_class ? ' class="' . implode( " ", $this->webpage->body_class ) . '"' : "";
				$this->add_var( "V_BODY_CLASS", $body_class );
				
				return parent::parse( $eval );
			}
			else return "";
		}
	}
}
?>