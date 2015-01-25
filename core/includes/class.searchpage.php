<?php
#============================================================================================================
# ** HomePage Class
#============================================================================================================
namespace Core;
{
	require( 'library/class.webpage.php' );
	require( 'library/data/class.article.php' );
	require( 'library/data/class.episode.php' );
	require( 'library/data/class.page.php' );

	class SearchPage extends WebPage
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		public $keywords = array();
		public $cat = "";
		protected $ignore = array();
		#----------------------------------------------------------------------------------------------------
		# * Constants
		#----------------------------------------------------------------------------------------------------
		const PER_PAGE = 5;
		const MODE_QUICK = "quick";
		const MODE_RESULTS = "results";
		const CAT_ARTICLES = "articles";
		const CAT_WEBSITE = "website";
		const CAT_EPISODES = "episodes";
		const CAT_RECIPES = "recipes";
		const IGNORE = "a able about above according across actual actually adj afterwards against almost alone along already also although always am among amongst an and anyhow anyone anything appear are aren't around as at b be became because become becomes becoming been beforehand began behind being below beside besides better between beyond billion both but by called can can't cannot cant co co. could couldn't crap currently d did didn't difference directly do does doesn't don't done dont down during e e.g. each eg eight eighty either else elsewhere ended ending enough even ever every everyone everything everywhere except f few fifty finding five following for former formerly forty found four from further g getting go going gone h had has hasn't have haven't he he'd he'll he's hence her here here's hereafter hereby herein hereupon hers herself him himself his how however hundred i i'd i'll i'm i've i.e. if im in inc inc. including indeed instead into is isn't it it's its itself j just know knowing known kruft kudos l later latter latterly let let's like likely look ltd m made makes making many may maybe meantime meanwhile meeting might million miss missed moreover mostly mr mrs much must my myself n namely need needs neither nevertheless nine ninety no nobody none nonetheless noone nor not note nothing now nowhere o of often once only onto or other others otherwise our ours ourselves overall p part per perhaps picked picking place please pm possible provide q questions rather really reason recently rid rotten s saw see seeing seem seemed seeming seems seen seven seventy several she she'd she'll she's should shouldn't showed showing shown since six sixty so some somehow someone something sometime sometimes somewhere still stopping such sure t take taken taking ten than thanks that that'll that's that've the their them themselves then thence there there'd there'll there're there's there've thereafter thereby therefore therein thereupon these they they'd they'll they're they've things think thinking thirty this those though thought thousand three through throughout thru thus to together too took toward towards trillion try twenty two u under unless unlike unlikely until up upon us v v. vast versus very via vs vs. w want was wasn't way we we'd we'll we're we've were weren't what what'll what's what've whatever whence whenever where's whereafter whereas whereby wherein whereupon wherever whether while whither who'd who'll who's whoever whole whom whomever whose will wish with within without won't would wouldn't y yes yet you you'd you'll you're you've youll your youre yours yourself yourselves";
		#----------------------------------------------------------------------------------------------------
		# * Constructor
		#----------------------------------------------------------------------------------------------------
		public function __construct( $title, $auto_header = true, $css = array(), $js = array(), $body_class = array() )
		{
			parent::__construct( $title, $auto_header, $css, $js, $body_class );
			$this->keywords = explode( " ",  $this->query );
			$this->cat = Globals::get( 'cat', "" );
			$this->ignore = explode( " ", self::IGNORE );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Search
		#----------------------------------------------------------------------------------------------------
		public function search( $query, $object, $cols, $page, $num_only = false )
		{
			$results = array();
			$results_n = 0;
			
			if ( $query )
			{
				// assemble query
				$limit 	= $this->mode == self::MODE_QUICK ? " LIMIT 3" : " LIMIT " . ( $page * self::PER_PAGE ) . ", " . self::PER_PAGE;
				// get results without limit to get total rows
				$results_n = (int)@mysql_num_rows( @mysql_query( $query ) );
				$results = $num_only ? array() : Database::select_query( $query . $limit );
				
				// limit the query
				if ( $err = mysql_error() )
				{
					$results = $err . " (Query: $query)";
					$results_n = false;
				}
				else
				{
					$results = call_user_func( "$object::getx_data", $object, $results, true, "id", clone $this );
					$results = call_user_func( "$object::highlight_keywords", $results, $this->keywords, $cols );
				}
			}
			

			return $num_only ? $results_n : array( $results, $results_n );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Search Articles
		#----------------------------------------------------------------------------------------------------
		public function search_articles( $page, $num_only = false )
		{
			$query = "";
			if ( $this->keywords && $keyword_string = $this->get_keyword_string() )
			{
				// get the keyword string
				// Updated
				
				$query = "SELECT t.* FROM ( "
														. " SELECT MATCH(a.article_name) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as name_matchrank "
																		. ", MATCH(a.article_name,a.article_content) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as matchrank "
																		. ", a.*"
														. " FROM articles as a "
														. " WHERE  a.article_type = '1' AND a.article_enabled != '0' AND a.article_deleted = '0' AND a.article_status = '1'"
													. ") as t "
								. " WHERE t.matchrank > 0 "
								. " ORDER BY t.name_matchrank DESC, t.matchrank DESC";

				/* OLD
				$query 	= "SELECT t.* FROM (SELECT MATCH(a.article_name) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as name_matchrank, MATCH(a.article_name,a.article_content) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as matchrank, a.* FROM " . Database::ARTICLES . " as a) as t "
						. "WHERE t.article_type = '1' AND t.matchrank > 0 AND t.article_enabled != '0' AND t.article_deleted = '0' AND t.article_status = '" . Data::APPROVED . "' "
						. "ORDER BY t.name_matchrank DESC, t.matchrank DESC";
				 */
			}

			return $this->search( $query, "Core\\Article", array( "content", "name" ), $page, $num_only );
		}
		#----------------------------------------------------------------------------------------------------
		# * Search Recipes
		#----------------------------------------------------------------------------------------------------
		public function search_recipes( $page, $num_only = false )
		{
			$query = "";
			// get the keyword string
			if ( !in_array( "recipe", $this->keywords ) )
				$this->keywords[] = "recipe";
			if ( $keyword_string = $this->get_keyword_string() )
			{
				$query 	= "SELECT t.* FROM (SELECT MATCH(a.article_name) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as name_matchrank, MATCH(a.article_name,a.article_short) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as matchrank, a.* FROM " . Database::ARTICLES . " as a) as t "
						. "WHERE t.article_type = '2' AND t.matchrank > 0 AND t.article_enabled != '0' AND t.article_deleted = '0' AND t.article_status = '" . Data::APPROVED . "' "
						. "ORDER BY t.name_matchrank DESC, t.matchrank DESC";
			}
			
			if ( $num_only )
				$results_n = $this->search( $query, "Core\\Article", array( "short", "name" ), $page, $num_only );
			else
			{
				list( $results, $results_n ) = $this->search( $query, "Core\\Article", array( "short", "name" ), $page, $num_only );
				
				if ( $results_n )
				{
					// fix the slugs to appear in their show
					foreach( $results as $i => $r )
					{
						$j = strpos( $results[ $i ]->slug->value, "-" );
						$handle = substr( $results[ $i ]->slug->value, 0, $j );
						if ( strpos( $handle, "recipe" ) === false )
							$results[ $i ]->href = $this->anchor( constant( strtoupper( $handle ) . "_ARTICLES" ), array( 'slug' => substr( $results[ $i ]->slug->value, ( $j + 1 ) ) ) );
					}
				}
			}
			
			return $num_only ? $results_n : array( $results, $results_n );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Search Episodes/Guests
		#----------------------------------------------------------------------------------------------------
		public function search_episodes( $page, $num_only = false )
		{
			$query = "";
			if ( $this->keywords && $keyword_string = $this->get_keyword_string() )
			{
				$episode_query = "SELECT e.*, s.*, u.*, f.*, t.*, null as guest_id, null as guest_matchrank, null as guest_name, null as guest_company, null as guest_bio, null as guest_title, null as guest_enabled, null as guest_enabled_by, null as guest_deleted, null as guest_deleted_by, null as guest_created, null as guest_modified, null as guest_modified_by, null as guest_author, null as guest_reviewed, null as guest_reviewed_by, null as guest_status, null as guest_notes, 1, 2 "
								. "FROM " . Database::EPISODES . " as e, " . Database::SEGMENTS . " as s, " . Database::PHOTOS . " as f, (SELECT MATCH(p.program_name) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as program_matchrank, p.* FROM " . Database::PROGRAMS . " as p ) as u, (SELECT MATCH(a.article_name) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as name_matchrank, MATCH(a.article_name,a.article_content) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as matchrank, a.* FROM " . Database::ARTICLES . " as a ) as t "
								. "WHERE e.episode_enabled != '0' AND e.episode_deleted = '0' AND e.episode_status = '" . Data::APPROVED . "' AND e.episode_prime_segment = s.segment_id AND s.segment_thumb = f.photo_id AND e.episode_program = u.program_id AND e.episode_article = t.article_id AND (t.matchrank > 0 OR u.program_matchrank > 0) "
								. "ORDER BY u.program_name DESC, t.name_matchrank DESC, t.matchrank DESC, e.episode_id DESC";
				$guest_query = "SELECT e.*, s.*, null as program_matchrank, p.*, f.*, null as name_matchrank, null as matchrank, a.*, x.*, t.* "
								. "FROM " . Database::EPISODES . " as e, " . Database::PROGRAMS . " as p, " . Database::ARTICLES . " as a, " . Database::PHOTOS . " as f, " . Database::SEGMENTS . " as s, " . Database::G2SEGMENTS . " as x, (SELECT MATCH(g.guest_name) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as guest_matchrank, g.* FROM " . Database::GUESTS . " as g ) as t "
								. "WHERE e.episode_enabled != '0' AND e.episode_deleted = '0' AND e.episode_status = '" . Data::APPROVED . "' AND e.episode_id = s.segment_episode AND p.program_id = e.episode_program AND x.segment_id = s.segment_id AND a.article_id = e.episode_article AND f.photo_id = s.segment_thumb AND x.guest_id = t.guest_id AND t.guest_matchrank > 0 "
								. "ORDER BY t.guest_matchrank DESC, e.episode_id DESC";
				$query = "SELECT * FROM (({$episode_query}) UNION ({$guest_query})) as complete GROUP BY complete.episode_id";
			}
			

			return $this->search( $query, "Core\\Episode", array( "content", "name", "segment_names", "guest_names" ), $page, $num_only );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Search Website
		#----------------------------------------------------------------------------------------------------
		public function search_website( $page, $num_only = false )
		{
			$query = "";
			if ( $this->keywords )
			{
				// get the keyword string
				$keyword_string = $this->get_keyword_string();
				$query 	= "SELECT t.* FROM (SELECT MATCH(s.page_keywords) AGAINST('" . $keyword_string . "' IN BOOLEAN MODE) as matchrank, s.* FROM " . Database::SEARCH . " as s) as t "
						. "WHERE t.matchrank > 0 ORDER BY t.matchrank DESC";
			}
			
			return $this->search( $query, "Core\\Page", array( "keywords" ), $page, $num_only );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * View
		#----------------------------------------------------------------------------------------------------
		public function view()
		{
			// initialize variables
			$html = new Template( $this, "search.html" );
			$num_website = $this->search_website( 1, true );
			$num_articles = $this->search_articles( 1, true );
			$num_episodes = $this->search_episodes( 1, true );
			$num_recipes = $this->search_recipes( 1, true );
			$start_tab = self::CAT_WEBSITE;
			
			// if the first tab is empty, try to find the nearest result
			if ( !$num_website )
			{
				if ( $num_episodes > 0 )
					$start_tab = self::CAT_EPISODES;
				else if ( $num_articles > 0 )
					$start_tab = self::CAT_ARTICLES;
				else if ( $num_recipes > 0 )
					$start_tab = self::CAT_RECIPES;
			}
			
			// add template vars
			$html->add_vars( array
			( 
				"U_SEARCH" => $this->anchor( MAIN_SEARCH, array( 'query' => $this->query, 'mode' => self::MODE_RESULTS ) ),
				"V_NUM_WEBSITE" => $num_website ? " ({$num_website})" : "",
				"V_NUM_ARTICLES" => $num_articles ? " ({$num_articles})" : "",
				"V_NUM_EPISODES" => $num_episodes ? " ({$num_episodes})" : "",
				"V_NUM_RECIPES" => $num_recipes ? " ({$num_recipes})" : "",
				"V_START_TAB" => $start_tab
			) );
			
			return $this->head . $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Quick Mode
		#----------------------------------------------------------------------------------------------------
		public function quick()
		{
			// search using the keywords
			$page = $page_tag = (int)Globals::get( 'epage', 1 ) - 1;
			list( $results, $results_n ) = $this->search_episodes( $page );
			list( $results2, $results2_n ) = $this->search_website( $page );
			$results3_n = $this->search_articles( 1, true );
			$results4_n = $this->search_recipes( 1, true );
			$results = array_merge( $results2, $results );
			$results_n += $results2_n + $results3_n + $results4_n;
			
			
			
			$html = new Template( $this, "quicksearch.html" );
			$html->add_vars( array
			( 
				"V_RESULTS" => $results, 
				"V_RESULTS_N" => $results_n > 3 ? $results_n : 0
			) );

			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Results
		#----------------------------------------------------------------------------------------------------
		public function results()
		{
			// initialize variables
			$pagination = array();
			$page 		= $page_tag = (int)Globals::get( 'epage', 1 );
			$page 		-= 1;
			$start 		= $page * self::PER_PAGE;
			$end 		= $start + self::PER_PAGE;
			$total_pages = 1;
			$temp 		= "";
			
			switch( $this->cat )
			{
				case self::CAT_RECIPES:		list( $results, $results_n ) = $this->search_recipes( $page );
											$temp = "search_recipes.html";
											break;
				case self::CAT_ARTICLES:	list( $results, $results_n ) = $this->search_articles( $page );
											$temp = "search_articles.html";
											break;
				case self::CAT_EPISODES:	list( $results, $results_n ) = $this->search_episodes( $page );
											$temp = "search_episodes.html";
											break;
				default:
				case self::CAT_WEBSITE:		list( $results, $results_n ) = $this->search_website( $page );
											$temp = "search_website.html";
											break;
			}
			
			if ( $results_n === false )
				$html = $results;
			else
			{
				$total_pages = ceil( $results_n / self::PER_PAGE );
				for ( $i = 1; $i <= $total_pages; $i++ )
					$pagination[] = array( 'index' => $i, 'sel' => ( $page_tag == $i ? "sel" : "" ) );
				$start = $start + 1;
				$end = $end > $results_n ? $results_n : $end;
			
				$html = new Template( $this, $temp );
				$html->add_vars( array
				( 
					"V_RESULTS" => $results,
					"V_RESULTS_N" => $results_n,
					"V_RESULTS_START" => $start,
					"V_RESULTS_END" => $end,
					"V_RESULTS_TO" => $start != $end,
					"V_RESULTS_PAGES" => $total_pages,
					"V_RESULTS_PAGE" => $page,
					"V_RESULTS_PERPAGE" => self::PER_PAGE,
					"V_MANYPAGES" => $results_n > self::PER_PAGE,
					"V_PAGINATION" => $pagination,
					"V_LOADTIME" => $this->stop_timer()
				) );
			}
			return $this->head . $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Get Keyword String
		#----------------------------------------------------------------------------------------------------
		protected function get_keyword_string()
		{
			$a = "";
			foreach( $this->keywords as $index => $kw )
			{
				$kw = trim( $kw );
				if ( !in_array( $kw, $this->ignore ) )
				{
					$this->keywords[ $index ] = $kw;
					$a .= "+" . addslashes( trim( $kw ) ) . "*";
					if ( ( $index + 1 ) != count( $this->keywords ) )
						$a .= " ";
				}
			}
			return $a;
		}
	}
}
?>
