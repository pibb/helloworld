<?php
#============================================================================================================
# ** Form Class
#============================================================================================================
namespace Core;

if ( !defined( "D_CLASS_FORM" ) )
{
	define( "D_CLASS_FORM", true );
	require( 'class.webpage.php' );
	require_once( 'class.recaptcha.php' );
	
	/**
 	 * File: class.form.php
	 *
 	 * @package Library
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	abstract class Form extends WebPage
	{	
		public $data = NULL;
		public $form = NULL;
		public $uploads = array();
		public $has_errors = false;
		public $auto_process = true;
		
		const ACTION_SUBMIT = "submit";
		const MODE_EDIT = "edit";
		const MODE_VALIDATE = "validate";
		const RECAPTCHA_PUBLICKEY = "6LdpRe4SAAAAAH0fFFulbUlGKvYm2OiO1UTLRLaF";
		const RECAPTCHA_PRIVATEKEY = "6LdpRe4SAAAAAIb8dIlmxoryq81YM7z7mpvcc6zM";
		const EMAIL_HEADERS = "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1'\r\nFrom: noreply@wnit.org\r\n";
		
		/**
		 * Extending classes must define $this->form (Template) and possible $this->data if that is relevant.
		 */
		abstract protected function init_form();
		
		/**
		 * Class constructor. Sets up most of the general, header, and footer template work.
		 * 
		 * @param string the title of the page if caller doesn't want to have it looked up. (Default = false)
		 * @param bool whether or not to parse the header at end.
		 * @param Array an array of page-specific CSS files to include.
		 * @param Array an array of page-specific JavaScript files to include.
		 * @param Array an array of classes to add to the body tag.
		 * @uses Column::$value
		 * @uses Column::errors
		 * @uses Data::$columns
		 * @uses Form::$auto_process
		 * @uses Form::$data
		 * @uses Form::get_data
		 * @uses Form::init_form
		 * @uses Form::not_unique
		 * @uses Form::processes
		 * @uses Form::set_form_vars
		 * @uses Webpage::$id
		 * @uses WebPage::$mode
		 * @uses Globals::get
		 * @return Form $this for method chaining.
		 */
		public function __construct( $title = false, $auto_header = false, $css = array(), $js = array(), $body_class = array() )
		{
			parent::__construct( $title, $auto_header, $css, $js, $body_class );
				
			// initialize
			$this->init_form();
			
			// check for validation callbacks
			if ( $this->mode == self::MODE_VALIDATE )
			{
				$col = Globals::get( 'x' );
				$val = Globals::get( 'y', '' );
				$this->id = (int)Globals::get( 'id', 0 );
				if ( !$this->data || !in_array( $col, array_keys( $this->data->columns ) ) )
					die( "An error occurred during validation." );
				else
				{
					$this->data->$col->value = $val;
					if ( !( $err = $this->data->$col->errors() ) )
						$err = $this->not_unique( $this->data->$col );
					die( $err );
				}
			}
			else
			{
				// finish setup
				$this->get_data();
				$this->set_form_vars();
				if ( $this->auto_process )
				$this->processes();
			}
			
			return $this;
		}
		
		/**
		 * Adds the current Data object to the database.
		 * 
		 * @uses Form::encrypt_passwords
		 * @uses Data::insert
		 * @uses Data::update
		 * @uses Data::$created
		 * @uses Data::$enabled
		 * @uses Data::$status
		 * @uses Data::$author
		 * @uses Data::$enabled_by
		 * @return string success message.
		 */
		protected function add()
		{
			// initialize variables
			$this->encrypt_passwords();
			$this->data->created = time();
			$this->data->enabled = time();
			$this->data->status = 1;
			$id = $this->data->insert();
			$this->data->author = $this->user->id;
			$this->data->enabled_by = $this->user->id;
			$this->data->update();
			return "Successfully added!";
		}
			
		/**
		 * Edits the current Data object and updates the database.
		 * 
		 * @uses Form::encrypt_passwords
		 * @uses Data::update
		 * @uses Data::$modified
		 * @uses Data::$modified_by
		 * @uses User::$id
		 * @return string success message.
		 */
		protected function edit()
		{
			// initialize variables
			$this->encrypt_passwords();
			$this->data->modified = time();
			$this->data->modified_by = $this->user->id;
			$this->data->update();
			return "Successfully updated!";
		}
			
		/**
		 * Looks for PassColumns and encrypts their values for data storage.
		 *
		 * @uses Data::$columns
		 * @uses Globals::post
		 * @uses Column::$name
		 * @uses Column::$value
		 * @uses PassColumn::encrypt
		 * @uses PassColumn::$match
		 * @uses PassColumn::$salt
		 * @uses Session::salt
		 * @return Form $this for method chaining.
		 */
		protected function encrypt_passwords()
		{
			// encrypt passwords
			foreach( $this->data->columns as $col )
			{
				if ( $col instanceof PassColumn ) 
				{
					$pass = Globals::post( $col->name );
					$pass1 = Globals::post( $col->name . "1" );
					
					// if the password field was left blank, we don't want to encrypt an already encrypted pass
					if ( ( !$col->match && $pass == $col->value ) || ( $col->match && $pass1 == $col->value ) )
					{
						$column = $col->name;
						$salt = "";
						$salty = $col->salt && isset( $this->data->salt );
						if ( $salty )
						{
							$salt = $this->session->salt();
							$this->data->salt = $salt;
						}
						$this->data->$column = $col->encrypt( $this->data->$column->value . $salt );
					}
				}
			}
			
			return $this;
		}
		
		/**
		 * If the form is posted, it will attempt to perform transactions.
		 *
		 * @uses Data::update
		 * @uses Form::$has_error
		 * @uses Form::$data
		 * @uses WebPage::anchor
		 * @uses WebPageLite::$page_id
		 * @uses WebPageLite::redirect
		 */
		public function processes()
		{
			$result = "";
			if ( $_POST && $this->data instanceof Data && !$this->has_errors )
			{
				$this->data->update();
				$result = "Successfully updated!";
			}
			
			if ( $result )
				$this->redirect( $this->anchor( $this->page_id, array( 'msg' => $result ) ) );
		}
	
		/**
		 * Organizes POST information for processing, usually with Data objects.
		 *
		 * @todo Currently, we only work with objects. We need forms that do not use Data objects.
		 * @uses Column::$name
		 * @uses Column::clean_post
		 * @uses Data::$columns
		 * @uses Form::$data
		 * @uses PassColumn::$match
		 * @return Form $this for method chaining.
		 */
		public function get_data()
		{
			if ( $_POST && $this->data instanceof Data )
			{
				foreach( $this->data->columns as $col )
				{
					$is_pass = $col instanceof PassColumn;
					$is_file = $col instanceof FileColumn;
					
					$name = $is_pass && $col->match ? ( $col->name . "1" ) : $col->name;
					if ( $col instanceof Column && (isset( $_POST[ $name ] ) || isset( $_FILES[ $name ] )) )
					{
						if ( $is_pass && !$_POST[ $col->name . "1" ] )
						{
							// we're unsetting them because no password value means we shouldn't try to validate it later
							unset( $_POST[ $col->name . "1" ] );
							unset( $_POST[ $col->name . "2" ] );
						}
						else
						{
							$column = $col->name;
							$this->data->$column = $col->clean_post();
						}
					}
				}
			}
		
			return $this;
		}
	
		/**
		 * Creates template variables from the known field list and POST'd values.
		 *
		 * @todo Only works with objects. Needs to also work with form that do not use Data objects.
		 * @uses Column::$name
		 * @uses Column::$value
		 * @uses Column::$is_required
		 * @uses Column::errors
		 * @uses Many2OneColumn::$other_table
		 * @uses Many2OneColumn::$other_prefix
		 * @uses Many2OneColumn::$other_table
		 * @uses Data::$columns
		 * @uses Database::select
		 * @uses Form::$form
		 * @uses Form::$data
		 * @uses Form::$uploads
		 * @uses Form::$has_errors
		 * @uses Form::not_unique
		 * @uses Globals::post
		 * @uses PassColumn::$match
		 * @uses Template::add_var
		 * @uses Template::add_vars
		 * @uses WebPage::anchor
		 * @uses WebPageLite::$page_id
		 * @return Form $this for method chaining.
		 */
		public function set_form_vars()
		{
			if ( $this->form instanceof Template )
			{
				$this->form->add_var( "V_VALIDATE", 'true' );
				$this->form->add_var( "U_VALIDATE", $this->anchor( $this->page_id, array( 'mode' => self::MODE_VALIDATE ) ) );
				if ( $this->data instanceof Data )
				{
					foreach( $this->data->columns as $col )
					{
						if ( $col instanceof Column )
						{
							// check for errors
							$must_match = $col instanceof PassColumn && $col->match;
							$name = $col->name . ( $must_match ? "1" : "" );
							$posted = $col instanceof FileColumn ? isset( $_FILES[ $name ] ) : isset( $_POST[ $name ] );
							$err = $not_unique = "";
							$oname = $col->name;
							$name = strtoupper( $name );
							
							
							
							// check for errors if the form was posted
							if ( $posted )
							{
								
								$err = $must_match ? $col->errors( NULL, Globals::post( $col->name . '2' ) ) : $col->errors();
								$not_unique = $this->not_unique( $col );
								
								if ( $err || $not_unique )
									$this->has_errors = true;
							}
							
							// file checking/movement would have happened by now (not when get_data is called)
							if ( $col instanceof FileColumn && isset( $this->uploads[ $col->name ] ) && $this->uploads[ $col->name ] )
								$this->data->$oname = $this->uploads[ $col->name ];
							if ( $col instanceof ImageColumn )
							{
								
								$this->form->add_var( 'U_' . $name . '_SHOW', $col->url_dir . $col->value );
							}
							
							if ( $col instanceof Many2OneColumn )
							{
								$other = Database::select( $col->other_table, "*", ( $this->mode == Website::MODE_EDIT ? $col->other_prefix . "id != '" . $this->id . "' ORDER BY " . $col->other_prefix . "id" : "1 ORDER BY " . $col->other_prefix . "id" ) );
								$this->form->add_var( "V_{$name}_LIST", $other );
							}
							

								
							// update template variables
							$this->form->add_vars( array
							(
								"V_{$name}_VAL" => $col->value,
								"V_{$name}_ERR" => $not_unique ? $not_unique : $err,
								"V_{$name}_REQ" => $col->is_required
							) );
							
							if ( $col instanceof MultiColumn && $col->options )
								foreach( $col->options as $val => $option_name )
									$this->form->add_var( strtoupper("V_{$name}_{$option_name}_VAL"), $col->value == $val );
								
							
						}
					}
				}
			}
			
			return $this;
		}
	
		/**
		 * Performs template processing for ReCaptcha API's interface.
		 *
		 * @param Template a separate form template; else uses $this->form. (Default = NULL)
		 * @uses Globals::post to get 'recaptcha_response_field' and 'recaptcha_challenge_field'
		 * @uses Form::$has_errors
		 * @uses Template::add_var
		 * @return string HTML
		 */
		public function get_captcha( Template &$form = NULL )
		{
			// initialize variables
			$response = Globals::post( 'recaptcha_response_field' );
			$challenge = Globals::post( 'recaptcha_challenge_field' );
			$resp = NULL;
			$error = NULL;
			$err_msg = false;
			
			if ( isset( $_POST[ 'recaptcha_response_field' ] ) )
			{
				if ( !$response )
				{
					$this->has_errors = true;
					$err_msg = "Please enter the phrase you see above.";
				}
				else
				{
					$resp = recaptcha_check_answer( self::RECAPTCHA_PRIVATEKEY, $_SERVER[ 'REMOTE_ADDR' ], $challenge, $response );
			
					if ( $resp->is_valid )
						$err_msg = false;
					else
					{
						$this->has_errors = true;
						$error = $resp->error;
						$err_msg = "CAPTCHA response was incorrect. Please try again.";
					}
				}
				
				if ( $form )
					$form->add_var( 'V_RECAPTCHA_ERR', $err_msg );
				else
					$this->form->add_var( 'V_RECAPTCHA_ERR', $err_msg );
			}
			
			return recaptcha_get_html( self::RECAPTCHA_PUBLICKEY, $error );
		}
		
		/**
		 * Determines if the given column has matching value in its MySQL table and must be unique
		 *
		 * @todo There's a $this->prefix reference that might be wrong.
		 * @param Column
		 * @uses Column::$name
		 * @uses Column::$unique
		 * @uses Column::$table
		 * @uses Column::$fullname
		 * @uses Column::$label
		 * @uses Database::query
		 * @uses WebPage::$id
		 * @return string error message, if any. 
		 */
		public function not_unique( Column $col )
		{
			// initialize variables
			$error = "";
			$low_name = $col->name;
			
			// check
			if ( $col->unique && ( $result = $this->db->query( "SELECT * FROM " . $col->table . " WHERE " . $col->fullname . " = '" . $this->data->$low_name . "'" . ( $this->id ? " AND " . $this->prefix . "id != '" . $this->id . "'" : "" ) . " LIMIT 1" ) ) !== false )
				if ( @mysql_num_rows( $result ) > 0 )
					$error = "That " . strtolower( $col->label ) . " has already been taken.";
			
			return $error;
		}
	}
}
?>