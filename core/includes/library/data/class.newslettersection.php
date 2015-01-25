<?php
#============================================================================================================
# ** Newsletter Class
#
# Version:   1.0
# Author:    Michael Shelton
#============================================================================================================
namespace Core;
if ( !defined( "D_CLASS_NEWSLETTERSECTION" ) )
{
	define( "D_CLASS_NEWSLETTERSECTION", true );
	require( __DIR__ . "/class.data.php" );
	require( __DIR__ . "/class.newsletterarticles.php" );
	require( __DIR__ . "/../../../" . Website::ADMIN_DIR ."includes/class.processor.php" );	//need access to Processor::row_is_pending


	class NewsletterSection extends data
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		public $articles			= array();
		
		protected $name 			= NULL;
		protected $vars 			= NULL;
		protected $newsparent 		= NULL;
		protected $editor 			= NULL;
		protected $blocks			= NULL;
		protected $blocks2			= NULL;
		protected $blocks3			= NULL;
		protected $blocks4			= NULL;
		protected $assortment		= NULL;
		
		static private $_block_templates = Array();
		static private $_block_fields = Array();
		
		
		const TABLE 				= Database::NEWSLETTERSECTIONS;
		const PREFIX 				= Database::NEWSLETTERSECTIONS_PRE;
		
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
		
		public function __construct( $id, WebPage &$webpage = NULL, $identifier = "id" )
		{
			if ( !$webpage )
			{
				debug_print_backtrace( 2 );
				die("webpage not set");
			}
			return parent::__construct( $id, $webpage, $identifier );
		}
		#----------------------------------------------------------------------------------------------------
		# * Setup
		#----------------------------------------------------------------------------------------------------
		protected function setup( $data = Array() )
		{
			$this->not_recorded[] = 'articles';
			$data = parent::setup( $data );
			
			// create columns
			$this->name			= new Column( self::TABLE, self::PREFIX, "name" ); 
			$this->vars			= new Column( self::TABLE, self::PREFIX, "vars" ); 
			$this->newsparent	= new Column( self::TABLE, self::PREFIX, "newsparent" ); 
			$this->editor		= new Column( self::TABLE, self::PREFIX, "editor" );
			$this->blocks		= new Column( self::TABLE, self::PREFIX, "blocks" );
			$this->blocks2		= new Column( self::TABLE, self::PREFIX, "blocks2" );
			$this->blocks3		= new Column( self::TABLE, self::PREFIX, "blocks3" );
			$this->blocks4		= new Column( self::TABLE, self::PREFIX, "blocks4" );
			$this->assortment		= new Column( self::TABLE, self::PREFIX, "assortment" );

			// add columns
			$this->add_col( 'name', true );
			$this->add_col( 'vars' );
			$this->add_col( 'newsparent' );
			$this->add_col( 'editor' );
			$this->add_col( 'blocks' );
			$this->add_col( 'blocks2' );
			$this->add_col( 'assortment' );


			if ( !$data )
				$this->set_vars( "" );
			else
			{
				
				$this->set_name( $data[ $this->prefix . 'name' ] );
				
				if ( get_class( $this ) == __CLASS__ )
				{
					$this->set_blocks( $data[ $this->prefix . 'blocks' ] );
					$this->set_blocks2( $data[ $this->prefix . 'blocks2' ] );
					$this->set_blocks3( $data[ $this->prefix . 'blocks3' ] );
					$this->set_blocks4( $data[ $this->prefix . 'blocks4' ] );
					$this->set_assortment( $data[ $this->prefix . 'assortment' ] );
					$this->set_newsparent( $data[ $this->prefix . 'newsparent' ] );
					$this->set_editor( $data[ $this->prefix . 'editor' ] );
					
					
					$articles = Database::select( Database::NEWSLETTERARTICLES, 
													  Database::NEWSLETTERARTICLES_PRE . 'id',
													  Database::NEWSLETTERARTICLES_PRE . 'section = ' . (int)$this->id );
					$this->articles = array();
					foreach( $articles as &$a ) {
						$this->articles[] = $a[ Database::NEWSLETTERARTICLES_PRE . 'id' ];
					}
					
					
					$valid_assortments = Newsletter::search_assortments( $this->webpage );
					if ( !in_array(  $this->assortment->value,  $valid_assortments ) )
					{
						$this->assortment->value = "single";
					}		
					
					if ( $this->webpage ) $this->_fix_blocks( $this->webpage );
				}
				
				$this->set_vars( $data[ $this->prefix . 'vars' ] );
				
				
				/*if ( $data[ $this->prefix . 'vars' ] )
				{
					$variables = unserialize( $data[ $this->prefix . 'vars' ] );
					foreach( $variables as $k => $v )
						$this->variables[$k] = stripslashes( $v );
				}*/

			}
			
			return $data;
		}
		
		
		#----------------------------------------------------------------------------------------------------
		# * Setters
		#----------------------------------------------------------------------------------------------------
		protected function set_name( $a )		{ return $this->name->value = $a; }
		protected function set_vars( $a )		{ return $this->vars->value = $a; }
		protected function set_newsparent( $a )	{ return $this->newsparent->value = (int)$a; }
		protected function set_editor( $a )		{ return $this->editor->value = (int)$a; }
		protected function set_blocks( $a )		{ return $this->blocks->value = $a; }
		protected function set_blocks2( $a )		{ return $this->blocks2->value = $a; }
		protected function set_blocks3( $a )		{ return $this->blocks3->value = $a; }
		protected function set_blocks4( $a )		{ return $this->blocks4->value = $a; }
		protected function set_assortment( $a )		{ return $this->assortment->value = $a; }
		
		
		#--------------------------------------------------------------------------------------------------
		# * _fix_blocks
		#--------------------------------------------------------------------------------------------------
		private function _fix_blocks( Webpage $webpage = NULL )
		{
			//init variables
			
			$blocks1 =  $this->blocks->value ? explode( ',' , $this->blocks->value ): array();
				$blocks2 =  $this->blocks2->value ? explode( ',' , $this->blocks2->value ): array();
			$blocks3 =  $this->blocks3->value ? explode( ',' , $this->blocks3->value ): array();
			$blocks4 =  $this->blocks4->value ? explode( ',' , $this->blocks4->value ): array();
			$debug = "";

			$column_count = Newsletter::get_column_count( $webpage, $this->assortment->value );
			
			
			//move all articles to an existing column
			foreach( $this->articles as $a )
			{
				$a = new Newsletterarticle( (int)$a, $this->webpage );
				
				if ( $a->column->value >= $column_count ) 
					Newsletterarticle::position( $webpage, $a->id, 0, $a->block->value, $a->index->value );
			}
			
			
			
			//fix each column
			for( $column_number = 0; $column_number < $column_count; $column_number++ )
			{
				$used_articles = array();
				$broken_articles = array();
				$blocks = array();
				
				
				switch ( $column_number )
				{
					case 0:
						$blocks = $blocks1;
						$db_column = "blocks";
						break;
					case 1:
						$blocks = $blocks2;
						$db_column = "blocks2";
						break;
					case 2:
						$blocks = $blocks3;
						$db_column = "blocks3";
						break;
					case 3:
						$blocks = $blocks4;
						$db_column = "blocks4";
						break;
					default:
						$blocks = array();
						$db_column = "";
						break;
				}
				

				$i = 0;
				foreach ( $blocks as &$block ) {
					$article_count = Newsletterarticle::get_article_count( $this->webpage, $block );
		
					$used_articles[ $i ] = array();
					for ( $n = 0; $n < $article_count; $n++ ) 
						$used_articles[ $i ] []= false; 
					$i++;
				}
				
				
			
				
				
				
				
				
				foreach( $this->articles as $a ) 
				{
					$a = new Newsletterarticle( (int)$a, $this->webpage );
					
					if ( $a->column->value == $column_number ) 
					{
						
						if ( isset( $used_articles[ $a->block->value ] ) && isset( $used_articles[ $a->block->value ][ $a->index->value ] ) )
						{
							$used_articles[ $a->block->value ][ $a->index->value ] = true;
						} else
							$broken_articles []= $a->id;
					}
				}
				
				

				
				
				foreach( $broken_articles as $a )
				{
					
					Newsletterarticle::position( $webpage, $a, $column_number, count( $blocks ) , 0 );
					$blocks []= 'single';

				}

				$query = sprintf("UPDATE %s SET %s%s = '%s' WHERE %sid = %d LIMIT 1",
								 self::TABLE,
								 self::PREFIX,
								 $db_column,
								 addslashes( implode (',', $blocks ) ),
								 self::PREFIX,
								 $this->id );

				$webpage->db->query( $query );
				//var_dump( $query );
				$this->$db_column->value = implode (',', $blocks ) ;


			}
			
			return;
		}
		

		
		
		#--------------------------------------------------------------------------------------------------
		# * Set Template Filename
		#--------------------------------------------------------------------------------------------------
		public function set_template_filename()
		{
			$fn = '';
			
			//get the parent newletters name field
			$row  = Database::fetch( Database::NEWSLETTERS, Database::NEWSLETTERS_PRE . 'name', Database::NEWSLETTERS_PRE . 'id = ' . (int)$this->newsparent->value );
			
			
			$this->parent_name = $row[Database::NEWSLETTERS_PRE . 'name'];

			$subtempls = self::get_sections( $this->webpage, $this->parent_name  );
			
			
			foreach ( $subtempls as $k => $name ) {
				
				if ( strtolower($name) == strtolower($this->name->value) ) {
					$this->own_order = $k;
					$fn = strtolower( $k . '_' . $name . '.html' );
				}
			}
				
			return $this->filename = $row[Database::NEWSLETTERS_PRE . 'name'] . DIRECTORY_SEPARATOR .  $fn;
		}


		#----------------------------------------------------------------------------------------------------
		# * section_edit_actionsbuttons_html
		# The actions in the inline newlsetter editor
		#----------------------------------------------------------------------------------------------------
		public function edit_actionsbuttons_html( Webpage $webpage = NULL, $edit = false ) {
			
			$html = "";
			
			$pending = $this->is_pending(); //Processor::row_is_pending( Database::NEWSLETTERSECTIONS, Database::NEWSLETTERSECTIONS_PRE, $this->id );
			$published = $this->is_published(); //Database::row_is_published( Database::NEWSLETTERSECTIONS, Database::NEWSLETTERSECTIONS_PRE, $this->id );
			$is_moderator = $webpage->user->is_admin();	//TODO: check moderator table
			
			$query = sprintf("SELECT %sauthor FROM %s WHERE %sid = %d LIMIT 1",
							 Database::NEWSLETTERS_PRE,
							 Database::NEWSLETTERS,
							 Database::NEWSLETTERS_PRE,
							 $this->newsparent->value);
			
			$r = $webpage->db->query( $query );
			$newsletter_creator = (int)mysql_result( $r, 0 );
			$editable = !$pending && !$published && ($webpage->user->id == $this->editor->value ||  $webpage->user->id == $newsletter_creator);
			$editable |= $webpage->user->is_admin();
			$publishable = !$pending && !$published && ($webpage->user->id == $this->editor->value ||  $webpage->user->id == $newsletter_creator || $webpage->user->is_admin());
			$approveable = $mods 	= $webpage->db->select( Database::MODERATORS, "*", "moderator_access = '" . Database::NEWSLETTERSECTIONS . "' AND moderator_user = " . $webpage->user->id );
			
	
			/*
			$html .="editable: " . (int)($editable)  ;
			$html .="pending: " . (int)($pending)  ;
			$html .="publishable: " . (int)($publishable)  ;
			$html .="published: " . (int)($published)  ;
			
			*/
			
			
			
			
			$publish_icon = $webpage->site->url . "themes/".$webpage->theme."/images/icons/publish.png";
			$approved_icon = $webpage->site->url . "themes/".$webpage->theme."/images/icons/approved.png";
			$pending_icon = $webpage->site->url . "themes/".$webpage->theme."/images/icons/pending.png";
			$deny_icon = $webpage->site->url . "themes/".$webpage->theme."/images/icons/delete.png";
			$pencil_icon = $webpage->site->url . "themes/".$webpage->theme."/images/icons/pencil.png";
			$help_icon = $webpage->site->url . "themes/".$webpage->theme."/images/icons/help.png";
			
			
			
			$pencil = $edit ? "<img class=\"edit-pencil\" src=\"$pencil_icon\"><br />" : '';
			if ( $editable ) {
				$html .= "
				<div class=\"loader\">&nbsp;</div>
				$pencil
				
				<ul class=\"section-panel\">
					<h2>Section Panel</h2>
					<li><div class=\"add-block block-hr\" > Divider</div></li>
					<li><div class=\"add-article\">New Article</div></li>
				</ul>
				
				<div class=\"save-section\">
				<button>Save</button>
				<button>Cancel</button>
				</div>
				";

			} 
			
			
			if ( $publishable ) {
				$html .= "
					<a class=\"admin\" href=\"".$webpage->anchor( $webpage->page_id, array( "naked" => 1, "action" => 'publish_section', 'id' => $this->newsparent->value, 'section' => $this->id ) )."\">
						Publish <img class=\"edit-publish\" src=\"$publish_icon\" alt=\"publish\">
					</a>";
			} else if ( $pending ) {
				$html .= "Pending Approval<img src=\"$pending_icon\" alt=\"pending\"><br />";
				if ( $approveable ) 
					$html .= "<a class=\"admin\" href=\"".$webpage->anchor( $webpage->page_id, array( "naked" => 1, "action" => 'approve_section', 'id' => $this->newsparent->value, 'section' => $this->id ) )."\">Approve This<img  src=\"$approved_icon\" alt=\"approve\"></a><br />"
					. "<a class=\"admin\" href=\"".$webpage->anchor( $webpage->page_id, array( "naked" => 1, "action" => 'deny_section', 'id' => $this->newsparent->value, 'section' => $this->id ) )."\">Deny This<img  src=\"$deny_icon\" alt=\"deny\"></a><br />";
			} else if ( $published ) {
				$html .= "Approved<img src=\"$approved_icon\" alt=\"approved\"><br />";
				if ( $is_moderator ) 
					$html .= "<a class=\"admin unpublish\" href=\"".$webpage->anchor( $webpage->page_id, array( "naked" => 1, "action" => 'unpublish_section', 'id' => $this->newsparent->value, 'section' => $this->id ) )."\">Unpublished This<img  src=\"$deny_icon\" alt=\"deny\"></a><br />";
			}
			
			
			
			$html .= "<div class=\"showhelp\" x-section=\"{$this->id}\" class=\"help\"><a href=\"#\">Help<img src=\"$help_icon\"></a></div>";

			
			
			
			return $html;
		}
		
		
		#----------------------------------------------------------------------------------------------------
		# * parse_article
		#----------------------------------------------------------------------------------------------------
		static public function parse_article( Webpage &$webpage = NULL ) {
			
		}
		
		#----------------------------------------------------------------------------------------------------
		# * parse
		# $webpage - Reference for template objects
		# $use_defaults - use defaults or not ([...]defaults.xml)
		# $section - Either an ARRAY of the format ('template'=>'monthly','section'=>'highlights','variant'=>'a')
		#			 an INT of the id of the newslettersection
		#			 or an instacne of newslettersection
		# $opts - Options ( see $default_opts )
		#----------------------------------------------------------------------------------------------------
		static public function parse( Webpage &$webpage, $template, $opts = Array() ) {
			
			$default_opts = Array(
				"use_object_vars" => TRUE,	//if TRUE use $this->vars (as opposed to example data only(?))
				"use_defaults" => FALSE,	//if TRUE use $this->vars (as opposed to example data only(?))
				"class" => '',			//should be either 'preview-highlight' or 'preview-lolight' or ''
				"edit" => false,		//- whethor or not to enable JS editing for this specfic section
				"with_actions" => false,//- whethor or not to show the action buttons
				"plain_page" => false,	//- if TRUE then only nessiary html is shown
				"block_changable" => true,	//whethor or not this section allows altering the block shape
				"blocks_to_allow" => Array(), //if you can alter blocks, what are they limited to (if empty then allow all)
				"blocks_to_filter" => Array( "hr" ), //if you can alter blocks, what can't be showed
			);	

			foreach( $default_opts as $optname => $value )
				if ( !isset( $opts[ $optname ] ) )
						$opts[ $optname ] = $value;

			//init variables
			$section 			= &$template;
			$section_name 		= "";
			$variant 			= FALSE;
			$path 				= "";
			$html 				= "";
			$use_object_vars 	= $opts[ 'use_object_vars' ];
			$class 				= $opts[ 'class' ];
			$edit_form 			= $opts[ 'edit' ];
			$show_actions 		= $opts[ 'with_actions' ];
			$plain_page  		= $opts[ 'plain_page' ];
			$block_library 		= Newsletter::search_blocks( $webpage );
			$edit_stub_pre 		= "";
			$edit_stub_post 	= "";			

			
			
			if ( is_int( $section ) ) 
				$section = new Newslettersection( $section, $webpage );		
			
			if ( $section instanceof Newslettersection ) 
			{
				//$template_name = $webpage->db->fetch_cell( Newsletter::TABLE, Newsletter::PREFIX . "name", Newsletter::PREFIX . "id = " . $section->newsparent->value );  
				
				$column_count = Newsletter::get_column_count( $webpage, $section->assortment->value );

				$db_blocks = Array( 'blocks', 'blocks2', 'blocks3' , 'blocks4' );
				$assortment_library = Newsletter::search_assortments( $webpage );
				
				//find assortment
				$filename = $section->assortment->value . ".html";
				
				$rel_path = '/../../../'.Website::ADMIN_DIR.'themes/' . $webpage->theme . '/templates/' . Newsletter::NEWSLETTER_DIR . "assortments/" . $filename;
				$assort_path = __DIR__. $rel_path;

				$assortment_template = new Template( $webpage, $rel_path );
				$assort_fields = self::get_fields( $webpage, $rel_path);
				$col_number = 0;
				foreach( $assort_fields as &$af ) 
					if ( $af[0] == 'COLUMN' ) 
					{
						
						//for each column...
						
						$b = $db_blocks[ $col_number ];
						{	
							$blocks_html = "";
							$blocks = $section->$b->value ? explode ( "," , $section->$b->value ) : array();
							
							if ( !$plain_page ) $blocks_html .= "<ul x-column-number=\"$col_number\" class=\"block\">\r\n";
							
							
							
							if ( !$plain_page && count( $blocks ) == 0 &&  $col_number < $column_count   )
							{
								//if there are no blocks
								$blocks_html .= "<div class=\"empty-section\">This section is empty. Click to <a href=\"#\" class=\"insert-new-article\">create an article</a>.</div>";
							}
							
							
							
							//for each block
							$i = 0;
							
							foreach ( $blocks as $k => &$block ) 
							{
								$index = (int)$k;
								
								if ( in_array( $block, $block_library ) ) 
								{

									
									if ( !isset( self::$_block_templates[ $block ] ) )
									{
										$filename = $block . ".html";
										$rel_path = '/../../../' . Website::ADMIN_DIR . 'themes/' . $webpage->theme . '/templates/' . Newsletter::NEWSLETTER_DIR . "blocks/" . $filename;
										$block_path = __DIR__. $rel_path;
										self::$_block_templates[ $block ] = new Template( $webpage, $rel_path );
										self::$_block_fields[ $block ] = self::get_fields( $webpage, $rel_path);
									}
									

									//for each article in each block
									$j = 0;
									$any_articles = false;
									foreach( self::$_block_fields[ $block ] as &$f ) 
										if ( $f[0] == 'ARTICLE' ) 
										{
											$article_id = Newsletterarticle::search( $section->id, $col_number, $i, $j );	
											
											$article_opts = Array( 
												"edit" => !$plain_page,
												"with_form" => false,
												"article_index" => $j,
											);
											$a_html = NewsletterArticle::parse( $webpage, $article_id, $section, $article_opts );

											self::$_block_templates[ $block ]->add_var( "V_" . $f[0] . '_' . $f[1], $a_html );
											
											if ( $article_id ) $any_articles = true;
											$j++;
										}
									
									$delete = $webpage->anchor( $webpage->page_id , array( "id" => $section->newsparent->value, "naked"=>"1", "mode" => NewsletterPage::MODE_CALLBACK, 'cb' => NewsletterPage::CALLBACK_BLOCK_EDIT, "action" => "delete", "section" => $section->id, "block_index" => $index  ) );
									$delete_btn_url = $webpage->site->url . 'themes/' . $webpage->theme . '/images/icons/delete.png';
									$buttons = "";
									
									$buttons .= "<a name=\"{$section->id}-block-$k\">&nbsp;</a>";
									
									if ( $opts[ 'block_changable' ] ) 
									{
										$buttons .= "
										<div  class=\"option\">
											Layout:<select class=\"block-convert\"  >";

											$blocks_to_allow = $opts[ 'blocks_to_allow' ] ? $opts[ 'blocks_to_allow' ] : Newsletter::search_blocks( $webpage );
											foreach ( $blocks_to_allow as $alblock ) 
												if ( !in_array( $alblock, $opts[ 'blocks_to_filter' ] ) || $alblock == $block ) $buttons .= "<option value=\"$alblock\" ". ($alblock == $block ? 'selected' : '') .">".ucfirst($alblock)."</option>\r\n";
										
										$buttons .= "</select>
										</div>";
									}
									
									if ( !$any_articles ) $buttons .= "<a href=\"$delete\"  class=\"delete button\"><img src=\"$delete_btn_url\" alt=\"Delete Block\"></a>";
									
									
									if ( !$plain_page ) $blocks_html .= "\t<li><div class=\"block-handle\">$buttons</div>\r\n\t\t";
									
									
									$blocks_html .= self::$_block_templates[ $block ]->parse(false);
									
									if ( !$plain_page ) $blocks_html .= "\r\n\t</li>\r\n";
								}
								$i++;
							}
							
							if ( !$plain_page ) $blocks_html .= "</ul>\r\n";
						}
						$assortment_template->add_var( "V_" . $af[0] . '_' . $af[1], $blocks_html );
						$col_number++;
					}
					

			
				$html .= $assortment_template->parse(false);
				

				if ( $show_actions ) {
					$buttons_html = $section instanceof Newslettersection ? $section->edit_actionsbuttons_html( $webpage, $opts[ 'edit' ] ) : '';
					$edit_stub_pre = "\r\n<span id=\"edit-stub-" . $section->id . "\" class=\"edit-stub\">"
								   . $buttons_html
								   . "\r\n</span><div id=\"section-{$section->id}\"  class=\"{$class}\">";
					
				}
				
				$form = strtolower (Newsletter::friendly_name( $section->name->value ) );
				
				if ( !$plain_page && ($edit_form  || $class == 'preview-lolight') ) {
					$edit_stub_post = "<form class=\"form\" id=\"form-section-{$section->id}\"><button class=\"form-save\">Save</button><button class=\"form-cancel\">Cancel</button></form></div>";
				} 
			}

			
			return $edit_stub_pre . $html . $edit_stub_post;
			
		}
		


		
		#----------------------------------------------------------------------------------------------------
		# * get_defaults_from_xml
		# $template - name of the template
		# $section - section_default
		#----------------------------------------------------------------------------------------------------
		static public function get_defaults_from_xml( Webpage $webpage, $template, $section ) 
		{
			// initialize variables
			$defaults = array();
			
			if ( $xml_map = self::_get_xml_data( $webpage, $template ) ) 
				if ( $xml_defaults = self::_xml_tree( $xml_map, Array( "NEWSLETTER", "DEFAULT", strtoupper($section) ) ) )
				{
					foreach( $xml_defaults as $indx => &$value ) 
						foreach( $value as $k => $i ) 
							$defaults[ $k ] = $i[ 'value' ];
				}
			
			

			return $defaults;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * get_example_from_xml
		# $template - name of the template
		# $section - optional, name of the subsection to check
		#----------------------------------------------------------------------------------------------------
		static public function get_example_from_xml( Webpage $webpage, $template, $section = NULL ) 
		{
			// initialize variables
			$examples =  array();

			if ( $xml_map = self::_get_xml_data( $webpage, $template ) ) 
				if ( $xml_defaults = self::_xml_tree( $xml_map, Array( "NEWSLETTER", "EXAMPLE", strtoupper($section) ) ) )
				{
					foreach( $xml_defaults as $indx => &$value ) 
						foreach( $value as $k => $i ) 
							$examples[ $k ] = $i[ 'value' ];
				}
			
			
	
			return $examples;
		}

		
		#----------------------------------------------------------------------------------------------------
		# * get_auto_articles_from_xml
		#----------------------------------------------------------------------------------------------------
		static public function get_auto_articles_from_xml( Webpage &$webpage, $template, $section  ) 
		{
			// initialize variables
			$articles =  array();
			$column_count = 0;
			

			
			
			if ( $xml = self::get_section_xml( $webpage, $template, $section ) ) 
			{
				
				foreach( $xml as &$taglist ) 
				{
					foreach( $taglist as $tag => &$value )
					{
						$blocks_xml = &$value[ 'children' ];
						$block_count = 0;
						$column_count++;
					
						foreach( $blocks_xml as &$bl_taglist ) 
						{
							foreach( $bl_taglist as &$bl_value )
							{
								$blocks_attribs = &$bl_value[ 'attributes' ];
								$article_xml = &$bl_value[ 'children' ];
								$article_count = 0;
								$block_count++;
								
								foreach( $article_xml as &$article_taglist ) 
								{
									foreach( $article_taglist as &$article_value )
									{
										$article_count++;
										$article_attribs = &$article_value[ 'attributes' ];
										$article_fields = array();
										$fields_xml = $article_value[ 'children' ];
										foreach( $fields_xml as &$fields_taglist ) 
										{
											foreach( $fields_taglist as $fields_name => &$fields_value )
											{
												$article_fields[ $fields_name ] = $fields_value;
											}
										}
										
										$article = array(
												"column_number" => $column_count,
												"block_number" => $block_count,
												"block_attribs" => $blocks_attribs,
												"article_attribs" => $article_attribs,
												"article_fields" => $article_fields 
												);
										$articles[] = $article;
									}
								}
							}
						}
					}
				}
			}


			return $articles;
		}
		


		
		#----------------------------------------------------------------------------------------------------
		# * get_section_xml
		#----------------------------------------------------------------------------------------------------
		static public function get_section_xml( Webpage &$webpage, $template, $section ) 
		{
			$result = NULL;
			$xml = Newsletter::get_xml( $webpage, $template );
			$xml = XML::tree( $xml, Array( "NEWSLETTER", "SECTIONS" ) );
			foreach( $xml as &$x )
			{
				$ax = array(&$x);
				if ( $section_xml = XML::attributes( $ax , Array( "SECTION" ) ))
					if ( strtoupper( $section ) == strtoupper( $section_xml[ 'NAME' ] ) )
					{
						
						$result = isset($x['SECTION']['children']) ? $x['SECTION']['children'] : null;
					}
			}
				
			return $result;
		}
		


		
		#----------------------------------------------------------------------------------------------------
		# * get_fields
		# $template can be a string or instance of Newslettersection
		#----------------------------------------------------------------------------------------------------
		static public function get_fields( Webpage &$webpage = NULL, $template) {
			
			// initialize variables
			$fields = array();
			
			
			if ( $template instanceOf Newslettersection ) {
			
				$path = Newslettersection::location_of( $webpage, $template->parent_name, $template->name->value );
				
				$base = explode( DIRECTORY_SEPARATOR, $path );
				$base = $base[ count( $base ) -1 ];
				
				if ( $template->variant->value ) 
					$filename = $base . '_' . $template->variant->value . ".html";
				else
					$filename = $base . ".html";
				
				

				$template = self::NEWSLETTER_DIR . $template->parent_name . '/' . $filename;
			} 
			$html = new Template( $webpage, $template );
			$vars = $html->get_vars();
			
			foreach ( $vars as $v ) {
				list( $type, $name ) = explode( '_', $v, 2 );	
				$fields[] = array( $type, $name );
			}
			

			return $fields;
		}
		
		
		public function publish( User &$user = null, Newsletter $newsletter = null )
		{
			
			$result = parent::publish( $user );

			if ( !$newsletter )
				$newsletter = new Newsletter( $this->newsparent->value, $this->webpage );
				
			if ( $newsletter )
				$newsletter->update_status( $user );
			
			return $result;
		}
		public function approve( User &$user = null, Newsletter $newsletter = null )
		{
			$result = parent::approve( $user );
			if ( !$newsletter )
				$newsletter = new Newsletter( $this->newsparent->value, $this->webpage );
				
			if ( $newsletter )
				$newsletter->update_status( $user );
			
			return $result;
		}
		public function deny( User &$user = null, Newsletter $newsletter = null )
		{
			$result = parent::deny( $user );
			if ( !$newsletter )
				$newsletter = new Newsletter( $this->newsparent->value, $this->webpage );
				
			if ( $newsletter )
				$newsletter->update_status( $user );
			
			return $result;
		}
		public function unpublish( User &$user = null, Newsletter $newsletter = null )
		{
			$result = parent::unpublish( $user );
			if ( !$newsletter )
				$newsletter = new Newsletter( $this->newsparent->value, $this->webpage );
				
			if ( $newsletter )
				$newsletter->update_status( $user );
			
			return $result;
		}
	}
}
?>