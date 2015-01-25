<?php
namespace Core;

if ( !defined( "D_CLASS_WEBFOOTER" ) )
{
	define( "D_CLASS_WEBFOOTER", true );
	require( "data/class.ad.php" );
	require( "class.twitterapi.php" );
	
	/**
 	 * File: class.webfooter.php
	 *
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class WebFooter extends Template
	{
		const TWITTER_CHECK = 600; // 10 minutes
		
		/**
		 * Class constructor.
		 *
		 * @param WebPage the current webpage.
		 * @uses Template::__construct
		 * @uses Template::add_vars
		 * @uses WebPage::$naked
		 * @uses WebFooter::most_recent_tweet
		 */
		public function __construct( WebPage &$webpage )
		{	
			if ( !$webpage->naked )
			{
				parent::__construct( $webpage, "footer.html" );
				
				// get most recent tweet if we're on the home page.
				list( $tweet, $tweet_time ) = $this->most_recent_tweet();
					
				$partners = array
				( 
					0 => array( 'IMG' => 'http://www.wnit.org/images/partners/artseverywhere.jpg', 		'NAME' => 'Arts Everywhere', 						'HREF' => "http://www.artseverywhere.com/" ),
					1 => array( 'IMG' => 'http://www.wnit.org/images/partners/wvpe.jpg', 				'NAME' => 'WVPE 88.1', 								'HREF' => "http://www.wvpe.org/index.php#/" ),
					2 => array( 'IMG' => 'http://www.wnit.org/images/partners/smso.jpg', 				'NAME' => 'Southwest Michigan Symphony Orchestra', 	'HREF' => "http://www.smso.org/" ),
					3 => array( 'IMG' => 'http://www.wnit.org/images/partners/fischoff.jpg', 			'NAME' => 'Fischoff', 								'HREF' => "http://www.fischoff.org/" ),
					4 => array( 'IMG' => 'http://www.wnit.org/images/partners/lubeznik.jpg', 			'NAME' => 'Lubeznik', 								'HREF' => "http://www.lubeznikcenter.org/" ),
					5 => array( 'IMG' => 'http://www.wnit.org/images/partners/krasl.jpg', 				'NAME' => 'Krasl Art Center', 						'HREF' => "http://www.krasl.org/" ),
					6 => array( 'IMG' => 'http://www.wnit.org/images/partners/sbct.jpg', 				'NAME' => 'South Bend Civic Theatre', 				'HREF' => "http://www.sbct.org/" ),
					7 => array( 'IMG' => 'http://www.wnit.org/images/partners/ect.jpg', 				'NAME' => 'Elkhart Civic Theatre', 					'HREF' => "http://www.elkhartcivictheatre.org/" ),
					8 => array( 'IMG' => 'http://www.wnit.org/images/partners/sbso.jpg', 				'NAME' => 'South Bend Symphony Orchestra', 			'HREF' => "http://www.southbendsymphony.com/" ),
					9 => array( 'IMG' => 'http://www.wnit.org/images/partners/ecs.jpg',	 				'NAME' => 'Elkhart County Symphony', 				'HREF' => "http://www.elkhartsymphony.net//" ),
					10 => array( 'IMG' => 'http://www.wnit.org/images/partners/betterworldbooks.jpg', 	'NAME' => 'Better World Books', 					'HREF' => "http://www.betterworldbooks.com/Stores/" ),
					11 => array( 'IMG' => 'http://www.wnit.org/images/partners/debartolo.jpg', 			'NAME' => 'DeBartolo Performing Arts Center', 		'HREF' => "http://performingarts.nd.edu/" )
				);
				
				$this->add_vars( array
				( 
					'V_PARTNERS' 	=> $partners,
					'V_TWEET' 		=> $tweet,
					'V_TWEETTIME' 	=> $tweet_time,
				) );
			}
		}
		
		/**
		 * Parses a tweet for links and hastags.
		 *
		 * @return string HTML Anchor
		 */
		public function parse_tweet( $a )
		{
			$a = preg_replace('|([\w\d]*)\s?(https?://([\d\w\.-]+\.[\w\.]{2,6})[^\s\]\[\<\>]*/?)|i', '$1 <a href="$2" target="_blank">$3</a>', $a);
			$a = preg_replace( '/#([A-Za-z0-9_]+)/', '<a href="https://twitter.com/search?q=%23${1}&amp;src=hash" class="tag" target="_blank">#${1}</a>', $a );
			$a = preg_replace( '/@([A-Za-z0-9_]+)/', '<a href="https://twitter.com/${1}" class="account" target="_blank">@${1}</a>', $a );
			return $a;
		}
		
		/**
		 * Gets the most recent tweet.
		 *
		 * @uses Generic::nicetime to parse the tweet timestamp.
		 * @uses WebPageLite::$tweet_recent
		 * @uses WebPageLite::$tweet_check
		 * @uses WebFooter::parse_tweet
		 * @uses TwitterAPIExchange::get_last_post
		 * @return Array array( $tweet, $timestamp );
		 */
		public function most_recent_tweet()
		{
			// check most recent tweet
			list( $tweet_time, $tweet ) = unserialize( $this->webpage->tweet_recent );
			
			// if it's been awhile, check again
			if ( time() - $this->webpage->tweet_check >= self::TWITTER_CHECK )
			{
				// get last tweet
				list( $tweet, $tweet_time ) = ( new TwitterAPIExchange )->get_last_post();
				
				// update the tweet in the database so we don't have to check every page load
				mysql_query( "UPDATE " . Database::CONFIG . " SET config_value = '" . time() . "' WHERE config_id = 'tweet_check'" );
				mysql_query( "UPDATE " . Database::CONFIG . " SET config_value = '" . serialize( array( $tweet_time, $tweet ) ) . "' WHERE config_id = 'tweet_recent'" );
			}
			
			// ads vars
			$tweet = $this->parse_tweet( htmlentities( htmlspecialchars_decode( $tweet ), ENT_NOQUOTES, 'UTF-8' ) );
			$time = Generic::nicetime( $tweet_time );
			
			return array( $tweet, $time );
		}
	}
}
?>