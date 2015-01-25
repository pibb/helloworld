<?php
#============================================================================================================
# ** Newsletterarticles Class
#
# Version:   1.0
# Author:    Travis Shelton
#============================================================================================================
namespace Core;
if ( !defined( "D_CLASS_NEWSLETTERARTICLE" ) )
{
	define( "D_CLASS_NEWSLETTERARTICLE", true );
	require( __DIR__ . "/class.data.php" );
	require( __DIR__ . "/../../../" . Website::ADMIN_DIR . "includes/class.processor.php" );	//need access to Processor::row_is_pending
	
	class NewsletterArticle extends Data
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		public $variables			= Array();
		
		protected $name 			= NULL;
		protected $vars 			= NULL;
		protected $section 			= NULL;
		protected $block 			= NULL;
		protected $index 			= NULL;
		protected $column 			= NULL;
	
		
		const TABLE 				= Database::NEWSLETTERARTICLES;
		const PREFIX 				= Database::NEWSLETTERARTICLES_PRE;
		
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
			$this->not_recorded[] = 'variables';
			$data = parent::setup( $data );
			
			// create columns
			$this->name			= new Column( self::TABLE, self::PREFIX, "name" ); 
			$this->vars			= new Column( self::TABLE, self::PREFIX, "vars" ); 
			$this->section		= new Column( self::TABLE, self::PREFIX, "section" ); 
			$this->block		= new Column( self::TABLE, self::PREFIX, "block" ); 
			$this->index		= new Column( self::TABLE, self::PREFIX, "index" ); 
			$this->column		= new Column( self::TABLE, self::PREFIX, "column" ); 

			// add columns
			$this->add_col( 'name', true );
			$this->add_col( 'vars' );
			$this->add_col( 'section' );
			$this->add_col( 'block' );
			$this->add_col( 'index' );
			$this->add_col( 'column' );

			
			
			if ( !$data )
				$this->set_vars( "" );
			else
			{
				$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_section( $data[ $this->prefix . 'section' ] );
				$this->set_block( $data[ $this->prefix . 'block' ] );
				$this->set_index( $data[ $this->prefix . 'index' ] );
				$this->set_column( $data[ $this->prefix . 'column' ] );
				
				$this->set_vars( $data[ $this->prefix . 'vars' ] );
				
				
				if ( $data[ $this->prefix . 'vars' ] ) 
				{
					$variables = unserialize( $data[ $this->prefix . 'vars' ] );
					foreach( $variables as $k => $v )
						$this->variables[ $k ] = stripslashes( $v );
				}
			}
			
			
			
			return $data;
		}


		#----------------------------------------------------------------------------------------------------
		# * search
		#----------------------------------------------------------------------------------------------------
		static public function search( $section, $column_number, $block, $index ) {
			$result = NULL;
			
			$r = Database::select( 
							 self::TABLE,
							 self::PREFIX . 'id',
							 sprintf('%scolumn = %d AND %sblock = %d AND %sindex = %d AND %ssection = %d',
									 self::PREFIX,
									 (int)$column_number,
									 self::PREFIX,
									 (int)$block,
									 self::PREFIX,
									 (int)$index,
									 self::PREFIX,
									 (int)$section)
								);
			
			if ( $r ) $result = $r[0][ self::PREFIX . 'id' ];
			
			return $result;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * position
		#----------------------------------------------------------------------------------------------------
		static public function position( Webpage $webpage, $id, $column, $block, $index ) {
			
			$query = sprintf("UPDATE %s SET %scolumn = %d, %sblock = %d, %sindex = %d WHERE %sid = %d",
							 self::TABLE,
							 self::PREFIX,
							 (int)$column,
							 self::PREFIX,
							 (int)$block,
							 self::PREFIX,
							 (int)$index,
							 self::PREFIX,
							 (int)$id);
			
			$webpage->db->query( $query );
							 
			
			return;
		}

		#----------------------------------------------------------------------------------------------------
		# * get_article_count
		#----------------------------------------------------------------------------------------------------
		static public function get_article_count( Webpage &$webpage, $article ) {
			$count = 0;
			
			if ( $article instanceof NewsletterArticle ) 
				$article = $article->name->value;
			
			$article_library = Newsletter::search_blocks( $webpage );

			$in_library = false;
			foreach( $article_library as $a )
			{
				if ( $a == $article )
					$in_library = true;
			};
			
			
			if ( $in_library )
			{			
				$filename = strtolower( $article . ".html" );
				
				$article_template_path = '../../../'.Website::ADMIN_DIR.'themes/' . $webpage->theme . '/templates/' . Newsletter::NEWSLETTER_DIR  . 'blocks/' . $filename;
					
				$fields = NewsletterSection::get_fields( $webpage, $article_template_path );
				foreach( $fields as &$f ) 
					if ( $f[0] == 'ARTICLE' ) 
						$count++;
			}
			return $count;
		}
		
		static public function get_field_html( $name, $value, $article_id, $section_id, $style )
		{
			$html = "";
			$v = &$value;
			
			if ( strpos( $name, "_" ) !== false )
				list( $type, $name) = explode( '_', $name, 2 );
			else
				list( $type, $name) = array( null, $name );
			
			$tag = "span";
			$classname = "";
			if ( $style == "showform" )
				$classname = strtolower( "editable section-" . $section_id    );
			if ( $style == "edit" )
				$classname = strtolower( "eddited section-" . $section_id    );
			
			switch ( $type ) {
				case 'PLAIN':
					if ( $style == 'showform' ) $tag = "input";
					if ( $tag == "span" )
						$html =  "<$tag  name=\"$name\"  varticle=\"$article_id\" vsection=\"{$section_id}\" vtype=\"$type\" class=\"$classname\" type=\"text\">$v</$tag>";
					else
						$html =  "<$tag  name=\"$name\"  varticle=\"$article_id\" vsection=\"{$section_id}\" vtype=\"$type\" class=\"$classname\" type=\"text\" value=\"$v\" >";
					break;
				case 'RICH':
					if ( $style == 'showform' ) $tag = "textarea";
					$html =  "<$tag name=\"$name\" varticle=\"$article_id\" vsection=\"{$section_id}\"  vtype=\"$type\" cols=\"30\" class=\"tinymce $classname\">$v</$tag>";
					break;
				case 'TEXT':
					if ( $style == 'showform' ) $tag = "textarea";
					$html =  "<$tag  name=\"$name\"  varticle=\"$article_id\" vsection=\"{$section_id}\" vtype=\"$type\" cols=\"30\" class=\"$classname\">$v</$tag>";
					break;
				case 'IMG':
					$tag = "img";
					
					if ( strpos( "_", $name ) != FALSE )
						list( $imgname, $size ) =  explode( '_', $name, 2 );
					else
						list( $imgname, $size ) =  array( $name, NULL );
					$width = '?';
					$height = '?';
					if ( $size ) list( $width, $height ) = explode( 'X', $size );
					
					if ( $height == '?' ) $height = '';
					if ( $width == '?' ) $width = '';
					
					/*
					$link = $newsletterarticle->vars->value[ strtolower( $name . '_LINK' ) ];
					if ( !$link ) $link = $newsletterarticle->vars->value[ strtoupper( $name . '_LINK' ) ];
					*/
					$link = NULL;
					if ( isset( $newsletterarticle->variables[ strtolower( $name . '_LINK' ) ] ) )
						$link = $newsletterarticle->variables[ strtolower( $name . '_LINK' ) ];
					if ( !$link && isset( $newsletterarticle->variables[ strtoupper( $name . '_LINK' ) ] ) ) 
						$link = $newsletterarticle->variables[ strtoupper( $name . '_LINK' ) ];
							
					if ( $style == 'showform' )
						$html =  "<div name=\"$name\"  varticle=\"$article_id\" vsection=\"{$section_id}\" vtype=\"$type\" class=\"$classname\" ><input class=\"link\" type=\"hidden\" name=\"".($k . '_LINK')."\" value=\"$link\" ><img   height=\"$height\" width=\"$width\"   src=\"$v\"> <button class=\"change-image\">Change Image</button></div>";
					if ( $style == 'edit' )
					{
						if ( $link ) $html .= "<a class=\"noclick\" href=\"$link\">";
						$html .= "<img  height=\"$height\" width=\"$width\"  name=\"$name\" varticle=\"$article_id\"  vsection=\"{$section_id}\" vtype=\"$type\" class=\"$classname\" src=\"$v\" > ";
						if ( $link ) $html .= "</a>";
					}
					if ( $style == "plain" )
					{
						if ( $link ) $html .= "<a href=\"$link\">";
						$html .= "<img  height=\"$height\" width=\"$width\"  src=\"$v\" > ";
						if ( $link ) $html .= "</a>";
					}
					break;

			}

			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * parse
		# $webpage	- Instace of a webpage 
		# $newsletterarticle - id or instance of newsletterarticle
		# $opts - pasring options (see $default_opts )
		#----------------------------------------------------------------------------------------------------
		static public function parse( Webpage &$webpage, $newsletterarticle, Newslettersection &$section  = NULL, Array $opts  = Array() ) {
			
			$default_opts = Array(
				"edit" => false,		//public side or admin side 
				"with_form" => false,	//if edditing, show textareas/inputs of article
				"article_index" => 0,	//the index of the article being parsed
				"limit_articles" => false,	//when creating a new article, can you change to other types?
				"use_defaults" => true,
				"template" => null,
				"section" => null,
			);	
			foreach( $default_opts as $optname => $value )
				if ( !isset( $opts[ $optname ] ) )
						$opts[ $optname ] = $value;
			list( $edit_mode, $with_form, $index ) = Array( $opts[ "edit" ], $opts[ "with_form" ], $opts[ "article_index" ] );
			
			
			$html = "";
			

			if ( !($newsletterarticle instanceof NewsletterArticle) && (int)$newsletterarticle ) {
				$newsletterarticle = new NewsletterArticle( (int)$newsletterarticle );
			}
			
			if ( !$newsletterarticle  ) {
				if ( $edit_mode ) $html .= "<ul x-article-index=\"$index\" class=\"article empty-article\"><li>Drag an article here or <a href=\"#\" class=\"insert-new-article\">create a new article</a>.</li></ul>";	
			} else {

				$filename = strtolower(($newsletterarticle instanceof NewsletterArticle) ?  $newsletterarticle->name->value : $newsletterarticle ) . ".html" ;
				
				$article_template_path = '/../../../'.Website::ADMIN_DIR.'themes/' . $webpage->theme . '/templates/' . Newsletter::NEWSLETTER_DIR  . 'articles/' . $filename;
				
				


				$body = new Template( $webpage, $article_template_path );
				$fields = NewsletterSection::get_fields( $webpage, $article_template_path );
				$field_value = array();
				foreach ( $fields as $f ) {
					$full_name = strtoupper(implode('_',$f));
					$field_value[$full_name] = "";
				}
				
				
				$article_name  = $newsletterarticle instanceof NewsletterArticle ? $newsletterarticle->name->value : $newsletterarticle;
				if ( $newsletterarticle instanceof NewsletterArticle ) {
					$section_id = $newsletterarticle->section->value;
					$section_name =  $webpage->db->fetch_cell( Newslettersection::TABLE, Newslettersection::PREFIX . "name",  Newslettersection::PREFIX ."id = " . (int)$section_id );
					$template_id = $webpage->db->fetch_cell( Newslettersection::TABLE, Newslettersection::PREFIX . "newsparent",  Newslettersection::PREFIX ."id = " . (int)$section_id );
					$template_name = $webpage->db->fetch_cell( Newsletter::TABLE, Newsletter::PREFIX . "name",  Newsletter::PREFIX ."id = " . (int)$template_id );
				} else {
					
					$section_name = $opts[ 'section' ];
					$template_name = $opts[ 'template' ];
				}
				
				if ( $opts['use_defaults'] ) {
					$defaults = self::get_article_defaults( $webpage, $template_name, $section_name, $article_name );
					foreach ( $defaults as $k  => $v ) 
						if ( isset( $field_value[strtoupper($k)] ) ) $field_value[strtoupper($k)] = $v;
						
				}
				
				if ($newsletterarticle instanceof NewsletterArticle)
					foreach ( $newsletterarticle->variables as $k  => $v )
						if ( isset( $field_value[strtoupper($k)] ) ) $field_value[strtoupper($k)] = $v;
						
				
				
				
				foreach ( $field_value as $k => $v ) {
					
					list( $type, $name) = explode( '_', $k, 2 );
					$section_id = 0;
					$article_id = 0;
					if ( $newsletterarticle instanceof NewsletterArticle)
					{
						$section_id = $newsletterarticle->section->value;
						$article_id = $newsletterarticle->id;
					} 
					
					$style = "plain";
					if ( $newsletterarticle && $edit_mode && $with_form && in_array( $type, array( 'PLAIN', 'RICH',  'TEXT', 'IMG' ) )  )
						$style = "showform";
					else if ( $newsletterarticle instanceof NewsletterArticle && $edit_mode )
						$style = "edit";
					
					$data = self::get_field_html( $k, $v, $article_id, $section_id, $style );					
					$body->add_var(  "V_" . strtoupper( $k ) , $data );
				}
				
				$bar = "";
				if ( $newsletterarticle instanceof NewsletterArticle ) {
					$delete_btn_url = $webpage->site->url . 'themes/' . $webpage->theme . '/images/icons/delete.png';
					$edit_btn_url = $webpage->site->url . 'themes/' . $webpage->theme . '/images/icons/pencil.png';
					$bar = "<div class=\"article-handle\">
								<a class=\"edit-article\" href=\"#\">
									<img src=\"$edit_btn_url\" alt=\"Delete Article\">
								</a>
					<a class=\"delete-article\" href=\"#\">
									<img src=\"$delete_btn_url\" alt=\"Delete Article\">
								</a>
								

								
							
						</div>";
				} else {
					if ( !$opts[ 'limit_articles' ] ) {
						$bar .= "Choose an article type: <select id=\"article-type\">\r\n";
						$articles = Newsletter::search_articles( $webpage );
						foreach( $articles as $a ) {
								$select = $a == $newsletterarticle ? ' selected ' : '';
								$bar .= "<option value=\"$a\" $select>$a</option>\r\n";
						}
						$bar .= "</select>\r\n";
					}
					
				}
				
				$buttons = "";
				$buttons .= "<button class=\"form-save article-btn article-index-$index hidden\">Save</button>";
				
				$article_id = 0;
				if ( $newsletterarticle instanceof NewsletterArticle )
					$article_id = $newsletterarticle->id;
				
				if ( $edit_mode ) 
					$html .= "<ul x-article-index=\"$index\" x-article-id=\"{$article_id}\" class=\"article\"><li>$bar";
				$html .= $body->parse( false );
				
				if ( $edit_mode ) $html .= "$buttons</li></ul>";
			}
			
			
			return $html;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * get_article_defaults
		#----------------------------------------------------------------------------------------------------
		static public function get_article_defaults( Webpage $webpage, $template, $section, $article ) 
		{
			// initialize variables
			$options =  array();
			
			
			if ( $xml_map = Newsletter::get_xml( $webpage, $template ) ) 
					if ( $xml = XML::tree( $xml_map, Array( "NEWSLETTER", "DEFAULT", strtoupper( $article ) ) ) )
						foreach( $xml as $indx => &$value ) 
							foreach( $value as $k => $i ) 
								$options[ $k ] = $i[ 'value' ];

			
	
			return $options;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Setters
		#----------------------------------------------------------------------------------------------------
		protected function set_name( $a )		{ return $this->name->value = $a; }
		protected function set_vars( $a )		{ return $this->vars->value = $a; }
		protected function set_block( $a )		{ return $this->block->value = (int)$a; }
		protected function set_index( $a )		{ return $this->index->value = (int)$a; }
		protected function set_column( $a )		{ return $this->column->value = (int)$a; }
		protected function set_section( $a )	{ return $this->section->value = (int)$a; }
		

	}
}
?>