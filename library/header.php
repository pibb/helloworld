<?php
	class Header
	{
		private $_webpage = null;
		public function __construct( Webpage &$webpage ) {
			$this->_webpage = $webpage;
		}
		
		public function parse(  ) {
			$html = file_get_contents( __DIR__ . "/../templates/header.html" );
			$html .= $this->_webpage->menuHTML();
			$html .= "Remote IP: " . $_SERVER[ 'REMOTE_ADDR' ];
			
			return $html;
		}
		
		
	}