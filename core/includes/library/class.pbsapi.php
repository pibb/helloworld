<?php
namespace Core;

if ( !defined( "D_CLASS_PBSAPI" ) )
{
	define( "D_CLASS_PBSAPI", true );
		
	/**
	 * File: class.pbsapi.php
	 *
	 * @package Library/API's
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	abstract class PBS_API
	{
		//public $webpage	= NULL;
		protected $api_id = "";
		protected $api_secret = "";
		
		/**
		 * Make an API request to PBS using the given URL.
		 *
		 * @param string URL
		 * @param bool whether or not to use stream_context_create with headers. Required to authenticate. (Default = false)
		 * @param int the current timestamp. (Default = time())
		 * @param string passed to the signature. (Default = "")
		 * @uses PBS_API::calc_signature
		 * @uses PBS_API::normalize_url
		 * @uses PBS_API::$api_id
		 * @return string the result.
		 */
		public function make_request( $url, $auth_using_headers = false, $timestamp = 0, $nonce = "" )
		{
			// check to see if we need to autogenerate the parameters
			$contents = "";
			if ( $timestamp == 0 ) 	$timestamp = time();
			if ( $nonce == "" ) 	$nonce = md5( rand() );
	 
			if ( !$auth_using_headers )
			{
				// Pick the correct separator to use
				$separator = "?";
				if ( strpos( $url, "?" ) !== false )
					$separator = "&";
					
				$url = $url . $separator . "consumer_key=" . $this->api_id . "&timestamp=" . $timestamp . "&nonce=" . $nonce;
				$signature = $this->calc_signature( $url, $timestamp, $nonce );
				// Now add signature at the end
				$url = $this->normalize_url( $url."&signature=" . $signature );
				$contents = file_get_contents( $url );
			}
			else
			{
				$signature = $this->calc_signature( $url, $timestamp, $nonce );
				
				// Put the authentication parameters into the HTTP headers instead of into the url parameters
				$opts = array( 'http'=> array( 	'method'=> "GET",
												'header'=> 	"X-PBSAuth: " . $this->api_id . "\r\n" . 
															"X-PBSAuth-Timestamp: {$timestamp}\r\n" . 
															"X-PBSAuth-Consumer-Key: " . $this->api_id . "\r\n".
															"X-PBSAuth-Signature: {$signature}\r\n".
															"X-PBSAuth-Nonce: {$nonce}\r\n" ) );
				$url = $this->normalize_url( $url );
				$context = stream_context_create( $opts );
				//$contents = file_get_contents( $url, false, $context );      
			}
	 
			return $contents;
		}
	 
		/**
		 * Generates a signature hash from the URL, timestamp, and API secret.
		 *
		 * @param string URL
		 * @param int the timestamp of the request.
		 * @nonce string amendments.
		 * @uses PBS_API::normalize_url
		 * @uses PBS_API::$api_id
		 * @uses PBS_API::$api_secret
		 * @return string the has signature.
		 */
		protected function calc_signature( $url, $timestamp, $nonce )
		{
			// Take the url and process it
			$normalized_url = $this->normalize_url( $url );
			 
			// Now combine all the required parameters into a single string
			// Note: We are always assuming 'get'
			$string_to_sign = "GET" . $normalized_url . $timestamp . $this->api_id . $nonce;
	 
			// And generate the hash using the secret
			$signature = hash_hmac( 'sha1', $string_to_sign, $this->api_secret );
			 
			return $signature;
		}
		
		/**
		 * Encodes the given URL properly for the request.
		 *
		 * @param string URL
		 * @return string URL
		 */
		protected function normalize_url( $url )
		{
			// initialize variables
			$final = "";
			
			if ( $url )
			{
				// break up the url into all the various components; we expect this to be a full url
				$parts = parse_url( $url );
				$query = isset( $parts[ 'query' ] ) ? $parts[ 'query' ] : "";
				
				if ( !$query )
					$parts[ 'query' ] = "";
				else
				{
					// break out the parameters from the query, but only as a single array of strings
					$params 	= explode( '&', $query );
					$parameters = array();
					
					// now we loop through each string and generate a tuple for a multi-array
					foreach( $params as $p )
					{
						// Split this string into two parts and add to the multi-array
						list( $key, $value ) = explode( '=', $p );
						// do the url encoding while we are looping here
						$parameters[ $key ] = utf8_encode( urlencode( $value ) );
					}
			 
					// now sort the parameter list
					ksort( $parameters );
					 
					// Now combine all the parameters into a single query string
					$newquerystring = http_build_query( $parameters );
					$newquerystring = "";
					
					foreach( $parameters as $key => $value )
						$newquerystring .= $key . "=" . $value . "&";
						
					$newquerystring = substr( $newquerystring, 0, strlen( $newquerystring ) - 1 );    
					
					// combine everything into the total url
					$parts[ 'query' ] = "?" . $newquerystring;
				}
				 
				$final = $parts[ 'scheme' ] . "://" . $parts[ 'host' ] . $parts[ 'path' ] . $parts[ 'query' ];
			}
			
			return $final;
		}
	}
}
?>