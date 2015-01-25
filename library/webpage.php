<?php
	require_once( "menu.php" );
	require_once( "header.php" );
	class Webpage
	{
		static private $_webpage = null;
		
		private $_menu = null;
		private $_header = null;
		
		private function __construct() {
			//header("Content-Type: application/xhtml+xml;charset=UTF-8");
			$this->_header = new Header( $this );
			echo $this->_header->parse();
			
		}
		function __destruct() {
			include( __DIR__ . "/../templates/footer.html" );
		}
		
		public function menuHTML() {

			if ( !$this->_menu )
				$this->_menu = new Menu();
			
			return $this->_menu->parse();
		}
		
		static public function self() {
			if ( !self::$_webpage ) {
				self::$_webpage  = new Webpage;
			} 
			return self::$_webpage;
		}
		
	}