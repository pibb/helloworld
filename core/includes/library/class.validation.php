<?php
namespace Core;
if ( !defined( "D_CLASS_VALIDATION" ) )
{
	define( "D_CLASS_VALIDATION", true );
	
	/**
	 * File: class.validation.php
	 *
	 * @package Library/Static
	 * @author Michael R. Shelton <mshelton@wnit.org>
	 * @version 1.0.0
	 */
	abstract class Validation
	{
		/**
		 * Look for repeating characters.
		 *
		 * @param int number of characters to match.
		 * @param string the string to interpret.
		 * @return string the repeated character.
		 */
		static public function check_repetition( $len, $str ) 
		{
			$result 	= "";
			$repeated 	= false;
			$str_len	= strlen( $str );
			
			for ( $i = 0; $i < $str_len; $i++ ) 
			{
				$repeated = true;
				for ( $j = 0; $j < $len && ( $j + $i + $len ) < $str_len; $j++ )
					$repeated = $repeated && substr( $str, ( $j + $i ), 1 ) == substr( $str, ( $j + $i + $len ), 1 );
				
				if ( $j < $len ) $repeated = false;
				
				if ( !$repeated ) 
					$result .= substr( $str, $i, 1 );
				else
					$i += $len - 1;
			}
			
			return $result;
		}
		
		/**
		 * Determine if there are only letters, numbers, and underscores in the string. Dashes not included.
		 *
		 * @param string the string to be interpreted.
		 * @return bool
		 */
		static public function is_alphanumeric( $a )
		{
			return !preg_match( '/^[A-Za-z0-9_]*$/', $a ) ? false : true;
		}
		
		/*
		 * Determine if there are only letters, numbers, and underscores in the string. Dashes not included.
		 *
		 * @param string the string to be interpreted.
		 * @return bool
		 *
		static public function is_alpha_numeric( $a )
		{
			return is_alphanumeric( $a );
		}*/
		
		/**
		 * Checks that a string length is within s specified range (spaces included).
		 *
		 * @param string - the string to be interpretted.
		 * @param int - minimum value in range (inclusive).
		 * @param int - maximum value in range (inclusive).
		 * @return bool
		 */
		static public function is_between( $a, $min, $max ) 
		{
			$length = mb_strlen( $a );
			return ( $length >= $min && $length <= $max );
		}
		
		/**
		 * Checks that a string length is a valid credit card number.
		 *
		 * @param string - the string to be interpretted.
		 * @param string - defaults to fast, which checks format of most major credit cards. (Default = "fast")
		 * @param boolean - check the Luhn algorithm of the credit card? (Default = false)
		 * @return bool
		 */
		static public function is_cc( $a, $type = "fast", $deep = false ) 
		{
			// remove any dashes
			$a = str_replace( array( '-', ' ' ), '', $a );
			$valid = false;
			
			// check the length
			if ( mb_strlen( $a ) >= 13 ) 
			{
				$cards = array(
					'all' => array(
						'amex' 		=> '/^3[4|7]\\d{13}$/',
						'bankcard' 	=> '/^56(10\\d\\d|022[1-5])\\d{10}$/',
						'diners'   	=> '/^(?:3(0[0-5]|[68]\\d)\\d{11})|(?:5[1-5]\\d{14})$/',
						'disc'     	=> '/^(?:6011|650\\d)\\d{12}$/',
						'electron' 	=> '/^(?:417500|4917\\d{2}|4913\\d{2})\\d{10}$/',
						'enroute'  	=> '/^2(?:014|149)\\d{11}$/',
						'jcb'      	=> '/^(3\\d{4}|2100|1800)\\d{11}$/',
						'maestro'  	=> '/^(?:5020|6\\d{3})\\d{12}$/',
						'mc'       	=> '/^5[1-5]\\d{14}$/',
						'solo'     	=> '/^(6334[5-9][0-9]|6767[0-9]{2})\\d{10}(\\d{2,3})?$/',
						'switch'   	=> '/^(?:49(03(0[2-9]|3[5-9])|11(0[1-2]|7[4-9]|8[1-2])|36[0-9]{2})\\d{10}(\\d{2,3})?)|(?:564182\\d{10}(\\d{2,3})?)|(6(3(33[0-4][0-9])|759[0-9]{2})\\d{10}(\\d{2,3})?)$/',
						'visa'     	=> '/^4\\d{12}(\\d{3})?$/',
						'voyager'  	=> '/^8699[0-9]{11}$/' ),
					'fast' => '/^(?:4[0-9]{12}(?:[0-9]{3})?|5[1-5][0-9]{14}|6011[0-9]{12}|3(?:0[0-5]|[68][0-9])[0-9]{11}|3[47][0-9]{13})$/' );
			
				if ( is_array( $type ) ) 
				 {
					foreach ( $type as $value ) 
					{
						$regex = $cards[ 'all' ][ strtolower( $value ) ];
			
						if ( preg_match( $regex, $a ) )
						{
							$valid = luhn( $a );
							break;
						}
					}
				} 
				else if ( $type == 'all' ) 
				{
					foreach ( $cards[ 'all' ] as $value ) 
					{
						$regex = $value;
			
						if ( preg_match( $regex, $a ) )
						{
							$valid = luhn( $a );
							break;
						}
					}
				} 
				else 
				{
					$regex = $cards[ 'fast' ];
					
					if ( preg_match( $regex, $a ) )
						$valid = luhn( $a );
				}
			}
			
			return $valid;
		}
		
		/**
		 * Checks date if matches given format and validity of the date.
		 *
		 * @param string - the variable being evaluated.
		 * @param string - Format of the date. Any combination of <i>mm<i>, <i>dd<i>, <i>yyyy<i> with single character separator between. (Default = 'yyyy-mm-dd')
		 * @return bool
		 */
		static public function is_date( $a, $format = 'yyyy-mm-dd' )
		{
			if ( strlen( $a ) >= 6 && strlen( $format ) == 10 )
			{   
				// find separator. Remove all other characters from $format
				$separator_only = str_replace( array( 'm','d','y' ), '', $format );
				$separator 		= $separator_only[ 0 ];
			   
				if ( $separator && strlen( $separator_only ) == 2 )
				{
					// make regex
					$regexp = str_replace( 'mm', '(0?[1-9]|1[0-2])', $format );
					$regexp = str_replace( 'dd', '(0?[1-9]|[1-2][0-9]|3[0-1])', $regexp );
					$regexp = str_replace( 'yyyy', '(19|20)?[0-9][0-9]', $regexp );
					$regexp = str_replace( $separator, ( $separator == "/" ? "\\" : "" ) . $separator, $regexp );
					if ( $regexp != $a && preg_match( '/' . $regexp . '\z/', $a ) )
					{
						// check date
						$arr	= explode( $separator, $a );
						$form	= explode( $separator, $format );
						$day	= $arr[ array_search( "dd", $form ) ];
						$month	= $arr[ array_search( "mm", $form ) ];
						$year	= $arr[ array_search( "yyyy", $form ) ];
						if( @checkdate( $month, $day, $year ) )
							return true;
					}
				}
			}
			
			return false;
		} 
		
		/**
		 * Determines whether or not the given string is decimal number.
		 *
		 * @param string - the string to be interpretted.
		 * @return bool
		 */
		static public function is_decimal( $a )
		{
			return preg_match( '/[-+]?[0-9]*\.?[0-9]+/', $a ) ? true : false;
		}
		
		/**
		 * Determines whether or not the given string is a domain.
		 *
		 * @param string - the string to be interpretted.
		 * @return bool
		 */
		static public function is_domain( $a )
		{
			return preg_match( '/[a-z0-9\-]+\.([a-z0-9\-]+\.)?(com|net|org|tv|info|biz|us|mobi|us|name|cc|de|jp|be|at|tk|tc|vg|nu|ms|mn|eu|jobs|tw|cn)$/', $a ) ? true : false;
		}
		
		/**
		 * Determines whether or not the given string is an e-mail address.
		 *
		 * @param string the string to be interpretted.
		 * @param bool check to see if the host is available? (Default = false)
		 * @return bool
		 */
		static public function is_email( $a, $deep = false )
		{
			$hostname 	= '(?:[a-z0-9][-a-z0-9]*\.)*(?:[a-z0-9][-a-z0-9]{0,62})\.(?:(?:[a-z]{2}\.)?[a-z]{2,4}|museum|travel)';
			$regex 		= '/^[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+\/=?^_`{|}~-]+)*@' . $hostname . '$/i';
			
			$return = preg_match( $regex, $a );
			if ( $deep === true && $return === true && preg_match( '/@(' . $hostname . ')$/i', $a, $regs ) ) 
			{
				if ( function_exists( 'getmxrr' ) && getmxrr( $regs[ 1 ], $mxhosts ) )
					$return = true;
				else if ( function_exists( 'checkdnsrr' ) && checkdnsrr( $regs[ 1 ], 'MX' ) )
					$return = true;
				else
					$return = is_array( gethostbynamel( $regs[ 1 ] ) );
			}
			
			return $return;
		}
		
		/**
		 * Determines whether or not the given string is a Twitter handle.
		 *
		 * @param string the string to be interpretted.
		 * @return bool
		 */
		static public function is_handle( $a )
		{
			return preg_match( '/^@([A-Za-z0-9_]{1,15})/', $a ) ? true : false;
		}
		
		/**
		 * Determines whether or not the given string is an I.P. address.
		 *
		 * @param string the string to be interpretted.
		 * @param string the IP Protocol version to validate against. (Default = "both")
		 * @return bool
		 */
		static public function is_ip( $a, $type = "both" ) 
		{
			$type 	= strtolower( $type );
			$flags 	= array();
			
			if ( $type === 'ipv4' || $type === 'both' )
				$flags[] = FILTER_FLAG_IPV4;
			if ( $type === 'ipv6' || $type === 'both' )
				$flags[] = FILTER_FLAG_IPV6;
			
			return (boolean)filter_var( $a, FILTER_VALIDATE_IP, array( 'flags' => $flags ) );
		}
		
		/**
		 * Checks that a string length is in valid currency format.
		 *
		 * @param string the string to be interpretted.
		 * @param string the position of the symbol (i.e. the dollar sign ($) is on the left.) (Default = "left")
		 * @return bool
		 */
		static public function is_money( $a, $pos = "left" ) 
		{
			$money = '(?!0,?\d)(?:\d{1,3}(?:([, .])\d{3})?(?:\1\d{3})*|(?:\d+))((?!\1)[,.]\d{2})?';
			if ( $pos == "right" )
				$regex = '/^' . $money . '(?<!\x{00a2})\p{Sc}?$/u';
			else
				$regex = '/^(?!\x{00a2})\p{Sc}?' . $money . '$/u';
			
			return preg_match( $regex, $a );
		}
		
		/**
		 * Determines whether or not the given string is a number.
		 *
		 * @param string the string to be interpretted.
		 * @return bool
		 */
		static public function is_number( $a )
		{
			return preg_match( '/^[0-9]+$/', $a ) ? true : false;
		}
		
		/**
		 * Checks that a string length is a valid phone number. (U.S.A.)
		 *
		 * @param string the string to be interpretted.
		 * @return bool
		 */
		static public function is_phone( $a ) 
		{
			return preg_match( '/^(?:\+?1)?[-. ]?\\(?[2-9][0-8][0-9]\\)?[-. ]?[2-9][0-9]{2}[-. ]?[0-9]{4}$/', $a );
		}
		
		/**
		 * Checks that a string length is a valid postal code.
		 *
		 * @param string the string to be interpretted.
		 * @param string the country where the code is found. (Default = "us")
		 * @return bool
		 */
		static public function is_postal( $a, $country = "us" ) 
		{
			switch ( $country ) 
			{
				case 'uk': 	$regex  = '/\\A\\b[A-Z]{1,2}[0-9][A-Z0-9]? [0-9][ABD-HJLNP-UW-Z]{2}\\b\\z/i'; break;
				case 'ca':	$regex  = '/\\A\\b[ABCEGHJKLMNPRSTVXY][0-9][A-Z] [0-9][A-Z][0-9]\\b\\z/i'; break;
				case 'it':
				case 'de':	$regex  = '/^[0-9]{5}$/i'; break;
				case 'be':	$regex  = '/^[1-9]{1}[0-9]{3}$/i'; break;
				default:	$regex  = '/\\A\\b[0-9]{5}(?:-[0-9]{4})?\\b\\z/i'; break;
			}
			
			return preg_match( $regex, $a );
		}
		
		/**
		 * Checks that a string length is a valid social security number.
		 *
		 * @param string the string to be interpretted.
		 * @param string the country where the ssn is found. (Default = "us")
		 * @return bool
		 */
		static public function is_ssn( $a, $country = "us" ) 
		{
			switch ( $country ) 
			{
				case 'dk':	$regex  = '/\\A\\b[0-9]{6}-[0-9]{4}\\b\\z/i'; break;
				case 'nl':	$regex  = '/\\A\\b[0-9]{9}\\b\\z/i'; break;
				default:	$regex  = '/\\A\\b[0-9]{3}-[0-9]{2}-[0-9]{4}\\b\\z/i'; break;
			}
				
			return preg_match( $regex, $a );
		}
		
		/**
		 * Determines whether or not the given string is a u.r.l.
		 *
		 * @param string the string to be interpretted.
		 * @return bool
		 */
		static public function is_url( $a )
		{
			return preg_match( '#^http[s]?\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $a ) ? true : false;
		}
		
		/**
		 * Determines whether or not the given string has the given file extensions.
		 *
		 * @param string the string to be interpretted. (can be an array of strings.)
		 * @param Array the list of valid extension types. (Default = array( 'gif', 'jpeg', 'png', 'jpg' ))
		 * @return bool
		 */
		static public function has_extension( $a, $check = array( 'gif', 'jpeg', 'png', 'jpg' ) ) 
		{
			$valid = false;
			
			if ( is_array( $a ) )
				$valid = has_extension( array_shift( $a), $check );
			else
			{
				$seg = explode( '.', $a );
				$ext = strtolower( array_pop( $seg ) );
				foreach ( $check as $value ) 
				{
					if ( $ext == strtolower( $value ) ) 
					{
						$valid = true;
						break;
					}
				}
			}
			
			return $valid;
		}
		
		/**
		 * Checks the Luhn Algorithm.
		 *
		 * @see http://en.wikipedia.org/wiki/Luhn_algorithm
		 * @param string the string to be interpretted. (can be an array of strings.)
		 * @return bool
		 */
		static public function luhn( $a ) 
		{
			$valid = false;
			
			if ( $a != 0 )
			{
				$sum = 0;
				$length = strlen($check);
			
				for ( $position = 1 - ( $length % 2 ); $position < $length; $position += 2 )
					$sum += $check[ $position ];
			
				for ( $position = ( $length % 2 ); $position < $length; $position += 2 ) 
				{
					$number = $check[ $position ] * 2;
					$sum += ( $number < 10 ) ? $number : $number - 9;
				}
				
				$valid = ( $sum % 10 == 0 );
			}
			
			return $valid;
		}
		
		/**
		 * Determines whether or not the given string is within limits.
		 *
		 * @param string the string to be interpretted.
		 * @param int the maximum length of the string.
		 * @return bool
		 */
		static public function maxlen( $a, $max ) 
		{
			return mb_strlen( $a ) <= $max;
		}
		
		/**
		 * Determines whether or not the given string is long enough.
		 *
		 * @param string the string to be interpretted.
		 * @param integer the minimum length of the string.
		 * @return bool
		 */
		static public function minlen( $a, $min ) 
		{
			return mb_strlen( $a ) >= $min;
		}
		
		/**
		 * Determines whether or not the given password is strong enough.
		 *
		 * @param string the password.
		 * @uses Validation::check_repetition
		 * @return string the error message if it wasn't strong enough.
		 */
		static public function pass_strength( $pass )
		{
			$result	= "";
			$score 	= 0; 
			$len	= strlen( $pass );
			
			// check length
			if ( $len < 3 )
				$result = "Password is too short.";
			else if ( !preg_match( "/^[A-Za-z0-9]+$/", $pass ) )
				$result = "No special characters are allowed.";
			else
			{
				//$pass length
				$score += $len * 4;
				$remin = self::check_repetition( 2, $pass );
				$score += strlen( self::check_repetition( 1, $pass ) ) - $len;
				$score += strlen( self::check_repetition( 2, $pass ) ) - $len;
				$score += strlen( self::check_repetition( 3, $pass ) ) - $len;
				$score += strlen( self::check_repetition( 4, $pass ) ) - $len;
				
				//$pass has 3 numbers
				if ( preg_match( "/(.*[0-9].*[0-9].*[0-9])/", $pass ) ) 
					$score += 5;
				
				//$pass has Upper and Lower chars
				if ( preg_match( "/([a-z].*[A-Z])|([A-Z].*[a-z])/", $pass ) ) 
					$score += 10;
				
		
				//$pass has number and chars
				if ( preg_match( "/([a-zA-Z])/", $pass ) && preg_match( "/([0-9])/", $pass ) ) 
					$score += 15;
				
				//$pass is just a numbers or chars
				if ( !preg_match( "/^[A-Za-z]+$/", $pass ) || !preg_match( "/^[0-9]+$/", $pass ) ) 
					$score -= $len;
		
				// look for a bad score
				if ( $score < 34 ) 
					$result = "Please select a stronger password.";
			}
			
			return $result;
		}
	}
}
?>