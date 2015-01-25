<?php
namespace Core;

if ( !defined( "D_CLASS_WEBSITE" ) )
{
	define( "D_CLASS_WEBSITE", true );
	
	/**
 	 * File: class.website.php
	 *
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class Website extends Base
	{	
		public $debug				= true;
		public $enabled				= true;
		public $register_enabled	= true;
		public $must_change_password= false;
		public $auto_path			= true;
		public $memory_alloc		= "512M";
		public $local_templates		= "";
		public $title				= "WNIT Public Television";
		public $owner_email			= "mshelton@wnit.org";
		public $copyright			= "Michiana Public Broadcasting Corporation. All Rights Reserved.";
		public $company_street		= "300 West Jefferson Boulevard, South Bend, Indiana 46601";
		public $company_phone		= "574.675.9648";
		public $company_email		= "wnit@wnit.org";
		
		protected $files 			= array();
		protected $url				= "";
		protected $url_admin		= "";
		protected $url_upload		= "";
		protected $local			= "";
		protected $local_upload		= "";
		protected $urls				= array();
		protected $paths			= array();
		protected $path_ignore		= array( "min", "includes", "errors", "cgi-bin", "themes", "video", "images" );
		protected $path_follow		= array();
		protected $titles			= array();
		
		protected $_getters 		= array( 'local', 'files', 'url', 'urls', 'url_admin', 'url_upload', 'local_upload', 'titles' );
		
		const MASTERKEY			= "jEvupasusay8ru7echa6aPhumefratrufraPhu7uqaswap8ufr7wremaprefespa";
		const SECS_IN_DAY		= 86400;
		const LOCALHOST 		= "127.0.0.1";
		
		const ACTION_LOGIN		= "login";
		const ACTION_LOGOUT		= "logout";
		const ACTION_ADD		= "add";
		const ACTION_DELETE		= "delete";
		const ACTION_UNDELETE	= "undelete";
		const ACTION_ENABLE		= "enable";
		const ACTION_DISABLE	= "disable";
		const ACTION_PUBLISH	= "publish";
		const ACTION_UNPUBLISH	= "unpublish";
		const ACTION_FORGET		= "forget";
		const ACTION_EDIT		= "edit";
		const ACTION_VIEW		= "view";
		const ACTION			= "-";
		
		const MODE_VIEW			= "view";
		const MODE_GRAVE		= "grave";
		const MODE_NEW			= "new";
		const MODE_EDIT			= "edit";
		const MODE				= "/";
		const ID				= "/";
		
		const BETA				= "http://alpha.wnit.us/";
		const REWRITE_MOD		= true;
		const HOME_DIR			= "";
		const ADMIN_DIR			= "account/";
		const IMAGES_DIR		= "images/";
		const REAL_EXT			= ".php";
		const FILE_EXT			= ".html";
		const ROOT_PATH			= "main";
		const ROOT_INDEX		= 1000;
		const ERROR_400			= "ERROR_400";
		const ERROR_401			= "ERROR_401";
		const ERROR_402			= "ERROR_402";
		const ERROR_404			= "ERROR_404";	
		const REGISTRY_FILE		= "registry.ini";
		const TITLES_INI		= "pages.ini";
		
		/**
		 * Class constructor.
		 *
		 * @uses Website::$url
		 * @uses Website::$local
		 * @uses Website::$url_admin
		 * @uses Website::$url_upload
		 * @uses Website::$local_upload
		 * @uses Website::$auto_path
		 * @uses Website::get_base_url
		 * @uses Website::get_base_dir
		 * @uses Website::register_files
		 * @uses Website::auto_register_paths
		 * @uses Website::auto_register_files
		 * @uses Website::register_titles_ini
		 */
		public function __construct()
		{
			error_reporting( E_ALL );
			
			$this->url 			= $this->get_base_url();
			$this->local 		= $this->get_base_dir();
			$this->url_admin	= $this->url . self::ADMIN_DIR;
			$this->url_upload	= $this->url . self::IMAGES_DIR . "upload/";
			$this->local_upload	= $this->local . self::IMAGES_DIR . "upload/";
			
			if ( !$this->auto_path )
				$this->register_files();
			else
			{
				$this->auto_register_paths();
				$this->auto_register_files();
			}
			$this->register_titles_ini();
		}
		
		/**
		 * Creates a URL from the page ID and parameters.
		 *
		 * @param string Page ID
		 * @param array GET arguments in an array form. (Default = array())
		 * @param bool whether or not to use html char for &. (Default = false)
		 * @param bool assume whether or not to revert to file reference instead of URL's if non_html_amp. (Default = true)
		 * @uses Website::$files
		 * @uses Website::$urls
		 * @uses Website::form_url
		 * @uses Generic::form_url
		 * @uses Globals::get
		 * @return string URL
		 */
		public function anchor( $page, array $query = array(), $non_html_amp = false, $assume = true )
		{
			// $this->files values are used in header redirects which do not recognize
			// the html &amp; entity, so using the $this->file is an assumption so I don't
			// need to specify which array on every call.
			
			if ( isset( $this->files[ $page ] ) || isset( $this->urls[ $page ] ) )
				$url = $non_html_amp && $assume ? $this->files[ $page ] : $this->urls[ $page ];
			else
				$url = $this->url;
				
			
			// without a rewrite mod, the url is formed normally
			if ( !self::REWRITE_MOD )
				$url = $this->form_url( $url . self::REAL_EXT, $query, $non_html_amp );
			else
			{
				// look for the lite
				$lite = Globals::get( 'lite' );
				if ( !isset( $query[ 'lite' ] ) && $lite )
					$query[ 'lite' ] = true;
				
				// get slug
				if ( isset( $query[ 'slug' ] ) && $query[ 'slug' ] )
				{
					// slugs are permalink identifiers
					$slug_page_found = false;
					$slug_pages = array( "segments" => "s/", "episodes" => "e/", "articles" => "a/" );
					foreach( $slug_pages as $p => $abbr )
					{
						if ( strpos( $url, $p ) !== false )
						{
							$url = str_replace( $p, $abbr, $url );
							$slug_page_found = true;
						}
					}
					
					// append the result
					if ( $slug_page_found )
					{
						$url .= $query[ 'slug' ];
						unset( $query[ 'slug' ] );
					}
				}
				else
				{
					// make a fancy-looking URL instead of appending GET variables
					if ( isset( $query[ 'mode' ] ) && $query[ 'mode' ] )
					{
						$url .= self::MODE . $query[ 'mode' ];
						unset( $query[ 'mode' ] );
					}
					if ( isset( $query[ 'id' ] ) && $query[ 'id' ] )
					{
						$url .= self::ID . $query[ 'id' ];
						unset( $query[ 'id' ] );
					}
					if ( isset( $query[ 'action' ] ) && $query[ 'action' ] )
					{
						$url .= self::ACTION . $query[ 'action' ];
						unset( $query[ 'action' ] );
					}
				}
				
				// put it all together
				$url = $query ? Generic::form_url( $url . self::FILE_EXT, $query, $non_html_amp ) : $url . self::FILE_EXT;
				
				// remove index.html
				$url = preg_replace( "/index\.(html|shtml|php)$/", "", $url );
			}
			
			return $url;
		}
		
		/**
		 * Simplfied version of anchor but sets the third parameter to true.
		 *
		 * This function is a more simplified version of anchor that doesn't include the
		 * final parameter. It does nothing different.
		 *
		 * @param string Page ID
		 * @param array GET arguments in an array form. (Default = array())
		 * @uses Website::anchor
		 * @return string URL
		 */
		public function anchor_file( $page, $query = array() )
		{
			return $this->anchor( $page, $query, true );
		}
		
		/**
		 * Finds the base directory on the web server.
		 *
		 * @return string local path
		 */
		public function get_base_dir()
		{
			return str_replace( "/", DIRECTORY_SEPARATOR, __DIR__ . "/../../" . self::HOME_DIR );
		}
		
		/**
		 * Finds the base URL for the website.
		 *
		 * @return string root URL
		 */
		public function get_base_url()
		{
			$url = "http";
 			if ( isset( $_SERVER[ 'HTTPS' ] ) && $_SERVER[ 'HTTPS' ] == "on" ) $url .= "s";
			$url .= "://";
 			if ( $_SERVER[ 'SERVER_PORT' ] != "80" )
				$url .= $_SERVER[ 'SERVER_NAME' ]. ":" . $_SERVER[ 'SERVER_PORT' ] . $_SERVER[ 'REQUEST_URI' ];
			else
				$url .= $_SERVER[ 'SERVER_NAME' ] . "/";
			
			return $url . self::HOME_DIR;
		}
		
		/**
		 * Get the domain associate with the server.
		 *
		 * @return string domain
		 */
		public function get_domain()
		{
			return $_SERVER[ 'SERVER_NAME' ];
		}
		
		/**
		 * Parses the page.ini file to learn page titles.
		 *
		 * @param string name of the [asterisk].ini file. (Default = self::TITLES_INI)
		 * @uses Website::$titles
		 */
		protected function register_titles_ini( $ini = self::TITLES_INI )
		{
			$this->titles = parse_ini_file( $ini );
			
		}
		
		/**
		 * Searches the local directories for valid script files.
		 *
		 * @uses Website::$paths
		 * @uses Website::$files
		 * @uses Website::$urls
		 * @uses Website::$url
		 * @uses Website::$local
		 */
		protected function auto_register_files()
		{
			// initialize variables
			$level = 0;
			
			// look through each director
			foreach( $this->paths as $i => $path )
			{
				// initialize variables
				$j 		= $level + 1001;
				$files 	= glob(  "{$path}*" . self::REAL_EXT );
				$len 	= strlen( self::REAL_EXT );
				
				foreach( $files as $f )
				{
					// determine handle
					$file_name 	= substr( $f, 0, strlen( $f ) - $len );
					
					if ( strpos( $file_name, $this->local ) !== false )
						$file_name = substr( $file_name, strlen( $this->local ) );
					
					$handle 	= $i == self::ROOT_PATH ? strtoupper( self::ROOT_PATH ) . "_" : "";
					$handle 	.= preg_replace( "/[^\w]/", "_", strtoupper( $file_name ) );
					define( $handle, $handle );
					/* The old page id number define( $handle . '_PAGEID', $j );*/
					
					// get rid of system directories
					if ( strpos( $file_name, $this->local ) !== false )
						$file_name = substr( $file_name, strlen( $this->local ) );
					
					// define properties
					$this->files[ $handle ] = $file_name;
					$this->urls[ $handle ] = $this->url . str_replace( "\\", "/", $file_name );
					$j++;
				}
				
				// increment for next path
				$level += 1000;
				$j = 1;
			}
		}
		
		/**
		 * Searches the local directories for valid web directories.
		 *
		 * @uses Website::$path_ignore
		 * @uses Website::$local
		 * @uses Website::$paths
		 */
		protected function auto_register_paths()
		{
			// prepend all the ignore paths
			$n = count( $this->path_ignore );
			for ( $i = 0; $i < $n; $i++ )
				$this->path_ignore[ $i ] = $this->local . $this->path_ignore[ $i ];
			
			$dirs = glob( $this->local . "*", GLOB_ONLYDIR );
			
			// setup all valid web directories
			$this->paths[ self::ROOT_PATH ] = $this->local;
			foreach( $dirs as $d )
				if ( !in_array( $d, $this->path_ignore ) )
					$this->paths[ basename( $d ) ] =  $d . DIRECTORY_SEPARATOR;
		}
		
		/**
		 * Register files via the [asterisk].ini file.
		 *
		 * @uses Website::$paths
		 * @uses Website::$path_follow
		 * @uses Website::$local
		 * @uses Website::register_ini
		 */
		protected function register_files()
		{
			// register paths
			$this->paths[ self::ROOT_INDEX ] = $this->local;
			$this->register_ini( $this->paths[ self::ROOT_INDEX ] . self::REGISTRY_FILE );
				
			foreach( $this->path_follow as $index => $p )
			{
				// put together local path names
				$this->paths[ $index ] = $this->local . $p . DIRECTORY_SEPARATOR;
				
				// look for registries to assign id's to
				$this->register_ini( $this->paths[ $index ] . self::REGISTRY_FILE, $p, $p, $index );
			}
		}
		
		/**
		 * Register files, paths, and constants via the [asterisk].ini file.
		 *
		 * @param string name of the [asterisk].ini file.
		 * @param string first half of the file constant (i.e., MAIN in MAIN_INDEX). (Default = self::ROOT_PATH)
		 * @param string local path to files. (Default = "")
		 * @param int index for $this->urls. (Default = 1000)
		 * @uses Website::$files
		 * @uses Website::$urls
		 * @uses Website::$url
		 * @return bool whether registration took place
		 */
		protected function register_ini( $filename, $handle = self::ROOT_PATH, $path = "", $index = 1000 )
		{
			// initialize variables
			$registered = false;
			if ( $path ) $path .= "/";
			
			if ( file_exists( $filename ) )
			{
				$files = parse_ini_file( $filename );
				foreach( $files as $f => $i )
				{
					// remember the files
					$file_id					= $index + $i;
					$file_name 					= "{$path}{$f}";
					$this->files[ $file_id ] 	= $file_name;
					$this->urls[ $file_id ] 	= $this->url . str_replace( "\\", "/", $file_name );
					
					// define the dynamic "constant"
					define( strtoupper( "{$handle}_{$f}" ), $file_id );
				}
				
				$registered = true;
			}
			
			return $registered;
		}
	}
}
?>