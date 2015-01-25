<?php
namespace Core;

if ( !defined( "D_CLASS_COLFILE" ) )
{
	define( "D_CLASS_COLFILE", true );
	require_once( __DIR__ . "/class.column.php" );
	
	/**
 	 * File: class.column_file.php
	 *
 	 * @package Library/Columns
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	class FileColumn extends Column
	{
		public $url_dir		= "";
		public $upload_dir	= "";
		public $file_maxsize= 0;
		public $file_types	= array();
		public $file_old	= "";
		public $file_name 	= "";
		private $_errors_called = false;
		
		const IMAGE_TYPES	= "jpg,png,gif";
		const VIDEO_TYPES	= "flv,mp4";
		
		/**
		 * Class constructor
		 *
		 * @param string The name of the MySQL table.
		 * @param string The prefix for the colum name (usually related to table name)
		 * @param string The name of the column minus prefix.
		 * @param string the local directory to upload the file to.
		 * @param string the url of the the local directory.
		 * @param Array acceptable file types.
		 * @param int the maximum file size. (Default = NULL)
		 * @uses Column::__construct
		 * @uses FileColumn::$upload_dir
		 * @uses FileColumn::$url_dir
		 * @uses FileColumn::$file_types
		 * @uses FileColumn::$file_old
		 * @uses FileColumn::$file_maxsize
		 * @uses Generic::strtobytes
		 */
		function __construct( $table, $prefix, $name, $upload_dir, $url_dir, $types, $max_size = NULL )
		{
			parent::__construct( $table, $prefix, $name );
			
			// get additional properties
			$this->upload_dir 	= $upload_dir;
			$this->url_dir 		= $url_dir;
			$this->file_types 	= is_int( $types ) ? $_types[ $types ] : $types;
			$this->file_old		= $this->name . "_file";
			$this->file_maxsize = $max_size ? (int)$max_size : Generic::strtobytes( ini_get( 'upload_max_filesize' ) );
		} 
		
		/**
		 * Finds $_POST variable and adds slashes, html special chas, and trims it.
		 * 
		 * @uses Globals::post
		 * @return string cleaned $_POST[ $this->name ]
		 */
		public function clean_post()
		{
			$this->errors();
			return $this->webpage->uploads[ $this->name ];
			
		}
		
		/**
		 * Adds file validation to normal error checking.
		 *
		 * @param mixed the value to be evaluated. (Default = NULL)
		 * @uses Column::$name
		 * @uses Column::$value
		 * @uses Column::$is_required
		 * @uses FileColumn::$file_old
		 * @uses FileColumn::$upload_dir
		 * @uses FileColumn::$file_maxsize
		 * @uses FileColumn::$file_types
		 * @uses Globals::post
		 * @uses Generic::clean
		 * @uses Generic::get_ext
		 * @uses WebPage::$uploads
		 * @uses WebPageLite::$user
		 * @uses User::is_admin
		 * @return string the error message.
		 */
		public function errors( $value = NULL )
		{
			if ( !$this->_errors_called )
			{
				$this->_errors_called = true;
				
				// initialize variables
				$errors = array();
				if ( is_null( $value ) )
					$value = $this->value;
				$value 	= isset( $_FILES[ $this->name ][ 'name' ] ) ? $_FILES[ $this->name ][ 'name' ] : Globals::post( $this->file_old );
				

				
				if ( !$value && $this->is_required )
					$errors = "Please upload a valid file.";
				else if ( isset( $_FILES[ $this->name ] ) && $_FILES[ $this->name ][ 'name' ] )
				{
					if ( $_FILES[ $this->name ][ 'error' ] ) 
						$errors = "An error occurred during the upload.";
					else if ( $_FILES[ $this->name ][ 'size' ] > $this->file_maxsize )
						$errors = "File is too large. The maximum size is {$this->file_maxsize} bytes.";
					else if ( !file_exists( $this->upload_dir ) )
						$errors = "Directory does not exist (" . $this->upload_dir . "). Please contact your administrator";
					else
					{
						// initialize variables
						$tmp_name 	= $_FILES[ $this->name ][ 'tmp_name' ];
						$name 		= Generic::clean( basename( $_FILES[ $this->name ][ 'name' ] ) );
						$ext		= Generic::get_ext( $name );
						
						// check file type
						if ( $this->file_types && !in_array( $ext, explode( ",", $this->file_types ) ) )
							$fld_name = "Invalid file type. (Allowed: " . $this->file_types . ")";
						else
						{
							
							$name		= md5( $name . time() );
							$this->file_name = "{$name}.{$ext}";
							$upload_dir	= $this->upload_dir . "{$name}.{$ext}";
							

							
							if ( move_uploaded_file( $tmp_name, $upload_dir ) === false && !file_exists( $upload_dir ) )
								$errors = $this->webpage->user->is_admin() ? "Unable to transfer file: $tmp_name to $upload_dir" : "Unable to transfer file.";
							else
								$this->webpage->uploads[ $this->name ] = "{$name}.{$ext}";
						}
					}
				}
				
				return $errors;
			}
		}
		
		/**
		 * Called when the row is about to be deleted, clear any non database info here...
		 * 
		 *
		 * @return bool if deleted successfully
		 */
		public function delete()
		{
			if ( file_exists( $this->upload_dir . $this->value ) && is_file( $this->upload_dir . $this->value  ) )
			{
				unlink( $this->upload_dir . $this->value );
			}
			
			return true;
		}
	}
}