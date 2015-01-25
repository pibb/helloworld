<?php
namespace Core;

if ( !defined( "D_CLASS_MENU" ) )
{
	define( "D_CLASS_MENU", true );
	
	/**
 	 * File: class.menu.php
	 *
	 * @todo Not sure if this will be even used anymore because of the expanded functionality of Template.
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class MenuLink extends Base
	{
		public $id			= 0;
		public $title 		= "";
		public $subtitle 	= "";
		public $webpage		= NULL;
		public $menu		= NULL;
		public $parent_link	= NULL;
		public $query		= array();
		public $hide		= false;
		
		/**
		 * Class constructor.
		 *
		 * @param WebPage the current webpage.
		 * @param string the current page id.
		 * @param string the title of that link.
		 * @param Array any GET parameters. (Default = array())
		 * @param Menu the menu this link is apart of. (Default = NULL)
		 * @param string A subtitle for the page. (Default = "")
		 * @uses MenuLink::$id
		 * @uses MenuLink::$webpage
		 * @uses MenuLink::$title
		 * @uses MenuLink::$subtitle
		 * @uses MenuLink::$menu
		 * @uses MenuLink::$query
		 */
		public function __construct( WebPage &$webpage, $id, $title, $query = array(), Menu $menu = NULL, $subtitle = "" )
		{
			$this->id 		= $id;
			$this->webpage	= $webpage;
			$this->title 	= $title;
			$this->subtitle = $subtitle;
			$this->menu 	= $menu;
			$this->query	= $query;
		}
		
		/**
		 * String conversion that calls $this->parse(). Automatically called.
		 *
		 * @uses MenuLink::parse
		 */
		public function __toString()
		{
			return $this->parse();
		}
		
		/**
		 * Turns the object into an anchor tag.
		 *
		 * @uses MenuLink::$id
		 * @uses MenuLink::$webpage
		 * @uses MenuLink::$query
		 * @uses MenuLink::$title
		 * @uses MenuLink::$subtitle
		 * @uses Website::anchor
		 * @return string HTML
		 */
		public function parse()
		{
			$html = "<a href=\"" . ( $this->id != "#" ? $this->webpage->site->anchor( $this->id, $this->query ) : $this->id ) . '"';
			$html .= '>' . $this->title . ( $this->subtitle ? "<br /><span>" . $this->subtitle . "</span>" : "" ) . "</a>";
			
			return $html;
		}
	}
	
	/**
 	 * File: class.menu.php
	 *
	 * @todo Not sure if this will be even used anymore because of the expanded functionality of Template.
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Menu extends Base
	{
		public $title	= "";
		public $links	= array();
		public $webpage	= NULL;
		public $hide	= false;
	
		/**
		 * Class constructor.
		 *
		 * @param WebPage the current webpage.
		 * @param string the title of the menu. (Default = "")
		 * @param bool whether or not to hide the submenu. (Default = false)
		 * @uses Menu::$title
		 * @uses Menu::$webpage
		 * @uses Menu::$hide
		 */
		public function __construct( WebPage &$webpage, $title = "", $hide = false )
		{
			$this->title 	= $title;
			$this->webpage 	= $webpage;
			$this->hide		= $hide;
		}
		
		/**
		 * String conversion that calls $this->parse(). Automatically called.
		 *
		 * @uses Menu::parse
		 */
		public function __toString()
		{
			return $this->parse();
		}
		
		/**
		 * Add a page to the menu.
		 *
		 * @param string the page id.
		 * @param Array the GET parameters. (Default = array())
		 * @param string the title of the page. (Default = false)
		 * @param Menu the submenu it may be apart of. (Default = NULL)
		 * @param string the subtitle of the link. (Default = "")
		 * @uses Menu::$hide
		 * @uses Menu::$links
		 * @uses Menu::$webpage
		 * @uses MenuLink::$parent_link
		 * @uses WebPage::get_title
		 */
		public function add( $id, $query = array(), $title = false, Menu $menu = NULL, $subtitle = "" )
		{
			// initialize variables
			$i = count( $this->links );
			$n = count( $menu );
			$mode = isset( $query[ 'mode' ] ) ? $query[ 'mode' ] : "";
			if ( $title === false ) $title = $this->webpage->get_title( $id, $mode );
			$this->links[ $i ] = new MenuLink( $this->webpage, $id, $title, $query, $menu, $subtitle, $this->hide );
			
			if ( $menu )
				for ( $j = 0; $j < $n; $j++ )
					$this->links[ $i ]->menu->links[ $j ]->parent_link = array( 'id' => $id, 'query' => $query );
		}
		
		/**
		 * Translates this object into HTML.
		 *
		 * @uses Generic::clean
		 * @uses Menu::$hide
		 * @uses Menu::$links
		 * @uses Menu::$title
		 * @uses Menu::matching_page_ids
		 * @uses MenuLink::$menu
		 * @return string HTML
		 */
		public function parse()
		{
			$html = "";
			
			if ( $this->links )
			{
				$html .= "\t<h3>" . $this->title . "</h3>\n\t<ul id=\"submenu-" . strtolower( Generic::clean( $this->title ) ) . "\">\n";
				
				foreach( $this->links as $link )
				{
					$matching_page_ids = $this->matching_page_ids( $link );
					
					// put together classes
					$classes = array();
					if ( $matching_page_ids )
						$classes[] = "sel";
					if ( $link->menu )
						$classes[] = "sub";
					
					// begin link
					$html .= "\t\t<li" . ( $classes ? ' class="' . implode( ' ', $classes ) . '"' : '' ) . ">" . $link;
					
					// check for nested submenu
					if ( $link->menu && ( !$this->hide || $matching_page_ids ) )
					{
						$html .= "<ul id=\"submenu-" .  strtolower( Generic::clean( $link->title ) ) . "\">\n";
						foreach( $link->menu->links as $sublink )
						{
							$html .= "<li" . ( $this->matching_page_ids( $sublink ) ? ' class="sel"' : '' ) . ">" . $sublink . "</li>\n";
						}
						$html .= "</ul>\n";
					}
					
					$html .= "</li>\n";
				}
				$html .= "\t</ul>\n";
			}
			
			return $html;
		}
		
		/**
		 * Searches the given link and its menu, if it has one, for the current page id.
		 *
		 * @param MenuLink
		 * @uses Menu::$links
		 * @uses Menu::matching_page_query
		 * @uses MenuLink::$id
		 * @uses MenuLink::$query
		 * @uses MenuLink::$menu
		 * @uses WebPageLite::$page_id
		 * @return bool whether the given link refers to the current page.
		 */
		protected function matching_page_ids( MenuLink $p )
		{
			$match = true;
			
			if ( $p->id == $this->webpage->page_id )
				$match = $this->matching_page_query( $p->query );
			else
			{
				$match = false;
				
				// search direct descendants for a possible match
				if ( $p->menu )
					foreach( $p->menu->links as $link )
						if ( $link->id == $this->webpage->page_id )
							if ( $this->matching_page_query( $link->query ) )
								$match = true;
			}
			
			return $match;
		}
		
		/**
		 * Searches the given query for current GET parameters.
		 *
		 * @uses Globals::get
		 * @return bool whether all of the query variables are set in the current page.
		 */
		protected function matching_page_query( array $query )
		{
			$match = true;
			
			foreach( $query as $index => $val )
				if ( $val != Globals::get( $index ) )
					$match = false;
						
			return $match;
		}
	}
}
?>