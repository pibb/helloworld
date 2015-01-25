<?php
	class Menu
	{
		static private $menu_items = Array( 
			"Home" => "index.php",
			"Services" => Array( 
				"href" => "services.php",
				"items" => Array( 
					"Hashing" => "hashing.php",
					"DNS" => "dns.php",
					"Twitch" => "twitch.php",
				)
			),
			"Information" => "information.php",
		);
		
		static private function _parse_menu( $key, $data, $tabs ='' ) {
			$html = "";
			
			if ( is_array( $data ) ) {
				$html .= "$tabs<li>\r\n";
				$href = $data['href'];
				$html .= "$tabs\t<a href=\"$href\">$key</a>\r\n";			
				foreach( $data[ 'items' ] as $subkey => $subdata ) {
					$html .= "$tabs\t<ul>\r\n";
					$html .= self::_parse_menu( $subkey, $subdata, "$tabs\t\t" );
					$html .= "$tabs\t</ul>\r\n";
				}
				$html .= "$tabs</li>\r\n";
			} else {
				$html .= "$tabs<li>\r\n";
				$html .= "$tabs\t<a href=\"$data\">$key</a>\r\n";
				$html .= "$tabs</li>\r\n";
			}
			
			return $html;
		}
		
		public function parse() {
			$html = '';
			
			$html .= "\t<ul class=\"menu\">\r\n";
			foreach( self::$menu_items as $item  => $url ) {
				$html .= self::_parse_menu( $item, $url, "\t\t\t\t" );
			}
			$html .= "\t\t\t</ul>\r\n";
			
			return $html;
		}
	}