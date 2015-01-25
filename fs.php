<?php
	require_once("library/webpage.php");
	
	
	$path = $_GET['path'] ? base64_decode( $_GET['path'] ) : ".";
	$show_contents = $_GET['contents'] ? true : false;
	
	if ( $show_contents ) {
		if ( $_GET['raw']) {
			echo file_get_contents( $path );
			exit;
		}
		Webpage::self();
		echo "<pre>";
		echo htmlentities( file_get_contents( $path ) );
		echo "</pre>";
		exit;
	}
	
	Webpage::self();
	echo "<h1>$path</h1>";
	echo "<pre>";
	
	
	$dirs = true;
	for ( $i = 0; $i < 2; $i++ ) {
		$dir = opendir( $path );
		while( $file = readdir( $dir ) )
		{
			if ( $dirs && !is_dir( $path . "/" . $file ) ) continue;
			if ( !$dirs && is_dir( $path . "/" . $file ) ) continue;
			
			$icon = sprintf( "<img src=\"images/%s.gif\">", is_dir( $path . "/" . $file ) ? "folder" : "binary" );
			$filename = $file;
			while ( strlen( $filename ) < 16 )
				$filename .= " ";
		
			$filesize = is_file( $path . "/" . $file ) ? filesize( $path . "/" . $file ) : '-';
			$chmod = fileperms( $path . "/" . $file );
			while ( strlen( $filesize ) < 16 )
				$filesize .= " ";
			
			$href = !is_dir( $path . "/" . $file ) ? 
				"?path=" . base64_encode( $path  . "/" . $file)  . "&contents=show" : 
				"?path=" . base64_encode( $path . "/" . $file) ;
			$readable = is_readable( $path . "/" . $file ) ? "READABLE" : "" ;
			$owner = fileowner( $path . "/" . $file );
			echo sprintf("%s <a href=\"%s\">%s</a> %s  %X %s %s\r\n", $icon, $href, $filename, $filesize, $chmod, $readable, $owner );
		}
		$dirs = false;
		closedir( $dir );
	}
	
	echo "</pre>";
	
?>
