<?php
#============================================================================================================
# ** Company Class
#
# Version:   1.0
# Author:    Michael Shelton
#============================================================================================================
namespace Core;
if ( !defined( "D_CLASS_COMPANY" ) )
{
	define( "D_CLASS_COMPANY", true );
	require( __DIR__ . "/class.data.php" );
	//require( __DIR__ . "/class.email.php" );
	//require( __DIR__ . "/class.phone.php" );
	//require( __DIR__ . "/class.anchor.php" );
	
	class Company extends Data
	{
		#----------------------------------------------------------------------------------------------------
		# * Properties
		#----------------------------------------------------------------------------------------------------
		protected $name			= "";
		protected $description	= "";
		protected $street		= "";
		protected $city			= "";
		protected $state		= "";
		protected $zipcode		= "";
		protected $facebook		= "";
		protected $email		= array();
		protected $phone		= array();
		protected $websites		= array();
		
		const TABLE				= Database::COMPANIES;
		const PREFIX			= Database::COMPANIES_PRE;
		#----------------------------------------------------------------------------------------------------
		# * Convert To String
		#----------------------------------------------------------------------------------------------------
		public function __toString()
		{
			// initialize variables
			$html 	= "<div class=\"company\">\n\t<span class=\"company-name\">" . $this->name . "</span>\n";
			$props 	= array( 'phone', 'email', 'websites' );
			
			// add contact information
			foreach( $props as $prop )
			{
				if ( $this->$prop )
				{
					$html .= "\t<ul class=\"" . $prop . "\">\n";
					foreach( $this->$prop as $p ) 		
						$html .= "\t\t<li>" . $p . "</li>\n";
					$html .= "\t</ul>\n";
				}
			}
			
			return $html.= "</div>\n";;
		}
		
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
		protected function setup( $data = array() )
		{
			$data = parent::setup( $data );
			
			$this->def_col( "name", $data, "Column", true, true );
			$this->def_col( "description", $data );
			$this->def_col( "street", $data );
			$this->def_col( "city", $data );
			$this->def_col( "state", $data );
			$this->def_col( "zipcode", $data );
			$this->def_col( "facebook", $data );
			
			// create columns
			/*$this->name 		= new Column( self::TABLE, self::PREFIX, "name" );
			$this->description 	= new Column( self::TABLE, self::PREFIX, "description" );
			$this->street 		= new Column( self::TABLE, self::PREFIX, "street" );
			$this->city 		= new Column( self::TABLE, self::PREFIX, "city" );
			$this->state 		= new Column( self::TABLE, self::PREFIX, "state" );
			$this->zipcode 		= new Column( self::TABLE, self::PREFIX, "zipcode" );
			$this->facebook 	= new Column( self::TABLE, self::PREFIX, "facebook" );
			
			// make adjustments
			$this->state->min = $this->state->max = 2;
			
			// add columns
			$this->add_col( 'name', true, true );
			$this->add_col( 'description' );
			$this->add_col( 'street' );
			$this->add_col( 'city', false, true );
			$this->add_col( 'state', false, true );
			$this->add_col( 'zipcode' );
			$this->add_col( 'facebook' );*/
			
			if ( $data )
			{
				/*$this->set_name( $data[ $this->prefix . 'name' ] );
				$this->set_description( $data[ $this->prefix . 'description' ] );
				$this->set_street( $data[ $this->prefix . 'street' ] );
				$this->set_city( $data[ $this->prefix . 'city' ] );
				$this->set_state( $data[ $this->prefix . 'state' ] );
				$this->set_zipcode( $data[ $this->prefix . 'zipcode' ] );
				$this->set_facebook( $data[ $this->prefix . 'facebook' ] );*/
				
				// get info from the database
				//$this->email = Email::geta_many_published( Database::C2EMAIL, Database::EMAIL_PRE, $this->prefix . $this->identifier . " = '" . $this->id . "'", $this->webpage );
				//$this->phone = Phone::geta_many_published( Database::C2PHONE, Database::PHONE_PRE, $this->prefix . $this->identifier . " = '" . $this->id . "'", $this->webpage );
				//$this->websites = Anchor::geta_many_published( Database::C2SITES, Database::SITES_PRE, $this->prefix . $this->identifier . " = '" . $this->id . "'", $this->webpage );

				// add this object to children for reference
				$this->adopt( 'email' );
				$this->adopt( 'phone' );
				$this->adopt( 'websites' );
			}
			
			return $data;
		}
		
		#----------------------------------------------------------------------------------------------------
		# * Setters
		#----------------------------------------------------------------------------------------------------
		/*protected function set_name( $a )			{ return $this->name->value = trim( stripslashes( $a ) ); }
		protected function set_description( $a )	{ return $this->description->value = trim( stripslashes( $a ) ); }
		protected function set_street( $a )			{ return $this->street->value = trim( stripslashes( $a ) ); }
		protected function set_city( $a )			{ return $this->city->value = trim( stripslashes( $a ) ); }
		protected function set_state( $a )			{ return $this->state->value = trim( stripslashes( $a ) ); }
		protected function set_zipcode( $a )		{ return $this->zipcode->value = trim( stripslashes( $a ) ); }
		protected function set_facebook( $a )		{ return $this->facebook->value = trim( stripslashes( $a ) ); }*/
	}
}
?>