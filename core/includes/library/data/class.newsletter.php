<?php
#============================================================================================================
# ** Newsletter Class
#
# Version:   1.0
# Author:    Travis Shelton
#============================================================================================================
namespace Core;
if ( !defined( "D_CLASS_NEWSLETTER" ) )
{
	define( "D_CLASS_NEWSLETTER", true );
	require( __DIR__ . "/class.data.php" );
	require( __DIR__ . "/class.newslettersection.php" );
	require( __DIR__ . "/../class.xml.php" );
	
	class Newsletter extends Data
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		public	  $sections 		= array();
		protected $broken_sections	= array();
		protected $name 			= NULL;
		protected $order 			= NULL;
		protected $title			= NULL;
		protected $slug				= NULL;
		protected $final_html		= NULL;
		protected $header			= NULL;
		protected $footer			= NULL;
		
		
		const TABLE 				= Database::NEWSLETTERS;
		const PREFIX 				= Database::NEWSLETTERS_PRE;
		const MAIN_FILE				= "main.html";	
		const XML_FILE				= "main.xml";	//defaults
		const EXAMPLE_FILE			= "example.xml";	//example
		const IMAGES_DIR			= 'newsletters/';
		const GLOB_TEMPLATES_PATH	= "themes/%s/templates/";
		const NEWSLETTER_DIR		= "newsletters/";
		
		#----------------------------------------------------------------------------------------------------
		# * Static Getters
		#----------------------------------------------------------------------------------------------------
		static public function geta( $amend = "", WebPage &$webpage = NULL ) 									{ return parent::getx( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		static public function geta_many( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 				{ return parent::getx_many( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		static public function geta_many_published( $table, $prefix, $amend = "", WebPage &$webpage = NULL ) 	{ return parent::getx_many_published( __CLASS__, self::TABLE, $table, self::PREFIX, $prefix, $amend, "id", $webpage ); }
		static public function geta_published( $amend = "", WebPage &$webpage = NULL ) 							{ return parent::getx_published( __CLASS__, self::TABLE, self::PREFIX, $amend, "id", $webpage ); }
		
		#----------------------------------------------------------------------------------------------------
		# * Initializers
		#----------------------------------------------------------------------------------------------------
		protected function init_table() 	{ return self::TABLE; }
		protected function init_prefix()	{ return self::PREFIX; }
		protected function init_classname()	{ return __CLASS__; }
		
		#----------------------------------------------------------------------------------------------------
		# * Setup
		#----------------------------------------------------------------------------------------------------
		protected function setup( $data = Array() )
		{
			
			$data = parent::setup( $data );
			
			$this->def_col( "order", $data );
			$this->def_col( "title", $data, "Column", true, true );
			$this->def_col( "name", $data );
			$this->def_col( "slug", $data );
			$this->def_col( "final_html", $data );
			$this->def_col( "header", $data );
			$this->def_col( "footer", $data );
			$this->def_col( "order", $data );
			
			if ( $data )
				$this->sections = $this->_get_order( $data );
			
			
			
			return $data;
		}

		#--------------------------------------------------------------------------------------------------
		# * _create_newslettersection
		#--------------------------------------------------------------------------------------------------		
		private function _create_newslettersection( $name )
		{
			$section = new NewsletterSection( 0 );
			$section->name->value = $name;
			$section->newsparent->value = $this->id;
			$section->editor->value = $this->author->value;
			return $section->insert();
		}
	
		#--------------------------------------------------------------------------------------------------
		# * Get Slug
		#--------------------------------------------------------------------------------------------------
		public static function generate_slug( Webpage &$webpage, $term = NULL )
		{
			if ( !$term ) $term = Globals::get('slug');
			$slug = $slug_base = preg_replace( '/[^\w-_]/', '', preg_replace( '/[\s]/', '-', strtolower( addslashes( $term ) ) ) );
			if ( $slug )
			{
				// look for the slug's existance in the database
				while( $webpage->db->get_total( self::TABLE, "WHERE ".self::PREFIX."slug = '{$slug}'" ) )
					$slug = $slug_base . "-" . Generic::random_string( 5, false );
			}
			return $slug;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * _get_order
		#--------------------------------------------------------------------------------------------------
		private function _get_order( $data, $repair_sections = true ) {
			$order = explode( ',' , $this->order->value );

			
			if ( $repair_sections ) {
			
				//Required Sections
					// existing sections
					$sections = Database::select( Database::NEWSLETTERSECTIONS, Database::NEWSLETTERSECTIONS_PRE . 'id , '  . Database::NEWSLETTERSECTIONS_PRE . 'name' , Database::NEWSLETTERSECTIONS_PRE . 'newsparent = ' . (int)$this->id  );
					
					//array of REQUIRED sections
					$subtemplates = self::get_sections( $this->webpage, $this->name->value );
					
					
					//marker for required sections, default to false ( not found )
					$marker = array(); foreach ( $subtemplates as $key => $template ) $marker[ $key ] = false;
					
					
		
					//determine which required sections exist
					foreach ( $subtemplates as $key => &$template_name ) {
						$found = false;
						$found_key = NULL;
						foreach( $sections as $section ) 
							if ($marker[ $key ] == false &&
								$section[ Database::NEWSLETTERSECTIONS_PRE . 'name' ]  == $template_name ) {
								$found = true;
								$found_key = $key;
								break;
							}
						if ( $found ) {
							$marker[ $found_key ] = true;
						}
					}
					

				
					
					foreach( $marker as $key => &$value ) 
						if ( $value === false ) 
							//required section wasn't found, so create it
							$this->_create_newslettersection( $subtemplates[ $key ] );
				
				
				//Reorder existing Sections
				

					//get existing sections
					$sections = Database::select( Database::NEWSLETTERSECTIONS, Database::NEWSLETTERSECTIONS_PRE . 'id , '  . Database::NEWSLETTERSECTIONS_PRE . 'name' , Database::NEWSLETTERSECTIONS_PRE . 'newsparent = ' . (int)$this->id  );
					
					$new_order = array();
					
					foreach( $order as $order_number ) {
						$order_number = (int)$order_number;
						foreach ( $sections as &$section ) 
							if ( $order_number == (int)$section[ Database::NEWSLETTERSECTIONS_PRE . 'id' ] &&
								 !in_array( $order_number, $new_order ) ) {
								$new_order[] = $order_number;
								break;
							}	
					}
					
					
					
					//priorize the hardcoded sections over the other ones if needed
					$unordered_segments = array();
					$unordered_segments_stage_2 = array();
					$unordered_segments_stage_mark = array();
					foreach ( $sections as &$section ) {
						$id = (int)$section[ Database::NEWSLETTERSECTIONS_PRE . 'id' ];
						if ( !in_array( $id, $new_order   )) 
							$unordered_segments [] = $id;
					}
					
					
					foreach ( $unordered_segments as $us_key => $us ) {
						$unordered_segments_stage_mark[ $us_key ] = false;
					}
					
					$required_sections = self::get_sections( $this->webpage, $this->name->value );
					
					foreach ( $required_sections as $rs ) {
						$rs = strtolower( $rs );
						foreach( $unordered_segments as $us_key => $us ) {
							$us_name = Database::select( Database::NEWSLETTERSECTIONS, Database::NEWSLETTERSECTIONS_PRE . 'id , '  . Database::NEWSLETTERSECTIONS_PRE . 'name' , Database::NEWSLETTERSECTIONS_PRE . 'id = ' . (int)$us  );
							$us_name = strtolower( $us_name[0][ Database::NEWSLETTERSECTIONS_PRE . 'name' ] );

							if ( $rs == $us_name && $unordered_segments_stage_mark[ $us_key ] == false ) {
								$unordered_segments_stage_2[] = $us;
								$unordered_segments_stage_mark[ $us_key ] = true;
								break;
							}
						}
					}
					
					foreach( $unordered_segments_stage_2 as $s ) 
						$new_order []= $s;

					foreach( $unordered_segments_stage_mark as  $us_key => $mark ) 
						if ( $mark === false )
							$new_order []= $unordered_segments[ $us_key ];
								

					$order = $new_order;
	
					
					
					$new_order = implode( ',', $new_order );
					
					// run query
					$query = 'UPDATE ' . Database::NEWSLETTERS . ' SET '. Database::NEWSLETTERS_PRE . "order = '" . $new_order . "' WHERE " . Database::NEWSLETTERS_PRE . 'id = ' . (int)$this->id . ' LIMIT 1';
					//var_dump( $query );
					$this->webpage->db->query( $query );
			}
			
			return $order;
		}
		

		#----------------------------------------------------------------------------------------------------
		# * rearange_section
		#----------------------------------------------------------------------------------------------------
		public function rearange_section( $section_index, $new_section_index  ) 
		{
			

			if ( isset( $this->sections[ $section_index ] ) &&
				 isset( $this->sections[ $new_section_index ] )) {
				
				
				
				$n = explode( ',' , $this->order->value );
				

				
				$t = $n[ $section_index ];
				$n[ $section_index ] = $n[ $new_section_index ];
				$n[ $new_section_index ] = $t;
				
				$t = $this->sections[ $section_index ];
				$this->sections[ $section_index ] = $this->sections[ $new_section_index ];
				$this->sections[ $new_section_index ] = $t;
				
				$new_order = implode( ',' , $n );
				

				
				
				$this->order->value = $new_order;
				
				$query = "UPDATE " . Database::NEWSLETTERS . ' SET  ' . Database::NEWSLETTERS_PRE . "order = '$new_order' WHERE " . Database::NEWSLETTERS_PRE . 'id = ' . $this->id . " LIMIT 1";
				Database::query( $query );
				
			}
			
			return true;
		}
		

		
		
		#----------------------------------------------------------------------------------------------------
		# * parse
		# $webpage - Reference for template objects
		# $use_defaults -  if TRUE then defaults are enabled (they are applied before example  and db data)
		# $template - if STRING then is the name of the template e.g. "monthly"...will parse the example data, and optionally the defaults
		#			  if INT then will expect the id of an existing newsletter
		#			  can also be instance of Newsletter
		# $use_object_vars - if TRUE use $this->vars to override whatever the above put in
		# $highlight_section -  int - id of a Newslettersection Database row to be highlighted
		#							should be one of the ids in $this->sections
		# $edit_section - boolean  - whethor or not to enable JS editing
		# $plain_page - remove  excess html/js/classes
		# $enable_final_html - whether or not to use "final_html" if it exists
		#----------------------------------------------------------------------------------------------------
		static public function parse( Webpage &$webpage, $template, $opts = Array()  ) 
		{

			
			
			$default_opts = Array(
				"use_defaults" => FALSE,	
				"use_object_vars" => true,	
				"highlight_section" => false,
				"edit" => false,		
				"with_actions" => false,
				"plain_page" => false,	
				"block_changable" => true,	
				"enable_final_html" => true, 
			);	

			foreach( $default_opts as $optname => $value )
				if ( !isset( $opts[ $optname ] ) )
						$opts[ $optname ] = $value;
			
			$enable_final_html = $opts[ 'enable_final_html' ];
			$highlight_section = $opts[ 'highlight_section' ];
			
			
			// initialize variables
			if  ( !($template instanceof Newsletter) ) {
				if ( is_int( $template ) ){
					$template = new Newsletter( $template, $webpage );
				} 
			}
			
			
			
			$section_editable = Array();
			$path = "";
			$filename = "";
			$template_name = "";
			if ( $template instanceof Newsletter ) {
				
				if (  $enable_final_html && $template->final_html->value ) {
					return $template->final_html->value; 
					exit;
				}
				
				$path = self::location_of( $webpage, $template->name->value );
				$filename = strtolower( $template->name->value ) . ".xml";
				$template_name = $template->name->value;
			} else {
				$path = self::location_of( $webpage, $template );
				$filename = Newsletter::MAIN_FILE;
				$template_name = $template;
			}
			
			
			
			//The fallback path if there is no local template directory is
			///home/wnittv/public_html/themes/classic/templates/[path]
			if ( $template instanceof Newsletter ) 
			{
				$rel_prefix = '../../../'.Website::ADMIN_DIR.'themes/' . $webpage->theme . '/templates/' . self::NEWSLETTER_DIR;
				$local_prefix = $webpage->site->local .Website::ADMIN_DIR.'themes/' . $webpage->theme . '/templates/' . self::NEWSLETTER_DIR;
				
			
				if ( !file_exists( $local_prefix . 'headers/' . $template->header->value ) )
					$header_file = $rel_prefix . 'headers/header.html';
				else
					$header_file = $rel_prefix . 'headers/' . $template->header->value;
					
				if ( !file_exists( $local_prefix . 'footers/' . $template->footer->value ) )
					$footer_file = $rel_prefix . 'footers/footer.html';
				else
					$footer_file = $rel_prefix . 'footers/' . $template->footer->value;
					
				$header 		= new Template( $webpage, $header_file );
				$footer 		= new Template( $webpage, $footer_file );
			} else 
			{
				$header 		= new Template( $webpage, '../../../'.Website::ADMIN_DIR.'themes/' . $webpage->theme . '/templates/' . self::NEWSLETTER_DIR . 'headers/header.html');
				$footer 		= new Template( $webpage, '../../../'.Website::ADMIN_DIR.'themes/' . $webpage->theme . '/templates/' . self::NEWSLETTER_DIR . 'footers/footer.html');
			}
			
			
			
			$html = "";
			$help_sections = array();
			$highlight_section_n = (int)$highlight_section;

			
			
			
			$section_blocks = array();

			if ( !($template instanceof Newsletter) ) {
				$sections = self::get_sections_from( $webpage, $template );
				
				foreach( $sections as $s ) {
					$s = array('template'=>$template,'section'=>strtolower( $s ));
					$html .= Newslettersection::parse(  $webpage, $s, Array( "use_defaults" => $use_defaults, "use_object_vars" => $use_object_vars ));
				}
			} else {	

				$query = sprintf("SELECT %sauthor FROM %s WHERE %sid = %d LIMIT 1",
								 Database::NEWSLETTERS_PRE,
								 Database::NEWSLETTERS,
								 Database::NEWSLETTERS_PRE,
								 $template->id);
				
				$r = $webpage->db->query( $query );
				$newsletter_creator = (int)mysql_result( $r, 0 );
			
				foreach( $template->sections as $s ) {
					
					$pending = false; //Processor::row_is_pending( Database::NEWSLETTERSECTIONS, Database::NEWSLETTERSECTIONS_PRE, $s );
					$published = false; //Database::row_is_published( Database::NEWSLETTERSECTIONS, Database::NEWSLETTERSECTIONS_PRE, $s );
					
					$s = new Newslettersection( $s, $webpage );
					$section_blocks[ $s->id ] = Array( "blocks1" => $s->blocks->value,
													   "blocks2" => $s->blocks2->value,
													   "blocks3" => $s->blocks3->value,
													   "blocks4" => $s->blocks4->value,
														"cols"	 => self::get_column_count( $webpage, $s->assortment->value  ));
					

					$editable = !$pending && !$published && ( $webpage->user->id == $template->author || $webpage->user->id == $s->editor->value  );
					
					$authors = $template->getAuthors( $webpage );
					$publishable = !$pending && !$published && ( in_array( $webpage->user->id, $authors) || $webpage->user->is_admin());
					$approveable = $mods 	= $webpage->db->select( Database::MODERATORS, "*", "moderator_access = '" . Database::NEWSLETTERSECTIONS . "' AND moderator_user = " . $webpage->user->id );
			
					if ( !$editable && $highlight_section_n == $s->id ) {
						$highlight_section = false;
					}
					
					
					$section_editable[ $s->id ] = (int)$editable;
					
					
					
					
					$show_help = $s->modified == $s->created;
					$show_section_html = true;

					if ( $show_help  )
					{
						$status = $published ? "Published" : ( $pending ? "Pending Approval" : "Not Ready" );
						$user = new User( $s->editor->value );
						$s_opts = self::get_options( $webpage, $template->name->value, $s->name->value );
						$section_help = $s_opts[ 'HELPTEXT' ];
						$help = new Template( $webpage, "account_newsletter_help.html" );
						if ( $webpage->user->id == $s->editor->value ) 
						{
							$help->add_var( 'IS_EDITOR', true );
							
						} else
							$show_section_html = false;
						$help->add_var( 'V_SECTION_ID', 		$s->id );
						$help->add_var( 'V_FRIENDLY_SECTION', 	Newsletter::friendly_name( $s->name->value) );
						$help->add_var( 'V_EDITOR', 			$webpage->user->id == $s->editor->value ? 'You' : ($user->fname . " " . $user->lname) );
						$help->add_var( 'V_INSTRUCTIONS', 		$section_help );
						$help->add_var( 'V_EDITOR_EMAIL', 		$user->email );
						$help->add_var( 'V_ADMIN_EMAIL', 		$webpage->site->company_email );
						$help->add_var( 'V_STATUS', 			$status );
						$help->add_var( 'V_NEWSLETTER_TOUR_LINK', $webpage->anchor( $webpage->page_id, array( "mode" => NewsletterPage::MODE_TOUR )  ) );
						$help = $help->parse( false  );
						$html .= $help;
						$help_sections []= $s->id;
					}
					
					if ( $show_section_html )
					{
						if ( $opts[ 'edit' ] && $editable  ) {
							if ( $highlight_section  ) {
							
								$s_opts = Array
								( 
										"use_defaults" => $use_defaults,
										"use_object_vars" => $use_object_vars,
										"class" => $highlight_section_n == $s->id ? "preview-highlight " : "preview-lolight  ",
										"edit" => $editable,
										"with_actions" => true 
								);
								
								
								$html .= Newslettersection::parse(  $webpage, $s, $s_opts );
							} else {
								$highlight_section = false;
								$s_opts = Array
								( 
										"use_defaults" => $opts['use_defaults'],
										"use_object_vars" => $opts['use_object_vars'],
										"class" => "preview-lolight " ,
										"edit" =>  $editable,
										"with_actions" => true 
								);
								
								$html .= Newslettersection::parse(  $webpage, $s,  $s_opts   );
								
							}
						} else {
							
							if ( $highlight_section ) {
								
								$html .= Newslettersection::parse(  $webpage, $s, Array( "use_defaults" => $use_defaults, "edit" => $editable, "with_actions" => !$plain_page, "use_object_vars" => $use_object_vars ) );
							} else {
								$s_opts = Array( 
									"use_defaults" => $opts[ 'use_defaults' ],
									"edit" => false,
									"class" => "preview-lolight ", 
									"with_actions" => !$opts[ 'plain_page' ] /*&& ($publishable || $approveable)*/,
									"use_object_vars" =>  $opts[ 'use_object_vars' ],
									"plain_page" => $opts[ 'plain_page' ]
									);
								$html .= Newslettersection::parse(  $webpage, $s, $s_opts );
							}
						}
					}
					
					
	
				}
			}		
			
			
			
			$header->add_var( 'U_URL' , $webpage->anchor( MAIN_INDEX ) );
			if ( $template instanceof Newsletter && defined( "ENEWSLETTER_ARTICLES" ) ) 
				$header->add_var( 'U_URL' , $webpage->anchor( ENEWSLETTER_ARTICLES, array( 'slug' => $template->slug->value ) )   );
			
			
			
			
			
			$body = "";
			if ( !$opts['plain_page'] ) $body = "<div id=\"newsletter\">\r\n";
			$body .=  $html ;
			if ( !$opts['plain_page'] ) $body .= "\r\n</div>\r\n";
			

			$blanked_style =  $highlight_section || $opts['edit'] ? 'hide' : '';
			if ( $highlight_section == false && $opts['edit'] ) 
				$blanked_style =  'hide fulledit';
			
			$footer->add_var( 'V_NONE_BLANKED', '<div id="blanked" class="'.$blanked_style.'"></div>' );
			if ( $opts['plain_page'] || ( $template instanceof Newsletter && count( $template->sections ) < 2 ) )
					$footer->add_var( 'V_NONE_BLANKED', '' );

			
			
			
			if ( $highlight_section || $opts['edit'] ) {
				
				$header->add_var('V_EDITOR_CSS','@import url("'.$webpage->site->url . Website::ADMIN_DIR . 'themes/' . $webpage->theme . '/css/admin_newsletters_editor.css");   @import url("http://www.wnit.org/themes/classic/css/fancybox.css");');
				
				$editor_js = '
						<script type="text/javascript" src="'.$webpage->site->url.'includes/js/jquery.js"></script>
						<script type="text/javascript" src="'.$webpage->site->url.'includes/js/tiny_mce/jquery.tinymce.js"></script>
						<script type="text/javascript" src="'.$webpage->site->url.'includes/js/jquery.fancybox.js"></script>
						<script type="text/javascript" src="'.$webpage->site->url.'includes/js/jquery.form.js"></script>
						<script type="text/javascript" src="'.$webpage->site->url.Website::ADMIN_DIR.'includes/js/jquery-ui.js"></script>
						<script type="text/javascript" src="'.$webpage->site->url.Website::ADMIN_DIR.'includes/js/newsletters_editor_blocks.js"></script>
						<script type="text/javascript" src="'.$webpage->site->url.Website::ADMIN_DIR.'includes/js/newsletters_editor_sections.js"></script>
						<script type="text/javascript" src="'.$webpage->site->url.Website::ADMIN_DIR.'includes/js/newsletters_editor_form.js"></script>
						<script type="text/javascript" src="'.$webpage->site->url.Website::ADMIN_DIR.'includes/js/newsletters_editor_articles.js"></script>
						<script> var callback_section_save 		= "'. $webpage->anchor( $webpage->page_id, array( 'mode' => NewsletterPage::MODE_CALLBACK, 'cb' => NewsletterPage::CALLBACK_SAVE_SECTION, 'id' => $template->id, 'naked' => 1 ) , true, false ) .'" </script>
						<script> var callback_gallery_chooser 	= "'. $webpage->anchor( $webpage->page_id, array( 'mode' => NewsletterPage::MODE_GALLERY, 'id' => $template->id, 'naked' => 1 ), true, false ) .'" </script>
						<script> var callback_block_edit 		= "'. $webpage->anchor( $webpage->page_id, array( 'mode' => NewsletterPage::MODE_CALLBACK, 'cb' => NewsletterPage::CALLBACK_BLOCK_EDIT, 'id' => $template->id, 'naked' => 1 ), true, false ) .'" </script>
						<script> var callback_article_edit 		= "'. $webpage->anchor( $webpage->page_id, array( 'mode' => NewsletterPage::MODE_CALLBACK, 'cb' => NewsletterPage::CALLBACK_ARTICLE_EDIT, 'id' => $template->id, 'naked' => 1 ), true, false ) .'" </script>
						<script> var auto_edit_section 			= '. (int)$highlight_section  .' </script>
						<script> var help_sections 				= "'. implode( ',', $help_sections )  . '"; </script>
						<script type="text/javascript" src="'.$webpage->site->url . Website::ADMIN_DIR . 'includes/js/newsletters_editor.js"></script>';
			
				

				$block_library = self::search_blocks( $webpage );
			
				$editor_js .= "\r\n<script>\r\nvar init_block_library = {};\r\n";
				foreach( $block_library as  $block ) {
					$count = Newsletterarticle::get_article_count( $webpage, $block );
					$editor_js .= "init_block_library[ '$block' ] =  $count;\r\n";
				}
				
				
				$editor_js .= "\r\n\r\nvar init_section_blocks = {};\r\n";
				foreach( $section_blocks as $section_id => $data ) {
					$editor_js .= "init_section_blocks[ $section_id ] =  Array();\r\n";
					
					$section_block1 = $data[ 'blocks1' ];
					$editor_js .= "init_section_blocks[ $section_id ].push( \"$section_block1\" );\r\n";
					
					if ( $data[ 'cols' ] > 1 ) {
						$section_block2 = $data[ 'blocks2' ];
						$editor_js .= "init_section_blocks[ $section_id ].push( \"$section_block2\" );\r\n";
					}
					
					if ( $data[ 'cols' ] > 2 ) {
						$section_block3 = $data[ 'blocks3' ];
						$editor_js .= "init_section_blocks[ $section_id ].push( \"$section_block3\" );\r\n";
					}
					
					if ( $data[ 'cols' ] > 3 ) {
						$section_block4 = $data[ 'blocks4' ];
						$editor_js .= "init_section_blocks[ $section_id ].push( \"$section_block4\" );\r\n";
					}
				}
				
				
				$editor_js .= "\r\ninit_nl_name =  '{$template->name->value}';\r\n";
				
				$editor_js .= "\r\n\r\nvar init_section_article_type = {};\r\n";
				$editor_js .= "\r\n\r\nvar init_section_name = {};\r\n";
				foreach( $section_blocks as $section_id => $data ) {
					$section = new Newslettersection( $section_id, $webpage );
					$opts = self::get_options( $webpage, $template->name->value, $section->name->value );
					
					$type = isset($opts[ 'ARTICLES' ]) ? $opts[ 'ARTICLES' ] : 'simple';
					$editor_js .= "init_section_article_type[ $section_id ] =  '$type';\r\n";
					$editor_js .= "init_section_name[ $section_id ] =  '{$section->name->value}';\r\n";

				}
				
				

				
				$editor_js .= "\r\n\r\nvar init_section_editable = {};\r\n";
				foreach( $section_blocks as $section_id => $data ) {
					$editable = $section_editable[ $section_id ];
					$editor_js .= "init_section_editable[ $section_id ] =  $editable;\r\n";
				}

				
				$editor_js .= "\r\nvar init_section_articles = {};\r\n";
				foreach( $section_blocks as $section_id => $data ) {
					$s = new Newslettersection( $section_id , $webpage );

					$editor_js .= "init_section_articles[ $section_id ] =  Array();\r\n";
					for ( $column_number = 0; $column_number < $data[ 'cols' ]; $column_number++ ) {
						
						$editor_js .= "(function(){var _articles = Array();\r\n";
						foreach( $s->articles as $a ) {
							$a = new NewsletterArticle( (int)$a );
							
							$block = $a->block->value;
							$index = $a->index->value;
							$column = $a->column->value;
							
							if ( $column == $column_number ) 
								$editor_js .= "_articles.push( {\"$block,$index\":\"{$a->id}\"} );\r\n";
							
						}
						$editor_js .= "init_section_articles[ $section_id ].push( _articles );})();\r\n";
					}

					
				}
				$editor_js .= "</script>\r\n";
				
				$header_select_box = "";
				$footer_select_box = "";
				if ( $webpage->user->is_admin() ) {
					$header_library = self::search_headers( $webpage );
					$footer_library = self::search_footers( $webpage );
					$header_select_box .= "<div>Change Header: <select>\r\n";
					foreach ( $header_library as $head ) {
						$selected = $head == $template->header->value ? ' selected ' : '';
						$header_select_box .= "<option value=\"$head\" $selected >$head</option>";
					}
					$header_select_box .= "</select></div>\r\n";
					
					$footer_select_box .= "<div>Change Footer: <select>\r\n";
					foreach ( $footer_library as $foot ) {
						$selected = $foot == $template->footer->value ? ' selected ' : '';
						$footer_select_box .= "<option value=\"$foot\" $selected >$foot</option>";
					}
					$footer_select_box .= "</div></select>\r\n";
				}
				
				$wrench = $webpage->site->url . Website::ADMIN_DIR . "themes/" . $webpage->theme . "/images/icons/wrench.png";
				$header->add_var('V_EDITOR_TOP', "");
				if ( $webpage->user->is_admin() ) 
					$header->add_var('V_EDITOR_TOP', "<div id=\"editor-top\" x-newsletter-id=\"$template->id\"><a href=\"".$webpage->anchor( $webpage->page_id, array( "id" => $template->id, "mode" => NewsletterPage::MODE_CALLBACK, 'cb' => NewsletterPage::CALLBACK_SETTINGS, "naked" => 1 ) )."\"><img src=\"$wrench\"> Newsletter settings</a></div>");
				$header->add_var('V_EDITOR_JS', $editor_js);
				
			} else {
				$header->add_var('V_EDITOR_CSS','');
				$header->add_var('V_EDITOR_JS','');
				$header->add_var('V_EDITOR_TOP', '');
			}
			

			
			return $header->parse(false) . $body  . $footer->parse(false);
		}

		#----------------------------------------------------------------------------------------------------
		# * edit_actionsbuttons_html
		# The actions in the inline newlsetter editor
		#----------------------------------------------------------------------------------------------------
		public function edit_actionsbuttons_html( Webpage $webpage = NULL, $edit = false ) {
			$html = "";
			
			$pending = $this->is_pending();
			$published = $this->is_published();

			$newsletter_creator = 0;
			$editable = !$pending && !$published && ($this->user->id == $this->editor->value ||  $this->user->id == $newsletter_creator);
			$publishable = !$pending && !$published && ($this->user->id == $this->editor->value ||  $this->user->id == $newsletter_creator);
			
			if ( $editable ) {
				$html .= "<img class=\"edit-pencil\" src=\"http://www.wnit.org/themes/classic/images/icons/pencil.png\"><br />";

			} 

			return $html;
		}
		

		#----------------------------------------------------------------------------------------------------
		# * get_images
		# returns an array of images related to this newsletter, e.g. array( 'banner' => array( '0123456789abcdefg.jpg', '0123456789abcdefg.jpg' ) )
		#----------------------------------------------------------------------------------------------------
		public function get_images( Webpage &$webpage ) {
			$images = array();
			 
			$path = $webpage->site->local . Website::IMAGES_DIR .self::IMAGES_DIR . $this->id . DIRECTORY_SEPARATOR;
			if ( !file_exists( $path ) ) mkdir( $path );
			$dir = dir( $path );
			while ( $d = $dir->read() ) 
			{
				if ( $d == '..' ) continue;
				if ( is_dir( $path . $d ) )
				{
					$images[ $d ] = array();
					$subpath = $path . $d . DIRECTORY_SEPARATOR;
					$subdir = dir( $subpath );
					while ( $sd = $subdir->read() ) 
					{
						if ( $sd == '.' || $sd == '..' ) continue;

						if ( is_file( $subpath . $sd ) ) $images[ $d ] []= $sd;
						
					}
				}
				
			}
			 
			return $images;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * location_of 
		# LOCAL path of the given [template[/section] ]
		# e.g. "/etc/......./public_html/.....[/monthly[/1_highlights]]"
		#--------------------------------------------------------------------------------------------------
		static public function location_of( Webpage $webpage, $template = NULL , $section = NULL )
		{
			$loc = "";
			if ( $webpage ) {
				$loc = $webpage->site->local .  Website::ADMIN_DIR . sprintf( self::GLOB_TEMPLATES_PATH, $webpage->theme ) . self::NEWSLETTER_DIR;
			} else {
				$loc = __DIR__ . "/../../../" . Website::ADMIN_DIR . sprintf( self::GLOB_TEMPLATES_PATH, self::DEFAULT_THEME ) . self::NEWSLETTER_DIR;
			}
			
			if ( $template ) {
				$loc = $loc . $template;
				if ( $section ) {
					$subsections = self::search_sub_formats( $template, $webpage );
					$section = strtolower( $section );
					foreach( $subsections as $k => $s ) {
						$s = strtolower( $s );
						if ( $s == $section ) {
							$loc = $loc .  DIRECTORY_SEPARATOR . $k . '_' . $s;
							break;
						}
					}
				} 
			}
			

			
			return $loc;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * get_xml		
		#----------------------------------------------------------------------------------------------------
		static public function get_xml(  Webpage $webpage, $template ) 
		{
			return XML::parse( self::location_of( $webpage, $template ) . ".xml" );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * get_sections_from_xml
		# $template - name of the template
		#----------------------------------------------------------------------------------------------------
		static public function get_sections( Webpage $webpage, $template ) 
		{
			// initialize variables
			$options =  array();
			
			if ( $xml_map = self::get_xml( $webpage, $template ) ) 
					if ( $xml = XML::tree( $xml_map, Array( "NEWSLETTER", "SECTIONS" ) ) )
					{
						$count = 0;
						foreach( $xml as $x ) { 
							if ( isset( $x['SECTION'] ) )
								foreach( $x as $k => $i ) {
									$options[ $count+1  ] = self::friendly_name( $i[ 'attributes' ][ 'NAME' ] );
									$count++;
								}
						}
					}
					
			return $options;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * get_common_images
		#  returns an array of images in the common dir array( 'banner' => array( '0123456789abcdefg.jpg', '0123456789abcdefg.jpg' ) )												  																	
		#----------------------------------------------------------------------------------------------------
		static public function get_common_images( Webpage &$webpage ) {
			$images = array();


			$path = $webpage->site->local . Website::IMAGES_DIR .self::IMAGES_DIR . 'common' . DIRECTORY_SEPARATOR;
			
			if ( !file_exists( $path ) ) mkdir( $path, 0777, true );
			
			$dir = dir( $path );
			while ( $d = $dir->read() )
			{
				if ( $d == '..' ) continue;
				if ( is_dir( $path . $d ) )
				{
					$images[ $d ] = array();
					$subpath = $path . $d . DIRECTORY_SEPARATOR;
					$subdir = dir( $subpath );
					while ( $sd = $subdir->read() )
					{
						if ( $sd == '.' || $sd == '..' ) continue;
						if ( is_file( $subpath . $sd ) ) $images[ $d ] []= $sd;
						
					}
				}
				
			}
			 
			return $images;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * friendly_name
		#----------------------------------------------------------------------------------------------------
		static public function friendly_name( $name ) 
		{
			return ucwords( strtolower( $name ) );;
		}
		
		
		#----------------------------------------------------------------------------------------------------
		# * Get Files
		#----------------------------------------------------------------------------------------------------
		static public function get_files( Webpage $webpage, $type = "*", $directory = "." ) 
		{
			// initialize variables
			$files 	= array();
			if ( $webpage instanceof Webpage )
				$path = $webpage->site->local . Website::ADMIN_DIR . sprintf( self::GLOB_TEMPLATES_PATH, $webpage->theme ) . self::NEWSLETTER_DIR   . "$directory/" . $type;
			else 
				$path = __DIR__ . "/../../../" . Website::ADMIN_DIR . sprintf( self::GLOB_TEMPLATES_PATH, self::DEFAULT_THEME )  . self::NEWSLETTER_DIR  . "$directory/" . $type;
			
			
			$files = glob( $path );
			
			return $files;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * _search
		#----------------------------------------------------------------------------------------------------
		static private function _search( Webpage &$webpage, $folder ) 
		{	
			$files = array();
			$block_files = self::get_files( $webpage, "*.html", $folder );
			foreach ( $block_files as &$block ) {
				$block = basename( $block );
				if ( strpos( $block, "." ) !== false ) {
					list ( $block, $ext ) = explode( ".", $block, 2 );
					$files[] = strtolower( $block );
				}
			}
			return $files;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * get_section_attributes
		#----------------------------------------------------------------------------------------------------
		static public function get_section_attributes( Webpage &$webpage, $template, $section ) 
		{
			$result = NULL;
			

			$xml = self::get_xml( $webpage, $template );
			$xml = XML::tree( $xml, Array( "NEWSLETTER", "SECTIONS" ) );
			foreach( $xml as &$x )
			{
				$ax = array(&$x);
				if ( $section_xml = XML::attributes( $ax, Array( "SECTION" ) ))
				{
					
					if ( strtoupper( $section ) == strtoupper( $section_xml[ 'NAME' ] ) )
					{
							$result = $section_xml;
							break;
					}
				}
			}
				
			return $result;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * get_options
		# $template - name of the template
		# $section - optional name of the template
		#----------------------------------------------------------------------------------------------------
		static public function get_options( Webpage &$webpage, $template, $section = NULL ) 
		{
			
			
			
			// initialize variables
			$options =  array();
			
			if ( $section ) 
			{
				$default_options = array( "HELPTEXT" => "", "NAME"=>"untitled", "ASSORTMENT"=>"single", "ARTICLES"=>"" );
				
				$options = self::get_section_attributes( $webpage, $template, $section );
				
				foreach( $options as $k => &$v )
					$default_options[ $k ] = $v;
				
				return $default_options;
			} else
			{
				$options = array( "HEADER" => "header.html", "FOOTER" => "footer.html" );
				
				if ( $xml_map = self::get_xml( $webpage, $template ) ) 
				if ( $xml = XML::tree( $xml_map, Array( "NEWSLETTER", "SETTINGS" ) ) )
				{
					foreach( $xml as $indx => &$value ) 
						foreach( $value as $k => $i ) 
							$options[ $k ] = $i[ 'value' ];
				}
			}


			return $options;
		}
		

		#----------------------------------------------------------------------------------------------------
		# * search_templates
		# returns an array of the available "level 1" templates
		#----------------------------------------------------------------------------------------------------
		static public function search_templates( Webpage &$webpage ) 
		{
			// initialize variables
			$templates 	= array();
			$dirs 		= glob( sprintf( self::GLOB_TEMPLATES_PATH, $webpage->theme ) . self::NEWSLETTER_DIR .  "*.xml" );
			
			// look through the various directories for ones with main files
			foreach( $dirs as $i => $d )
			{
				$basename = basename( $d );
				if ( strpos( $basename, "." ) !== false ) {
					
					list( $basename, $ext ) = explode( ".", $basename,  2 );
				}
				$templates[] = $basename;
			}
			
			
			
			foreach ( $templates as $key => $template_name ) {
				$defaultdata = self::get_options( $webpage, $template_name  );
				if ( isset($defaultdata[ 'DISABLED' ]) ) unset( $templates[ $key ] ); 
			}
			
			
			
			return $templates;
		}

		#----------------------------------------------------------------------------------------------------
		# * search_articles
		# returns an array of the available blocks
		#----------------------------------------------------------------------------------------------------
		static public function search_articles( Webpage &$webpage ) 
		{				
			return self::_search( $webpage, 'articles' );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * search_blocks
		# returns an array of the available blocks
		#----------------------------------------------------------------------------------------------------
		static public function search_blocks( Webpage &$webpage ) 
		{	
			return self::_search( $webpage, 'blocks' );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * search_headers
		# returns an array of the available headers
		#----------------------------------------------------------------------------------------------------
		static public function search_headers( Webpage &$webpage ) 
		{	
			return self::_search( $webpage, 'headers' );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * search_assortments
		# returns an array of the available assortments
		#----------------------------------------------------------------------------------------------------
		static public function search_assortments( Webpage &$webpage ) 
		{	
			//echo "<pre>";
			//debug_print_backtrace( 2 );
			return self::_search( $webpage, 'assortments' );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * search_footers
		# returns an array of the available blocks
		#----------------------------------------------------------------------------------------------------
		static public function search_footers( Webpage &$webpage ) 
		{	
			return self::_search( $webpage, 'footers' );
		}
		
		#----------------------------------------------------------------------------------------------------
		# * get_column_count
		#----------------------------------------------------------------------------------------------------
		static public function get_column_count( Webpage &$webpage, $assortment ) {
			$count = 0;
			
			$filename = strtolower( $assortment . ".html" );
			$template_path = '/../../../'.Website::ADMIN_DIR.'themes/' . $webpage->theme . '/templates/' . Newsletter::NEWSLETTER_DIR  . 'assortments/' . $filename;
			$fields = NewsletterSection::get_fields( $webpage, $template_path );
			foreach( $fields as &$f ) 
				if ( $f[0] == 'COLUMN' ) 
					$count++;
				
			return $count;
		}
		

		#----------------------------------------------------------------------------------------------------
		# * update_status
		#----------------------------------------------------------------------------------------------------
		public function update_status( User &$user ) 
		{

			$pending = $this->is_pending();
			if ( $pending )
			{
				$this->status = Data::PENDING;
			} else {
				if ( $this->is_published() )
					$this->status = Data::APPROVED;
				else
					$this->status = Data::DENIED;
			}
			
			
			$this->update();
			if ( $this->status == Data::APPROVED )
				$this->finalize_html();
				
			return true;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * is_pending
		#----------------------------------------------------------------------------------------------------
		public function is_pending() 
		{

			$result = false;

			foreach ( $this->sections as $s ) 
			{
				$s = new Newslettersection( $s, $this->webpage );
				if ( $s->is_pending() ) 
				{
					$result = true;
					break;
				}
			}
			
			
			
			return $result;
		}
		
		
		#----------------------------------------------------------------------------------------------------
		# * is_newsletter_published
		#----------------------------------------------------------------------------------------------------
		public function is_published() 
		{
			$result = false;
			
			if ( $this->id ) 
			{
				$result = true;
				foreach ( $this->sections as $s ) 
				{
					$s = new Newslettersection( $s, $this->webpage );
					if ( !$s->is_published() ) 
					{
						$result = false;
						break;
					}
				}
			}
			
			
			return $result;
		}
		
		#--------------------------------------------------------------------------------------------------
		# * finalize_html
		#--------------------------------------------------------------------------------------------------
		public function finalize_html()
		{
			$opts = Array( 'plain' => true );
			$html = self::parse( $this->webpage, $this, $opts );
			$this->final_html->value = $html;
			$this->update();
		}
		
		#--------------------------------------------------------------------------------------------------
		# * finalize_html
		#--------------------------------------------------------------------------------------------------
		public function getAuthors( Webpage &$webpage )
		{
			$authors = array();
			
			foreach( $this->sections as $section )
			{
				//$sql = sprintf("SELECT %seditor FROM %s WHERE %snewsparent = %d", Database::NEWSLETTERSECTIONS_PRE, Database::NEWSLETTERSECTIONS, Database::NEWSLETTERSECTIONS_PRE, $this->id );
				$editor = $webpage->db->fetch_cell( Database::NEWSLETTERSECTIONS, Database::NEWSLETTERSECTIONS_PRE . "editor", Database::NEWSLETTERSECTIONS_PRE . "id = " . (int)$section );
				
				if ( !in_array( $editor, $authors ) )
					$authors[] = (int)$editor;
			}
			
			return $authors;
		}
		
	}
}
?>