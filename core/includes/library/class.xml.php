<?php
#============================================================================================================
# ** XML Class
#============================================================================================================
namespace Core;
{

	class XML
	{
		#--------------------------------------------------------------------------------------------------
		# * Parse XML
		#--------------------------------------------------------------------------------------------------
		static function parse( $xml_file )
		{	
			$result = NULL;
			$data 		= file_get_contents( $xml_file );
			$parser 	= xml_parser_create();
			
			// break the xml into an array
			if ( xml_parse_into_struct( $parser, $data, $vars ) ) 
				$result = self::_xml_to_array( $vars );
				

			// free the parser
			xml_parser_free( $parser );
			
			return $result;
		}
		
		
		#----------------------------------------------------------------------------------------------------
		# * _xml_to_array
		# Reentrant function
		# Parse blob created by xml_parse_into_struct
		#----------------------------------------------------------------------------------------------------
		static private function _xml_to_array( array &$xml, $start_idx = 0, $level = 1, $debug = false ) {
			
			
			$result = array();
			$current_level = $level;
			$a = $start_idx;
			$n = count( $xml );
			$children = array();
			$complete_children = array();
			
			$current_xml_level = 0;

			for ( $i = $a; $i < $n; $i++ ) {
				//current_xml_level
				if ( $debug ) {
					//var_dump( "current_xml_level  = $current_xml_level, debug $i/$n<br />", $xml[ $i ] );
				}
				if ( $xml[ $i ][ 'type' ] == 'open' ) {
					$current_xml_level++;
				}
				if ( $xml[ $i ][ 'type' ] == 'close' ) {
					if ( $current_xml_level == 0 ) break;
					$current_xml_level--;
				}

				//if ( $xml[ $i ][ 'level' ] == $level ) {
					if ( $xml[ $i ][ 'type' ] == 'open' && $current_xml_level == 1 ) {
						$children[] = $i;
					}
					if ( $xml[ $i ][ 'type' ] == 'complete' && $current_xml_level == 0 ) {
						$complete_children[] = $i;
					}
				//}
			}
			
			$n = count( $children );
			for ( $i = 0; $i < $n; $i++ ) {
				$child = array();
				
				$debug = false;
				if ( $xml[ $children[ $i ] ][ 'tag' ] == 'SECTIONS' ) {
					$debug = true;
				}
				$child[ 'children' ] = self::_xml_to_array( $xml, $children[ $i ] + 1 , $level +1, $debug );
				
				if ( isset( $xml[ $children[ $i ] ][ 'attributes' ] ) ) {
					$child[ 'attributes' ] =  $xml[ $children[ $i ] ][ 'attributes' ];
				}
				if ( isset( $xml[ $children[ $i ] ][ 'cdata' ] ) ) {
					$child[ 'cdata' ] =  $xml[ $children[ $i ] ][ 'cdata' ];
				}
				
				
				$result[] = Array( $xml[ $children[ $i ] ][ 'tag' ] => $child );
				
			}
			
			$n = count( $complete_children );
			for ( $i = 0; $i < $n; $i++ ) {
				$child = array();

				if ( isset( $xml[ $complete_children[ $i ] ][ 'attributes' ] ) ) {
					$child[ 'attributes' ] =  $xml[ $complete_children[ $i ] ][ 'attributes' ];
				}
				if ( isset( $xml[ $complete_children[ $i ] ][ 'value' ] ) ) {
					$child[ 'value' ] =  $xml[ $complete_children[ $i ] ][ 'value' ];
				}				
				$result[] = Array( $xml[ $complete_children[ $i ] ][ 'tag' ] => $child );
				
			}
			
			return $result;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * _xml_tree
		#----------------------------------------------------------------------------------------------------
		static public function tree(  Array &$tree, Array $path  ) 
		{
			$npath = Array();
			foreach( $path as $p ) {
				$npath[] = -1;
				$npath[] = $p;
				$npath[] = "children";
			}
			
			return self::_search_tree( $tree, $npath );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * _xml_attributes
		#----------------------------------------------------------------------------------------------------
		static public function attributes(  Array &$tree, Array $path  ) 
		{
			$npath = Array();
			foreach( $path as $p ) {
				$npath[] = -1;
				$npath[] = $p;
				$npath[] = "children";
			}
			
			$npath[ count( $npath ) -1 ] = "attributes";
			
			return self::_search_tree( $tree, $npath );
		}

		#----------------------------------------------------------------------------------------------------
		# * _search_tree
		#----------------------------------------------------------------------------------------------------
		static private function _search_tree(  Array &$tree, Array &$path ) 
		{
			$cursor = &$tree;
			foreach( $path as $k => &$p  ) {
				if ( $p === -1 ) 
				{
					$next = $path[ $k+1 ];
					$found = false;
					foreach( $cursor as $kk => &$cur ) {
						
						if ( isset( $cur[ $next ] ) ) 
						{
							$cursor = &$cursor[ $kk ];
							$found = true;
							break;
						}
					}
					if ( $found == false ) return NULL;
				} else
					if ( isset( $cursor[ $p ] ) ) {
						$cursor = &$cursor[ $p ];
					} else
						return NULL;
			}
			return $cursor;
		}		
	}
}