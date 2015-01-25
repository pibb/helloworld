<?php
	require_once("library/webpage.php");
	Webpage::self();
	
	function _get( $name ) {
		return isset( $_GET[ $name ] )  ?  $_GET[ $name ] : NULL;
	}
	
	$submit = _get( 'SUBMIT' );
	$HOST = _get( 'HOST' );
	$QTYPE = _get( 'QTYPE' );
	$QCLASS = _get( 'QCLASS' );
	$DNS = _get( 'DNS' );
	
	function byte_to_hex( $byte ) {return sprintf( "%02X", $byte ) ;}
	function htons( $value ) {
		$r = (int)$value;
		$t = $value & 0xFF;
		$value = $value >> 8;
		$value += $t << 8;
		return $value;
	}
	function word_to_string( $word ) { $s =   chr( $word ) . chr( $word>>8); return $s; }
	
	function string_to_word( $string, $offset = 0 ) {
		return (ord( $string[0+$offset] ) << 8) + ord( $string[1+$offset] );
	}
	function string_to_dword( $string, $offset = 0 ) {
		return (ord( $string[0+$offset] ) << 24) + 
			   (ord( $string[1+$offset] ) << 16) +
			   (ord( $string[2+$offset] ) << 8) +
			   (ord( $string[3+$offset] ) << 0);
	}
	function dump_hex( $string ) {
		$s = "";
		for ( $i = 0; $i < strlen( $string ); $i++ ) {
			if ( $i>0 && $i % 8 == 0 ) $s .= "<br />";
			$s .= byte_to_hex(  ord( $string[ $i ] ) );
			$s .= " ";
		}
		return $s;
	}
	function dump_safe_ascii( $string, $newlines = TRUE ) {
		$s = "";
		for ( $i = 0; $i < strlen( $string ); $i++ ) {
			if (  $newlines ) if ( $i>0 && $i % 8 == 0 ) $s .= "<br />";
			$ord = ord( $string[ $i ] );
			$ord = $ord > 0x20 ? $ord :  ord(".");
			$ord = $ord < 0x7f ? $ord :  ord(".");
			if ( $ord == ord("&") ) $ord = 0x20;
			if ( $ord == ord("<") ) $ord = 0x20;
			if ( $ord == ord(">") ) $ord = 0x20;
			$s .= chr( $ord );
		}
		return $s;
	}
	
	function name_decoder( $data, &$new_offset ) {
		$names = array();
		$offset = (int)$new_offset;
		
		$within_reference = FALSE;

		while ( 1 ) {
			$name_len = ord( $data[ $offset ] );  $offset += 1;
			$new_offset += $within_reference ? 0 : 1;
			
			if ( $name_len == 0 ) break;
			if ( $name_len & 0xC0 ) {
				//REFERENCE NAME

				$offset = 0x3FFF & string_to_word( $data,  $offset-1  );
				


				$new_offset += $within_reference ? 0 : 1;
				
				$within_reference = TRUE;
				

			} else {
				$names[] = substr( $data, $offset, $name_len );
				$offset += $name_len;
				$new_offset += $within_reference ? 0 : $name_len;
			}
		}
			

		return $names;
	}
	
	function analyzer( $data ) {
		$s = "";
		
		$offset = 0;
		
		$header_id =  string_to_word( $data, 0 ); $offset += 2;
		$header_flags =  string_to_word( $data, $offset ); $offset += 2;
		$header_qcount =  string_to_word( $data, $offset ); $offset += 2;
		$header_acount =  string_to_word( $data, $offset ); $offset += 2;
		$header_ncount =  string_to_word( $data, $offset ); $offset += 2;
		$header_rcount =  string_to_word( $data, $offset ); $offset += 2;
		

		
		$header_flags_QR = 	(int)(bool)($header_flags & 0x8000);
		$header_flags_OPCODE = ($header_flags >> 11) & 0xF;
		$header_flags_AA= (int)(bool)($header_flags  & 0x0400);
		$header_flags_TC= (int)(bool)($header_flags  & 0x0200);
		$header_flags_RD= (int)(bool)($header_flags  & 0x0100);
		$header_flags_RA= (int)(bool)($header_flags  & 0x0080);
		$header_flags_RCODE = $header_flags  & 0xF;
		
		$header_flags_QR_html = $header_flags_QR ? "<label>QR = 1 (This is a Response)</label>\n" : "<label>QR = 0 (This is a Query)</label>\n";
		switch ( $header_flags_OPCODE ) {
			case 0:
				$opcode = "QUERY";
			break;
			case 1:
				$opcode = "IQUERY (inverse query)";
			break;
			case 2:
				$opcode = "STATUS";
			break;
			default:
				$opcode = "RESERVED/UNKNOWN";
			break;
		}
		switch ( $header_flags_RCODE ) {
			case 0:
				$rcode = "No errors";
			break;
			case 1:
				$rcode = "Format error (request malformed)";
			break;
			case 2:
				$rcode = "Server failure";
			break;
			case 3:
				$rcode = "Name Error (name does not exist)";
			break;
			case 4:
				$rcode = "Query Not Implemented";
			break;
			case 5:
				$rcode = "Refused";
			break;
			default:
				$rcode = "RESERVED/UNKNOWN";
			break;
		}
		$header_flags_OPCODE_html = "OPCODE = $header_flags_OPCODE ( $opcode )\n";
		$header_flags_AA_html = "AA = $header_flags_AA ( Authoritative Answer )\n";
		$header_flags_TC_html = "TC = $header_flags_TC ( Whethor or not the message is truncated )\n";
		$header_flags_RD_html = "RD = $header_flags_RD ( Recursion Request )\n";
		$header_flags_RA_html = "RA = $header_flags_RA ( Recursion Available )\n";
		$header_flags_RCODE_html = "RCODE = $header_flags_RCODE ( $rcode )\n";
	
		
		$header_flags_print = sprintf( "[%04X] - ", $header_flags  ) . "<pre>" . 
								$header_flags_QR_html . 
								$header_flags_OPCODE_html. 
								$header_flags_AA_html .  
								$header_flags_TC_html .
								$header_flags_RD_html .
								$header_flags_RA_html .
								$header_flags_RCODE_html .
								"</pre>";
		
		$s .= "
		<h3>DNS Header</h3>
		<ul>
			<li>Programed Defined ID: $header_id</li>
			<li>Flags: $header_flags_print</li>
			<li>Question Count: $header_qcount</li>
			<li>Answer Count: $header_acount</li>
			<li>Naverserver Count: $header_ncount</li>
			<li>Additional Count: $header_rcount</li>
		</ul>
		";

		if ( $header_qcount ) $s .= "<h3>Questions</h3><ul>";
		for ( $i = 0; $i < $header_qcount; $i++ ) {
			$s .= "<li>";
			$names = name_decoder( $data, $offset );

			$name = implode( ".", $names );
			$question_qtype =  string_to_word( $data, $offset ); $offset += 2;
			$question_qclass =  string_to_word( $data, $offset ); $offset += 2;
			$s .= "
			<ul>
				<li>Question HOST: $name</li>
				<li>Question QTYPE: $question_qtype</li>
				<li>Question QCLASS:  $question_qclass</li>
			</ul>
			";
			$s .= "</li>";
		}
		if ( $header_qcount ) $s .= "</ul>";
		
		$data_types = array( "Answers" => $header_acount, "Nameservers" => $header_ncount, "Additional Resources"  => $header_rcount );
		foreach ( $data_types as $type => $count ) {
			
			if ( $count ) $s .= "<h3>$type</h3><ul>";
			for ( $i = 0; $i < $count; $i++ ) {
				$s .= "<li>";
				$names = name_decoder( $data, $offset );

				$name = implode( ".", $names );
				$qtype =  string_to_word( $data, $offset ); $offset += 2;
				$qclass =  string_to_word( $data, $offset ); $offset += 2;
				$TTL =  string_to_dword( $data, $offset ); $offset += 4;
				$rd_len =  string_to_word( $data, $offset ); $offset += 2;
				$rd_offset = $offset;
				$rd_data =  substr( $data, $offset, $rd_len );
				$offset += $rd_len;
				
				$rd_data_show = "<br /><pre>" .dump_hex( $rd_data ) ."</pre>";
				
				switch ( $qtype ) {
					case 1:
						$rd_data_show = "<pre>IPv4 Address: " 
						. ord( $rd_data[0] ) . "." 
						. ord( $rd_data[1] ) . "." 
						. ord( $rd_data[2] ) . "." 
						. ord( $rd_data[3] ) . "</pre>";
					break;
					case 28:
						$rd_data_show = "<pre>IPv6 Address: " 
						. sprintf( "%02X", ord( $rd_data[0] ) ) 
						. sprintf( "%02X", ord( $rd_data[1] ) )
						. ":"
						. sprintf( "%02X", ord( $rd_data[2] ) ) 
						. sprintf( "%02X", ord( $rd_data[3] ) )
						. ":"
						. sprintf( "%02X", ord( $rd_data[4] ) ) 
						. sprintf( "%02X", ord( $rd_data[5] ) )
						. ":"
						. sprintf( "%02X", ord( $rd_data[6] ) ) 
						. sprintf( "%02X", ord( $rd_data[7] ) )
						. ":"
						. sprintf( "%02X", ord( $rd_data[8] ) ) 
						. sprintf( "%02X", ord( $rd_data[9] ) )
						. ":"
						. sprintf( "%02X", ord( $rd_data[10] ) ) 
						. sprintf( "%02X", ord( $rd_data[11] ) )
						. ":"
						. sprintf( "%02X", ord( $rd_data[12] ) ) 
						. sprintf( "%02X", ord( $rd_data[13] ) )
						. ":"
						. sprintf( "%02X", ord( $rd_data[14] ) ) 
						. sprintf( "%02X", ord( $rd_data[15] ) )

						. "</pre>";
					break;
					case 6:
						$rd_data_show = "";
						
						$t_offset = $rd_offset;
						$tnames = name_decoder( $data, $t_offset );
						$tname = implode( ".", $tnames );
						$rd_data_show .= "<pre>$tname</pre>";
						

						$tnames = name_decoder( $data, $t_offset );
						$tname = implode( ".", $tnames );
						$rd_data_show .= "<pre>$tname</pre>";
						
						$SERIAL =  string_to_dword( $data, $t_offset ); $t_offset += 4;
						$REFRESH =  string_to_dword( $data, $t_offset ); $t_offset += 4;
						$RETRY =  string_to_dword( $data, $t_offset ); $t_offset += 4;
						$EXPIRE =  string_to_dword( $data, $t_offset ); $t_offset += 4;
						$MINIMUM =  string_to_dword( $data, $t_offset ); $t_offset += 4;
						
						$rd_data_show .= "<pre>SERIAL: $SERIAL\n";
						$rd_data_show .= "REFRESH: $REFRESH\n";
						$rd_data_show .= "RETRY: $RETRY\n";
						$rd_data_show .= "EXPIRE: $EXPIRE\n";
						$rd_data_show .= "MINIMUM: $MINIMUM</pre>";
						
						break;
					case 5:
					case 2:
						$t_offset = $rd_offset;
						$tnames = name_decoder( $data, $t_offset );
						$tname = implode( ".", $tnames );
						$rd_data_show = "<pre>$tname </pre>";
						break;
					case 15:
						$t_offset = $rd_offset;
						$preference =  string_to_word( $data, $t_offset ); $t_offset += 2;
						$dname = name_decoder( $data, $t_offset );
						$dname = implode( ".", $dname );
						
						$rd_data_show = "<pre>PREFERENCE: " .  $preference . "\r\n " . $dname  . "</pre>";
						break;
					case 16:
						$rd_data_show = dump_safe_ascii( $rd_data, false );
						break;
					break;
				}
				
				$s .= "
				Result at offset: $offset
				<ul>
					<li>HOST: $name</li>
					<li>TYPE: $qtype</li>
					<li>CLASS:  $qclass</li>
					<li>TTL (seconds):  $TTL</li>
					<li>RD Length:  $rd_len</li>
					<li>RD: $rd_data_show  </li>
				</ul>
				";
				
				$s .= "</li>";
			}
			
			if ( $count ) $s .= "</ul>";
			
		}
		return $s;
	}
	
	if ( $submit == 'SIMPLE' ) {
		$s = socket_create( AF_INET, SOCK_DGRAM, SOL_UDP );
		
		if ( !socket_connect( $s, $DNS , 53 ) );
		
		//HEADER
		$header_id = rand(0,0xFFFF);
		$header_flags =  htons ( 0 );
		$header_qcount =  htons ( 1 );
		$header_acount =  htons ( 0 );
		$header_ncount =  htons ( 0 );
		$header_rcount =  htons ( 0 );
		
		//QUESTION
		$question_host = "";
		$question_qtype = htons( $QTYPE );
		$question_qclass = htons( $QCLASS );
		
		$host_chunks = explode( ".", $HOST );
		foreach( $host_chunks as $chunk ) {
			$question_host .= chr( strlen( $chunk ) ) . $chunk;
		}
		$question_host .= "\x00";

		

		$data = "";
		$data .= word_to_string( $header_id );
		$data .= word_to_string( $header_flags );
		$data .= word_to_string( $header_qcount );
		$data .= word_to_string( $header_acount );
		$data .= word_to_string( $header_ncount );
		$data .= word_to_string( $header_rcount );
		
		$data .= $question_host;
		$data .= word_to_string( $question_qtype );
		$data .= word_to_string( $question_qclass );
		
		
		$hex = dump_hex( $data );
		$ascii = dump_safe_ascii( $data );
		$analysis = analyzer( $data );
		echo "
		<h2>Request</h2>
		<table cellpadding=\"10\" class=\"dns-table\">
			<tr>
				<td><pre>$hex</pre></td>
				<td><pre>$ascii</pre></td>
				<td>$analysis</td>
			</tr>
		</table>";
		
		

		socket_send( $s, $data, strlen( $data ), 0 );
		
		
		
		$select_read = array(  $s );
		$select_write = array(   );
		$select_except = array(   );
		
		if (socket_select( $select_read, $select_write  , $select_except , 1, 0 )) {
			$answer = "";
			socket_recv( $s, $answer, 512, 0 );
			
			$hex = dump_hex( $answer );
			$ascii = dump_safe_ascii( $answer );
			$analysis = analyzer( $answer );
			echo "
			<h2>Response</h2>
			<table cellpadding=\"10\" class=\"dns-table\">
				<tr>
					<td><pre>$hex</pre></td>
					<td><pre>$ascii</pre></td>
					<td>$analysis</td>
				</tr>
			</table>";

		} else {
			echo "<h1>Error Timeout</h1>";
		}
		
		socket_close( $s );
		
	}
	

?>


<label>Enter Question</label>
<form action="?" method="GET">
	<ul>
		<li>
			<label for="DNS">DNS IP ( root: 198.41.0.4 )</label> 
			<input name="DNS" id="DNS" type="text" value="<?php echo $DNS; ?>" />
		</li>
		
		<li>
			<label for="HOST">Enter Host Name</label> 
			<input name="HOST" id="HOST" type="text"  value="<?php echo $HOST; ?>" />
		</li>
		<li>
			<label for="QTYPE">Enter QTYPE</label>
			<select name="QTYPE" id="QTYPE">
				<option value="1" <?php if  ($QTYPE == 1) echo "selected=\"selected\"" ?> >[ 1 ] A - IPV4 Address </option>
				<option value="2" <?php if  ($QTYPE == 2) echo "selected=\"selected\"" ?> >[ 2 ] NS - Nameserver </option>
				<option value="5" <?php if  ($QTYPE == 5) echo "selected=\"selected\"" ?> >[ 5 ] CNAME - Canonical Name </option>
				<option value="6" <?php if  ($QTYPE == 6) echo "selected=\"selected\"" ?> >[ 6 ] SOA - Start of Authority </option>
				<option value="11" <?php if  ($QTYPE == 11) echo "selected=\"selected\"" ?> >[ 11 ] WKS - Well known Service Description </option>
				<option value="12" <?php if  ($QTYPE == 12) echo "selected=\"selected\"" ?> >[ 12 ] PTR - Domain Name Pointer </option>
				<option value="13" <?php if  ($QTYPE == 13) echo "selected=\"selected\"" ?> >[ 13 ] HINFO - Host Information </option>
				<option value="14" <?php if  ($QTYPE == 14) echo "selected=\"selected\"" ?> >[ 14 ] MINFO - Mail Information </option>
				<option value="15" <?php if  ($QTYPE == 15) echo "selected=\"selected\"" ?> >[ 15 ] MX - Mail Exhchange </option>
				<option value="16" <?php if  ($QTYPE == 16) echo "selected=\"selected\"" ?> >[ 16 ] TXT - Text String </option>
				<option value="252" <?php if  ($QTYPE == 252) echo "selected=\"selected\"" ?> >[ 252 ] AXFR - Transfer of an entire zone </option>
				<option value="253" <?php if  ($QTYPE == 253) echo "selected=\"selected\"" ?> >[ 253 ] MAILB - Mail Records </option>
				<option value="255" <?php if  ($QTYPE == 255) echo "selected=\"selected\"" ?> >[ 255 ] * - All Records </option>
			</select>
		</li>
		<li>
			<label for="QCLASS">Enter QCLASS</label>
			<select name="QCLASS" id="QCLASS">
				<option value="1">[ 1 ] IN - Internet </option>
			</select>
		</li>
		
		<li>
			<button name="SUBMIT" value="SIMPLE" >Submit</button>
		</li>
	</ul>
</form>

<hr />
