<?php
namespace Core;

if ( !defined( "D_CLASS_TWITTERAPI" ) )
{
	define( "D_CLASS_TWITTERAPI", true );
	
	/**
	* Twitter-API-PHP : Simple PHP wrapper for the v1.1 API (Modified)
	*
	* @package Core/API's
	* @author James Mallison <me@j7mbo.co.uk>
	* @license MIT License
	* @see http://github.com/j7mbo/twitter-api-php
	*/
	class TwitterAPIExchange
	{
		protected $postfields;
		protected $getfield;
		protected $oauth;
		public $url;
		
		const TWITTER_CONSUMER_KEY 			= "JkUjODJPrf5fj3hVYulg";
		const TWITTER_CONSUMER_SECRET 		= "zoWwjzdAQkPecEC3qLTkvGDy8M8JIzh9hidgfhw90";
		const TWITTER_ACCESS_TOKEN 			= "133427478-oIYhTcFVgeZGAqAeziJ1p32aN0tZjvj0gQ7AsMLR";
		const TWITTER_ACCESS_TOKEN_SECRET 	= "hdiu0YSVRaoJM3kErAubsMYGTrn1cia5vEx0FOy0";
		const ENDPOINT	 					= "https://api.twitter.com/1.1/statuses/user_timeline.json";
		const USERNAME						= "wnitpublictv";
		
		/**
		 * Class constructor.
		 *
		 * Create the API access object. Requires an array of settings::
		 * oauth access token, oauth access token secret, consumer key, consumer secret
		 * These are all available by creating your own application on dev.twitter.com
		 * Requires the cURL library
		 *
		 * @throws Exception if cURL isn't installed.
		 * @return TwitterAPIExchange Instance of self for method chaining.
		 */
		public function __construct()
		{
			if ( !in_array( 'curl', get_loaded_extensions() ) ) 
				throw new \Exception( 'You need to install cURL, see: http://curl.haxx.se/docs/install.html' );
				
			return $this;
		}
		
		/**
		 * Set postfields array, example: array('screen_name' => 'J7mbo').
		 *
		 * @param array $array Array of parameters to send to API
		 * @throws Exception if get field is not NULL.
		 * @uses TwitterAPIExchange::get_get_field
		 * @uses TwitterAPIExchange::$postfields
		 * @return TwitterAPIExchange Instance of self for method chaining
		 */
		public function set_post_fields( array $array )
		{
			if ( !is_null( $this->get_get_field() ) )
				throw new Exception( 'You can only choose get OR post fields.' );
			
			if ( isset( $array[ 'status' ] ) && substr( $array[ 'status' ], 0, 1) === '@' )
				$array[ 'status' ] = sprintf( "\0%s", $array[ 'status' ] );
			
			$this->postfields = $array;
			
			return $this;
		}
		
		/**
		 * Set getfield string, example: '?screen_name=J7mbo'.
		 *
		 * @param string $string Get key and value pairs as string
		 * @throws Exception if post field is not NULL.
		 * @uses TwitterAPIExchange::get_get_field
		 * @uses TwitterAPIExchange::$getfield
		 * @return TwitterAPIExchange Instance of self for method chaining
		 */
		public function set_get_field( $string )
		{
			if ( !is_null( $this->get_post_fields() ) )
				throw new Exception( 'You can only choose get OR post fields.' ); 
			
			$search = array( '#', ',', '+', ':' );
			$replace = array( '%23', '%2C', '%2B', '%3A' );
			$string = str_replace( $search, $replace, $string );  
			
			$this->getfield = $string;
			
			return $this;
		}
		
		/**
		 * Get getfield string (simple getter).
		 *
		 * @return string $this->getfields
		 */
		public function get_get_field()
		{
			return $this->getfield;
		}
		
		/**
		 * Get postfields array (simple getter).
		 *
		 * @return array $this->postfields
		 */
		public function get_post_fields()
		{
			return $this->postfields;
		}
		
		/**
		 * Build the Oauth object using params set in construct and additionals passed to this method.
		 *
		 * @see https://dev.twitter.com/docs/api/1.1 for v1.1
		 * @see https://api.twitter.com/1.1/search/tweets.json. for API URL example.
		 * @param string $url The API url to use.
		 * @param string $request_method Either POST or GET.
		 * @throws Exception if method is not POST or GET.
		 * @uses TwitterAPIExchange::get_get_field
		 * @uses TwitterAPIExchange::build_base_string
		 * @uses TwitterAPIExchange::$url
		 * @uses TwitterAPIExchange::$oauth
		 * @return TwitterAPIExchange Instance of self for method chaining
		 */
		public function build_oauth( $url, $request_method )
		{
			if ( !in_array( strtolower( $request_method ), array( 'post', 'get' ) ) )
				throw new Exception( 'Request method must be either POST or GET' );
			
			$oauth = array
			( 
				'oauth_consumer_key' => self::TWITTER_CONSUMER_KEY,
				'oauth_nonce' => time(),
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_token' => self::TWITTER_ACCESS_TOKEN,
				'oauth_timestamp' => time(),
				'oauth_version' => '1.0'
			);
			$getfield = $this->get_get_field();
			
			if ( !is_null( $getfield ) )
			{
				$getfields = str_replace( '?', '', explode( '&', $getfield ) );
				foreach( $getfields as $g )
				{
					$split = explode( '=', $g );
					$oauth[ $split[ 0 ] ] = $split[ 1 ];
				}
			}
			
			$base_info = $this->build_base_string( $url, $request_method, $oauth );
			$composite_key = rawurlencode( self::TWITTER_CONSUMER_SECRET ) . '&' . rawurlencode( self::TWITTER_ACCESS_TOKEN_SECRET );
			$oauth_signature = base64_encode( hash_hmac( 'sha1', $base_info, $composite_key, true ) );
			$oauth[ 'oauth_signature' ] = $oauth_signature;
			
			$this->url = $url;
			$this->oauth = $oauth;
			
			return $this;
		}
		
		/**
		 * Perform the actual data retrieval from the API.
		 *
		 * @param bool $return If true, returns data. (Default = true)
		 * @throws Exception if $return is not a boolean.
		 * @uses TwitterAPIExchange::get_get_field()
		 * @uses TwitterAPIExchange::get_post_fields()
		 * @uses TwitterAPIExchange::build_authorization_header()
		 * @uses TwitterAPIExchange::$url
		 * @uses TwitterAPIExchange::$oauth
		 * @return string JSON If $return param is true, returns JSON data.
		 */
		public function perform_request( $return = true )
		{
			if ( !is_bool( $return ) ) 
				throw new Exception( 'perform_request parameter must be true or false' ); 
			
			$header 	= array( $this->build_authorization_header( $this->oauth ), 'Expect:' );
			$getfield 	= $this->get_get_field();
			$postfields = $this->get_post_fields();
			$options 	= array
			( 
				CURLOPT_HTTPHEADER 		=> $header,
				CURLOPT_HEADER 			=> false,
				CURLOPT_URL 			=> $this->url,
				CURLOPT_RETURNTRANSFER 	=> true
			);
	
			if ( !is_null( $postfields ) )
				$options[ CURLOPT_POSTFIELDS ] = $postfields;
			else if ( $getfield !== '' )
				$options[ CURLOPT_URL ] .= $getfield;
	
			$feed = curl_init();
			curl_setopt_array( $feed, $options );
			$json = curl_exec( $feed );
			curl_close( $feed );
	
			if ( $return ) { return $json; }
		}
		
		/**
		 * Generates the base string used by cURL.
		 *
		 * @param string $base_uri
		 * @param string $method
		 * @param array $params
		 * @return string Built base string
		 */
		protected function build_base_string( $base_uri, $method, $params ) 
		{
			$return = array();
			ksort( $params );
			
			foreach( $params as $key => $value )
				$return[] = "$key=" . $value;
			
			return $method . "&" . rawurlencode( $base_uri ) . '&' . rawurlencode( implode( '&', $return ) ); 
		}
		
		/**
		 * Generates the base string used by cURL.
		 *
		 * @param array $oauth Array of oauth data generated by build_oauth().
		 * @return string $return Header used by cURL for request.
		 */
		protected function build_authorization_header( array $oauth ) 
		{
			$return = 'Authorization: OAuth ';
			$values = array();
			
			foreach( $oauth as $key => $value )
				$values[] = "$key=\"" . rawurlencode( $value ) . "\"";
			
			$return .= implode( ', ', $values );
			return $return;
		}
		
		/**
		 * Get the last post of the given user.
		 *
		 * @param string the user name. (Default = self::USERNAME)
		 * @uses TwitterAPIExchange::set_get_field
		 * @return Array array( $text, $created_at )
		 */
		public function get_last_post( $user = self::USERNAME )
		{
			$field 	= $this->set_get_field( "?username={$user}&count=1" );
			$text 	= $field->build_oauth( self::ENDPOINT, "GET" )->perform_request(); 
			$post	= json_decode( $text );
			
			return array( $post[ 0 ]->text, $post[ 0 ]->created_at );
		}
		
		/**
		 * Get the number of followers of the given user.
		 *
		 * @param string the user name. (Default = self::USERNAME)
		 * @uses TwitterAPIExchange::set_get_field
		 * @return int
		 */
		public function num_followers( $user = self::USERNAME )
		{
			$field 	= $this->set_get_field( "?username={$user}" );
			$text 	= $field->build_oauth( self::ENDPOINT, "GET" )->perform_request(); 
			$post	= json_decode( $text );
			
			return $post[ 0 ]->user->followers_count;
		}
	}
}
?>